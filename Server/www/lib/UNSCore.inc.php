<?php
/**
 * UNSCore is the functions that both the Admin and Client
 * interfaces need to use, which is basically the install 
 * checker, the LED blinker, and last connection to the 
 * program from a client. Also a place holder for the 
 * exception handler.
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

/** Should only be on for Development */
error_reporting(E_ALL & E_STRICT);


class UNSCore
{
    function __construct()
    {
        $folders    = explode("/", $_SERVER['SCRIPT_NAME']);
        $base       = "/{$folders[1]}/";
        
        define('WWW_DIR', $_SERVER['DOCUMENT_ROOT'].$base);
	define('SMARTY_DIR', $_SERVER['DOCUMENT_ROOT'].$base.'/smarty/');
        set_exception_handler('exception_handler');
        $this->Install_Update_Check($base);
        
        require WWW_DIR.'configs/vars.php';
        require WWW_DIR.'configs/conn.php';
        $this->version      = "2.0";
        $this->release_date = "8/26/2012";
        $this->DateFormat   = "Y-m-d H:i:s";
        $this->emergency    = 0;
        $this->error_flag   = 0;
        $this->title        = $config['name_title'];
        $this->path         = $config['path'].$config['root'];
        $this->root         = $config['root'];
        $this->host         = $config['host'];
        $this->SSL          = $config['SSL'];
        if($this->SSL)
        {
            $this->proto = "https://";
        }else
        {
            $this->proto = "http://";
        }
        $this->host_path        = $this->proto.$this->host.$config['root'];
        $this->page_timeout     = $config['page_timeout'];
        $this->page_refresh     = $config['page_refresh'];
        $this->max_conn_history = $config['max_conn_history'];
        $this->led_blink        = $config['led_blink'];
        $this->lpt_write_bin    = $config['lpt_write_bin'];
        $this->lpt_read_bin     = $config['lpt_read_bin'];
        $this->client           = "";
        /* 
	 * Lets Setup Smarty
	 */
	require_once(SMARTY_DIR.'Smarty.class.php');
	$this->smarty = new Smarty();
	$this->smarty->setTemplateDir( WWW_DIR.'smarty/templates/' );
	$this->smarty->setCompileDir( WWW_DIR.'smarty/templates_c/' );
	$this->smarty->setCacheDir( WWW_DIR.'smarty/cache/' );
	$this->smarty->setConfigDir( WWW_DIR.'/smarty/configs/');
        $this->smarty->error_reporting  = 0;
        $this->smarty->assign("UNS_Title", $this->title);
        $this->smarty->assign("UNS_URL", $this->host_path);
        $this->smarty->assign("version", $this->version);
        $this->smarty->assign("release_date", $this->release_date);
        /* 
	 * Lets Setup SQL
	 */
        require $this->path.'configs/conn.php';
        $this->sql                    = new stdClass();
        $this->sql->host              = $sql_args['server'];
        $this->sql->service           = $sql_args['service'];
        $this->sql->db                = $sql_args['db'];
        $this->sql->dsn               = $this->sql->service.':host='.$this->sql->host.';dbname='.$this->sql->db;
        if($this->sql->service == "mysql")
        {
            $options = array(
                PDO::MYSQL_ATTR_INIT_COMMAND => 'SET NAMES utf8',
                PDO::ATTR_PERSISTENT => TRUE,
            );
        }
        else
        {
            $options = array(
                PDO::ATTR_PERSISTENT => TRUE,
            );
        }
        $this->sql->conn    = new PDO($this->sql->dsn, $sql_args['username'], @$sql_args['password'], $options);
	/* Mike Rowe does some dirty jobs. */
    }
    
    function blinky($id)
    {
        if(!$this->led_blink){return 1;}
        if(!@$id){return 0;}
        
        
        $binary_array = array(
            0=>1,# Client 1
            1=>1,# Client 2
            2=>1,# Client 3
            3=>1,# Client 4
            4=>1,# Client 5
            5=>1,# Client 6
            6=>1,# Client 7
            7=>1 # Emerg flag
        );
        
        $sql = "SELECT `emerg` FROM `{$this->sql->db}`.`settings` LIMIT 1";
        $result = $this->sql->conn->query($sql);
        $emerg = $result->fetch(2);
        if($emerg['emerg']){$binary_array[7]=0;} #set Emerg led in var
       #echo "INIT: $dec\r\n".decbin($dec)."\r\n";
        
        $binary_string = implode("", $binary_array);
        $dec = bindec($binary_string);
        
        $this->blink($dec); #set initial

        if($id > 6)
        {
            
        }else
        {
            $on = $dec - $c_leds[$id];
        }
       #echo "ON: $on\r\n".decbin($on)."\r\n";

        $this->blink($on);

        if($id > 6)
        {
            
        }else
        {
            
        }
       #echo "OFF: $off\r\n".decbin($off)."\r\n";

        $this->blink($off);
    }

    function blink($value)
    {
        # 255 #emerg off
        # 127 #emerg on
        if(!$this->led_blink){return 1;}
        usleep(700);
        $lpt_write = system($this->lpt_write_bin." $value;");
        if($lpt_write == '')
        {
            #echo "SET LPT TO: $on\r\n";
            return 1;
        }
        else
        {
            #echo "FAILED TO SET LPT...\r\n$lpt_write";
            return 0;
        }
    }
    
    function GetFriendly($id="")
    {
        if($id == "")
        {
            $id = $this->client;
        }
        $sql = "SELECT `friendly` FROM `{$this->sql->db}`.`friendly` WHERE `client` = :client";
        $prep = $this->sql->conn->prepare($sql);
        $prep->bindParam(":client", $id, PDO::PARAM_STR);
        $prep->execute();
        $friendly_ret = $prep->fetch(2);
        $this->friendly = $friendly_ret['friendly'];
        return $this->friendly;
    }
    
    function GetEmergFlag()
    {
        $sql = "SELECT `emerg` FROM `{$this->sql->db}`.`settings` LIMIT 1";
        $query = $this->sql->conn->query($sql);
        $query->execute();
        $array = $query->fetch(2);
        $this->emerg_flag = $array['emerg'];
    }
    
    function parse_edit_url($request)
    {
        $definition = array(
            #Admin page parsing
            'allids'=>FILTER_SANITIZE_STRING,
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
            'saved'=>FILTER_SANITIZE_NUMBER_INT,
            'save_q'=>FILTER_SANITIZE_STRING,
            'id'=>FILTER_SANITIZE_NUMBER_INT,
            'cl_led_id'=>FILTER_SANITIZE_NUMBER_INT,
            'client_name'=>FILTER_SANITIZE_STRING,
            'client_id'=>FILTER_SANITIZE_STRING,
            'friendly'=>FILTER_SANITIZE_STRING,
            'cl_timezone'=>FILTER_SANITIZE_SPECIAL_CHARS,
            'toggle_global_emerg'=>FILTER_SANITIZE_STRING,
            'edit_clients'=>FILTER_SANITIZE_STRING,
            'edit_emerg'=>FILTER_SANITIZE_STRING,
            'c_messages'=>FILTER_SANITIZE_STRING,
            'img_messages'=>FILTER_SANITIZE_STRING,
            'rss_feeds'=>FILTER_SANITIZE_STRING,
            'edit_users'=>FILTER_SANITIZE_STRING,
            'edit_options'=>FILTER_SANITIZE_STRING,
            'username'=>FILTER_SANITIZE_STRING,
            'userid'=>FILTER_SANITIZE_NUMBER_INT,
            'hash'=>FILTER_SANITIZE_STRING,
            'password_reset_flag'=>FILTER_SANITIZE_NUMBER_INT,
            
            #Client Fetching Parsing
            'client'=>FILTER_SANITIZE_STRING,
            'type'=>FILTER_SANITIZE_STRING,
            'out'=>FILTER_SANITIZE_STRING,
            
        );
        $this->parsed_edit_uri = array_filter(filter_var_array($request, $definition));
    }
    
    function read_lpt()
    {
        $read = system($this->lpt_read_bin);
        return $read;
    }
    
    function Install_Update_Check($base = "")
    {
        
        if(!file_exists($_SERVER['DOCUMENT_ROOT'].$base."configs/vars.php"))
        {throw new Exception("Could not find configs/vars.php");}
        
        if(!file_exists($_SERVER['DOCUMENT_ROOT'].$base."configs/conn.php"))
        {throw new Exception("Could not find configs/conn.php");}
        
        require WWW_DIR.'configs/conn.php'; #the configs have been found, lets load the SQL one, and see if the database is setup correctly.
        
        $dsn = $GLOBALS['sql_args']['service'].':host='.$GLOBALS['sql_args']['server'].';dbname='.$GLOBALS['sql_args']['db'];
        if($GLOBALS['sql_args']['service'] == "mysql")
        {
            $options = array(
                PDO::MYSQL_ATTR_INIT_COMMAND => 'SET NAMES utf8',
                PDO::ATTR_PERSISTENT => TRUE,
            );
        }
        else
        {
            $options = array(
                PDO::ATTR_PERSISTENT => TRUE,
            );
        }
        $conn    = new PDO($dsn, $GLOBALS['sql_args']['username'], @$GLOBALS['sql_args']['password'], $options);
        
        $sql = "SELECT `uns_ver` FROM `settings` ORDER BY `id` ASC LIMIT 1";
        $result = $conn->query($sql);
        if($result)
        {
            $uns_ver = $result->fetch(2);
        }
        else
        {
            {throw new Exception("SQL Error: ".var_export($conn->errorInfo()));}
        }

        if($uns_ver['uns_ver'] == "")
        {
            {throw new Exception("UNS Has some tables, but seems to have no data. You may need to install or update. <a href='{$base}install.php'>Install Page</a>");}
        }
        else
        {
            if($uns_ver['uns_ver'] != "2.0")
            {
                {throw new Exception("UNS Is out of date, you need to upgrade it. <a href='{$base}upgrade.php'>Upgrade Page</a>");}
            }else
            {
                return $uns_ver['uns_ver'];
            }
        }
    }
    
    function last_conn()
    {
        $sql = "SELECT last_conn, last_url FROM `{$this->sql->db}`.`connections` WHERE `client` = :client ORDER BY `last_conn` DESC LIMIT 1";
        $prep = $this->sql->conn->prepare($sql);
        $prep->bindParam(":client", $this->client, PDO::PARAM_STR);
        $info = $prep->fetch(2);
        if($info['last_conn'])
        {
            return $info;
        }else
        {
            return 0;
        }
    }
    
    function Log($message, $type = 0)
    {
        $time = time();
        if($type == 1)
        {
            $message = $message.var_export($_REQUEST,1)."\r\n";
        }
        $sql = "INSERT INTO `{$this->sql->db}`.`log` (`id`, `detail`, `level`, `time`, `username`, `ip_addr`) VALUES (NULL, :detail, :level, :time, :username, :ip_addr)";
        $prep = $this->sql->conn->prepare($sql);
        $prep->bindParam(":detail", $message, PDO::PARAM_STR);
        $prep->bindParam(":level", $type, PDO::PARAM_STR);
        $prep->bindParam(":time", $time, PDO::PARAM_INT);
        $prep->bindParam(":username", $this->username, PDO::PARAM_STR);
        $prep->bindParam(":ip_addr", $_SERVER['REMOTE_ADDR'], PDO::PARAM_STR);
        $prep->execute();
        $this->check_pdo_error($prep);
        return 1;
    }
            
    function check_pdo_error($obj)
    {
        $error = $obj->errorCode();
        if($error == "00000" ){return 1;}
        else
        {
            if($error == NULL)
            {
                $info = "You might have forgot to run execute on your PDO statement.";
            }else
            {
                $info = $obj->errorInfo();
            }
            $this->log("CRITICAL ERROR: ".var_export($info,1)."\r\n", 2);
            throw new Exception(implode("<br>",$info));
            return 0;
        }
    }
}



/* 
 * Exception handler
 * 
 * Have not found a way to integrate it into the main class.
 * So basically there are two smarty objects created if there 
 * is an error. That can get ugly if the system has a whole 
 * bunch of clients connecting and throwing errors
*/
function exception_handler($err)
{
    require_once(SMARTY_DIR.'Smarty.class.php');
    $smarty = new Smarty();
    $smarty->setTemplateDir( WWW_DIR.'smarty/templates/' );
    $smarty->setCompileDir( WWW_DIR.'smarty/templates_c/' );
    $smarty->setCacheDir( WWW_DIR.'smarty/cache/' );
    $smarty->setConfigDir( WWW_DIR.'/smarty/configs/');
    $smarty->assign("UNS_Title", "UNS");
    
    $trace = array( 
                'Error' =>strval($err->getCode()),
                'Message'=>$err->getMessage(),
                'Code'=>strval($err->getCode()),
                'File'=>$err->getFile(),
                'Line'=>strval($err->getLine())
                );
    $smarty->assign('UNS_Trace', $trace);
    $smarty->display('error.tpl');
}
?>