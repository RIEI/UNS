<?php
/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 * 
 * @author Phillip Ferland <pferland@randomintervals.com>
 * @link http://uns.randomintervals.com UNS Site
 * @date 6/10/2012
 * @version 1.0
 * 
 * Copyright (C) 2012  Phillip Ferland
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 *  This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see http://www.gnu.org/licenses/
 */
require 'UNSCore.inc.php';

class UNSAdmin extends UNSCore
{
    function __construct()
    {
        parent::__construct();
        $this->global_login_seed    = $GLOBALS['config']['global_login_seed'];
        $this->username             = NULL;
        $this->password             = NULL;
        $this->logins_left          = 0;
        $this->cookie_timeout       = $GLOBALS['config']['timeout'];
        $this->max_archives         = $GLOBALS['config']['max_archives'];
        $this->mysql_dump_bin       = $GLOBALS['config']['mysql_dump_bin'];
        $this->timezone             = $GLOBALS['config']['timezone'];
        $this->admin_path           = $this->host_path.'admin/';
        $this->client_path          = $this->host_path.'?';
        $this->LDAP                 = $GLOBALS['config']['LDAP'];
        $this->max_bad_logins       = $GLOBALS['config']['max_bad_logins'];
        if($this->LDAP)
        {
            $this->smarty->assign("ldap", '<font size="1">(domain\user)</font>');
        }else
        {
            $this->smarty->assign("ldap", '');
        }
        $this->LDAP_domain          = $GLOBALS['config']['LDAP_domain'];
        $this->LDAP_port            = $GLOBALS['config']['LDAP_port'];
        
        
        $this->client_led           = 0;
        $this->nav_bar              = array();
        $this->side_bar             = array();
        $this->all_messages         = array();
        $this->smarty->assign("client_url", $this->client_path);
        $this->smarty->assign("admin_url", $this->admin_path);
    }
    
    function __destruct()
    {exit();}
    
    function check_archives()
    {
        if($this->max_archives === 0){return 0;}
        $sql = "SELECT * FROM `{$this->sql->db}`.`archive_links` WHERE `client` = ? ORDER BY `date` ASC";
        $prep = $this->sql->conn->prepare($conn);
        $prep->execute(array($$this->client));
        $rows = $prep->rowCount();
        if($this->max_archives < $rows)
        {
            $sql = "DELETE FROM `{$this->sql->db}`.`archive_links` WHERE `id` = ?";
            $prep = $this->sql->conn->prepare($sql);
            while($arcs = $prep->fetchAll(2))
            {
                if($rows+1 == $this->max_archives){break;}
                
                $prep->execute(array($arcs['id']));
                if($prep->errorCode() != "00000")
                {
                    $err = $prep->errorInfo();
                    throw new Exception($err[2]);
                    return 0;
                }else
                {
                    echo "Removed row [".$arcs['id']."]<br />";
                    $rows--;
                }
            }
            return 2;
        }else
        {
            return 1;
        }
    }
    
    function check_pdo_error($obj)
    {
        $error = $obj->errorCode();
        if($error == "00000"){return 0;}
        else
        {
            $info = $obj->errorInfo();
            throw new Exception($info[2]);
            return 1;
        }
    }
    
    function ClearLoginHashes()
    {
        $sql = "DELETE FROM `{$this->sql->db}`.`login_hashes` WHERE `user` = ?";
        $prep = $this->sql->conn->prepare($sql);
        $prep->execute(array($this->username));
        
        if($prep->errorCode() != "00000")
        {
            $err = $prep->errorInfo();
            throw new Exception($err[2]);
            return 0;
        }
        return 1;
    }
    
    function CopyClientURLS()
    {
        foreach($_POST['copy_clients'] as $copy_client)
        {
            $fail = 0;
            $sql = "SELECT * FROM `{$this->sql->db}`.`client_links` WHERE `client` = ?";
            $prep = $this->sql->conn->prepare($sql);
            $prep->execute(array($copy_client));
            $links = array(); #get list of URLS from Client that you want to copy to

            while($client_links = $prep->fetch(2))
            {
                $links[] = $client_links['url']."~".$client_links['refresh'];
            }
            #lets get its friendly name
            $friend = $this->GetFriendly($copy_client);
            if(!@is_null($links[0]))
            {
                $name = "Backup of URLS for $friend on ".date("F j, Y \a\t g:i a");
                $imp_links = implode("|", $links);
                $sql = "INSERT INTO `archive_links` (`id`, `client`, `urls`, `name`, `details`, `date`) 
                    VALUES ('', ?,?,?,?,?)";
                $result = $this->sql->conn->prepare($sql);
                $result->execute(array($copy_client, $imp_links, $name, 'Automated backup.', time()));
                if(!$this->check_pdo_error($result))
                {
                    $this->AddMessage("URLs for Client: $friend have been backed up.");
                }else
                {
                    $this->AddMessage("URLs for Client: $friend have <u><b>NOT</b></u> been backed up.");
                    $fail = 1;
                }
            }else
            {
                $this->AddMessage("Client: $friend Does not have any URLs yet.");
            }

            if(!$fail)
            {
                $ids = explode("|", $this->url_imp);

                $sql = "DELETE FROM `{$this->sql->db}`.`client_links` WHERE `client` = ?";
                $prep = $this->sql->conn->prepare($sql);
                $prep->execute(array($copy_client));
                if($this->check_pdo_error($prep)){echo "Error Truncating table<br />".$prep->errorInfo();}
                foreach($ids as $id)
                {
                    $this->AddMessage("Start Copy of ID: $id for Client: $friend");
                    $sql = "SELECT * FROM `{$this->sql->db}`.`client_links` where `id` = ?";
                    $result = $this->sql->conn->prepare($sql);
                    $result->execute(array($id));
                    $copy_link = $result->fetch(2);
                    $sql = "INSERT INTO `{$this->sql->db}`.`client_links` (`id`, `url`, `disabled`, `refresh`, `client`) 
                        VALUES ( '', ?,?,?,?)";
                    $prep = $this->sql->conn->prepare($sql);
                    $prep->execute(array($copy_link['url'], 0, $copy_link['refresh'], $copy_client));

                    if($this->check_pdo_error($prep))
                    {
                        $this->AddMessage("Failed to copy URL [$id] to client: $friend.");
                    }else
                    {
                        $this->AddMessage("Copied URL [$id] to Client: $friend.");
                    }
                }
            }else
            {
                $this->AddMessage("URLs for Client: $friend have <u><b>NOT</b></u> been copied.");
            }
        }
        
    }
    
    function CreateClient($name, $led)
    {
        $new_client = sha256(rand(000000,999999));
        $sql = "INSERT INTO `allowed_clients` VALUES('', ?, ?)";
        $prep = $this->sql->conn->prepare($sql);
        $prep->execute(array($new_client, $led));
        
        if(!$this->check_pdo_error($prep))
        {
            $sql = "INSERT INTO `friendly` VALUES('', ?, ?)";
            $prep = $this->sql->conn->prepare($sql);
            $prep->execute(array($name, $new_client));
            if(!$this->check_pdo_error($prep))
            {
                $this->AddMessage("Client has been added successfully!");
            }
        }
        return 1;
    }
    
    
    function GetClientList($skip=NULL)
    {
        if($skip!==NULL)
        {
            $sql = "SELECT * FROM `friendly` where `client` NOT LIKE ?";
        }else
        {
            $sql = "SELECT * FROM `friendly`";
        }
        
        $prep = $this->sql->conn->prepare($sql);
        $prep->execute(array($this->client));
        while($all_clients = $prep->fetch(2))
        {
            $clients[] = array(
                                $all_clients['client'],
                                $all_clients['friendly']
                            );
        }
        $this->client_list = $clients;
        return $this->client_list;
    }

    function GetSavedLists()
    {
        $sql = "SELECT * FROM `{$this->sql->db}`.`saved_lists`";
        $result = $this->sql->conn->query($sql);
        $save = $result->fetchAll(2);
        $this->SavedLists = $save;
    }

    function SaveList()
    {
        $sql = "INSERT INTO `{$this->sql->db}`.`saved_lists` ( `id` ,`urls` ,`name` ,`details` ,`date` )
                VALUES ( NULL ,  ?,  ?, ?, ? )";
        $prep = $this->sql->conn->prepare($sql);
        $prep->execute(array($this->parsed_edit_uri['urls'],$this->parsed_edit_uri['name'],$this->parsed_edit_uri['details'], date($this->DateFormat)));
        $this->check_pdo_error($prep);
        $this->AddMessage("Saved List: {$this->parsed_edit_uri['name']}");
        return 1;
    }
    
    /**
     * <pre>
     * Generate LED Groups for the current Client
     * Set $this->led_groups and return an array of the data.
     * </pre>
     * @return string A string with a message of what was done.
     */
    function CreateUser()
    {
        if($this->permissions['edit_users'])
        {
            $user = filter_input(INPUT_POST, 'user_N', FILTER_SANITIZE_STRING);
            $internal_user = @filter_input(INPUT_POST, 'internal_user', FILTER_SANITIZE_STRING);
            
            if(!$internal_user)
            {
                $domain = @filter_input(INPUT_POST, 'domain_N', FILTER_SANITIZE_STRING);
                $sql = "INSERT INTO `allowed_users` (`id`, `username`, `domain`, `edit_urls`, `edit_emerg`, `edit_users`)
                VALUES ('', '$user', '$domain', '1', '0', '0')";
                if($this->sql->conn->query($sql))
                {
                    $ret = "Added new User ($domain\\$user).";
                    $this->Redirect('func=view_users');
                }else
                {
                    $ret = "Failed to add new User.<br />\r\n".$this->sql->conn->error;
                }
            }else
            {
                $userseed = $this->GernerateSeed();
                $pwd = @filter_input(INPUT_POST, 'pwd_N', FILTER_SANITIZE_STRING);
                $pwd = sha256($pwd.$userseed.$this->global_login_seed);
                $sql = "INSERT INTO `allowed_users` (`id`, `username`, `domain`, `edit_urls`, `edit_emerg`, `edit_users`)
                VALUES ('', '$user', '', '1', '0', '0')";
                if($this->sql->conn->query($sql))
                {
                    $sql = "INSERT INTO `internal_users` (`id`, `username`, `password`, `disabled`, `failed`)
                    VALUES ('', '$user', 'SHA256:{$userseed}:{$pwd}', '0', '0')";
                    if($this->sql->conn->query($sql))
                    {
                        $ret = "Added new Internal User ($user).";
                        $this->Redirect('func=view_users');
                    }else
                    {
                        $ret = "Failed to add new User.<br />\r\n".$this->sql->conn->errorInfo;
                    }
                }else
                {
                    $ret = "Failed to add new User.<br />\r\n".$this->sql->conn->errorInfo;
                }
            }
        }else
        {
            throw new Exception("Ummm, you shouldn't be here.. I think you should leave before the droids come. O_o");
        }
        return $ret;
    }
    
    /**
     * <pre>
     * Generate a seed for either the cookie hash, login salt, or something else.
     * </pre>
     * @return string The Seed that has been generated.
     */
    function GenerateSeed()
    {
        $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $seed = '';
        for ($p = 0; $p < 32; $p++)
        {
            $seed .= $characters[mt_rand(0, strlen($characters)-1)];
        }
        return $seed;
    }
    
    /**
     * <pre>
     * Get the permissions for the current user.
     * </pre>
     * @return int 1 if it is successful, 0 if there is an error
     */
    function GetPermissions()
    {
        $sql = "SELECT * FROM `{$this->sql->db}`.`allowed_users` where `username` like ? LIMIT 1";
        $prep = $this->sql->conn->prepare($sql);
        $prep->execute(array($this->username));
        if($prep->errorCode() != "00000")
        {
            $err = $prep->errorInfo();
            throw new Exception($err[2]);
            return 0;
        }
        $this->permissions = array('edit_urls'=>0,'edit_emerg'=>0,'edit_users'=>0,'img_messages'=>0,'c_messages'=>0,'rss_feeds'=>0,'edit_options'=>0);
        $perms = $prep->fetch(2);
        #############
        
        if($perms['edit_urls'])
        {
            $this->nav_bar[] = '<td align="center" class="navtd">Edit Clients: <br /><font color="lawngreen">Allowed</font></td>';
            $this->side_bar[] = '<p><a href="?" class="side_links">List Clients</a></p>';
            $this->permissions['edit_urls'] = 1;
        }else
        {
            $this->nav_bar[] = '<td align="center" class="navtd">Edit Clients: <br /><font color="red">Denied</font></td>';
            $this->side_bar[] = '';
        }
        #############
        if($perms['edit_emerg'])
        {
            $this->nav_bar[] = '<td align="center" class="navtd">Emergency Messages: <br /><font color="lawngreen">Allowed</font></td>';
            $this->side_bar[] = '<p><a href="?func=edit_emerg" class="side_links">Emergency Messages</a></p>';
            $this->permissions['edit_emerg'] = 1;
        }else
        {
            $this->nav_bar[] = '<td align="center" class="navtd">Emergency Messages: <br /><font color="red">Denied</font></td>';
            $this->side_bar[] = '';
        }
        #############
        if($perms['edit_users'])
        {
            $this->nav_bar[] = '<td align="center" class="navtd">Edit Users: <br /><font color="lawngreen">Allowed</font></td>';
            $this->side_bar[] = '<p><a href="?func=view_users" class="side_links">User Permissions</a></p>';
            $this->permissions['edit_users'] = 1;
        }else
        {
            $this->nav_bar[] = '<td align="center" class="navtd">Edit Users: <br /><font color="red">Denied</font></td>';
            $this->side_bar[] = '';
        }
        #############
        if($perms['img_messages'])
        {
            $this->nav_bar[] = '<td align="center" class="navtd">Image Messages: <br /><font color="lawngreen">Allowed</font></td>';
            $this->side_bar[] = '<p><a href="?func=img_messages" class="side_links">Image Messages</a></p>';
            $this->permissions['img_messages'] = 1;
        }else
        {
            $this->nav_bar[] = '<td align="center" class="navtd">Image Messages: <br /><font color="red">Denied</font></td>';
            $this->side_bar[] = '';
        }
        #############
        if($perms['c_messages'])
        {
            $this->nav_bar[] = '<td align="center" class="navtd">Custom Messages: <br /><font color="lawngreen">Allowed</font></td>';
            $this->side_bar[] = '<p><a href="?func=c_messages" class="side_links">Custom Messages</a></p>';
            $this->permissions['c_messages'] = 1;
        }else
        {
            $this->nav_bar[] = '<td align="center" class="navtd">Custom Messages: <br /><font color="red">Denied</font></td>';
            $this->side_bar[] = '';
        }
        #############
        if($perms['rss_feeds'])
        {
            $this->nav_bar[] = '<td align="center" class="navtd">RSS Feeds: <br /><font color="lawngreen">Allowed</font></td>';
            $this->side_bar[] = '<p><a href="?func=rss_feeds" class="side_links">RSS Feeds</a></p>';
            $this->permissions['rss_feeds'] = 1;
        }else
        {
            $this->nav_bar[] = '<td align="center" class="navtd">RSS Feeds: <br /><font color="red">Denied</font></td>';
            $this->side_bar[] = '';
        }
        if($perms['edit_options'])
        {
            $this->nav_bar[] = '<td align="center" class="navtd">UNS Options: <br /><font color="lawngreen">Allowed</font></td>';
            $this->side_bar[] = '<p><a href="?func=edit_options" class="side_links">UNS Options</a></p>';
            $this->permissions['edit_options'] = 1;
        }else
        {
            $this->nav_bar[] = '<td align="center" class="navtd">UNS Options: <br /><font color="red">Denied</font></td>';
            $this->side_bar[] = '';
        }
        $this->side_bar[] = '<p><a href="?func=logout" class="side_links">Logout ('.$this->username.')</a></p>';
        
        $this->smarty->assign("side_bar", implode("", $this->side_bar));
        $this->smarty->assign("nav_bar", implode("", $this->nav_bar));
        return 1;
    }
    
    /**
     * <pre>
     * Generate TimeZone Options and set the smarty var
     * </pre>
     */
    function GenTzOpts()
    {
        $sql = "SELECT `tz` FROM `{$this->sql->db}`.`allowed_users` where `username` = '$this->username'";
        $result = $this->sql->conn->query($sql);
        $array = $result->fetch(2);
        $user_TZ = explode(":",$array['tz']);
        $tz_opts = array();
        foreach(timezone_abbreviations_list() as $key=>$TZ_L)
        {
            foreach($TZ_L as $key1=>$TL)
            {
                if(($key == @$user_TZ[0])&&($key1 == $user_TZ[1]))
                {
                    $offset = (($TL["offset"]/60)/60);
                    $tz_opts[] = array(
                        "value"=>$key.":".$key1,
                        "selected"=>'selected="yes"',
                        "label"=> $TL["timezone_id"]." [".$offset."]"
                        );
                }else
                {
                    $offset = (($TL["offset"]/60)/60);
                    $tz_opts[] = array(
                        "value"=>$key.":".$key1,
                        "label"=> $TL["timezone_id"]." [".$offset."]"
                        );
                }
            }
        }
        $this->smarty->assign("UNS_Timezones", $tz_opts);
    }
    
    /**
     * <pre>
     * Get the current clients LED ID
     * Set $this->client_led.
     * </pre>
     * @return int The current clients LED ID.
     */
    function GetClientLED()
    {
        $sql = "SELECT `led` FROM `{$this->sql->db}`.`allowed_clients` WHERE `client_name` like ?";
        $prep = $this->sql->conn->prepare($sql);
        $prep->execute(array($this->client));
        $led = $prep->fetch(2);
        $this->client_led = $led['led'];
        return $this->client_led;
    }
    
    /**
     * <pre>
     * Generate LED Groups for the current Client
     * Set $this->led_groups and return an array of the data.
     * </pre>
     * @return array The LED groups, with the current clients set as selected.
     */
    function GenerateLEDGroups()
    {
        $this->led_groups = array();
        $i=6;
        while($i !== 0)
        {
            $this->led_groups[$i]['group'] = $i;
            if($this->client_led == $i)
            {
                $this->led_groups[$i]['selected'] = "selected='yes'";
            }else
            {
                $this->led_groups[$i]['selected'] = "";
            }
            $i--;
        }
    }
    
    function GetClient()
    {
        $this->GetFriendly($this->client);
        $this->GetClientLED();
        $this->GenerateLEDGroups();
        $this->GetClientURLs();
        
        
        $this->smarty->assign("client_name", $this->client);
        $this->smarty->assign("friendly", $this->friendly);
        $this->smarty->assign("led_groups", $this->led_groups);
        $this->smarty->assign("client_urls", $this->client_urls);
        $this->smarty->assign("refresh", $this->page_refresh);
    }
    
    function GetClientURLs()
    {
        $this->client;
        $sql = "SELECT * FROM `{$this->sql->db}`.`client_links` WHERE `client` = ? ORDER BY `url` ASC";
        $prep = $this->sql->conn->prepare($sql);
        $prep->execute(array($this->client));
        $this->client_urls = $prep->fetchAll(2);
        return $this->client_urls;
    }
    
    function GetAllClients()
    {
        $sql = "SELECT `friendly`.`id`,`client`,`friendly`,`led` FROM `{$this->sql->db}`.`allowed_clients`,`{$this->sql->db}`.`friendly` WHERE `friendly`.`client` = `allowed_clients`.`client_name`";
        $result = $this->sql->conn->query($sql);
        $i = 0;
        $client = array();
        while($clients = $result->fetch(2))
        {
            $i++;
            $this->client = $clients['client'];
            $last = $this->last_conn();
            $client[$i]['friendly'] = $clients['friendly'];
            $client[$i]['date'] = date("F j, Y, g:i a",$last['last_conn']);
            $client[$i]['name'] = $this->client;
            if($last['last_conn'])
            {
                switch($last['last_url'])
                {
                    case "no_urls":
                        $client[$i]['last_url'] = "Client Has No URLS";
                        break;
                    default:
                        $client[$i]['last_url'] = '<a class="links" target="_blank" href="'.$last['last_url'].'">'.wordwrap($last['last_url'],60, '<br />', 1).'</a>';
                        break;
                }
            }else
            {
                $client[$i]['last_url'] = "Has not connected yet...";
            }
        }
        $this->client_all_data = $client;
    }
    
    function GetUserInfo()
    {
        
    }
    
    function DisplayPage($page, $mesg)
    {
        $this->smarty->assign("message", $mesg);
        $this->smarty->display($page);
        exit();
    }

    function Login()
    {
        #if($this->LoginCheck())
        #{
        #    return 1;
        #}
        
        $sql = "SELECT * FROM `{$this->sql->db}`.`internal_users` WHERE `username` = '{$this->username}'";
        $result = $this->sql->conn->query($sql);
        $user_array = $result->fetch(2);
        if($user_array['disabled'])
        {
            return -1;
        }
        $this->logins_left = $user_array['failed'];
        $test_hash = strpos($user_array['password'], ":");
        if($test_hash)
        {
            $pass_exp = explode(":", $user_array['password']);
            $this->user_seed = $pass_exp[1];
            $this->pass_comp = $pass_exp[2];
            $this->password = sha256($this->password.$this->user_seed.$this->global_login_seed);
            if(strcasecmp($this->password, $this->pass_comp) === 0)
            {
                /* User has supplied the correct password, now lets log the data and time to their user row. */
                $time_stamp = time();
                
                list($usec, $sec) = explode(' ', microtime());
                $seed = (float) $sec + ((float) $usec * 100000);
                mt_srand($seed);
                $LOGIN_HASH = md5(mt_rand(000000, 999999));
                $expire = $time_stamp+3600;
                
                /* Lets clear all hashes for this user before we insert a new one */
                $this->ClearLoginHashes();
                
                
                $sql = "INSERT INTO `{$this->sql->db}`.`login_hashes` VALUES ('', ?, ?, ?)";
                $prep = $this->sql->conn->prepare($sql);
                $prep->execute(array($LOGIN_HASH, $expire, $this->username));
                
                if($prep->errorCode() != "00000")
                {
                    $err = $prep->errorInfo();
                    throw new Exception($err[2]);
                }
                
                $sql = "UPDATE `{$this->sql->db}`.`allowed_users` SET `last_active` = ?, `failed` = ?";
                $prep = $this->sql->conn->prepare($sql);
                $prep->execute(array($time_stamp, 0));
                if($prep->errorCode() != "00000")
                {
                    $err = $prep->errorInfo();
                    throw new Exception($err[2]);
                }
                
                setcookie("UNS_LOGIN_HASH", $this->username.":".$LOGIN_HASH, ($time_stamp+$this->cookie_timeout), "/".$this->root."admin/", str_replace(array("/","http://"), "", $this->host), $this->SSL, 1);
#                echo "UNS_LOGIN_HASH"."\r\n", $this->username.":".$LOGIN_HASH."\r\n", ($time_stamp+$this->cookie_timeout)."\r\n", "/".$this->root."admin/"."\r\n", str_replace(array("/","http://"), "", $this->host)."\r\n", $this->SSL."\r\n", 1;
#                die();
                $this->password=NULL;
                return 1;
            }else
            {
                /* User failed to supply the correct password, lets increment the failed login apptempt flag, and return a failure flag */
                $sql = "SELECT `failed` FROM `{$this->sql->db}`.`allowed_users` WHERE `username` = '{$this->username}'";
                $result = $this->sql->conn->query($sql);
                $failed = $result->fetch(2);
                $failed_count = $failed['failed']+1;
                
                if($failed_count >= $this->max_bad_logins)
                {
                    /* Too many bad login attempts, lock the user */
                    $sql = "UPDATE `{$this->sql->db}`.`allowed_users` SET `disabled` = ?";
                    $prep = $this->sql->conn->prepare($sql);
                    $prep->execute(array(1));
                
                }
                else
                {
                    /* bad login, increase counter */
                    $sql = "UPDATE `{$this->sql->db}`.`allowed_users` SET `failed` = ?";
                    $prep = $this->sql->conn->prepare($sql);
                    $prep->execute(array($failed_count));
                }
                
                if($prep->errorCode() != "00000")
                {
                    $err = $prep->errorInfo();
                    throw new Exception($err[2]);
                }
                return -2;
            }
        }else
        {
            $this->UpdatePassword();
        }
    }
    
    function LoginCheck()
    {
        if(!@$_COOKIE['UNS_LOGIN_HASH']){return 0;}
        $cookie_exp = explode(":", html_entity_decode($_COOKIE['UNS_LOGIN_HASH']));
        
        $sql = "SELECT `hash`, `user` FROM `{$this->sql->db}`.`login_hashes` WHERE `user` = ?";
        $prep = $this->sql->conn->prepare($sql);
        $prep->execute(array($cookie_exp[0]));
        $login_hashes = $prep->fetch(2);
        #var_dump($login_hashes);
        if(strcasecmp($login_hashes['hash'], $cookie_exp[1]) === 0)
        {
            $this->username = $login_hashes['user'];
            return 1;
        }else
        {
            return 0;
        }
    }
    
    function Logout()
    {
        if(@$_COOKIE['UNS_LOGIN_HASH'])
        {
            $cookie_exp = explode(":", $_COOKIE['UNS_LOGIN_HASH']);
            $cookie_hash = $cookie_exp[1];

            $sql = "SELECT * FROM `{$this->sql->db}`.`hash_links` where `hash` like ?";
            
            $prep = $this->sql->conn->prepare($sql);
            $prep->execute(array($cookie_hash));
            $links = $prep->fetch(2);
            
            $sql = "DELETE FROM `{$this->sql->db}`.`login_hashes` where `id` = ?";
            $prep = $this->sql->conn->prepare($sql);
            $prep->execute(array($links['id']));
            $time_stamp = time();
            if($prep->errorCode() != "00000")
            {
                setcookie("UNS_LOGIN_HASH", ":00000000000000000000000000101010", ($time_stamp-(3600*48)), "/".$this->root."admin/", str_replace(array("/","http://"), "", $this->host), $this->SSL, 1);
                
                $err = $prep->errorInfo();
                throw new Exception("Failed to remove session from table, but the cookie was removed. - ".$err[2]);
                
                return -1;
            }else
            {
                if(setcookie("UNS_LOGIN_HASH", ":00000000000000000000000000101010", ($time_stamp-(3600*48)), "/".$this->root."admin/", str_replace(array("/","http://"), "", $this->host), $this->SSL, 1))
                {
                    return 1;
                }
            }
        }else
        {
            return 0;
        }
    }
    
    function parse_edit_url($request)
    {
        $definition = array(
            'client'=>FILTER_SANITIZE_STRING,
            'copy2'=>FILTER_SANITIZE_STRING,
            'save_list'=>FILTER_SANITIZE_STRING,
            'remove'=>FILTER_SANITIZE_STRING,
            'disable'=>FILTER_SANITIZE_NUMBER_INT,
            'refresh'=>FILTER_SANITIZE_NUMBER_INT,
            'func'=>FILTER_SANITIZE_STRING,
            'cl_func'=>FILTER_SANITIZE_STRING,
            'copy_clients'=>FILTER_SANITIZE_STRING,
            'jobtodo'=>FILTER_SANITIZE_STRING,
            'NEWURLS'=>FILTER_SANITIZE_STRING,
            'name'=>FILTER_SANITIZE_STRING,
            'urls'=>FILTER_SANITIZE_STRING,
            'details'=>FILTER_SANITIZE_STRING,
        );
        $this->parsed_edit_uri = filter_var_array($request, $definition);
    }
    
    function AddURLs()
    {
        $urls = explode("\n", $this->parsed_edit_uri['NEWURLS']);
        $sql = "INSERT INTO `{$this->sql->db}`.`client_links` 
                (`id`, `url`, `client`, `disabled`, `refresh`) 
                VALUES (NULL, ?, ?, ?, ?)";
        $prep = $this->sql->conn->prepare($sql);
        foreach($urls as $url)
        {
            $prep->execute(array($url, $this->client, 0, $this->parsed_edit_uri['refresh']));
            $this->check_pdo_error($prep);
        }
        $this->AddMessage("URLs Added Successfully");
        return 1;
    }
    
    function SetRefreshURLS()
    {
        foreach($_POST['urls'] as $key=>$url)
        {
            $sql = "UPDATE ``.`client_links` SET `refresh` = ? WHERE `client` = ? AND `id` = ?";
            $prep = $this->sql->conn->prepare($sql);
            $prep->execute(array($_POST['refresh_time'][$key], $this->client, $url));
            $this->check_pdo_error($prep);
            $this->AddMessage("Updated Client [{$this->client}] URL [{$url}] Refresh time");
        }
        return 1;
    }
    
    function Redirect($uri)
    {
        if(!$this->error_flag)
        {
#            header($this->admin_path.$uri, 1);
            echo "<script language=\"JavaScript\"> window.parent.location = '{$this->admin_path}{$uri}'; </script>";
            exit();
            #$this->smarty->assign("redirect", "<script language=\"JavaScript\"> windows.location.href = '{$this->admin_path}{$uri}'; </script>");
        }
        else{return 0;}
    }
    
    function AddMessage($msg)
    {
        $this->all_messages[] = array('time'=>date($this->DateFormat), 'msg'=>$msg);
    }
    
    function RemoveClients()
    {
        if(!@$_POST['remove'])
        {
            return 0;
        }
        $clients = $_POST['remove'];
        foreach($clients as $id)
        {
            $sql = "DELETE FROM `allowed_clients` WHERE `client_name` = ?";
            $prep = $this->sql->conn->prepare($sql);
            $prep->execute(array($id));

            if(!$this->check_pdo_error($prep))
            {
                $sql = "DELETE FROM `friendly` WHERE `client` = ?";
                $prep = $this->sql->conn->prepare($sql);
                $prep->execute(array($id));
                if(!$this->check_pdo_error($prep))
                {
                    $sql = "DELETE FROM `client_links` WHERE `client` = ?";
                    $prep = $this->sql->conn->prepare($sql);
                    $prep->execute(array($id));
                    if(!$this->check_pdo_error($prep))
                    {
                        $this->AddMessage("Removed client [$id]");
                    }else
                    {
                        $this->AddMessage("Failed to delete links for client: $id");
                    }
                }else
                {
                    $this->AddMessage("Failed to remove client [$id] from friendly");
                }
            }else
            {
                $this->AddMessage("Failed to remove client [$id] from allowed list");
            }
        }
    }
    
    function ShowResults($title="")
    {
        $this->smarty->assign("title_of_job", $title);
        $this->smarty->assign("all_messages", $this->all_messages);
        $this->smarty->display("results_page.tpl");
    }
    
    function RenameClient()
    {
        $sql = "UPDATE `{$this->sql->db}`.`friendly` SET `friendly` = ? WHERE `client` = ?";
        $prep = $this->sql->conn->prepare($sql);
        $prep->execute(array($this->clientNewName, $this->client));
        if(!$this->check_pdo_error($prep))
        {
            $this->AddMessage("Successfully renamed Client to {$this->clientNewName}");
        }
    }
    
    function UpdatePassword()
    {
        if($this->update_pwd)
        {
            $this->username = filter_input(INPUT_POST, 'username', FILTER_SANITIZE_SPECIAL_CHARS);
            $OldPassword = filter_input(INPUT_POST, 'oldpassword', FILTER_SANITIZE_SPECIAL_CHARS);
            $old_pwd_hash = md5($OldPassword.$this->global_login_seed);
            $sql = "SELECT * FROM `{$this->sql->db}`.`internal_users` WHERE `username` = '{$this->username}'";
            $result = $this->sql->conn->query($sql);
            $array = $result->fetch(2);
            if(strcasecmp($old_pwd_hash, $array['password']) !== 0)
            {
                $this->smarty->assign("message", "Passwords do not match, try again please.");
                $this->smarty->display("update_password.tpl");
                exit();
            }
            
            $Password = filter_input(INPUT_POST, 'newpassword', FILTER_SANITIZE_SPECIAL_CHARS);
            $PasswordAgain = filter_input(INPUT_POST, 'newpasswordagain', FILTER_SANITIZE_SPECIAL_CHARS);
            if(strcmp($Password, $PasswordAgain) === 0)
            {
                $user_seed = $this->GenerateSeed();
                $pass_hash = sha256($Password.$user_seed.$this->global_login_seed);
                $sql = "UPDATE `{$this->sql->db}`.`internal_users` SET `password` = ? WHERE `username` = ?";
                echo "sha256:{$user_seed}:{$pass_hash}";
                $values = array(
                    "sha256:{$user_seed}:{$pass_hash}",
                    $this->username
                );
                $prep = $this->sql->conn->prepare($sql);
                $prep->execute($values);
                if($prep->errorCode() != "00000")
                {
                    $err = $prep->errorInfo();
                    throw new Exception($err[2]);
                }else
                {
                    return 1;
                }
            }else
            {
                $this->smarty->assign("message", "Passwords do not match, try again please.");
                $this->smarty->display("update_password.tpl");
                exit();
            }
        }else
        {
            $this->smarty->display("update_password.tpl");
            exit();
        }
    }
}

?>
