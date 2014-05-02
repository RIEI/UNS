<?php
#    index.php, Client page, grabs the URL for the client ID supplied
#    Copyright (C) 2010  Phillip Ferland
#
#    This program is free software: you can redistribute it and/or modify
#    it under the terms of the GNU General Public License as published by
#    the Free Software Foundation, either version 3 of the License, or
#    (at your option) any later version.
#
#    This program is distributed in the hope that it will be useful,
#    but WITHOUT ANY WARRANTY; without even the implied warranty of
#    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
#    GNU General Public License for more details.
#
#    You should have received a copy of the GNU General Public License
#    along with this program.  If not, see <http://www.gnu.org/licenses/>.

include "configs/vars.php";
include "shared.php";

gen_base_urls(".");
$proto = $GLOBALS['proto'];
$admin_url = $GLOBALS['admin_url'];
$reg_url = $GLOBALS['reg_url'];

if(!check_install('.'))
{
    echo "You need to Install or Upgrade first.<br /><a href='../install.php'>Install Page</a>";
    die();
}
date_default_timezone_set($TZ);
$scroll_code = '';
$out = @strtolower(filter_input(INPUT_GET, 'out', FILTER_SANITIZE_SPECIAL_CHARS));
$client = filter_input(INPUT_GET, 'id', FILTER_SANITIZE_SPECIAL_CHARS);
if($led_blink){blinky(get_client_led_id($client));}
if($client != "")
{
    $chosen_one = get_client_url($client);
    $emerg = $chosen_one[2];
}else
{
    $emerg = 0;
}

if($out != "")
{
    switch($out)
    {
        case "xml":
            header ("Content-Type:text/xml");
            if(is_string($client))
            {
                switch($chosen_one[0])
                {
                    case "bad_client":
                        $data = "<error>1</error>\r\n<url>".$reg_url."html/bad_client.html</url>
<refresh>60</refresh>\r\n";
                        if($emerg)
                        {
                            $data .= "<emerg>1</emerg>\r\n";
                        }else
                        {
                            $data .= "<emerg>0</emerg>\r\n";
                        }
                        break;
                    case "no_urls":
                        $data = "<error>1</error>\r\n<url>".$reg_url."html/no_urls.html</url>
<refresh>60</refresh>\r\n";
                        if($emerg)
                        {
                            $data .= "<emerg>1</emerg>\r\n";
                        }else
                        {
                            $data .= "<emerg>0</emerg>\r\n";
                        }
                        break;
                    default:
                        $data = "<error>0</error>\r\n<url><![CDATA[".$chosen_one[0]."]]></url>
<refresh>".$chosen_one[1]."</refresh>\r\n";
                        if($emerg)
                        {
                            $data .= "<emerg>1</emerg>\r\n";
                        }else
                        {
                            $data .= "<emerg>0</emerg>\r\n";
                        }
                        break;
                }
                echo '<?xml version="1.0" encoding="utf-8"?>
<uns>
'.$data."</uns>";
            }else
            {
                echo '<?xml version="1.0" encoding="utf-8"?>
';
                include "configs/conn.php";
                $conn = new mysqli($server, $username, $password, $db);
                $result = $conn->query("SELECT friendly.friendly,friendly.client FROM allowed_clients,friendly WHERE friendly.client = allowed_clients.client_name ORDER by friendly.friendly ASC", 1);
                $NN=0;
                $data = "<clients>\r\n";
                while($clients = $result->fetch_array(1))
                {
                    $data .= '   <client ref="'.$reg_url.'index.php?id='.$clients['client'].'" id="'.$clients['client'].'">'.$clients['friendly'].'</client>
';
                    $NN++;
                }
                $data .= "</clients>";
                if(!$NN)
                {
                    echo '<error>There are no clients. How do you expect to let people know whats going on with no way to display it. Go add some.</error>';
                    die();
                }else
                {
                    echo $data;
                }
            }
            break;
        default:
            echo '<?xml version="1.0" encoding="utf-8"?>
<error>Only XML is supported as an alternate output.</error>';
            break;
    }
}else
{
    if(is_string($client))
    {
        switch($chosen_one[0])
        {
            case "bad_client":
                $head = $scroll_code.'<meta http-equiv="refresh" content="60;/'.$root.'index.php?id='.$client.'">';
                $body = '<iframe src="'.$reg_url.'html/bad_client.html" border="0" width="100%" scrolling="no" height="100%">
        </iframe>';
                break;
            case "no_urls":
                $head = $scroll_code.'<meta http-equiv="refresh" content="60;/'.$root.'index.php?id='.$client.'">';
                $body = '<iframe src="'.$reg_url.'html/no_urls.html" border="0" scrolling="no" width="100%" height="100%">
        </iframe>';
                break;
            default:
                $head = $scroll_code.'<meta http-equiv="refresh" content="'.$chosen_one[1].';/'.$root.'index.php?id='.$client.'">';
                $body = '<iframe src="'.$chosen_one[0].'" width="100%" border="0" scrolling="no" height="100%">
        </iframe>';
                break;
        }

    }else
    {
        include "configs/conn.php";
        $head = $scroll_code;
        $body = '<div align="center"><table width="75%"><tr><th>Clients</th></tr>';
        $conn = new mysqli($server, $username, $password, $db);
        $result = $conn->query("SELECT friendly.friendly,friendly.client FROM allowed_clients,friendly WHERE friendly.client = allowed_clients.client_name ORDER by friendly.friendly ASC", 1);
        $NN=0;
        while($clients = $result->fetch_array(1))
        {
            $body .= '
            <tr>
                <td>
                    <a href="'.$reg_url.'index.php?id='.$clients['client'].'" target="_blank">'.$clients['friendly'].'</a>
                </td>
            </tr>';
            $NN++;
        }
        if(!$NN)
        {
            $body .= '
            <tr>
                <td>
                    There are no clients.<br /><font size="2">How do you expect to let people know whats going on with no way to display it. <a href="'.$admin_url.'admin/index.php">Go add some.</a></font>
                </td>
            </tr>';
        }
        $body .= "</table></div>";
    }
    ?>
<html>
    <head>
        <title>UNS</title>
        <?php echo $head; ?>
    </head>
    <body style="margin: 0px 0px 0px 0px;" >
        <?php echo $body; ?>
    </body>
</html>
<?php
}

#####
## Functions
#####
function get_client_led_id($client)
{
    include "configs/conn.php";
    $conn = new mysqli($server, $username, $password, $db);
    if(mysqli_connect_errno())
    {
        printf("Connect failed: %s\n", mysqli_connect_error());
        exit();
    }
    $query = "SELECT led FROM `allowed_clients` where `client_name` LIKE '$client'";
    $result = $conn->query($query, 1);
    $array = $result->fetch_array(1);
    if(!$array['led'])
    {
        return 0;
    }else
    {
        return $array['led'];
    }

}
function get_client_url($client)
{
    $ret = array();
    include "configs/conn.php";
    $conn = new mysqli($server, $username, $password, $db);
    if (mysqli_connect_errno())
    {
        return array(mysqli_connect_error(), 0);
    }
    $query = "SELECT * FROM `allowed_clients` where `client_name` LIKE '$client'";
    $result = $conn->query($query, 1);
    $array = $result->fetch_array(1);
    $cl_id = $array['id'];

    if(!$array['client_name'])
    {
        return array("bad_client", 0);
    }
    $result->free();
    $query = "SELECT * FROM `settings`";
    $result = $conn->query($query, 1);
    $array = $result->fetch_array(1);
    $emerg_fl = $array['emerg'];
    if(!$emerg_fl)
    {
        $result->free();
        $query = "SELECT * FROM `connections` where `client` LIKE '$client' ORDER by `last_conn` DESC LIMIT 1";
        $result = $conn->query($query, 1);
        if($result)
        {
            $prev = $result->fetch_array(1);
            $prev_url = $prev['last_url'];
            $query = "SELECT * FROM `".$client."_links` where `disabled` NOT LIKE '1' AND `url` NOT LIKE '$prev_url'";
            $result->free();
            $result = $conn->query($query, 1);
            while($array = $result->fetch_array(1))
            {
                $ret[] = array($array['url'], $array['refresh']);
            }

            if(@$ret[0] == "")
            {
                $query = "SELECT * FROM `".$client."_links` where `disabled` NOT LIKE '1'";
                $result->free();
                $result = $conn->query($query, 1);
                while($array = $result->fetch_array(1))
                {
                    $ret[] = array($array['url'], $array['refresh']);
                }
            }
        }else
        {
            $query = "SELECT * FROM `".$client."_links` where `disabled` NOT LIKE '1'";
            $result->free();
            $result = $conn->query($query, 1);
            while($array = $result->fetch_array(1))
            {
                $ret[] = array($array['url'], $array['refresh']);
            }
        }
        
    }else
    {
        $result->free();
        $query = "SELECT * FROM `emerg` WHERE `cl_id` = '$cl_id' OR `cl_id` = '0' AND `enabled` = '1'";
        $result = $conn->query($query, 1);
        
        while($array = $result->fetch_array(1))
        {
            $ret[] = array($array['url'], $array['refresh'], 1);
        }
    }
    if(@$ret[0] == "")
    {
        $ret1 = array("no_urls", 0);
    }else
    {
        $pick = array_rand($ret);
        $ret1 = array($ret[$pick][0], $ret[$pick][1]);
    }
    $time = time();
    $last_url = $ret1[0];
    check_conn_tbl($client);
    $sql = "INSERT INTO `connections` (`id`, `client`, `last_conn`, `last_url`) VALUES ('', '$client', '$time', '$last_url')";
    if(!$conn->query($sql))
    {
        return array($conn->error, 0);
    }

    if($emerg_fl)
    {
        $r = array(html_entity_decode($ret1[0], ENT_QUOTES), $ret1[1], 1);
    }else
    {
        $r = array(html_entity_decode($ret1[0], ENT_QUOTES), $ret1[1], 0);
    }
    return $r;
}



function check_conn_tbl($client)
{
    include "configs/vars.php";
    include "configs/conn.php";
    if(!$conn = mysqli_connect($server, $username, $password, $db))
    {return -1;}
    $sql = "SELECT * FROM `connections` WHERE `client` = '$client'";
    if(!($result = mysqli_query($conn, $sql)))
    {return -1;}
    $rows = mysqli_num_rows($result);
    if($max_conn_hist == $rows)
    {
        $sql = "SELECT id FROM `connections` WHERE `client` = '$client' ORDER BY `last_conn` ASC LIMIT 1";
        $result = mysqli_query($conn, $sql);
        while($array = mysqli_fetch_array($result))
        {
            $sql = "DELETE FROM `connections` WHERE `id` = '".$array['id']."'";
            $result1 = mysqli_query($conn, $sql);
            if(!$result1){return -1;}
        }
        return 1;
    }else
    {
        return 0;
    }
}
?>