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
        $this->page_timeout         = $GLOBALS['config']['page_timeout'];
        $this->cookie_timeout       = $GLOBALS['config']['coockie_timeout'];
        $this->max_archives         = $GLOBALS['config']['max_archives'];
        $this->mysql_dump_bin       = $GLOBALS['config']['mysql_dump_bin'];
        $this->timezone             = $GLOBALS['config']['timezone'];
        $this->admin_path           = $this->host_path.'admin/';
        $this->client_path          = $this->host_path.'?';
        $this->LDAP                 = $GLOBALS['config']['LDAP'];
        $this->From_Email                 = $GLOBALS['config']['Email'];
        $this->max_bad_logins       = $GLOBALS['config']['max_bad_logins'];
        $this->password_reset_hash_timeout = $GLOBALS['config']['password_hash_timeout'];
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
        $this->GetPermissions();
        $this->GetEmergFlag();
        $this->smarty->assign("emerg_flag", $this->emerg_flag);
        $this->smarty->assign("client_url", $this->client_path);
        $this->smarty->assign("admin_url", $this->admin_path);
    }
    
    function __destruct()
    {exit();}
    
    function AddEmergURLs($urls="")
    {
        $urls_exp = explode("\r\n", $urls);
        $sql = "INSERT INTO `{$this->sql->db}`.`emerg` (`id`, `url`, `refresh`, `enabled`, `cl_id`)
                VALUES (NULL, :url, :refresh, 1, '')";
        $prep = $this->sql->conn->prepare($sql);
        foreach($urls_exp as $url)
        {
            $prep->bindParam(":url", $url);
            $prep->bindParam(":refresh", $this->parsed_edit_uri['refresh']);
            $prep->execute();
            
            $this->check_pdo_error($prep);
            $this->AddMessage("Added New Emergency URL ({$url})");
        }
        return 1;
    }
    
    function check_archives()
    {
        if($this->max_archives == 0){return 0;}
        $sql = "SELECT * FROM `{$this->sql->db}`.`archive_links` WHERE `client` = :client ORDER BY `date` ASC";
        $prep = $this->sql->conn->prepare($sql);
        $prep->bindParam(":client", $this->client, PDO::PARAM_STR);
        $prep->execute();
        $rows = $prep->rowCount();
        if($this->max_archives < $rows)
        {
            $sql = "DELETE FROM `{$this->sql->db}`.`archive_links` WHERE `id` = :id";
            $prep1 = $this->sql->conn->prepare($sql);
            while($arcs = $prep->fetchAll(2))
            {
                if($rows == $this->max_archives){break;}
                $prep1->bindParam(":id", $arcs['id'], PDO::PARAM_INT);
                $prep1->execute();
                $this->check_pdo_error($prep1);
                
                $rows--;
                $this->AddMessage("Removed row [".$arcs['id']."]<br />");
            }
        }
        return 1;
    }
    
    function ClearLoginHashes()
    {
        $sql = "DELETE FROM `{$this->sql->db}`.`login_hashes` WHERE `user` = :user AND `time` < :time";
        $prep = $this->sql->conn->prepare($sql);
        $time = time()-($this->cookie_timeout);
        $prep->bindParam(":user", $this->username, PDO::PARAM_STR);
        $prep->bindParam(":time", $time, PDO::PARAM_INT);
        #$prep->execute();
        #$this->check_pdo_error($prep);
        return 1;
    }
    
    function CopyClientURLS()
    {
        foreach($_REQUEST['copy_clients'] as $copy_client)
        {
            $fail = 0;
            $sql = "SELECT * FROM `{$this->sql->db}`.`client_links` WHERE `client` = :client";
            $prep = $this->sql->conn->prepare($sql);
            $prep->bindParam(":client", $copy_client, PDO::PARAM_STR);
            $prep->execute();
            $links = array(); #get list of URLS from Client that you want to copy to
            while($client_links = $prep->fetch(2))
            {
                $links[] = $client_links['url']."~".$client_links['refresh'];
            }
            #lets get its friendly name
            $this->copy_friendly = $this->GetFriendly($copy_client);
            $this->copy_client = $copy_client;
            if(!@is_null($links[0]))
            {
                $date = date($this->DateFormat);
                $name = "Backup of URLS for {$this->friendly} on {$date}";
                $this->ArchiveLinks($name, $links);
            }else
            {
                $this->AddMessage("Client: $this->copy_friendly Does not have any URLs yet. No need to back them up.");
            }
            
            $ids = explode("|", $this->ids_imp);
            #var_dump($this->ids_imp);
            $sql = "DELETE FROM `{$this->sql->db}`.`client_links` WHERE `client` = :client";
            $prep = $this->sql->conn->prepare($sql);
            $prep->bindParam(":client", $copy_client, PDO::PARAM_STR);
            $prep->execute();
            $this->check_pdo_error($prep);
            $sql = "SELECT * FROM `{$this->sql->db}`.`client_links` where `id` = :id";
            $prep1 = $this->sql->conn->prepare($sql);
            
            $sql = "INSERT INTO `{$this->sql->db}`.`client_links` (`id`, `url`, `disabled`, `refresh`, `client`) 
                VALUES ( NULL, :url, 0, :refresh, :client)";
            $prep2 = $this->sql->conn->prepare($sql);
            
            foreach($ids as $id)
            {
                $this->AddMessage("Start Copy of URL ID: {$id} to Client: {$this->copy_friendly}");
                
                $prep1->bindParam(":id", $id, PDO::PARAM_INT);
                $prep1->execute();
                $copy_link = $prep1->fetch(2);
                #var_dump($copy_link);
                #exit;
                $this->check_pdo_error($prep1);
                $prep2->bindParam(":url", $copy_link['url'], PDO::PARAM_STR);
                $prep2->bindParam(":refresh", $copy_link['refresh'], PDO::PARAM_INT);
                $prep2->bindParam(":client", $copy_client, PDO::PARAM_STR);
                $prep2->execute();
                
                $this->check_pdo_error($prep2);
                #{
                #    $this->AddMessage("Failed to copy URL [$id] to client: $this->copy_friendly.");
                #}else
                #{
                #    $this->AddMessage("Copied URL [$id] to Client: $this->copy_friendly.");
                #}
            }
        }
    }
    
    function ArchiveLinks($name="", $links="")
    {
        $date = date($this->DateFormat);
        $links_imp = implode("|", $links);
        $sql = "INSERT INTO `{$this->sql->db}`.`archive_links` (`id`, `client`, `urls`, `name`, `details`, `date`) 
            VALUES ('', :client, :urls, :name, 'Automated backup.', :date)";
        $prep = $this->sql->conn->prepare($sql);
        $prep->bindParam(":client", $this->copy_client, PDO::PARAM_STR);
        $prep->bindParam(":urls", $links_imp, PDO::PARAM_STR);
        $prep->bindParam(":name", $name, PDO::PARAM_STR);
        $prep->bindParam(":date", $date, PDO::PARAM_STR);
        
        $prep->execute();
        if($this->check_pdo_error($prep))
        {
            $this->AddMessage("URLs for Client: {$this->copy_friendly} have been backed up.");
        }else
        {
            $this->AddMessage("URLs for Client: {$this->copy_friendly} have <u><b>NOT</b></u> been backed up.");
        }
    }
    
    function CreateClient($name, $led)
    {
        $new_client = sha256(rand(000000,999999));
        $sql = "INSERT INTO `{$this->sql->db}`.`allowed_clients` VALUES('', :client, :led)";
        $prep = $this->sql->conn->prepare($sql);
        $prep->bindParam(":client", $new_client, PDO::PARAM_STR);
        $prep->bindParam(":led", $led, PDO::PARAM_INT);
        
        $prep->execute();
        
        if(!$this->check_pdo_error($prep))
        {
            $sql = "INSERT INTO `{$this->sql->db}`.`friendly` VALUES('', :freindly, :client)";
            $prep = $this->sql->conn->prepare($sql);
            $prep->bindParam(":freindly", $name, PDO::PARAM_STR);
            $prep->bindParam(":client", $new_client, PDO::PARAM_STR);
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
            $sql = "SELECT * FROM `{$this->sql->db}`.`friendly` where `client` NOT LIKE :client";
        }else
        {
            $sql = "SELECT * FROM `{$this->sql->db}`.`friendly`";
        }
        
        $prep = $this->sql->conn->prepare($sql);
        $prep->bindParam(":client", $this->client, PDO::PARAM_STR);
        $prep->execute();
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
        $sql = "SELECT * FROM `{$this->sql->db}`.`saved_lists` WHERE `client` = :client";
        $prep = $this->sql->conn->prepare($sql);
        $prep->bindParam(":client", $this->client, PDO::PARAM_STR);
        $prep->execute();
        
        $saved = array();
        while($save = $prep->fetch(2))
        {
            $saved[] = array_merge($save, array("url_array" => explode("|", $save['urls'])));
        }
        
        $this->SavedLists = $saved;
    }

    function SaveList($append=0)
    {
        $urls = "";
        $name = @$this->parsed_edit_uri['name'];
        foreach(explode("|", $this->parsed_edit_uri['urls']) as $url_id)
        {
            $sql = "SELECT `url` FROM `{$this->sql->db}`.`client_links` WHERE `id` = :id";
            $prep = $this->sql->conn->prepare($sql);
            $prep->bindParam(":id", $url_id, PDO::PARAM_INT);
            $prep->execute();
            
            $this->check_pdo_error($prep);
            $url_fetch = $prep->fetch(1);
            $url[] = $url_fetch['url'];
        }
        $date = date($this->DateFormat,time());
        if($append != 0)
        {
            $sql = "SELECT `urls`,`name` FROM `{$this->sql->db}`.`saved_lists` WHERE `id` = :id";
            $prep = $this->sql->conn->prepare($sql);
            $prep->bindParam(":id", $append, PDO::PARAM_INT);
            $prep->execute();
            $this->check_pdo_error($prep);
            $url_fetch = $prep->fetch(1);
            
            $urls = $url_fetch['urls'].'|'.implode("|", $url);
            $name = $url_fetch['name'];
            $sql = "UPDATE `{$this->sql->db}`.`saved_lists` SET `urls` = :urls, `date` = :date WHERE `id` = :id";
            $prep = $this->sql->conn->prepare($sql);
            $prep->bindParam(":urls", $urls, PDO::PARAM_STR);
            $prep->bindParam(":date", $date, PDO::PARAM_STR);
            $prep->bindParam(":id", $append, PDO::PARAM_INT);
            $prep->execute();
        }else
        {
            $urls = implode("|", $url);
            $sql = "INSERT INTO `{$this->sql->db}`.`saved_lists` ( `id`, `client`, `urls` ,`name` ,`details` ,`date` )
                VALUES ( NULL, :client, :urls, :name, :details, :date)";
            $prep = $this->sql->conn->prepare($sql);
            $prep->bindParam(":client", $this->client, PDO::PARAM_STR);
            $prep->bindParam(":urls", $urls, PDO::PARAM_STR);
            $prep->bindParam(":name", $this->parsed_edit_uri['name'], PDO::PARAM_STR);
            $prep->bindParam(":details", $this->parsed_edit_uri['details'], PDO::PARAM_STR);
            $prep->bindParam(":date", $date, PDO::PARAM_STR);
            
            $prep->execute();
        }
        
        $this->check_pdo_error($prep);
        $this->AddMessage("Saved List: {$name} on {$date} by {$this->username}");
        return 1;
    }
    
    function RemoveSavedList($ids)
    {
        foreach($ids as $id)
        {
            $sql = "DELETE FROM `{$this->sql->db}`.`saved_lists` WHERE `id` = :id";
            $prep = $this->sql->conn->prepare($sql);
            $prep->bindParam(":id", $id, PDO::PARAM_INT);
            $prep->execute();
            $this->check_pdo_error($prep);
        }
        return 1;
    }
    
    function RemoveArchivedList($ids)
    {
        foreach(explode("|", $ids) as $id)
        {
            $sql = "SELECT * FROM `{$this->sql->db}`.`archive_links` WHERE `id` = :id";
            $prep = $this->sql->conn->prepare($sql);
            $prep->bindParam(":id", $id, PDO::PARAM_INT);
            $prep->execute();
            $array = $prep->fetch();
            
            $sql = "DELETE FROM `{$this->sql->db}`.`archive_links` WHERE `id` = :id";
            $prep = $this->sql->conn->prepare($sql);
            $prep->bindParam(":id", $id, PDO::PARAM_INT);
            $prep->execute();
            $this->check_pdo_error($prep);
            $this->AddMessage("Removed Archived List {$array['name']}");
        }
        return 1;
    }
    
    function RestoreArchivedList($id)
    {
        $sql = "SELECT * FROM `{$this->sql->db}`.`client_links` WHERE `client` = :client";
        $prep = $this->sql->conn->prepare($sql);
        $prep->bindParam(":client", $this->client, PDO::PARAM_STR);
        $prep->execute();
        $this->check_pdo_error($prep);
        $old_list = $prep->fetch_all(1);
        
        $date = date($this->DateFormat, time());
        $sql = "INSERT INTO `{$this->sql->db}`.`archive_links` (`id`, `client`, `urls`, `name`, `details`, `date`)
            VALUES ( NULL, :client, :urls, :name, :details, :date)";
        $prep = $this->sql->conn->prepare($sql);
        $prep->bindParam(":client", $this->client, PDO::PARAM_STR);
        $prep->bindParam(":urls", implode("|", $old_list['url']), PDO::PARAM_STR);
        $prep->bindParam(":name", "Automated backup on {$date}", PDO::PARAM_STR);
        $prep->bindParam(":details", "Automated backup of URLS for Client {$this->friendly}[{$this->client}] by {$this->user}", PDO::PARAM_STR);
        $prep->bindParam(":date", $date, PDO::PARAM_STR);

        $prep->execute();
        $this->check_pdo_error($prep);
        
        $sql = "SELECT * FROM `{$this->sql->db}`.`archive_links` WHERE `id` = :id";
        $prep = $this->sql->conn->prepare($sql);
        $prep->bindParam(":id", $id, PDO::PARAM_INT);
        $prep->execute();
        $this->check_pdo_error($prep);
        $archived_list = $prep->fetch(1);
        $archived_list_exp = explode("|", $archived_list['urls']);
        
        foreach($archived_list_exp as $url)
        {
            $sql = "INSERT INTO `{$this->sql->db}`.`client_links` (`id`, `client`, `url`, `refresh`, `disabled`)
                VALUES ( NULL, :client, :url, :refresh, :disabled)";
            $prep = $this->sql->conn->prepare($sql);
            $prep->bindParam(":client", $this->client, PDO::PARAM_STR);
            $prep->bindParam(":url", $url, PDO::PARAM_STR);
            $prep->bindParam(":refresh", 30, PDO::PARAM_INT);
            $prep->bindParam(":disabled", 0, PDO::PARAM_INT);
            $prep->execute();
            $this->check_pdo_error($prep);
        }
        $this->AddMessage("Restored urls from {$archived_list['name']} to {$this->friendly} on {$date} by {$this->user}");
        return 1;
    }
    
    function RestoreSavedList($id)
    {
        $this->GetFriendly($this->client);
        $urls = array();
        $sql = "SELECT `url` FROM `{$this->sql->db}`.`client_links` WHERE `client` = :client";
        $prep = $this->sql->conn->prepare($sql);
        $prep->bindParam(":client", $this->client, PDO::PARAM_STR);
        $prep->execute();
        $this->check_pdo_error($prep);
        $old_list = $prep->fetchAll(2);
        foreach($old_list as $list)
        {
            $urls[] = $list['url'];
        }
        $date = date($this->DateFormat);
        $url_imp = implode("|", $urls);
        $name = "Automated backup on {$date}";
        $details = "Automated backup of URLS for Client {$this->friendly}[{$this->client}] by {$this->username}";
        $date = date($this->DateFormat, time());
        $sql = "INSERT INTO `{$this->sql->db}`.`archive_links` (`id`, `client`, `urls`, `name`, `details`, `date`)
            VALUES ( NULL, :client, :urls, :name, :details, :date)";
        $prep = $this->sql->conn->prepare($sql);
        $prep->bindParam(":client", $this->client, PDO::PARAM_STR);
        $prep->bindParam(":urls", $url_imp , PDO::PARAM_STR);
        $prep->bindParam(":name", $name, PDO::PARAM_STR);
        $prep->bindParam(":details", $details, PDO::PARAM_STR);
        $prep->bindParam(":date", $date, PDO::PARAM_STR);
        $prep->execute();
        
        $this->check_pdo_error($prep);
        
        $sql = "SELECT * FROM `{$this->sql->db}`.`saved_lists` WHERE `id` = :id";
        $prep = $this->sql->conn->prepare($sql);
        $prep->bindParam(":id", $id, PDO::PARAM_STR);
        $prep->execute();
        $this->check_pdo_error($prep);
        $saved_list = $prep->fetch(1);
        $saved_list_exp = explode("|", $saved_list['urls']);
        
        foreach($saved_list_exp as $url)
        {
            $sql = "INSERT INTO `{$this->sql->db}`.`client_links` (`id`, `client`, `url`, `refresh`, `disabled`)
                VALUES ( NULL, :client, :url, 30, 0)";
            $prep = $this->sql->conn->prepare($sql);
            $prep->bindParam(":client", $this->client, PDO::PARAM_STR);
            $prep->bindParam(":url", $url, PDO::PARAM_STR);
            $prep->execute();
            $this->check_pdo_error($prep);
        }
        $this->AddMessage("Restored urls from {$saved_list['name']} to {$this->friendly} on {$date} by {$this->username}");
        return 1;
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
        $p = 0;
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
        $this->nav_bar = array();
        $this->side_bar = array();
        
        if($this->username == NULL)
        {
            return 0;
        }
        
        $sql = "SELECT `edit_clients`, `edit_emerg`, `edit_users`, `img_messages`, `c_messages`, `rss_feeds`, `edit_options`
                FROM `{$this->sql->db}`.`allowed_users` where `username` = :user LIMIT 1";
        $prep = $this->sql->conn->prepare($sql);
        $prep->bindParam(":user",$this->username, PDO::PARAM_STR);
        $prep->execute();
        $this->check_pdo_error($prep);
        $this->permissions = array(
                                    'edit_clients'=>0,
                                    'edit_emerg'=>0,
                                    'edit_users'=>0,
                                    'img_messages'=>0,
                                    'c_messages'=>0,
                                    'rss_feeds'=>0,
                                    'edit_options'=>0
                                   );
        $perms = $prep->fetch(2);
        #############
        if($perms['edit_clients'])
        {
            $this->nav_bar[] = '<td align="center" class="navtd">Edit Clients: <br /><font color="lawngreen">Allowed</font></td>';
            $this->side_bar[] = '<p><a href="?" class="side_links">List Clients</a></p>';
            $this->permissions['edit_clients'] = 1;
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
        #############
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
        #############
        $this->side_bar[] = '<p><a href="?func=logout" class="side_links">Logout ('.$this->username.')</a></p>';
        #############
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
        $sql = "SELECT `tz` FROM `{$this->sql->db}`.`allowed_users` where `username` = :user";
        $prep = $this->sql->conn->prepare($sql);
        $prep->bindParam(":user", $this->username, PDO::PARAM_STR);
        $prep->execute();
        $array = $prep->fetch(1);
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
        $sql = "SELECT `led` FROM `{$this->sql->db}`.`allowed_clients` WHERE `client_name` like :client";
        $prep = $this->sql->conn->prepare($sql);
        $prep->bindParam(":client", $this->client, PDO::PARAM_STR);
        $prep->execute();
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
    
    function GetArchivedLists()
    {
        $sql = "SELECT * FROM `{$this->sql->db}`.`archive_links` WHERE `client` = :client";
        $prep = $this->sql->conn->prepare($sql);
        $prep->bindParam("client", $this->client, PDO::PARAM_STR);
        $prep->execute();
        $this->check_pdo_error($prep);
        $archived = array();
        while($archive = $prep->fetch(2))
        {
            $archived[] = array_merge($archive, array("url_array" => explode("|", $archive['urls'])));
        }
        
        $this->ArchivedLists = $archived;
    }
    
    function GetClient()
    {
        $this->GetFriendly();
        $this->GetClientLED();
        $this->GenerateLEDGroups();
        $this->GetClientURLs();
        $this->GetSavedLists();
        $this->GetArchivedLists();
        
        $this->smarty->assign('client_archived_links', $this->ArchivedLists);
        $this->smarty->assign('client_saved_links', $this->SavedLists);
        $this->smarty->assign("client_name", $this->client);
        $this->smarty->assign("friendly", $this->friendly);
        $this->smarty->assign("led_groups", $this->led_groups);
        $this->smarty->assign("client_urls", $this->client_urls);
        $this->smarty->assign("refresh", $this->page_refresh);
    }
    
    function GetClientURLs()
    {
        $this->client;
        $sql = "SELECT * FROM `{$this->sql->db}`.`client_links` WHERE `client` = :client ORDER BY `url` ASC";
        $prep = $this->sql->conn->prepare($sql);
        $prep->bindParam(":client", $this->client, PDO::PARAM_STR);
        $prep->execute();
        $this->client_urls = $prep->fetchAll(2);
        return $this->client_urls;
    }
    
    function GetAllClients()
    {
        $sql = "SELECT `friendly`.`id`,`client`,`friendly`,`led` FROM 
            `{$this->sql->db}`.`allowed_clients`,`{$this->sql->db}`.`friendly` 
                WHERE `friendly`.`client` = `allowed_clients`.`client_name`";
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
    
    function GetImgMessages()
    {
        $sql = "SELECT `id`, `name`, `body`, `refresh`, `wrapper`, `enabled` FROM `{$this->sql->db}`.`img_messages`";
        $query = $this->sql->conn->query($sql);
        $fetch = $query->fetchAll(2);
        $this->AllImgMessages = $fetch;
        return $fetch;
    }


    function CheckPermissions()
    {
        switch($this->parsed_edit_uri['func'])
        {
            case "edit_urls":
                if($this->permissions['edit_clients'])
                {return 1;}
                else
                {return 0;}
                break;
            case "add_client":
                if($this->permissions['edit_clients'])
                {return 1;}
                else
                {return 0;}
                break;
            case "remove_cl":
                if($this->permissions['edit_clients'])
                {return 1;}
                else
                {return 0;}
                break;
            case "rename_client":
                if($this->permissions['edit_clients'])
                {return 1;}
                else
                {return 0;}
                break;
            case "client_led_set":
                if($this->permissions['edit_clients'])
                {return 1;}
                else
                {return 0;}
                break;
            case "view_clients":
                if($this->permissions['edit_clients'])
                {return 1;}
                else
                {return 0;}
                break;
            case "edit_emerg":
                if($this->permissions['edit_emerg'])
                {return 1;}
                else
                {return 0;}
                break;
            case "emerg_proc":
                if($this->permissions['edit_emerg'])
                {return 1;}
                else
                {return 0;}
                break;
            case "add_emerg":
                if($this->permissions['edit_emerg'])
                {return 1;}
                else
                {return 0;}
                break;
            case "view_users":
                if($this->permissions['edit_users'])
                {return 1;}
                else
                {return 0;}
                break;
            case "edit_users":
                if($this->permissions['edit_users'])
                {return 1;}
                else
                {return 0;}
                break;
            case "img_messages":
                if($this->permissions['img_messages'])
                {return 1;}
                else
                {return 0;}
                break;
            case "c_messages":
                if($this->permissions['c_messages'])
                {return 1;}
                else
                {return 0;}
                break;
            case "rss_feeds":
                if($this->permissions['rss_feeds'])
                {return 1;}
                else
                {return 0;}
                break;
            case "edit_options":
                if($this->permissions['edit_options'])
                {return 1;}
                else
                {return 0;}
                break;
            default:
                return 1;
                break;
        }
    }
    
    function UpdatePermissions()
    {
        $perms = array();

        if($this->parsed_edit_uri['edit_clients'])
        {
            $perms['edit_clients'] = 1;
        }else
        {
            $perms['edit_clients'] = 0;
        }
        ###########################
        if($this->parsed_edit_uri['edit_emerg'])
        {
            $perms['edit_emerg'] = 1;
        }else
        {
            $perms['edit_emerg'] = 0;
        }
        ###########################
        if($this->parsed_edit_uri['edit_users'])
        {
            $perms['edit_users'] = 1;
        }else
        {
            $perms['edit_users'] = 0;
        }
        ###########################
        if($this->parsed_edit_uri['edit_options'])
        {
            $perms['edit_options'] = 1;
        }else
        {
            $perms['edit_options'] = 0;
        }
        ###########################
        if($this->parsed_edit_uri['c_messages'])
        {
            $perms['c_messages'] = 1;
        }else
        {
            $perms['c_messages'] = 0;
        }
        ###########################
        if($this->parsed_edit_uri['img_messages'])
        {
            $perms['img_messages'] = 1;
        }else
        {
            $perms['img_messages'] = 0;
        }
        ###########################
        if($this->parsed_edit_uri['rss_feeds'])
        {
            $perms['rss_feeds'] = 1;
        }else
        {
            $perms['rss_feeds'] = 0;
        }
        $sql = "UPDATE `{$this->sql->db}`.`allowed_users` SET 
                `edit_clients` = :edit_clients,
                `edit_emerg` = :edit_emerg,
                `edit_users` = :edit_users,
                `edit_options` = :edit_options,
                `rss_feeds` = :rss_feeds,
                `c_messages` = :c_messages,
                `img_messages` = :img_messages
                WHERE `username` = :username ";
        $prep = $this->sql->conn->prepare($sql);
        $prep->bindParam(":edit_clients", $perms['edit_clients'], PDO::PARAM_INT);
        $prep->bindParam(":edit_emerg", $perms['edit_emerg'], PDO::PARAM_INT);
        $prep->bindParam(":edit_users", $perms['edit_users'], PDO::PARAM_INT);
        $prep->bindParam(":edit_options", $perms['edit_options'], PDO::PARAM_INT);
        $prep->bindParam(":c_messages", $perms['c_messages'], PDO::PARAM_INT);
        $prep->bindParam(":img_messages", $perms['img_messages'], PDO::PARAM_INT);
        $prep->bindParam(":rss_feeds", $perms['rss_feeds'], PDO::PARAM_INT);
        $prep->bindParam(":username", $this->parsed_edit_uri['username'], PDO::PARAM_INT);
        $prep->execute();
        $this->check_pdo_error($prep);
        $this->AddMessage("Updated persissions for user {$this->parsed_edit_uri['username']}".var_export($perms, 1));
        return 1;
    }
    
    function ResetFailedLogin()
    {
        $sql = "UPDATE `{$this->sql->db}`.`internal_users` SET `failed` = 0, `disabled` = 0 WHERE `id` = :id";
        $prep = $this->sql->conn->prepare($sql);
        $prep->bindParam(":id", $_REQUEST['userid'], PDO::PARAM_INT);
        $prep->execute();
        $this->check_pdo_error($prep);
        
        $sql = "UPDATE `{$this->sql->db}`.`allowed_users` SET `failed` = 0, `disabled` = 0 WHERE `username` = :username AND `domain` = :domain";
        $prep = $this->sql->conn->prepare($sql);
        $prep->bindParam(":username", $_REQUEST['username'], PDO::PARAM_STR);
        $prep->bindParam(":domain", $_REQUEST['domain'], PDO::PARAM_STR);
        $prep->execute();
        $this->check_pdo_error($prep);
        
        $this->AddMessage("Reset Failed Logins and Enabled User: {$this->parsed_edit_uri['username']}");
    }

    function RemoveUsers()
    {
        $user = $_REQUEST['username'];
        $domain = $_REQUEST['domain'];
        
        $sql = "DELETE FROM `{$this->sql->db}`.`allowed_users` WHERE `username` = :username AND `domain` = :domain";
        $prep = $this->sql->conn->prepare($sql);
        
        $sql = "DELETE FROM `{$this->sql->db}`.`internal_users` WHERE `username` = :username";
        $prep1 = $this->sql->conn->prepare($sql);

        $prep->bindParam(":username", $user, PDO::PARAM_STR);
        $prep->bindParam(":domain", $domain, PDO::PARAM_STR);
        $prep->execute();
        $this->check_pdo_error($prep);
        $this->AddMessage("Removed User from Allowed Users Table ({$this->parsed_edit_uri['username']})");
        if($domain == "")
        {
            $prep1->bindParam(":username", $user, PDO::PARAM_STR);
            $prep1->execute();
            $this->check_pdo_error($prep1);
            $this->AddMessage("Removed User from Internal Users Table ({$this->parsed_edit_uri['username']})");
        }
        return 1;
    }
    
    function ToggleUser()
    {
        $sql = "UPDATE `{$this->sql->db}`.`allowed_users` SET `disabled` = :disabled WHERE `username` = :username ";
        $prep = $this->sql->conn->prepare($sql);
        
        $sql = "UPDATE `{$this->sql->db}`.`internal_users` SET `disabled` = :disabled WHERE `username` = :username ";
        $prep1 = $this->sql->conn->prepare($sql);
        #var_dump($_REQUEST);
        
        $user = $_REQUEST['username'];
        $domain = $_REQUEST['domain'];
        
        $select_sql = "SELECT `disabled` from `{$this->sql->db}`.`allowed_users` WHERE `username` = :username AND `domain` = :domain";
        $prep2 = $this->sql->conn->prepare($select_sql);
        $prep2->bindParam(":username", $user);
        $prep2->bindParam(":domain", $domain);
        $prep2->execute();
        $this->check_pdo_error($prep2);
        $array = $prep2->fetch(2);
        $state = !$array['disabled'];
        if($state)
        {
            $done = "Disabled";
        }
        else
        {
            $done = "Enabled";
        }

        #echo $done." -- ".$user;

        $prep->bindParam(":disabled", $state, PDO::PARAM_INT);
        $prep->bindParam(":username", $user, PDO::PARAM_STR);
        $prep->execute();
        $this->check_pdo_error($prep);
        $this->AddMessage("{$done} user in Allowed Users Table: ({$this->parsed_edit_uri['username']})");
        if($domain == "")
        {
            $prep->bindParam(":disabled", $state, PDO::PARAM_INT);
            $prep->bindParam(":username", $user, PDO::PARAM_STR);
            $prep->execute();
            $this->check_pdo_error($prep);
            $this->AddMessage("{$done} user in Internal Users Table: ({$this->parsed_edit_uri['username']})");
        }
        return 1;
    }
    
    function PrepPasswordReset()
    {
        $username = $this->parsed_edit_uri['username'];
        $id = $this->parsed_edit_uri['userid'];
        $hash = $this->parsed_edit_uri['hash'];
        
        $sql = "SELECT * FROM `{$this->sql->db}`.`reset_hashes` WHERE `hash` = :hash LIMIT 1";
        $prep = $this->sql->conn->prepare($sql);
        $prep->bindParam(":hash", $hash, PDO::PARAM_STR);
        $prep->execute();
        $this->check_pdo_error($prep);
        $array = $prep->fetch(2);
        
        if(!$array)
        {
            $this->AddMessage("There is no hash like that here...", 1);
            return 0;
        }
        
        if((int)$array['time'] < time())
        {
            $sql = "DELETE FROM `{$this->sql->db}`.`reset_hashes` WHERE `hash` = :hash";
            $prep = $this->sql->conn->prepare($sql);
            $prep->bindParam(":hash", $array['hash'], PDO::PARAM_STR);
            $prep->execute();
            $this->check_pdo_error($prep);
            
            $this->AddMessage("Password Hash has timed out, ask for another one.", 1);
            return 0;
        }
        
        $this->reset_username = $array['username'];
        $this->reset_hash = $array['hash'];
        return 1;
    }
    
    function SetPassword()
    {
        $hash = $this->parsed_edit_uri['hash'];
        
        $sql = "SELECT * FROM `{$this->sql->db}`.`reset_hashes` WHERE `hash` = :hash LIMIT 1";
        $prep = $this->sql->conn->prepare($sql);
        $prep->bindParam(":hash", $hash, PDO::PARAM_STR);
        $prep->execute();
        $this->check_pdo_error($prep);
        $array = $prep->fetch(2);
        if(!$array)
        {
            $this->AddMessage("There is no hash like that here...",1);
            return 0;
        }
        
        if((int)$array['time'] < time())
        {
            $sql = "DELETE FROM `{$this->sql->db}`.`reset_hashes` WHERE `hash` = :hash";
            $prep = $this->sql->conn->prepare($sql);
            $prep->bindParam(":hash", $array['hash'], PDO::PARAM_STR);
            $prep->execute();
            $this->check_pdo_error($prep);
            $this->AddMessage("Password Hash has timed out, ask for another one.");
            return 0;
        }
        
        $password1 = filter_input(INPUT_POST, 'password1', FILTER_SANITIZE_STRING);
        $password2 = filter_input(INPUT_POST, 'password2', FILTER_SANITIZE_STRING);
        
        if(strcasecmp($password1, $password2))
        {
            $this->AddMessage("Your passwords did not match, please try again.", 1);
            return 0;
        }
        $pass_hashed = sha256($password1.$this->user_seed.$this->global_login_seed);
        
        $sql = "UPDATE `{$this->sql->db}`.`internal_users` SET `password` = :password WHERE `username` = :username";
        $prep = $this->sql->conn->prepare($sql);
        $prep->bindParam(":username", $array['username'], PDO::PARAM_STR);
        $prep->bindParam(":password", $pass_hashed, PDO::PARAM_STR);
        $prep->execute();
        $this->check_pdo_error($prep);
        
        $sql = "DELETE FROM `{$this->sql->db}`.`reset_hashes` WHERE `hash` = :hash";
        $prep = $this->sql->conn->prepare($sql);
        $prep->bindParam(":hash", $array['hash'], PDO::PARAM_STR);
        $prep->execute();
        $this->check_pdo_error($prep);

        $this->AddMessage("Updated User: {$array['username']} Password!");
        return 1;
    }
    
    function SendPasswordReset()
    {
        $sql = "SELECT `email` from `{$this->sql->db}`.`internal_users` WHERE `username` = :username";
        $prep = $this->sql->conn->prepare($sql);
        $prep->bindParam(":username", $this->parsed_edit_uri['username'], PDO::PARAM_STR);
        $prep->execute();
        $this->check_pdo_error($prep);
        $array = $prep->fetch(2);
        
        $time = time()+$this->password_reset_hash_timeout;
        $user = $this->parsed_edit_uri['username'];
        $hash = implode("-", str_split(sha256($this->GenerateSeed()), 5));
        
        $sql = "INSERT INTO `{$this->sql->db}`.`reset_hashes` (`id`, `username`, `time`, `hash`) VALUES (NULL, :username, :time, :hash)";
        $prep = $this->sql->conn->prepare($sql);
        $prep->bindParam(":username", $user, PDO::PARAM_STR);
        $prep->bindParam(":time", $time, PDO::PARAM_INT);
        $prep->bindParam(":hash", $hash, PDO::PARAM_STR);
        $prep->execute();
        
        $this->check_pdo_error($prep);
        
        $subject = 'UNS Password Reset Request';
        $message = 'UNS Password Reset Request

You Have Requested to have your UNS User password reset.
Here is the link to set your new password: <a href="'.$this->host_path.'admin/?func=password_reset&hash='.$hash.'">Link</a>';
        $headers = 'From: '.$this->From_Email."\r\n".
            'Reply-To: '.$this->From_Email."\r\n" .
            'X-Mailer: UNS/V2.0';
        if(mail($array['email'], $subject, $message, $headers))
        {
            return 1;
        }else
        {
            throw new Exception("Could not send mail message :/");
        }
    }
    
    function GetUserInfo($skip = "")
    {
        $sql = "SELECT `id`, `username`, `domain`, `edit_clients`, `edit_emerg`, `edit_users`, 
                            `edit_options`, `c_messages`, `img_messages`, `rss_feeds`, `last_active`, `failed`, `disabled`
            FROM `{$this->sql->db}`.`allowed_users` WHERE `username` != :username";
        $prep = $this->sql->conn->prepare($sql);
        $prep->bindParam(":username", $skip, PDO::PARAM_STR);
        $prep->execute();
        $this->all_user_info = $prep->fetchAll(2);
        return 1;
    }
    
    function ToggleEmergState()
    {
        $sql = "SELECT `emerg` FROM `{$this->sql->db}`.`settings` LIMIT 1";
        $query = $this->sql->conn->query($sql);
        $query->execute();
        $array = $query->fetch(2);
        
        $this->check_pdo_error($query);
        $emerg = !$array['emerg'];
        $sql = "UPDATE `{$this->sql->db}`.`settings` SET `emerg` = :emerg";
        $prep = $this->sql->conn->prepare($sql);
        $prep->bindParam(":emerg", $emerg, PDO::PARAM_INT);
        $prep->execute();
        $this->check_pdo_error($prep);
        if($emerg)
        {
            $toggle = "Enabled";
        }else
        {
            $toggle = "Disabled";
        }
        $this->AddMessage("Succsesfully {$toggle} Emergency Messages");
    }
    
    function ToggleEmergURL()
    {
        foreach($_REQUEST['ids'] as $id)
        {
            $sql = "SELECT `enabled` FROM `{$this->sql->db}`.`emerg` WHERE `id` = :id";
            $prep = $this->sql->conn->prepare($sql);
            $prep->bindParam(":id", $id, PDO::PARAM_INT);
            $prep->execute();
            $array = $prep->fetch(2);
            var_dump($array);
            $this->check_pdo_error($prep);
            $state = !$array['enabled'];

            $sql = "UPDATE `{$this->sql->db}`.`emerg` SET `enabled` = :state WHERE `id` = :id";
            $prep = $this->sql->conn->prepare($sql);
            $prep->bindParam(":state", $state, PDO::PARAM_INT);
            $prep->bindParam(":id", $id, PDO::PARAM_INT);
            $prep->execute();
            $this->check_pdo_error($prep);

            if($state)
            {
                $toggle = "Enabled";
            }else
            {
                $toggle = "Disabled";
            }
            $this->AddMessage("Succsesfully {$toggle} Emergency Message [$id]");
        }
        return 1;
    }
    
    function DeleteEmergencyMessage()
    {
        foreach($_REQUEST['ids'] as $id)
        {
            $sql = "DELETE FROM `{$this->sql->db}`.`emerg` WHERE `id` = :id";
            $prep = $this->sql->conn->prepare($sql);
            $prep->bindParam(":id", $id, PDO::PARAM_INT);
            $prep->execute();
            $this->check_pdo_error($prep);
            $this->AddMessage("Removed Emergency Message [{$id}]");
        }
        return 1;
    }
    
    function GetEmergencyMessages()
    {
        $sql = "SELECT * FROM `{$this->sql->db}`.`emerg`";
        $query = $this->sql->conn->query($sql);
        $query->execute();
        $all_emerg_msgs = $query->fetchAll(2);
        $this->check_pdo_error($query);
        $this->all_emerg_msgs = $all_emerg_msgs;
        return 1;
    }
    
    function DisplayPage($page, $mesg="")
    {
        $this->smarty->assign("message", $mesg);
        $this->smarty->display($page);
        exit();
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
        $user = @filter_input(INPUT_POST, 'new_username', FILTER_SANITIZE_STRING);
        $domain = @filter_input(INPUT_POST, 'new_domain', FILTER_SANITIZE_STRING);
        $sql = "INSERT INTO `{$this->sql->db}`.`allowed_users` (`id`, `username`, `domain`, `edit_clients`, `edit_emerg`, `edit_users`)
                VALUES (NULL, :user, :domain, '1', '0', '0')";
        $prep = $this->sql->conn->prepare($sql);
        
        if($domain == "")
        {
            $pass = @filter_input(INPUT_POST, 'new_password', FILTER_SANITIZE_STRING);
            $userseed = $this->GenerateSeed();
            $pass_hash = "sha256:".sha256($pass.$userseed.$this->global_login_seed).":".$userseed;
            
            $prep->bindParam(":user", $user, PDO::PARAM_STR);
            $prep->bindParam(":domain", $domain, PDO::PARAM_STR);
            $prep->execute();
            $this->check_pdo_error($prep);
            
            $sql = "INSERT INTO `{$this->sql->db}`.`internal_users` (`id`, `username`, `password`, `disabled`, `failed`)
            VALUES (NULL, :user, :pwd, '0', '0')";
            $prep1 = $this->sql->conn->prepare($sql);
            $prep1->bindParam(":user", $user, PDO::PARAM_STR);
            $prep1->bindParam(":pwd", $pass_hash, PDO::PARAM_STR);
            $prep1->execute();
            $this->check_pdo_error($prep1);
            $ret = "Added new Internal User ($user).";
        }else
        {
            $prep->bindParam(":user", $user, PDO::PARAM_STR);
            $prep->bindParam(":domain", $domain, PDO::PARAM_STR);
            $prep->execute();
            $this->check_pdo_error($prep);
            $ret = "Added new User {$user}@{$domain}.";
        }
        $this->AddMessage($ret, 0);
        return $ret;
    }
    
    function Login()
    {
        #if($this->LoginCheck())
        #{
        #    return 1;
        #}
        
        $sql = "SELECT * FROM `{$this->sql->db}`.`internal_users` WHERE `username` = :user";
        $prep = $this->sql->conn->prepare($sql);
        $prep->bindParam(":user", $this->username, PDO::PARAM_STR);
        $prep->execute();
        $user_array = $prep->fetch(2);
        if($user_array['disabled'])
        {
            return -1;
        }
        $this->logins_left = $user_array['failed'];
        $pass_exp = explode(":", $user_array['password']);

        if($pass_exp[0] == "sha256")
        {
            $this->user_seed = $pass_exp[2];
            $this->pass_comp = $pass_exp[1];
            $this->password_en = sha256($this->password_unen.$this->user_seed.$this->global_login_seed);
            if(strcasecmp($this->password_en, $this->pass_comp) === 0)
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
                
                $sql = "INSERT INTO `{$this->sql->db}`.`login_hashes` VALUES (NULL, :hash, :expire, :user)";
                $prep = $this->sql->conn->prepare($sql);
                $prep->bindParam(":hash", $LOGIN_HASH, PDO::PARAM_STR);
                $prep->bindParam(":expire", $expire, PDO::PARAM_STR);
                $prep->bindParam(":user", $this->username, PDO::PARAM_STR);
                $prep->execute();
                $this->check_pdo_error($prep);
                
                $sql = "UPDATE `{$this->sql->db}`.`allowed_users` SET `last_active` = :last, `failed` = 0";
                $prep = $this->sql->conn->prepare($sql);
                $prep->bindParam(":last", $time_stamp, PDO::PARAM_INT);
                $prep->execute();
                $this->check_pdo_error($prep);
                
                setcookie("UNS_LOGIN_HASH", $this->username.":".$LOGIN_HASH, ($time_stamp+$this->cookie_timeout), "/".$this->root."admin/", str_replace(array("/","http://"), "", $this->host), $this->SSL, 1);
#                echo "UNS_LOGIN_HASH"."\r\n", $this->username.":".$LOGIN_HASH."\r\n", ($time_stamp+$this->cookie_timeout)."\r\n", "/".$this->root."admin/"."\r\n", str_replace(array("/","http://"), "", $this->host)."\r\n", $this->SSL."\r\n", 1;
#                die();
                $this->password=NULL;
                return 1;
            }else
            {
                /* User failed to supply the correct password, lets increment the failed login apptempt flag, and return a failure flag */
                $sql = "SELECT `failed` FROM `{$this->sql->db}`.`allowed_users` WHERE `username` = :user";
                $prep = $this->sql->conn->prepare($sql);
                $prep->bindParam(":user", $this->username, PDO::PARAM_STR);
                $failed = $prep->fetch(1);
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
                
                $this->check_pdo_error($prep);
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
        
        $sql = "SELECT `hash`, `user` FROM `{$this->sql->db}`.`login_hashes` WHERE `user` = :user ORDER BY `time` DESC";
        $prep = $this->sql->conn->prepare($sql);
        $prep->bindParam(":user", $cookie_exp[0], PDO::PARAM_STR);
        $prep->execute();
        $login_hashes = $prep->fetch(2);
        #var_dump($login_hashes);
        #var_dump($cookie_exp);
        #exit();
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

            $sql = "SELECT * FROM `{$this->sql->db}`.`hash_links` where `hash` like :hash";
            
            $prep = $this->sql->conn->prepare($sql);
            $prep->bindParam(":hash",$cookie_hash, PDO::PARAM_STR);
            $prep->execute();
            $links = $prep->fetch(2);
            
            $sql = "DELETE FROM `{$this->sql->db}`.`login_hashes` where `id` = :id";
            $prep = $this->sql->conn->prepare($sql);
            $prep->bindParam(":id", $links['id'], PDO::PARAM_STR);
            $prep->execute();
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
    
    function AddURLs()
    {
        $urls = explode("\n", $this->parsed_edit_uri['NEWURLS']);
        $sql = "INSERT INTO `{$this->sql->db}`.`client_links` 
                (`id`, `url`, `client`, `disabled`, `refresh`) 
                VALUES (NULL, :url, :client, 0, :refresh)";
        $prep = $this->sql->conn->prepare($sql);

        foreach($urls as $url)
        {
            $prep->bindParam(":url", $url, PDO::PARAM_STR);
            $prep->bindParam(":client", $this->client, PDO::PARAM_STR);
            $prep->bindParam(":refresh", $this->parsed_edit_uri['refresh'], PDO::PARAM_INT);
            $prep->execute();
            $this->check_pdo_error($prep);
        }
        $this->AddMessage("URLs Added Successfully");
        return 1;
    }
    
    function RemoveURLs($ids_string = "")
    {
        #var_dump($ids_string);
        $ids = explode("|", $ids_string);
       # var_dump($ids);
        
        $sql = "DELETE FROM `{$this->sql->db}`.`client_links` WHERE `id` = :id";
        #var_dump($sql);
        $prep = $this->sql->conn->prepare($sql);
        foreach($ids as $id)
        {
            #var_dump($id);
            $id = $id+0;
            $prep->bindParam(":id", $id, PDO::PARAM_INT);
            $prep->execute();
            $this->check_pdo_error($prep);
        }
        #exit();
        $this->AddMessage("URLs Removed Successfully");
        return 1;
    }
    
    function SetEmergRefreshURLS()
    {
        foreach($_REQUEST['refresh_time'] as $key=>$time)
        {
            $id = $_REQUEST['refresh_ids'][$key];
            $sql = "UPDATE `{$this->sql->db}`.`emerg` SET `refresh` = :refresh WHERE `id` = :id";
            $prep = $this->sql->conn->prepare($sql);
            $prep->bindParam(":refresh", $time, PDO::PARAM_INT);
            $prep->bindParam(":id", $id, PDO::PARAM_INT);
            $prep->execute();
            $this->check_pdo_error($prep);
            $this->AddMessage("Updated Emergency Message [{$id}] Refresh time");
        }
        return 1;
    }
    
    function SetRefreshURLS()
    {
        foreach($_REQUEST['allids'] as $key=>$id)
        {
            $refresh = $_POST['refresh_time'][$key];
            $sql = "UPDATE `{$this->sql->db}`.`client_links` SET `refresh` = :refresh WHERE `client` = :client AND `id` = :id";
            $prep = $this->sql->conn->prepare($sql);
            $prep->bindParam(":client", $this->client, PDO::PARAM_STR);
            $prep->bindParam(":id", $id, PDO::PARAM_INT);
            $prep->bindParam(":refresh", $refresh, PDO::PARAM_INT);
            $prep->execute();
            $this->check_pdo_error($prep);
            $this->AddMessage("Updated Client [{$this->client}] URL [{$id}] Refresh time ($refresh)");
        }
        return 1;
    }
    
    function Redirect($uri, $override_time = NULL)
    {
        if(!$this->error_flag)
        {
#            header($this->admin_path.$uri, 1);
            if($override_time === NULL)
            {
                $timeout = $this->page_timeout*1000;
            }
            else
            {
                if($override_time != 0)
                {
                    $timeout = $override_time*1000;
                }else
                {
                    $timeout = 0;
                }
            }
            echo "<script language=\"JavaScript\"> setTimeout(\"window.parent.location = '{$this->admin_path}{$uri}'\", {$timeout}); </script>";
            exit();
            #$this->smarty->assign("redirect", "<script language=\"JavaScript\"> windows.location.href = '{$this->admin_path}{$uri}'; </script>");
        }
        else{return 0;}
    }
    
    function AddMessage($msg, $type = 0)
    {
        $this->all_messages[] = array('time'=>date($this->DateFormat), 'msg'=>$msg);
        $this->Log($msg, $type);
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
        exit;
    }
    
    function RenameClient()
    {
        $sql = "UPDATE `{$this->sql->db}`.`friendly` SET `friendly` = :friendly WHERE `client` = :client";
        $prep = $this->sql->conn->prepare($sql);
        $prep->bindParam(":friendly", $this->clientNewName, PDO::PARAM_STR);
        $prep->bindParam(":client", $this->client, PDO::PARAM_STR);
        $prep->execute();
        
        if($this->check_pdo_error($prep))
        {
            $this->AddMessage("Successfully Renamed Client ({$this->friendly}) to ({$this->clientNewName}) by {$this->username}");
        }
    }
    
    function UpdatePassword()
    {
        if($this->update_pwd)
        {
            $this->username = filter_input(INPUT_POST, 'username', FILTER_SANITIZE_SPECIAL_CHARS);
            $OldPassword = filter_input(INPUT_POST, 'oldpassword', FILTER_SANITIZE_SPECIAL_CHARS);
            $old_pwd_hash = md5($OldPassword.$this->global_login_seed);
            $sql = "SELECT * FROM `{$this->sql->db}`.`internal_users` WHERE `username` = :user";
            $prep = $this->sql->conn->prepare($sql);
            $prep->bindParam(":user", $this->username, PDO::PARAM_STR);
            $prep->execute();
            $array = $prep->fetch(2);
            
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
                $sql = "UPDATE `{$this->sql->db}`.`internal_users` SET `password` = :pwd WHERE `username` = :user";
                #echo "sha256:{$user_seed}:{$pass_hash}";
                $prep = $this->sql->conn->prepare($sql);
                $prep->bindParam(":user", $this->usernme, PDO::PARAM_STR);
                $prep->bindParam(":pwd", "sha256:{$user_seed}:{$pass_hash}", PDO::PARAM_STR);
                $prep->execute();
                $this->check_pdo_error($prep);
                return 1;
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
    
    function DisableURLs()
    {
        foreach($_REQUEST['allids'] as $key=>$id)
        {
            if(!@is_null($_REQUEST['disabled'][$key]))
            {
                $disable = !($_REQUEST['disabled'][$key]+0);
            }else
            {
                break;
            }
            
            $sql = "UPDATE `{$this->sql->db}`.`client_links` SET `disabled` = :disabled WHERE `client` = :client AND `id` = :id";
            $prep = $this->sql->conn->prepare($sql);
            $prep->bindParam(":client", $this->client, PDO::PARAM_STR);
            $prep->bindParam(":id", $id, PDO::PARAM_INT);
            $prep->bindParam(":disabled", $disable, PDO::PARAM_INT);
            $prep->execute();
            $this->check_pdo_error($prep);
            $this->AddMessage("Updated Client [{$this->client}] URL [{$id}] Disabled/Enabled ($disable)");
        }
        return 1;
    }
    
    function ChangeClientLED($LED=0)
    {
        $sql = "UPDATE `{$this->sql->db}`.`allowed_clients` SET `led` = :led WHERE `client_name` = :id";
        $prep = $this->sql->conn->prepare($sql);
        $prep->bindParam(":led", $LED, PDO::PARAM_INT);
        $prep->bindParam(":id", $this->client, PDO::PARAM_STR);
        $prep->execute();
        $this->check_pdo_error($prep);
        $this->AddMessage("Successfully Changed {$this->friendly}'s LED Group to {$LED} by {$this->username}");
    }
}

?>
