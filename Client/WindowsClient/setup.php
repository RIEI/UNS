<?php
#    setup.php, sets up the UNS Firefox client enviroment.
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

$ver = "1.1";
$last_edit = "27-Dec-2010";

$args = parseArgs($argv);
$num_args = count($args);
if(@$args['h'] || @$args['help'] || $args === 0)
{
    die("
Usage: php setup.php [OPTIONS]...
Setup the UNS Client Firefox Portable Enviroment

  --host        The Hostname, or IP address of your UNS server.
  --unspath     The folder that UNS is in on the server. If not set will default to /
                (ie http://unsserver/path/to/uns/ would be 'path/to/uns'.)
  --client      Mandatory, This is the clients ID, you get this from the UNS admin panel
                after you have added a client to UNS
  --ffcfile     This is the location of the Firefox Portable prefs.js file. Can be either
                a relitive file or absolute. Default is 'FirefoxPortable/Data/profile/prefs.js'
                relitive to this script.
  -v            Verbose, otherwise it will just tell you about errors.
  -h, --help    Shows this help message.

Example: php setup.php --host=192.168.3.140 --unspath=path/to/uns --client=1af4b824f54e278c34aa3246 --ffcfile=/etc/firefox/prefs.js

Report bugs to pferland@randomintervals.com

Copyright (C) 2010  Phillip Ferland
Version: $ver  Last Edit: $last_edit
This program comes with ABSOLUTELY NO WARRANTY.
This is free software, and you are welcome to redistribute it
under certain conditions.

");
}

echo "
    setup.php  Copyright (C) 2010  Phillip Ferland
    Version: $ver  Last Edit: $last_edit
    This program comes with ABSOLUTELY NO WARRANTY.
    This is free software, and you are welcome to redistribute it
    under certain conditions.

";

$hp = 0;$v = @$args['v'];
$host = @$args['host'];
$unspath = @$args['unspath'];
$client = @$args['client'];
$ffcfile = @$args['ffcfile'];

if($ffcfile == ''){$ffcfile = "FirefoxPortable/Data/profile/prefs.js";}
if($v){echo "UNS Client setup for Firefox Portable. For Windows and Linux.\r\n";}
if(!$host){die("UNS Host Value is not set.\r\nIE: --host=192.168.1.1\r\n");}
if(!$client){die("Client Value is not set.\r\nIE: --client=1af4b824f54e278c34aa3246\r\n");}

if(!$unspath){if($v){echo "--unspath not set, defaulting to root dir\r\n";}}

if(!($file = @file($ffcfile)))
{die("Could not find the FireFox prefs.js file\r\nMaybe you should define it with --ffcfile\r\n");}

foreach($file  as $key=>$line)
{
    if(strstr($line,'user_pref("browser.startup.homepage",'))
    {
        $file[$key] = 'user_pref("browser.startup.homepage","http://'.$host.'/'.$unspath.'/index.php?id='.$client."\");\r\n";
        if($v){echo "Found Homepage setting\r\n";$hp=1;break;}
    }
}

if(!$hp)
{
    $file[0] = "# Mozilla User Preferences\r\n\r\n";
    $file[1] = 'user_pref("browser.startup.homepage","http://'.$host.'/'.$unspath.'/index.php?id='.$client."\");\r\n";
}

if(file_put_contents($ffcfile, $file))
{
    if($v){echo "Updated Firefox prefs.js File.\r\n";}
    return 1;
}


##########################
##########################
function parseArgs($argv){
    array_shift($argv);
    $out = array();
    foreach ($argv as $arg){
        if (substr($arg,0,2) == '--'){
            $eqPos = strpos($arg,'=');
            if ($eqPos === false){
                $key = substr($arg,2);
                $out[$key] = isset($out[$key]) ? $out[$key] : true;
            } else {
                $key = substr($arg,2,$eqPos-2);
                $out[$key] = substr($arg,$eqPos+1);
            }
        } else if (substr($arg,0,1) == '-'){
            if (substr($arg,2,1) == '='){
                $key = substr($arg,1,1);
                $out[$key] = substr($arg,3);
            } else {
                $chars = str_split(substr($arg,1));
                foreach ($chars as $char){
                    $key = $char;
                    $out[$key] = isset($out[$key]) ? $out[$key] : true;
                }
            }
        } else {
            $out[] = $arg;
        }
    }
    return $out;
}

?>