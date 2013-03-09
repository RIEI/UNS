<?php
/*
 * UNS Client index page, this page shows a list of all UNS clients
 * or the URL for the client to display. Output is either XML or HTML
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
require 'lib/UNSClient.inc.php';
$UNSClient = new UNSClient();
$UNSClient->parse_edit_url($_REQUEST);

$UNSClient->out = @strtolower(filter_input(INPUT_GET, 'out', FILTER_SANITIZE_SPECIAL_CHARS));
$UNSClient->client = filter_input(INPUT_GET, 'client', FILTER_SANITIZE_SPECIAL_CHARS);

if($UNSClient->led_blink)
{
    $UNSClient->blinky($UNSClient->GetClientLEDid());
}
switch($UNSClient->out)
{
    case "xml":
        if(is_string($UNSClient->client))
        {
            $UNSClient->GetClientURL();
            $UNSClient->DisplayClientURL();
        }else
        {
            $UNSClient->GetClientList();
            $UNSClient->DisplayAllClients();
        }
        
    break;

    case "html":
        if(is_string($UNSClient->client))
        {
            $UNSClient->GetClientURL();
            $UNSClient->DisplayClientURL();
        }else
        {
            switch($UNSClient->type)
            {
                case "img":
                    $UNSClient->GetImageMsg($UNSClient->msgid);
                    
                    break;
                
                case "c_msg":
                    $UNSClient->GetCustomMsg($UNSClient->cmsgid);
                    break;
                
                case "rss":
                    $UNSClient->GetRSSFeed($UNSClient->rssid);
                    break;
                
                default :
                    $UNSClient->GetClientList();
                    $UNSClient->DisplayAllClients();
                    break;
            }
        }
    break;

    default:
        $UNSClient->out = "html";
        $UNSClient->client = NULL;
        $UNSClient->GetClientList();
        $UNSClient->DisplayAllClients();
    break;
}
?>