<?php
/*
 * UNSClient is the front end of UNS for the clients.
 * It gets a list of all clients and their links, gets 
 * the URL for each client, and also sets and emergency
 * pages that need to be viewed.
 * 
 * 
 * @author Phillip Ferland <pferland@randomintervals.com>
 * @link http://uns.randomintervals.com UNS Site
 * @date 6/10/2012
 * @version 1.0
 
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

class UNSClient extends UNSCore
{
    function __construct()
    {
        parent::__construct();
        
    }
    
    function __destruct()
    {exit();}
    
    function CheckConnTable()
    {
        $sql = "SELECT * FROM `{$this->sql->db}`.`connections` WHERE `client` = :client";
        $prep = $this->sql->conn->prepare($sql);
        $prep->bindParam(":client", $this->client, PDO::PARAM_STR);
        $prep->execute();
        if($this->max_conn_history == @$prep->rowCount)
        {
            $sql = "SELECT id FROM `{$this->sql->db}`.`connections` WHERE `client` = :client ORDER BY `last_conn` ASC LIMIT 1";
            $prep = $this->sql->conn->prepare($sql);
            $prep->bindParam(":client", $this->client, PDO::PARAM_STR);
            $prep->execute();
            while($array = $prep->fetch(2))
            {
                $sql = "DELETE FROM `{$this->sql->db}`.`connections` WHERE `id` = :id";
                $prep1 = $this->sql->conn->prepare($sql);
                $prep1->bindParam(":id", $array['id'], PDO::PARAM_INT);
                $prep1->execute();
                $this->check_pdo_error($prep1);
            }
        }
        return 1;
    }
    
    function GetClientList()
    {
        $clients = array();
        $sql = "SELECT client_name, friendly FROM 
                `{$this->sql->db}`.`allowed_clients`, `{$this->sql->db}`.`friendly` 
                WHERE allowed_clients.client_name = friendly.client";
        $client_query = $this->sql->conn->query($sql);
        while($clients_sql = $client_query->fetch(2) )
        {
            $clients[] = array("path"=>$this->host_path."?client={$clients_sql["client_name"]}&out={$this->out}", "id"=>$clients_sql["client_name"], "name"=>$clients_sql["friendly"]);
        }
        $this->client_all_data = $clients;
        #var_dump($clients);
        #die();
    }
    
    function GetClientLEDid()
    {
        $query = "SELECT `led` FROM `{$this->sql->db}`.`allowed_clients` where `client_name` = :client LIMIT 1";
        $prep = $this->sql->conn->prepare($query);
        $prep->bindParam(":client", $this->client, PDO::PARAM_STR);
        $prep->execute();
        $array = $prep->fetch(2);
        if(!$array['led'])
        {
            return 0;
        }else
        {
            return $array['led'];
        }
    }
    
    function GetClientURL()
    {
        $ret1   = array();
        $ret    = "";
        $query  = "SELECT * FROM `{$this->sql->db}`.`allowed_clients` where `client_name` = :client LIMIT 1";
        
        $prep = $this->sql->conn->prepare($query);
        $prep->bindParam(":client", $this->client, PDO::PARAM_STR);
        $prep->execute();
        $array  = $prep->fetch(2);
        $cl_id  = $array['id'];
        
        if(!$array['client_name'])
        {
            return array("bad_client", 0);
        }
        
        $query = "SELECT * FROM `settings` LIMIT 1";
        $result = $this->sql->conn->query($query);
        $array = $result->fetch(2);
        $emerg_fl = $array['emerg'];
        if(!$emerg_fl)
        {
            $query = "SELECT * FROM `{$this->sql->db}`.`connections` where `client` LIKE :client ORDER by `last_conn` DESC LIMIT 1";
            $prep = $this->sql->conn->prepare($query);
            $prep->bindParam(":client", $this->client, PDO::PARAM_STR);
            $prep->execute();
            if($prep->rowCount > 0)
            {
                $prev = $prep->fetch(2);
                $prev_url = $prev['last_url'];
                
                $query = "SELECT * FROM `{$this->sql->db}`.`client_links` where `disabled` NOT LIKE '1' AND `url` NOT LIKE :prev_url";
                $prep = $this->sql->conn->prepare($query);
                $prep->bindParam(":prev_url", $prev_url, PDO::PARAM_STR);
                $prep->execute();
                while($array = $prep->fetch(2))
                {
                    $ret1[] = array($array['url'], $array['refresh']);
                }

                if(@$ret[0] == "")
                {
                    $query = "SELECT * FROM `{$this->sql->db}`.`client_links` where `disabled` != '1' AND `client` = :client";
                    $prep = $this->sql->conn->prepare($query);
                    $prep->bindParam(":client", $this->client, PDO::PARAM_STR);
                    $prep->execute();
                    while($array = $prep->fetch(2))
                    {
                        $ret1[] = array($array['url'], $array['refresh']);
                    }
                }
            }
            else
            {
                $query = "SELECT * FROM `{$this->sql->db}`.`client_links` where `disabled` NOT LIKE '1' AND `client` = :client";
                $prep = $this->sql->conn->prepare($query);
                $prep->bindParam(":client", $this->client, PDO::PARAM_STR);
                $prep->execute();
                while($array = $prep->fetch(2))
                {
                    $ret1[] = array($array['url'], $array['refresh']);
                }
            }
        }
        else
        {
            $query = "SELECT * FROM `{$this->sql->db}`.`emerg` WHERE `cl_id` = :client OR `cl_id` = '0' AND `enabled` = '1'";
            $prep = $this->sql->conn->prepare($query);
            $prep->bindParam(":client", $this->client, PDO::PARAM_STR);
            $prep->execute();
            while($array = $prep->fetch(2))
            {
                $ret1[] = array($array['url'], $array['refresh'], 1);
            }
        }
        if(@$ret1[0] == "")
        {
            $ret = array("no_urls", 0);
        }
        else
        {
            $pick = array_rand($ret1);
            $ret = array($ret1[$pick][0], $ret1[$pick][1]);
        }
        
        if(!$this->CheckConnTable())
        {
            throw new Exception("Error Checking Connection Table.");
        }
        
        $time = time();
        $last_url = $ret[0];
        
        $sql = "INSERT INTO `{$this->sql->db}`.`connections` 
            (`id`, `client`, `last_conn`, `last_url`) 
            VALUES ( NULL, :client, :time, :last_url)";
        
        $prep->bindParam(":client", $this->client, PDO::PARAM_STR);
        $prep->bindParam(":time", $time, PDO::PARAM_INT);
        $prep->bindParam(":last_url", $last_url, PDO::PARAM_STR);
        $prep->execute();
        $this->check_pdo_error($prep);

        if($emerg_fl)
        {
            $ret = array(html_entity_decode($ret[0], ENT_QUOTES), $ret[1], 1);
        }
        else
        {
            $ret = array(html_entity_decode($ret[0], ENT_QUOTES), $ret[1], 0);
        }
        
        $this->client_url_data = $ret;
    }
    
    function DisplayClientURL()
    {
        switch($this->out)
        {
            case "html":
                $this->smarty->assign("Client_URL_Refresh", $this->client_url_data[1].';/'.$this->root.'index.php?out=html&client='.$this->client);
                $this->smarty->assign("Client_URL", $this->client_url_data[0]);
                $this->smarty->assign("UNS_Emerg_Flag", $this->emergency);
                $this->smarty->assign("UNS_Error_Flag", $this->error_flag);
                $this->smarty->display("client_frame.tpl");
            break;
            
            case "xml":
                $this->smarty->assign("Client_URL_Refresh", $this->client_url_data[1]);
                $this->smarty->assign("Client_URL", $this->client_url_data[0]);
                $this->smarty->assign("Client_Emerg_Flag", $this->client_url_data[2]);
                $this->smarty->assign("UNS_Emerg_Flag", $this->emergency);
                $this->smarty->assign("UNS_Error_Flag", $this->error_flag);
                $this->smarty->display("client_url_xml.tpl");
            break;
        }
    }
    
    function DisplayAllClients()
    {
        switch($this->out)
        {
            case "html":
                $this->smarty->assign("UNS_Clients_All", $this->client_all_data);
                $this->smarty->display("main_index.tpl");
                break;
        
            case "xml";
                #var_dump($this->client_all_data);
                $this->smarty->assign("UNS_Clients_All", $this->client_all_data);
                $this->smarty->display("list_clients_xml.tpl");
                break;
        }
    }
}


?>
