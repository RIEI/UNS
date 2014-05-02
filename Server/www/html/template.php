<?php
#    template.php, Lays out the data for a custom message or an RSS feed.
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

$ID = filter_input(INPUT_GET, 'id', FILTER_SANITIZE_SPECIAL_CHARS);
$type = @filter_input(INPUT_GET, 'type', FILTER_SANITIZE_SPECIAL_CHARS);
include '../configs/vars.php';
include '../configs/conn.php';
date_default_timezone_set($TZ);
$conn = new mysqli($server, $username, $password, $db);

switch($type)
{
    case "rss":
        $template_head = $template_head_rss;
        $template_foot = $template_foot_rss;
        $sql = "SELECT * FROM `rss_feeds` WHERE `id` = '$ID'";
        $result = $conn->query($sql, 1);
        if($result)
        {
            $array = $result->fetch_array(1);
            if(@$array['id'] != '')
            {
                $max = $array['maxlines']+0;
                $rss_ret = gen_rss($array['url'], $max);
                if($rss_ret == -1)
                {
                    $array['name'] = "RSS Feed Not Found";
                    $array['body'] = "You did something wrong...";
                }else
                {
                    $array['name'] = $rss_ret['name'];
                    $array['body'] = $rss_ret['body'];
                }
            }else
            {
                $array['name'] = "RSS Feed Not Found";
                $array['body'] = "You did something wrong....";
            }
        }else
        {
            $array['name'] = "RSS Feed Not Found";
            $array['body'] = "You did something wrong.....<br />".$conn->error;
        }
        break;
    case "c_message":
        $template_head = $template_head_cmsg;
        $template_foot = $template_foot_cmsg;
        $sql = "SELECT * FROM `c_messages` WHERE `id` = '$ID'";
        $result = $conn->query($sql, 1);
        if($result)
        {
            $array = $result->fetch_array(1);
            if(!$array['wrapper'])
            {
                echo html_entity_decode($array['body'], ENT_QUOTES);
                die();
            }
        }else
        {
            $array['name'] = "Message Not Found";
            $array['body'] = "You did something wrong.....<br />".$conn->error;
        }
        break;
    default:
        $array['name'] = "Switch Error!!!";
        $array['body'] = "You did something wrong.....";
        break;
}

########################
echo $template_head.
     html_entity_decode($array['body']).
     $template_foot;
########################



#################
### Functions ###
#################
function gen_rss($file, $max)
{
    if(!$file){return -1;}
    $file = file($file);
    $RSS = xml2ary(@implode("\r\n", $file));
    $x = 0;
    if(!@$RSS['rss'])
    {
        ###ATOM RSS
        $title = $RSS['feed']['_c']['title']['_v'];
        $body = "
            <table style='width: 100%' align='left'>
                <tr>
                    <td class='RSS_feed_title' width='1%'>
                        <img alt='RSS' src='../html/rss_logo.png' width='50' height='50'>
                    </td>
                    <td class='RSS_feed_title'>
                        ".$RSS['feed']['_c']['title']['_v']."
                    </td>
                </tr>
            </table>
            <br />
            <table width='100%' border='1' class='RSS_table_back'>";
        foreach($RSS['feed']['_c']['entry'] as $key=>$feed)
        {
            if($max == $key){break;}
            $i_title = $feed['_c']['title']['_v'];
            $i_desc = trim(str_replace( "<br/><br/><br/>", "", str_replace( "&deg;", utf8_encode(html_entity_decode("&#176;")), str_replace("&nbsp;", utf8_encode(" "), htmlspecialchars_decode( $feed['_c']['summary']['_v'] )))));
            if($x == 0)
            {
                $body .= "<tr>
                    <td width='50%' align='center' valign='top'>
                    <div class='RSS_title'>$i_title</div>
                    <div class='RSS_desc'>$i_desc</div>
                    </td>";
                $x++;
            }else
            {
                $body .= "
                    <td width='50%' align='center' valign='top'>
                    <div class='RSS_title'>$i_title</div>
                    <div class='RSS_desc'>$i_desc</div>
                    </td>
                    </tr>";
                $x=0;
            }
        }
        $body .= "</div>";
    }else
    {
        ###RSS V1 & V2
        $title = $RSS['rss']['_c']['channel']['_c']['title']['_v'];
        $body = "<table style='width: 100%' align='left'>
            <tr>
                <td class='RSS_feed_title' width='1%'>
                    <img alt='RSS' src='../html/rss_logo.png' width='50' height='50'>
                </td>
                <td class='RSS_feed_title'>
                    ".$RSS['rss']['_c']['channel']['_c']['title']['_v']."
                </td>
            </tr>
        </table>
        <br />
        <table width='100%' border='1' class='RSS_table_back'>";
        foreach($RSS['rss']['_c']['channel']['_c']['item'] as $key=>$feed)
        {
            if($max == $key){break;}
            if(@$feed['_c']['content:encoded']['_v'])
            {
                $i_title = $feed['_c']['title']['_v'];
                $i_desc = trim(str_replace("<br/><br/><br/>", "", str_replace("&deg;",utf8_encode(html_entity_decode("&#176;")), str_replace("&nbsp;", utf8_encode(" "), htmlspecialchars_decode($feed['_c']['content:encoded']['_v'], ENT_NOQUOTES)))));
            }else
            {
                $i_title = $feed['_c']['title']['_v'];
                $i_desc = trim(str_replace("<br/><br/><br/>", "", str_replace("&deg;",utf8_encode(html_entity_decode("&#176;")),str_replace("&nbsp;", utf8_encode(" "), htmlspecialchars_decode($feed['_c']['description']['_v'], ENT_NOQUOTES)))));
            }
            if($x == 0)
            {
                $body .= "<tr>
                    <td width='50%' align='center' valign='top'>
                    <div class='RSS_title'>$i_title</div>
                    <div class='RSS_desc'>$i_desc</div>
                    </td>";
                $x++;
            }else
            {
                $body .= "
                    <td width='50%' align='center' valign='top'>
                    <div class='RSS_title'>$i_title</div>
                    <div class='RSS_desc'>$i_desc</div>
                    </td>
                    </tr>";
                $x=0;
            }
        }
    }
    $body .= "</table>";
    return array('name'=>$title, 'body'=>$body);
}

function xml2ary(&$string)
{
    $parser = xml_parser_create();
    xml_parser_set_option($parser, XML_OPTION_CASE_FOLDING, 0);
    xml_parse_into_struct($parser, $string, $vals, $index);
    xml_parser_free($parser);

    $mnary=array();
    $ary=&$mnary;
    foreach ($vals as $r)
    {
        $t=$r['tag'];
        if ($r['type']=='open') {
            if (isset($ary[$t])) {
                if (isset($ary[$t][0])) $ary[$t][]=array(); else $ary[$t]=array($ary[$t], array());
                $cv=&$ary[$t][count($ary[$t])-1];
            } else $cv=&$ary[$t];
            if (isset($r['attributes'])) {foreach ($r['attributes'] as $k=>$v) $cv['_a'][$k]=$v;}
            $cv['_c']=array();
            $cv['_c']['_p']=&$ary;
            $ary=&$cv['_c'];

        } elseif ($r['type']=='complete') {
            if (isset($ary[$t])) { // same as open
                if (isset($ary[$t][0])) $ary[$t][]=array(); else $ary[$t]=array($ary[$t], array());
                $cv=&$ary[$t][count($ary[$t])-1];
            } else $cv=&$ary[$t];
            if (isset($r['attributes'])) {foreach ($r['attributes'] as $k=>$v) $cv['_a'][$k]=$v;}
            $cv['_v']=(isset($r['value']) ? $r['value'] : '');

        } elseif ($r['type']=='close') {
            $ary=&$ary['_p'];
        }
    }
    _del_p($mnary);
    return $mnary;
}

    // _Internal: Remove recursion in result array
function _del_p(&$ary)
{
    foreach ($ary as $k=>$v)
    {
        if ($k==='_p') unset($ary[$k]);
        elseif (is_array($ary[$k])) _del_p($ary[$k]);
    }
}

    // Array to XML
function ary2xml($cary, $d=0, $forcetag='')
{
    $res=array();
    foreach ($cary as $tag=>$r)
    {
        if (isset($r[0])) {
            $res[]=ary2xml($r, $d, $tag);
        } else {
            if ($forcetag) $tag=$forcetag;
            $sp=str_repeat("\t", $d);
            $res[]="$sp<$tag";
            if (isset($r['_a'])) {foreach ($r['_a'] as $at=>$av) $res[]=" $at=\"$av\"";}
            $res[]=">".((isset($r['_c'])) ? "\n" : '');
            if (isset($r['_c'])) $res[]=ary2xml($r['_c'], $d+1);
            elseif (isset($r['_v'])) $res[]=$r['_v'];
            $res[]=(isset($r['_c']) ? $sp : '')."</$tag>\n";
        }
    }
    return implode('', $res);
    }

    // Insert element into array
function ins2ary(&$ary, $element, $pos)
{
    $ar1=array_slice($ary, 0, $pos); $ar1[]=$element;
    $ary=array_merge($ar1, array_slice($ary, $pos));
}
?>
