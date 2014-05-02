<?php
#    index.php, Main source code for the UNS administration
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

include "../shared.php";
if(!check_install('..'))
{
    echo "You need to Install or Upgrade first.<br /><a href='../install.php'>Install Page</a>";
    die();
}
include "../configs/vars.php";
if($led_blink){blinky('admin');}

gen_base_urls("..");
$proto = $GLOBALS['proto'];
$admin_url = $GLOBALS['admin_url'];
$reg_url = $GLOBALS['reg_url'];

$func_1 = filter_input(INPUT_GET, 'func', FILTER_SANITIZE_ENCODED);
if(str_replace("/","",$host) != $_SERVER['SERVER_NAME'])
{
    ?>
       <script>location.href = '<?php echo $admin_url;?>admin/index.php';</script>
    <?php
}


date_default_timezone_set($TZ);

if($func_1 === "logout")
{
    if(@$_COOKIE['login_yes'])
    {
        include "../configs/conn.php";
        $conn = new mysqli($server, $username, $password, $db);
        $cookie_exp = explode(":", $_COOKIE['login_yes']);
        $cookie_hash = $cookie_exp[0];

        $sql = "SELECT * FROM `hash_links` where `hash` like '$cookie_hash'";
        $result = $conn->query($sql, 1);
        $links = $result->fetch_array(1);
        $result->free();
        $sql = "DELETE FROM `hash_links` where `id` = '".$links['id']."'";
        if($conn->query($sql))
        {
            if(setcookie("login_yes", "", time()-3600 , "/".$root."admin", '', $SSL, 1))
            {echo "Logged out";
            ?>
       <script>location.href = '<?php echo $admin_url;?>admin/index.php';</script>
                    <?php
            die();}
        }else
        {
            setcookie("login_yes", "", $time , "/".$root."admin", '', $SSL, 1);
            echo "Failed to remove session from table.";
            die();
        }
    }else
    {
        echo "Logged out";
            ?>
      <script>location.href = '<?php echo $admin_url;?>admin/index.php';</script>
                    <?php
            die();
    }
}

$GET_login = filter_input(INPUT_GET, 'login', FILTER_SANITIZE_ENCODED);
if($GET_login)
{
    include "../configs/conn.php";
    #var_dump($_POST); echo"<br />";
    $usr = filter_input(INPUT_POST, 'user', FILTER_SANITIZE_SPECIAL_CHARS);
    $pwd = filter_input(INPUT_POST, 'pass', FILTER_SANITIZE_SPECIAL_CHARS);
    #var_dump($usr); echo"<br />";
    if($usr == "")
    {
        login_form("Username cannot be Blank.");
    }
    if($pwd == "")
    {
        login_form("Password cannot be Blank.");
    }
    $conn = new mysqli($server, $username, $password, $db);
    $sql = "SELECT * FROM `settings` limit 1";
    $result = $conn->query($sql, 1);
    $settings = $result->fetch_array(1);
    $result->free();
    if($LDAP)
    {
        $internal = 0;
        $usr_exp = explode("\\", strtolower($usr));
        if(!@$usr_exp[1])
        {
            $user = $usr_exp[0];
            $internal = 1;
        }
        if(!$internal)
        {
            $user = $usr_exp[1];
            $u_domain = $usr_exp[0];
            $sql = "SELECT * FROM `allowed_users` where `domain` LIKE '$u_domain' AND `username` = '$user'";
            $result = $conn->query($sql, 1);
            $array = $result->fetch_array(1);
            if($user == $array['username'])
            {
                $ldap = ldap_connect($domain, $port);
                $bind = ldap_bind($ldap, $usr, $pwd);
                if(!$bind){ login_form("Error: Failed to connect to $domain."); }
                ldap_unbind($ldap);
                if(create_cookie($array['username']))
                {
                    die("Logged In!");
                }else
                {
                    login_form("Login Failed.");
                }
            }else
            {
                login_form("User is not allowed...");
            }
        }
    }
    $sql = "SELECT * FROM `allowed_users` where `username` LIKE '$usr'";
    #var_dump($sql); echo"<br />";
    $result = $conn->query($sql, 1);
    $array = $result->fetch_array(1);
    if($usr == $array['username'])
    {
        if(!$settings['built_in_admin'] && $usr == "unsadmin")
        {
            #echo $pwd."---".$seed; echo"<br />";
            $pwd = md5($pwd.$seed);
            #echo $pwd."<br />";;
            $result->free();
            $sql = "SELECT `username`,`password` FROM `internal_users` where `username` = '$usr'";
            #echo $sql."<br />";
            $result = $conn->query($sql, 1);
            $array = $result->fetch_array(1);
            #echo "$pwd == ".$array['password'];
            if($pwd == $array['password'])
            {
                if($cook = create_cookie($array['username']))
                {echo "Logged In!";}else{login_form("cookie failed: ".$cook);}
            }
            else{login_form("Login Failed.");}

        }elseif($settings['built_in_admin'] && $user == "unsadmin")
        {
            login_form("User is Disabled.");
        }
    }else
    {
        login_form("User is not allowed...");
    }
    die();
}

if(!login_check())
{
    login_form("");
}

if(login_check())
{
    include "../configs/vars.php";
    include "../configs/conn.php";
    $cookie = explode(":", filter_input(INPUT_COOKIE, 'login_yes', FILTER_SANITIZE_SPECIAL_CHARS));
    $cookie_hash = $cookie[0];
    $cookie_user = $cookie[1];
    $conn = new mysqli($server, $username, $password, $db);
    $sql = "SELECT * FROM `hash_links` where `hash` = '$cookie_hash'";
    $result = $conn->query($sql, 1);
    $hash = $result->fetch_array(1);
    $ID = $hash['id'];
    if(time() < $hash['time'])
    {
        ?>
<html>
    <head>
        <title>UNS Admin Panel</title>
        <link rel="stylesheet" href="../configs/styles.css">
    </head>
    <body class="main_body">
        <?php
        $result->free();
        $sql = "SELECT tz FROM `allowed_users` where `username` = '$cookie_user'";
        $result = $conn->query($sql, 1);
        $tx_array = $result->fetch_array(1);
        $exp = explode(":", $tx_array['tz']);
        $tz_list = timezone_abbreviations_list();
        date_default_timezone_set($tz_list[$exp[0]][$exp[1]]["timezone_id"]);
        $func = filter_input(INPUT_GET, 'func', FILTER_SANITIZE_SPECIAL_CHARS);
        admin_panel($cookie_user, $func, $proto);
    }else
    {
        if($root == "" or $root == "/"){$path = "/admin";}else{$path = "/".$root."admin";}
        setcookie("login_yes", "", time()-3600 , $path, '', $SSL, 1);
        login_form("Session timed out. Log In Again.");
        $sql = "DELETE FROM `$db`.`hash_links` where `time` < '".time()."'";
        $result->free();
        if(!($result = $conn->query($sql)))
        {
            echo $conn->error;
        }
    }
    
}
?>
        <div align="center">
            <font size="1">
                Powered by <a class="links" href="http://uns.randomintervals.com/ver.htm#1">UNS v1.0</a><br />
                (
                <!-- replace with final release date -->
                <?php echo date("Y-m", filemtime('index.php'));?>
                ) Phillip Ferland
            </font>
        </div>
    </body>
</html>

<?php

function admin_panel($usr, $func, $proto)
{
    include '../configs/vars.php';
    include '../configs/conn.php';
    
    #gen_base_urls("..");
    $proto = $GLOBALS['proto'];
    $admin_url = $GLOBALS['admin_url'];
    $reg_url = $GLOBALS['reg_url'];
    
    $conn = new mysqli($server, $username, $password, $db);
    ?>
    <table width="100%">
        <tr>
            <td width="10px">
                <img src="../html/logo.png" title="UNS Logo">
            </td>
            <td align="left" valign="center">
                <font size="5"><?php echo $name_title;?> Notification System Administration Panel</font>
            </td>
            <td align="right">
                <form name="tz_change" action="?func=chg_tz" method="POST">
                    <select name="cl_timezone" onchange='this.form.submit()'>
                    <?php
                    $sql = "SELECT `tz` FROM `allowed_users` where `username` LIKE '$usr'";
                    $result = $conn->query($sql,1);
                    $array = $result->fetch_array(1);
                    $user_TZ = explode(":",$array['tz']);
                    echo $array['tz'];
                    foreach(timezone_abbreviations_list() as $key=>$TZ_L)
                    {
                        foreach($TZ_L as $key1=>$TL)
                        {
                            if(($key1 == $user_TZ[1])&&($key == $user_TZ[0]))
                            {
                                ?><option value="<?php echo $key;?>:<?php echo $key1;?>" selected="yes"><?php echo $TL["timezone_id"];?> (<?php echo ($TL["offset"]/60)/60;?>)</option><?php
                            }else
                            {
                                ?><option value="<?php echo $key;?>:<?php echo $key1;?>"><?php echo $TL["timezone_id"];?> (<?php echo ($TL["offset"]/60)/60;?>)</option><?php
                            }
                        }
                    }
                    ?>
                    </select>
                </form>
            </td>
        </tr>
    </table>
    
    <?php
    $result->free();
    $sql = "SELECT `emerg` FROM `settings` LIMIT 1";
    $result = $conn->query($sql, 1);
    $emerg = $result->fetch_array(1);
    if($emerg['emerg'])
    {
        ?>
        <table border="1px" width="100%">
            <tr class="Emerg">
                <td align="center"><font size="6">The Emergency Message is set.</font></td>
            </tr>
        </table>
        <?php
    }
    $result->free();
    $sql = "SELECT * FROM `allowed_users` where `username` like '$usr'";
    $result = $conn->query($sql, 1);
    $perms = $result->fetch_array(1);
    #############
    $o=0;
    if($perms['edit_urls'])
    {
        $nav_bar[] = '<td align="center" class="navtd">Edit Clients: <br /><font color="lawngreen">Allowed</font></td>';
        $side_bar[] = '<p><a href="?" class="side_links">List Clients</a></p>';
    }else
    {
        $nav_bar[] = '<td align="center" class="navtd">Edit Clients: <br /><font color="red">Denied</font></td>';
        $side_bar[] = '';
        $o++;
    }
    #############
    if($perms['edit_emerg'])
    {
        $nav_bar[] = '<td align="center" class="navtd">Emergency Messages: <br /><font color="lawngreen">Allowed</font></td>';
        $side_bar[] = '<p><a href="?func=edit_emerg" class="side_links">Emergency Messages</a></p>';
    }else
    {
        $nav_bar[] = '<td align="center" class="navtd">Emergency Messages: <br /><font color="red">Denied</font></td>';
        $side_bar[] = '';
        $o++;
    }
    #############
    if($perms['edit_users'])
    {
        $nav_bar[] = '<td align="center" class="navtd">Edit Users: <br /><font color="lawngreen">Allowed</font></td>';
        $side_bar[] = '<p><a href="?func=view_users" class="side_links">User Permissions</a></p>';
    }else
    {
        $nav_bar[] = '<td align="center" class="navtd">Edit Users: <br /><font color="red">Denied</font></td>';
        $side_bar[] = '';
        $o++;
    }
    #############
    if($perms['c_messages'])
    {
        $nav_bar[] = '<td align="center" class="navtd">Custom Messages: <br /><font color="lawngreen">Allowed</font></td>';
        $side_bar[] = '<p><a href="?func=c_messages" class="side_links">Custom Messages</a></p>';
    }else
    {
        $nav_bar[] = '<td align="center" class="navtd">Custom Messages: <br /><font color="red">Denied</font></td>';
        $side_bar[] = '';
        $o++;
    }
    #############
    if($perms['rss_feeds'])
    {
        $nav_bar[] = '<td align="center" class="navtd">RSS Feeds: <br /><font color="lawngreen">Allowed</font></td>';
        $side_bar[] = '<p><a href="?func=rss_feeds" class="side_links">RSS Feeds</a></p>';
    }else
    {
        $nav_bar[] = '<td align="center" class="navtd">RSS Feeds: <br /><font color="red">Denied</font></td>';
        $side_bar[] = '';
        $o++;
    }
    if($perms['edit_options'])
    {
        $nav_bar[] = '<td align="center" class="navtd">UNS Options: <br /><font color="lawngreen">Allowed</font></td>';
        $side_bar[] = '<p><a href="?func=edit_options" class="side_links">UNS Options</a></p>';
    }else
    {
        $nav_bar[] = '<td align="center" class="navtd">UNS Options: <br /><font color="red">Denied</font></td>';
        $side_bar[] = '';
        $o++;
    }
    $side_bar[] = '<p><a href="?func=logout" class="side_links">Logout ('.$usr.')</a></p>';
    #############

    if($o == count($nav_bar))
    {
        $side_bar[0] = "No Permissions :-(";
    }

    ?>
    <table border="1px" width="100%">
        <tr>
            <td class="side_bar" valign="top" width="16%">
                <?php
                foreach($side_bar as $side)
                {
                    echo $side."\r\n";
                }
                ?>
            </td>
            <td valign="top" class="main_cell">
                <table border="1px" width="100%">
                    <tr class="nav_bar">
                        
                        
    <?php
    foreach($nav_bar as $nav)
    {
        echo $nav."\r\n";
    }
    ?>
                    </tr>
                </table>
    <?php

    switch($func)
    {
        case "chg_tz":
            $cl_timezone = filter_input(INPUT_POST, 'cl_timezone', FILTER_SANITIZE_SPECIAL_CHARS);
            $sql = "UPDATE `allowed_users` SET `tz` = '$cl_timezone' WHERE `username` = '$usr'";
            $result->free();
            if($conn->query($sql))
            {
                echo "Changed Time Zone.";
                ?>
    <script>
        setTimeout("location.href = '<?php echo  $admin_url;?>admin/index.php?func=rss_feeds'",<?php echo $page_timeout;?>);
    </script>
                <?php
            }else
            {
                echo "Failed to Change Time Zone.<br />\r\n".$conn->error;
            }
            break;
        case "del_backup":
            foreach($_POST['remove'] as $rem)
            {
                if(unlink(getcwd()."/backups/".str_replace("../", "", $rem)))
                {
                    echo "Removed Old DB dump ($rem)<br/>";
                }else
                {
                    echo "Failed to Remove Old DB dump ($rem)<br />";
                }
            }
            break;
        case "backup_options":
            ?>
                <script type="text/javascript">
                <!--
                function SetAllCheckBoxes(FormName, FieldName, CheckValue)
                {
                        if(!document.forms[FormName])
                                return;
                        var objCheckBoxes = document.forms[FormName].elements[FieldName];
                        if(!objCheckBoxes)
                                return;
                        var countCheckBoxes = objCheckBoxes.length;
                        if(!countCheckBoxes)
                                objCheckBoxes.checked = CheckValue;
                        else
                                // set the check value for all check boxes
                                for(var i = 0; i < countCheckBoxes; i++)
                                        objCheckBoxes[i].checked = CheckValue;
                }
                // -->
                </script>
                <div align='center'>
                <table>
                    <tr>
                        <td width="50%" align="center">
                            <form name="bk_now" action="?func=backup" method="POST">
                                <input type="submit" value="Backup Database Now" />
                            </form>
                        </td>
                    </tr>
                </table>
                    <form name="bk_files" action="?func=del_backup" method="POST">
                <table width="75%" border="1">
                    <tr class="client_table_head">
                        <th colspan="3">
                            Previous Backups
                        </th>
                    </tr>
                    <?php
            $dir = getcwd()."/backups/";
            $dh = opendir($dir);
            $bk_files = array();
            while (($file = readdir($dh)) !== false)
            {
                $file_e = explode(".", $file);
                if($file_e[1] != "sql")continue;
                $bk_files[] = $file;
            }
            closedir($dh);
            rsort($bk_files);
                    ?>
                    <tr class="client_table_head">
                        <th>Name</th><th>Size</th><th>Delete</th>
                    </tr>
            <?php
            foreach($bk_files as $file)
            {
                echo "<tr class='client_table_body'><td align='center'><a href='".$admin_url."admin/backups/$file'>$file</a></td><td align='center'>".format_bytes(filesize($dir.$file))."</td><td align='center'><input type='checkbox' name='remove[]' value='$file'></td></tr>";
            }
            ?>
                <tr>
                    <td class="client_table_tail">&nbsp;</td>
                    <td class="client_table_tail" align='center'><input type="submit" value="Delete"/></td>
                    <td class="client_table_tail" align='center'>
                        <input type="button" onclick="SetAllCheckBoxes('bk_files', 'remove[]', true);" value="Check"> 
                        <input type="button" onclick="SetAllCheckBoxes('bk_files', 'remove[]', false);" value="Uncheck">
                    </td>
                </table></form></div><?php
            break;
        case "backup":
            $bak_fldr = getcwd()."/backups/";
            $filename = 'UNS_'.date("Y-m-d-H-i-s") . '.sql';
            $backupFile = $bak_fldr.$filename;

            $command = $mysql_dump_bin." -v -h $server -u $username --password=$password -B $db>$backupFile";
            #echo $command."<Br />";
            exec($command,$sys, $ret);

            if(@filesize($backupFile) > 0)
            {
                echo "Backed up to <a href='".$admin_url."admin/backups/".$filename."' target='_blank'>$filename</a><br /><a href='javascript:history.go(-1)'>Go back</a>";
                ?>
    <!--<script>
        setTimeout("location.href = '<?php echo  $admin_url;?>admin/index.php?func=edit_options'",<?php echo $page_timeout;?>);
    </script>-->
                <?php
            }else
            {
                echo "Failed to Backup DB.<br /><a href='javascript:history.go(-1)'>Go back</a>";
            }
            break;
        case "restore":
            $restore = $_FILES['restore_sql']['tmp_name'];
            $saved = getcwd().'/restores/'.$_FILES['restore_sql']['name'];
            $ext = strstr($saved, ".sql");
            if($ext != ".sql"){echo "Wrong File type.<br/>";break;}
            $command = "mysql -h $server -u $username --password=$password < $saved";
            if(move_uploaded_file($restore, $saved))
            {
                $sys = system($command, $ret);
                echo "Ran MySQL Restore<br/>You may need to logout, then log back in.<br /><a onclick='history.back()'>Go back</a>";
            }else
            {
                echo "Failed to move temp file.<br/><a onclick='history.back()'>Go back</a>";
            }
            break;
        case "edit_opt_proc":
            include ('../configs/vars.php');
            $sql_host = @filter_input(INPUT_POST, 'sql_host', FILTER_SANITIZE_ENCODED);
            $uns_sql_usr = @filter_input(INPUT_POST, 'uns_sql_usr', FILTER_SANITIZE_ENCODED);
            $uns_sql_pwd = @filter_input(INPUT_POST, 'uns_sql_pwd', FILTER_SANITIZE_SPECIAL_CHARS);
            if($uns_sql_pwd == ""){die("You need to enter your UNS SQL password in order for the Configurator to finish.<br /><a onclick='history.back()'>Go back</a> and do it again.");}
            $db_name = @filter_input(INPUT_POST, 'db_name', FILTER_SANITIZE_ENCODED);
            
            $hostname = html_entity_decode(@filter_input(INPUT_POST, 'hostname', FILTER_SANITIZE_SPECIAL_CHARS));
            $uns_name = html_entity_decode(@filter_input(INPUT_POST, 'uns_name', FILTER_SANITIZE_SPECIAL_CHARS));
            $root1 = @filter_input(INPUT_POST, 'root', FILTER_SANITIZE_SPECIAL_CHARS);
            $timeout1 = @filter_input(INPUT_POST, 'timeout', FILTER_SANITIZE_ENCODED)+0;
            $SSL1 = @filter_input(INPUT_POST, 'ssl', FILTER_SANITIZE_ENCODED)+0;

            $domain1 = @filter_input(INPUT_POST, 'ldap_domain', FILTER_SANITIZE_ENCODED);
            $domain_port1 = @$_POST['ldap_port']+0;
            $page_timeout1 = @filter_input(INPUT_POST, 'page_timeout', FILTER_SANITIZE_ENCODED)+0;
            
            $refresh1 = @filter_input(INPUT_POST, 'refresh', FILTER_SANITIZE_ENCODED)+0;
            $max_arch1 = @filter_input(INPUT_POST, 'max_arch', FILTER_SANITIZE_ENCODED)+0;
            $max_conns1 = @filter_input(INPUT_POST, 'max_conns', FILTER_SANITIZE_ENCODED)+0;
            $ldap1 = @filter_input(INPUT_POST, 'ldap', FILTER_SANITIZE_ENCODED)+0;
            
            $leds = @filter_input(INPUT_POST, 'leds', FILTER_SANITIZE_ENCODED)+0;
            $lpt_binary = html_entity_decode(@filter_input(INPUT_POST, 'lpt_binary', FILTER_SANITIZE_SPECIAL_CHARS));
            $portctl = html_entity_decode(@filter_input(INPUT_POST, 'portctl', FILTER_SANITIZE_SPECIAL_CHARS));

            $mysql_dump_binary = @filter_input(INPUT_POST, 'mysql_dump', FILTER_SANITIZE_ENCODED);

            $vars_file = "<?php
$"."name_title     = '$uns_name';                    # Name of your Install, Will be displayed on all papes
$"."host           = '$hostname';              # The HTTP server the clients will connect to.
$"."root           = '$root1';                   # Folder UNS lives in
$"."timeout        = ($timeout1);                   # Cookie Time out
$"."SSL            = $SSL1;                        # Cookie SSL only?
$"."domain         = '$domain1';    # LDAP Domain to connect to for user authentication
$"."port           = $domain_port1;                     # LDAP Port
$"."TZ             = '$TZ';                    # Local Time Zone
$"."page_timeout   = $page_timeout1;                        # Refresh time for page to forward in seconds.
$"."refresh        = $refresh1;                       # Time for client pages to refresh.
$"."seed           = '$seed';     # Only used for internal user logins, to hash the password and store that.
$"."LDAP           = $ldap1;                        # If this flag is set, internal users will be overridden, except for the Admin.
$"."max_archives   = $max_arch1;                       # The Maximum number of Archived URL lists that will be kept before the oldest is killed
$"."max_conn_hist  = $max_conns1;                       # The Maximum number of Connection histories that will be kept per client.
$"."lpt_read_app   = '$portctl';
$"."lpt_set_app    = '$lpt_binary';
$"."led_blink      = $leds;
$"."mysql_dump_bin = '$mysql_dump_binary';

# The Template variables for RSS feeds
$"."template_head_rss = '$template_head_rss';

$"."template_foot_rss = '$template_foot_rss';

# The Template variables for Custom Messages
$"."template_head_cmsg = '$template_head_cmsg';

$"."template_foot_cmsg = '$template_foot_cmsg';
?>";
            $cwd = str_replace("admin","",getcwd());
            
            if($fp = fopen($cwd."configs/vars.php", 'w+'))
            {fwrite($fp, $vars_file); fclose($fp);echo "Wrote Vars Config File.<br />";}
            else{echo "Failed to write Vars Config File.<br />";}
            sleep(1);
            
            $conn_file = '<?php
$server = "'.$sql_host.'";  # MySQL Host
$username = "'.$uns_sql_usr.'";      # User for UNS
$password = "'.$uns_sql_pwd.'";      # Users password
$db = "'.$db_name.'";            # Database with UNS tables
?>';
            
            if($fp1 = fopen($cwd."configs/conn.php", 'w+'))
            {fwrite($fp1, $conn_file);echo "Wrote Conn Config File.<br />";
            ?>
    <!--<script>
        setTimeout("location.href = '<?php echo  $admin_url;?>admin/index.php?func=edit_options'",<?php echo $page_timeout;?>);
    </script>-->
            <?php
            }
            else{echo "Failed to write Conn Config File.<br />";}
            break;
        case "edit_options":
             ?>
                <script type="text/javascript">
                    function endisable( ) {
                        document.forms['edit_options'].elements['ldap_domain'].disabled =! document.forms['edit_options'].elements['ldap'].checked;
                        document.forms['edit_options'].elements['ldap_port'].disabled =! document.forms['edit_options'].elements['ldap'].checked;
                    }
                    function endisable_led( ) {
                        document.forms['edit_options'].elements['lpt_binary'].disabled =! document.forms['edit_options'].elements['leds'].checked;
                        document.forms['edit_options'].elements['portctl'].disabled =! document.forms['edit_options'].elements['leds'].checked;
                    }
                </script>
                <div align="center">
                    <table border="1">
                        <tr class="client_table_head">
                            <td width="50%" align="center">
                                <a href="?func=backup_options">Backup Options</a>
                            </td>
                            <td align="center">
                                <form enctype="multipart/form-data" name="backup_restore_options" action="?func=restore" method="POST">
                                    <input type="hidden" name="MAX_FILE_SIZE" value="1000000"/>
                                    <input type="file" name="restore_sql" ACCEPT="text/plain" /><br />
                                    <input type="submit" value="Restore Database" />
                                </form>
                            </td>
                        </tr>
                    </table>
                    <form name="edit_options" action="?func=edit_opt_proc" method="POST">
                        <table border="1">
                            <tr class="client_table_head">
                                <th colspan="2">
                                    UNS Options Editor
                                </th>
                            </tr>
                            <tr class="client_table_head">
                                <th colspan="2">
                                    SQL Settings
                                </th>
                            </tr>
                            <tr class="client_table_body">
                                <td width="250px">
                                    SQL Host
                                </td>
                                <td width="200px">
                                    <input type="text" name="sql_host" style="width:100%" value="<?php echo html_entity_decode($server);?>"/>
                                </td>
                            </tr>
                            <tr class="client_table_body">
                                <td>
                                    UNS SQL Username
                                </td>
                                <td>
                                    <input type="text" name="uns_sql_usr" style="width:100%" value="<?php echo $username;?>"/>
                                </td>
                            </tr>
                            <tr class="client_table_body">
                                <td>
                                    UNS SQL Password
                                </td>
                                <td>
                                    <input type="password" name="uns_sql_pwd" style="width:100%" value=""/>
                                </td>
                            </tr>
                            <tr class="client_table_body">
                                <td>
                                    Database Name
                                </td>
                                <td>
                                    <input type="text" name="db_name" style="width:100%" value="<?php echo $db;?>"/>
                                </td>
                            </tr>
                            <tr class="client_table_head">
                                <th colspan="2">
                                    UNS Variables
                                </th>
                            </tr>
                            <tr class="client_table_body">
                                <td>
                                    Instance Name
                                </td>
                                <td>
                                    <input type="text" name="uns_name" style="width:100%" value="<?php echo html_entity_decode($name_title);?>"/>
                                </td>
                            </tr>
                            <tr class="client_table_body">
                                <td>
                                    Hostname
                                </td>
                                <td>
                                    <input type="text" name="hostname" style="width:100%" value="<?php echo html_entity_decode($host);?>"/>
                                </td>
                            </tr>
                            <tr class="client_table_body">
                                <td>
                                        HTTP root for UNS
                                </td>
                                <td>
                                    <input type="text" name="root" style="width:100%" value="<?php echo html_entity_decode($root);?>"/>
                                </td>
                            </tr>
                            <tr class="client_table_body">
                                <td>
                                    Session Timeout <font size="1">( Seconds )</font>
                                </td>
                                <td>
                                    <input type="text" name="timeout" style="width:100%" value="<?php echo $timeout;?>"/>
                                </td>
                            </tr>
                            <tr class="client_table_body">
                                <td>
                                    SSL Admin Folder?
                                </td>
                                <td>
                                    <input type="checkbox" name="ssl" value="1" <?php if($SSL){echo "checked";}?>/>
                                </td>
                            </tr>
                            <tr class="client_table_body">
                                <td>
                                    Use LDAP?
                                </td>
                                <td>
                                    <input type="checkbox" name="ldap" value="1" <?php if($LDAP){echo "checked";}?> onchange="endisable()"/>
                                </td>
                            </tr>
                            <tr class="client_table_body">
                                <td>
                                    LDAP Domain
                                </td>
                                <td>
                                    <input type="text" name="ldap_domain" style="width:100%" value="<?php echo $domain;?>" <?php if(!$LDAP){echo "disabled";}?>/>
                                </td>
                            </tr>
                            <tr class="client_table_body">
                                <td>
                                    LDAP Port
                                </td>
                                <td>
                                    <input type="text" name="ldap_port" style="width:100%" value="<?php echo $port;?>" <?php if(!$LDAP){echo "disabled";}?>/>
                                </td>
                            </tr>
                            <tr class="client_table_body">
                                <td>
                                    Redirect Page Timeout <font size="1">( Zero [0], will be an instant redirect. )</font>
                                </td>
                                <td>
                                    <input type="text" name="page_timeout" style="width:100%" value="0"/>
                                </td>
                            </tr>
                            <tr class="client_table_body">
                                <td>
                                    Default URL Refresh time
                                </td>
                                <td>
                                    <input type="text" name="refresh" style="width:100%" value="30"/>
                                </td>
                            </tr>
                            <tr class="client_table_body">
                                <td>
                                    Max Number of Archived links per Client
                                </td>
                                <td>
                                    <input type="text" name="max_arch" style="width:100%" value="<?php echo $max_archives;?>"/>
                                </td>
                            </tr>
                            <tr class="client_table_body">
                                <td>
                                    Max Number of Connection History per Client.
                                </td>
                                <td>
                                    <input type="text" name="max_conns" style="width:100%" value="<?php echo $max_conn_hist;?>"/>
                                </td>
                            </tr>
                            <tr class="client_table_body">
                                <td>
                                    Use LEDs?
                                </td>
                                <td>
                                    <input type="checkbox" name="leds" value="1" <?php if($led_blink){echo "checked";}?> onchange="endisable_led()"/>
                                </td>
                            </tr>
                            <tr class="client_table_body">
                                <td>
                                    LPT Binary
                                </td>
                                <td>
                                    <input type="text" name="lpt_binary" style="width:100%" value="<?php echo html_entity_decode($lpt_set_app);?>" <?php if(!$led_blink){echo "disabled";}?>/>
                                </td>
                            </tr>
                            <tr class="client_table_body">
                                <td>
                                    Portctl Binary
                                </td>
                                <td>
                                    <input type="text" name="portctl" style="width:100%" value="<?php echo html_entity_decode($lpt_read_app);?>" <?php if(!$led_blink){echo "disabled";}?>/>
                                </td>
                            </tr>
                            <tr class="client_table_body">
                                <td>
                                    Mysql Dump Binary
                                </td>
                                <td>
                                    <input type="text" name="mysql_dump" style="width:100%" value="<?php echo html_entity_decode($mysql_dump_bin);?>" />
                                </td>
                            </tr>
                            <tr class="client_table_tail">
                                <td align="center"colspan="2">
                                    <input type="submit" value="Submit" />
                                </td>
                            </tr>
                        </table>
                    </form>
                </div>
                    <?php
            break;
        case "rss_feeds":
            if($perms['rss_feeds'])
            {
                $mode = @filter_input(INPUT_GET, 'mode', FILTER_SANITIZE_SPECIAL_CHARS);
                switch($mode)
                {
                    case "add_rss":
                        $url = @filter_input(INPUT_POST, 'url_n', FILTER_SANITIZE_SPECIAL_CHARS);
                        $name = @filter_input(INPUT_POST, 'name_n', FILTER_SANITIZE_SPECIAL_CHARS);
                        $maxlines = @filter_input(INPUT_POST, 'maxlines_n', FILTER_SANITIZE_SPECIAL_CHARS);
                        $sql = "INSERT INTO `rss_feeds` (`id`, `name`, `url`, `maxlines`) VALUES ('', '$name', '$url', '$maxlines')";
                        $result->free();
                        if($conn->query($sql))
                        {
                            echo "Added Feeds.";
                            ?>
                <script>
                    setTimeout("location.href = '<?php echo  $admin_url;?>admin/index.php?func=rss_feeds'",<?php echo $page_timeout;?>);
                </script>
                            <?php
                        }else
                        {
                            echo "Failed to update Feeds<br />\r\n".$conn->error;
                        }
                        break;
                    case "edit_rss":
                        if(@$_POST['remove'] == "Remove")
                        {
                            if(!@$_POST['remove_'])
                            {
                                ?>
                        <script>
                            setTimeout("location.href = '<?php echo $admin_url;?>admin/index.php?func=rss_feeds'",<?php echo $page_timeout;?>);
                        </script>
                                <?php
                                break;
                            }
                            foreach($_POST['remove_'] as $key=>$del)
                            {
                                $search = $reg_url."html/template.php?type=rss&#38;id=$del";
                                $sql = "SELECT id FROM `emerg` WHERE `url` LIKE '$search'";
                                $result->free();
                                $result = $conn->query($sql,1);
                                $id =  $result->fetch_array(1);
                                if(@$id['id'])
                                {
                                    echo "Found message in Emergency Table.....";
                                    $sql = "DELETE FROM `emerg` WHERE `id` = '".$id['id']."'";
                                    $result->free();
                                    if($conn->query($sql))
                                    {echo "Removed!<br />";}
                                    else{echo "Failed to Remove<br />";}
                                }else{echo "Custom Message [$del] was not in the Emergency Table<br />";}
                                #Gather client ids list.
                                $sql = "SELECT client_name FROM `allowed_clients`";
                                @$result->free();
                                $result = $conn->query($sql,1);
                                $cl_c = 0;
                                #Check Client lists for Custom Messages
                                $link = mysqli_connect($server, $username, $password, $db);
                                while($clid = $result->fetch_array(1))
                                {
                                    $cl = $clid['client_name'];
                                    $sql = "SELECT id FROM `".$cl."_links` WHERE `url` LIKE '%rss&#38;id=$del' LIMIT 1";
                                    $result1 = mysqli_query($link, $sql);
                                    $id =  @mysqli_fetch_array($result1);
                                    if(@$id['id'])
                                    {
                                        echo $id['id']."<br />";
                                        if(@$id['id'])
                                        {
                                            echo "Found message in $cl Link Table.....";
                                            $sql = "DELETE FROM `".$cl."_links` WHERE `id` = '".$id['id']."'";
                                            if(mysqli_query($link, $sql))
                                            {echo "Removed!<br />";}
                                            else{echo "Failed to Remove<br />";}
                                            $cl_c++;
                                        }
                                    }else{echo "none<br />";}
                                    echo "<hr />";
                                }
                                mysqli_close($link);
                                if(!$cl_c){echo "<br /><br />Couldnt Find Any Clients with Custom Message [$del]<br />";}
                                else{echo "<br /><br />Found [$cl_c] Clients with Custom Message [$del].<br />";}
                                #remove Custom message
                                $sql = "DELETE FROM `rss_feeds` WHERE `id` = '$del'";
                                $result->free();
                                if($conn->query($sql))
                                {
                                    echo "Removed message [$del].";
                                    ?>
                        <script>
                            setTimeout("location.href = '<?php echo  $admin_url;?>admin/index.php?func=rss_feeds'",<?php echo $page_timeout;?>);
                        </script>
                                    <?php
                                }else
                                {
                                    echo "Failed to Remove message [$del].<br />\r\n".$conn->error;
                                }
                            }
                        }else
                        {
                            $result->free();
                            foreach($_POST['id'] as $key=>$id)
                            {
                                $url = $_POST['body'][$key];
                                $name = $_POST['name'][$key];
                                $maxlines = $_POST['maxlines'][$key];
                                $sql = "UPDATE `rss_feeds` SET `name` = '$name', `url` = '$url', `maxlines` = '$maxlines' WHERE `id` = '$id'";
                                echo $sql."<br />";
                                if($conn->query($sql))
                                {
                                    echo "Updated Feed [$id] ($name).";
                                    ?>
                        <script>
                            setTimeout("location.href = '<?php echo  $admin_url;?>admin/index.php?func=rss_feeds'",<?php echo $page_timeout;?>);
                        </script>
                                    <?php
                                }else
                                {
                                    echo "Failed to Update Feed [$id] ($name).<br />\r\n".$conn->error;
                                }
                            }
                        }
                        break;
                    default:
                        ?>
                <script type="text/javascript">
                <!--
                function SetAllCheckBoxes(FormName, FieldName, CheckValue)
                {
                        if(!document.forms[FormName])
                                return;
                        var objCheckBoxes = document.forms[FormName].elements[FieldName];
                        if(!objCheckBoxes)
                                return;
                        var countCheckBoxes = objCheckBoxes.length;
                        if(!countCheckBoxes)
                                objCheckBoxes.checked = CheckValue;
                        else
                                // set the check value for all check boxes
                                for(var i = 0; i < countCheckBoxes; i++)
                                        objCheckBoxes[i].checked = CheckValue;
                }
                function expandcontract(tbodyid,ClickIcon)
                {
                        if (document.getElementById(ClickIcon).innerHTML == "+")
                        {
                                document.getElementById(tbodyid).style.display = "";
                                document.getElementById(ClickIcon).innerHTML = "-";
                        }else{
                                document.getElementById(tbodyid).style.display = "none";
                                document.getElementById(ClickIcon).innerHTML = "+";
                        }
                }
                // -->
                </script>
                <table border="1px" width="100%">
                    <tr class="client_table_head">
                        <form name="save_new" action="?func=rss_feeds&mode=edit_rss" method="POST">
                        <th colspan="6">RSS Feeds</th>
                    </tr>
                    <tr class="client_table_head">
                        <th colspan="6"><input type='submit' name="update_rss" value="Update All Feeds"></th>
                    </tr>
                    <tr class="client_table_head">
                        <th>+/-</th><th>Name</th><th>Max lines</th><th>RSS Feed URL</th><th>Options</th>
                    </tr>
                    <?php
                        $link = mysqli_connect($server, $username, $password, $db);
                        $sql = "SELECT * FROM `rss_feeds` ORDER by `name` ASC";
                        $result = mysqli_query($link, $sql);
                        if(mysqli_num_rows($result))
                        {
                            $tablerowid=0;
                            while($links = mysqli_fetch_array($result))
                            {
                            ?>
                        <tr class="client_table_body">
                            <td onclick="expandcontract('mesgRow<?php echo $tablerowid;?>','mesgClickIcon<?php echo $tablerowid;?>')"
                                id="mesgClickIcon<?php echo $tablerowid;?>" style="cursor: pointer; cursor: hand;">+</td>
                            </td>
                            <td style="width:25%;">
                                <input type="hidden" name="id[]" value="<?php echo $links['id'];?>"/>
                                <input type="text" name="name[]" style="width:90%;" value="<?php echo $links['name'];?>"/>
                            </td>
                            <td>
                                <input type="text" name="maxlines[]" style="width:45px;" value="<?php echo $links['maxlines'];?>"/>
                            </td>
                            <td>
                                <a class="links" href="<?php echo $reg_url;?>html/template.php?type=rss&id=<?php echo $links['id'];?>" target="_blank"><?php echo $reg_url;?>html/template.php?type=rss&id=<?php echo $links['id'];?></a>
                            </td>
                            <td align="center">
                                <input type="checkbox" name="remove_[]" value="<?php echo $links['id'];?>"/>
                            </td>
                        </tr>
                        <tbody id="mesgRow<?php echo $tablerowid;?>" style="display:none">
                        <tr>
                            <td colspan="6">
                                <input type="text" name="body[]" style="width:100%" value="<?php echo $links['url'];?>" />
                                <br />
                                <br />
                            </td>
                        </tr>
                        </tbody>
                            <?php
                                $tablerowid++;
                            }
                        }else
                        {
                        ?>
                        <tr>
                            <td align="center" colspan="5">
                                There are no RSS Feeds yet.
                            </td>
                        </tr>
                        <?php
                        }
                        ?>
                    <tr class="client_table_tail">
                        <td align="center" colspan="4">
                        </td>
                        <td align="center">
                            <table width="100%">
                                <tr>
                                    <td align="center">
                                        <input type='submit' name="remove" value='Remove'>
                                    </td>
                                    <td align="center">
                                        <input type="button" onclick="SetAllCheckBoxes('save_new', 'remove_[]', true);" value="Check"><br />
                                        <input type="button" onclick="SetAllCheckBoxes('save_new', 'remove_[]', false);" value="Uncheck">
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>
                    </form>
                    <tr class="client_table_tail">
                        <td colspan="6" align="center">
                            <form name="save_new1" action="?func=rss_feeds&mode=add_rss" method="POST">
                            <table>
                                <tr>
                                    <td valign="center">
                                        Name:
                                    </td>
                                    <td>
                                        <input type="text" name="name_n" style="width:400px;" value="">
                                    </td>
                                </tr>
                                <tr>
                                    <td valign="center">
                                        RSS URL:
                                    </td>
                                    <td>
                                        <input type="text" name="url_n" style="width:400px;" value="http://">
                                    </td>
                                </tr>
                                <tr>
                                    <td valign="center">
                                        Max Lines:
                                    </td>
                                    <td>
                                        <input type="text" name="maxlines_n" style="width:45px" value="<?php echo $refresh; ?>">
                                    </td>
                                </tr>
                                <tr>
                                    <td colspan="2" align="center">
                                        <input type='submit' value='Add RSS'>
                                    </td>
                                </tr>
                            </table>
                            </form>
                        </td>
                    </tr>
                </table>
                        <?php
                        break;
                }
            }else
            {
                echo "Ummm, you shouldn't be here.. I think you should leave before the droids come. O_o";
            }
            break;
        case "c_messages":
            if($perms['c_messages'])
            {
                $mode = @filter_input(INPUT_GET, 'mode', FILTER_SANITIZE_SPECIAL_CHARS);
                switch($mode)
                {
                    case "add_messg":
                        $body = @filter_input(INPUT_POST, 'body_n', FILTER_SANITIZE_SPECIAL_CHARS);
                        $name = @filter_input(INPUT_POST, 'name_n', FILTER_SANITIZE_SPECIAL_CHARS);
                        $wrapper = @filter_input(INPUT_POST, 'wrapper', FILTER_SANITIZE_SPECIAL_CHARS)+0;
                        $sql = "INSERT INTO `c_messages` (`id`, `name`, `body`, `wrapper`) VALUES ('', '$name', '$body', '$wrapper')";
                        $result->free();
                        if($conn->query($sql))
                        {
                            echo "Updated message.";
                            ?>
                <script>
                    setTimeout("location.href = '<?php echo  $admin_url;?>admin/index.php?func=c_messages'",<?php echo $page_timeout;?>);
                </script>
                            <?php
                        }else
                        {
                            echo "Failed to update message<br />\r\n".$conn->error;
                        }
                        break;
                    case "edit_messg":
                        if(@$_POST['remove'] == "Remove")
                        {
                            if(!@$_POST['remove_'])
                            {
                                ?>
                        <script>
                            setTimeout("location.href = '<?php echo  $admin_url;?>admin/index.php?func=c_messages'",<?php echo $page_timeout;?>);
                        </script>
                                <?php
                                break;
                            }
                            foreach($_POST['remove_'] as $key=>$del)
                            {
                                #Check Emerg for Custom messages
                                $search = $reg_url."html/template.php?type=c_message&#38;id=$del";
                                $sql = "SELECT id FROM `emerg` WHERE `url` LIKE '$search'";
                                $result->free();
                                $result = $conn->query($sql,1);
                                $id =  $result->fetch_array(1);
                                if(@$id['id'])
                                {
                                    echo "Found message in Emergency Table.....";
                                    $sql = "DELETE FROM `emerg` WHERE `id` = '".$id['id']."'";
                                    $result->free();
                                    if($conn->query($sql))
                                    {echo "Removed!<br />";}
                                    else{echo "Failed to Remove<br />";}
                                }else{echo "Custom Message [$del] was not in the Emergency Table<br />";}
                                #Gather client ids list.
                                $sql = "SELECT client_name FROM `allowed_clients`";
                                @$result->free();
                                $result = $conn->query($sql,1);
                                $cl_c = 0;
                                #Check Client lists for Custom Messages
                                $link = mysqli_connect($server, $username, $password, $db);
                                while($clid = $result->fetch_array(1))
                                {
                                    $cl = $clid['client_name'];
                                    $sql = "SELECT id FROM `".$cl."_links` WHERE `url` LIKE '%c_message&#38;id=$del' LIMIT 1";
                                    $result1 = mysqli_query($link, $sql);
                                    $id =  @mysqli_fetch_array($result1);
                                    if(@$id['id'])
                                    {
                                        echo $id['id']."<br />";
                                        if(@$id['id'])
                                        {
                                            echo "Found message in $cl Link Table.....";
                                            $sql = "DELETE FROM `".$cl."_links` WHERE `id` = '".$id['id']."'";
                                            if(mysqli_query($link, $sql))
                                            {echo "Removed!<br />";}
                                            else{echo "Failed to Remove<br />";}
                                            $cl_c++;
                                        }
                                    }else{echo "none<br />";}
                                    echo "<hr />";
                                }
                                mysqli_close($link);
                                if(!$cl_c){echo "<br /><br />Couldnt Find Any Clients with Custom Message [$del]<br />";}
                                else{echo "<br /><br />Found [$cl_c] Clients with Custom Message [$del].<br />";}
                                #remove Custom message
                                $sql = "DELETE FROM `c_messages` WHERE `id` = '$del'";
                                $result->free();
                                if($conn->query($sql))
                                {
                                    echo "Removed message [$del].";
                                    ?>
                        <script>
                            setTimeout("location.href = '<?php echo  $admin_url;?>admin/index.php?func=c_messages'",<?php echo $page_timeout;?>);
                        </script>
                                    <?php
                                }else
                                {
                                    echo "Failed to Remove message [$del].<br />\r\n".$conn->error;
                                }
                            }
                        }else
                        {
                            if(!@$_POST['body'])
                            {
                                ?>
                        <script>
                            setTimeout("location.href = '<?php echo  $admin_url;?>admin/index.php?func=c_messages'",<?php echo $page_timeout;?>);
                        </script>
                                <?php
                                break;
                            }
                            foreach($_POST['body'] as $key=>$body)
                            {
                                $body = htmlentities($body, ENT_QUOTES);
                                $id = $_POST['id'][$key];
                                $name = $_POST['name'][$key];
                                $wrapper = $_POST['wrapper'][$key];
                                $sql = "UPDATE `c_messages` SET `name` = '$name', `body` = '$body', `wrapper` = '$wrapper' WHERE `id` = '$id'";
                                @$result->free();
                                if($conn->query($sql))
                                {
                                    echo "Updated message [$id] ($name).<br/>";
                                    ?>
                        <script>
                            setTimeout("location.href = '<?php echo  $admin_url;?>admin/index.php?func=c_messages'",<?php echo $page_timeout;?>);
                        </script>
                                    <?php
                                }else
                                {
                                    echo "Failed to Update message [$id] ($name).<br />\r\n".$conn->error;
                                }
                            }
                        }
                        break;
                    default:
                        ?>
                <script type="text/javascript">
                <!--
                function SetAllCheckBoxes(FormName, FieldName, CheckValue)
                {
                        if(!document.forms[FormName])
                                return;
                        var objCheckBoxes = document.forms[FormName].elements[FieldName];
                        if(!objCheckBoxes)
                                return;
                        var countCheckBoxes = objCheckBoxes.length;
                        if(!countCheckBoxes)
                                objCheckBoxes.checked = CheckValue;
                        else
                                // set the check value for all check boxes
                                for(var i = 0; i < countCheckBoxes; i++)
                                        objCheckBoxes[i].checked = CheckValue;
                }
                function expandcontract(tbodyid,ClickIcon)
                {
                        if (document.getElementById(ClickIcon).innerHTML == "+")
                        {
                                document.getElementById(tbodyid).style.display = "";
                                document.getElementById(ClickIcon).innerHTML = "-";
                        }else{
                                document.getElementById(tbodyid).style.display = "none";
                                document.getElementById(ClickIcon).innerHTML = "+";
                        }
                }
                // -->
                </script>
                <table border="1px" width="100%">
                    <tr class="client_table_head">
                        <form name="save_new" action="?func=c_messages&mode=edit_messg" method="POST">
                        <th colspan="5">Custom Messages</th>
                    </tr>
                    <tr class="client_table_head">
                        <th colspan="5"><input type='submit' name="update_body" value="Update All Messages"></th>
                    </tr>
                    <tr class="client_table_head">
                        <th style="width:1px;">+/-</th><th>Name</th><th>Message URL</th><th width="1px">Options</th>
                    </tr>
                    <?php
                        $link = mysqli_connect($server, $username, $password, $db);
                        $sql = "SELECT * FROM `c_messages` ORDER by `id` ASC";
                        $result = mysqli_query($link, $sql);
                        if(mysqli_num_rows($result))
                        {
                            $tablerowid=0;
                            while($links = mysqli_fetch_array($result))
                            {
                            ?>
                        <tr class="client_table_body">
                            <td onclick="expandcontract('mesgRow<?php echo $tablerowid;?>','mesgClickIcon<?php echo $tablerowid;?>')"
                                id="mesgClickIcon<?php echo $tablerowid;?>" style="cursor: pointer; cursor: hand;">+
                            </td>
                            <td style="width:25%;">
                                <input type="hidden" name="id[]" value="<?php echo $links['id'];?>"/>
                                <input type="text" name="name[]" style="width:90%;" value="<?php echo $links['name'];?>"/>
                            </td>
                            <td>
                                <a class="links" href="<?php echo  $reg_url;?>html/template.php?type=c_message&id=<?php echo $links['id'];?>" target="_blank"><?php echo $reg_url;?>html/template.php?type=c_message&id=<?php echo $links['id'];?></a>
                            </td>
                            <td style="width:1%;" align="center">
                                <input type="checkbox" name="remove_[]" value="<?php echo $links['id'];?>"/>
                            </td>
                        </tr>
                        <tbody id="mesgRow<?php echo $tablerowid;?>" style="display:none">
                        <tr>
                            <td colspan="5">
                                <textarea name="body[]" rows="10" style="width:90%"><?php echo $links['body'];?></textarea>
                                <br />
                                Use the UNS Wrapper? <input type="checkbox" name="wrapper[]" value="1" <?php if($links['wrapper']){echo "Checked";} ?>/>
                                <br />
                            </td>
                        </tr>
                        </tbody>
                            <?php
                                $tablerowid++;
                            }
                        }else
                        {
                        ?>
                        <tr>
                            <td align="center" colspan="5">
                                There are no custom messages yet.
                            </td>
                        </tr>
                        <?php
                        }
                        ?>
                    <tr class="client_table_tail">
                        <td align="center" colspan="3">
                        </td>
                        <td align="center">
                            <table>
                                <tr>
                                    <td align="center" valign="center">
                                        <input type='submit' name="remove" value='Remove'>
                                    </td>
                                    <td align="center" valign="center">
                                        <input type="button" onclick="SetAllCheckBoxes('save_new', 'remove_[]', true);" value="Check"><br />
                                        <input type="button" onclick="SetAllCheckBoxes('save_new', 'remove_[]', false);" value="Uncheck">
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>
                    </form>
                    <tr class="client_table_tail">
                        <td colspan="5" align="center">
                            <form name="save_new1" action="?func=c_messages&mode=add_messg" method="POST">
                            <table>
                                <tr>
                                    <td valign="center">
                                        Name:
                                    </td>
                                    <td>
                                        <input type="text" name="name_n" style="width:100%;" value="">
                                    </td>
                                </tr>
                                <tr>
                                    <td valign="center">
                                        Message:<br />
                                        <font size="1">In HTML</font>
                                    </td>
                                    <td>
                                        <textarea name="body_n" cols="100" rows="10">[Put Message Here]</textarea>
                                    </td>
                                </tr>
                                <tr>
                                    <td align="center">
                                        Use the UNS Wrapper?
                                    </td>
                                    <td>
                                        <input type="checkbox" name="wrapper" value="1" Checked/>
                                    </td>
                                </tr>
                                <tr>
                                    <td colspan="2" align="center">
                                        <input type='submit' value='Add Message'>
                                    </td>
                                </tr>
                            </table>
                            </form>
                        </td>
                    </tr>
                </table>
                        <?php
                        break;
                }

            }else
            {
                echo "Ummm, you shouldn't be here.. I think you should leave before the droids come. O_o";
            }
            break;
        case "edit_urls":
            if($perms['edit_urls'])
            {
                $client_get = @filter_input(INPUT_GET, 'client', FILTER_SANITIZE_SPECIAL_CHARS);
                if(!$client_get)
                {
                    $sql = "SELECT `client_name` FROM `allowed_clients`";
                    $result->free();
                    $result = $conn->query($sql, 1);
                    $clients = array();
                    $id = 0;
                    $pre = "";
                    while($links = $result->fetch_array(1))
                    {
                        $clients[] = $links['client_name'];
                    }
                    #$clients = array_unique($clients);
                    ?>
                    <table border="1px" width="100%">
                        <tr>
                            <th class="client_table_head"><font size="4">Edit Standard Message URLs for Clients</font></th>
                        </tr>
                        <tr>
                            <th class="client_table_head">Client Name</th>
                        </tr>
                        <?php
                    foreach($clients as $client)
                    {
                        $sql = "SELECT * FROM `friendly` WHERE `client` like '$client'";
                        $result1 = $conn->query($sql,1);
                        $friendly = $result1->fetch_array(1);
                        $result1->free();
                        ?>
                            <tr class="client_table_body">
                                <td><a href="?func=edit_urls&client=<?php echo $client;?>"><?php echo $friendly['friendly'];?></a></td>
                            </tr>
                        <?php
                    }
                    ?>
                    </table>
                    <?php
                }else
                {
                    $cl_func = filter_input(INPUT_GET, 'cl_func', FILTER_SANITIZE_SPECIAL_CHARS);
                    switch($cl_func)
                    {
                        case "copy2_proc":
                            foreach($_POST['copy_clients'] as $copy_client)
                            {
                                $fail = 0;
                                $result->free();
                                $sql = "SELECT * FROM `".$copy_client."_links`";
                                $result = $conn->query($sql, 1);
                                $links = array(); #get list of URLS from Client that you want to copy to

                                while($client_links = $result->fetch_array(1))
                                {
                                    $links[] = $client_links['url']."~".$client_links['refresh'];
                                }
                                @$result->free();
                                #lets get its friendly name
                                $sql = "SELECT friendly FROM `friendly` where `client` like '$copy_client'";
                                $result = $conn->query($sql, 1);
                                $friendly = $result->fetch_array(1);
                                $friend = $friendly['friendly'];
                                if(!@is_null($links[0]))
                                {
                                    $name = "Backup of URLS for $friend on ".date("F j, Y \a\t g:i a");
                                    $imp_links = implode("||", $links);
                                    $result->free();
                                    $sql = "INSERT INTO `archive_links` (`id`, `client`, `urls`, `name`, `details`, `date`) VALUES ('', '$copy_client', '$imp_links', '$name', 'Automated backup.', '".time()."')";
                                    if($result = $conn->query($sql))
                                    {
                                        echo "URLs for Client: $friend have been backed up.<br /><br />\r\n";
                                    }else
                                    {
                                        echo "URLs for Client: $friend have <u><b>NOT</b></u> been backed up.<br /><br />\r\n";
                                        $fail = 1;
                                    }
                                }else
                                {
                                    echo "Client: $friend Does not have any URLs yet.<br /><br />";
                                }
                                if(!$fail)
                                {
                                    $ids = explode("|", $_POST['urls']);
                                    if(is_object($result)){@$result->free();}
                                    $sql = "TRUNCATE TABLE `".$copy_client."_links`";
                                    if(!$conn->query($sql)){echo "Error Truncating table<br />".$conn->error;}
                                    foreach($ids as $id)
                                    {
                                        echo "Start Copy of ID: $id for Client: $friend<br />";
                                        $sql = "SELECT * FROM `".$client_get."_links` where `id` like '$id'";
                                        if(is_object($result)){@$result->free();}
                                        $result = $conn->query($sql, 1);
                                        $copy_link = $result->fetch_array(1);
                                        $sql = "INSERT INTO `".$copy_client."_links` (`id`, `url`, `disabled`, `refresh`) VALUES ( '', '".$copy_link['url']."', '0', '".$copy_link['refresh']."')";                                    @$result->free();
                                        if(!$conn->query($sql))
                                        {
                                            echo "Failed to copy URL [$id] to client: $friend.<br /><br />";
                                        }else
                                        {
                                            echo "Copied URL [$id] to Client: $friend.<br /><br />";
                                        }
                                    }
                                }else
                                {
                                    echo "URLs for Client: $friend have <u><b>NOT</b></u> been copied.<br /><br />\r\n";
                                }
                                ?>
                   <script>
                        setTimeout("location.href = '<?php echo  $admin_url;?>admin/index.php?func=view_client&client=<?php echo $client_get;?>'",<?php echo $page_timeout;?>);
                    </script>
                                <?php
                                echo "---------------<br />";
                            }
                            break;
                        case "edit_proc":
                            if(@$_POST['copy2'])
                                {
                                    ?>
                    <form name="client_copy" action="?func=edit_urls&client=<?php echo $client_get;?>&cl_func=copy2_proc" method="POST">
                    <table>
                        <tr>
                            <th>Choose Clients to Copy URLs to:</th>
                        </tr>
                        <tr>
                            <td>
                                <select name="copy_clients[]" style="width:100%;" size="10" multiple="multiple">
                            <?php
                            $result->free();
                            $sql = "SELECT * FROM `friendly` where `client` NOT LIKE '$client_get'";
                            $result = $conn->query($sql, 1);
                            while($all_clients = $result->fetch_array(1))
                            {
                                ?><option value="<?php echo $all_clients['client'];?>"><?php echo $all_clients['friendly'];?></option><?php
                            }
                            $urls_imp = implode("|", $_POST['urls']);
                            ?>
                                </select>
                                <input type="hidden" name="urls" value="<?php echo $urls_imp; ?>">
                            </td>
                        </tr>
                        <tr>
                            <td align="center">
                                <input type='submit' name="submit" value='submit'>
                            </td>
                        </tr>
                    </table>
                    </form>
                            <?php
                                }
                                if(@$_POST['save_list'])
                                {
                                    $urls_imp = implode("|", $_POST['urls']);
                                    ?>
                    <form name="save_new" action="?func=edit_urls&client=<?php echo $client_get;?>&cl_func=save_new" method="POST">
                    <table>
                        <tr>
                            <th>Save to List:</th>
                        </tr>
                        <tr>
                            <td valign="center">
                                Name:
                            </td>
                            <td>
                                <input type="text" name="name" value="">
                                <input type="hidden" name="urls" value="<?php echo $urls_imp; ?>">
                            </td>
                        </tr>
                        <tr>
                            <td valign="center">
                                Details:
                            </td>
                            <td>
                                <textarea name="details" cols="40" rows="10"></textarea>
                            </td>
                        </tr>
                        <tr>
                            <td align="center">
                                <input type='submit' name="submit" value='submit'>
                            </td>
                        </tr>
                    </table>
                        <hr />
                    </form>
                    <form name="save_append" action="?func=edit_urls&client=<?php echo $client_get;?>&cl_func=save_append" method="POST">
                    <table>
                        <tr>
                            <th>Append to List:</th>
                        </tr>
                        <tr>
                            <td>
                                <select name="saved" style="width:100%;" size="10">
                            <?php
                            $result->free();
                            $sql = "SELECT * FROM `saved_lists`";
                            $result = $conn->query($sql, 1);
                            while($all_clients = $result->fetch_array(1))
                            {
                                ?><option value="<?php echo $all_clients['id'];?>"><?php echo $all_clients['name'];?></option><?php
                            }
                            $urls_imp = implode("|", $_POST['urls']);
                            ?>
                                </select>
                                <input type="hidden" name="urls" value="<?php echo $urls_imp; ?>">
                            </td>
                        </tr>
                        <tr>
                            <td align="center">
                                <input type='submit' name="submit" value='submit'>
                            </td>
                        </tr>
                    </table>
                    </form>
                            <?php
                                }
                                if(@$_POST['remove'])
                                {
                                    $urls = array();
                                    if(is_array($_POST['urls']))
                                    {
                                        $result->free();
                                        $freindly = gen_friendly($client_get);
                                        foreach($_POST['urls'] as $url)
                                        {
                                            $sql = "SELECT * FROM `".$client_get."_links` WHERE `id` = '$url'";
                                            $result = $conn->query($sql, 1);
                                            if($conn->error != "")
                                            {
                                                echo "URL Does not Exsist any more.<br />";
                                                continue;
                                            }
                                            $link = $result->fetch_array(1);
                                            $result->free();
                                            $sql = "DELETE FROM `".$client_get."_links` WHERE `id` = '$url'";
                                            if($conn->query($sql))
                                            {
                                                echo "Removed Link [$url] from ($freindly)'s list.<br />";
                                                $urls[] = $link['url']."~".$link['refresh'];
                                            }else
                                            {
                                                echo "Failed to Remove Link [$url] from ($freindly)'s list.<br />\r\n".$conn->error;
                                            }
                                        }
                                    }else
                                    {
                                        $result->free();
                                        $url = addslashes($_POST['urls']);
                                        $sql = "SELECT * FROM `".$client_get."_links` WHERE `id` = '$url'";
                                        $result = $conn->query($sql, 1);
                                        $link = $result->fetch_array(1);

                                        $result->free();
                                        $sql = "DELETE FROM `".$client_get."_links` WHERE `id` = '$url'";
                                        if($conn->query($sql))
                                        {
                                            echo "Removed Link [$url] from ($freindly)'s list.<br />";
                                            $urls[] = $link['url']."~".$link['refresh'];
                                        }else
                                        {
                                            echo "Failed to Remove Link [$url] from ($freindly)'s list.<br />\r\n".$conn->error;
                                        }
                                    }
                                    if(is_null($urls))
                                    {
                                        echo "No URLS were deleted, none to back up.<br />";
                                        ?>
                   <script>
                        setTimeout("location.href = '<?php echo $admin_url;?>admin/index.php?func=view_client&client=<?php echo $client_get;?>'",<?php echo $page_timeout;?>);
                    </script>
                                        <?php
                                    }else
                                    {
                                        $url_imp = implode("|", $urls);
                                        $time = time();
                                        $name = "Automated Backup on ".date("F j, Y, g:i a");
                                        $details = "Automated Backup of removed URLs $client_get";
                                        $sql = "INSERT INTO `archive_links` (`id`, `client`, `urls`, `name`, `details`, `date`) VALUES ('', '$client_get', '$url_imp', '$name', '$details', '$time')";
                                        if($conn->query($sql))
                                        {
                                            echo "Backed up Links for ($client_get).";
                                    ?>
                    <script>
                        setTimeout("location.href = '<?php echo $admin_url;?>admin/index.php?func=view_client&client=<?php echo $client_get;?>'",<?php echo $page_timeout;?>);
                    </script>
                                    <?php
                                        }else
                                        {
                                            echo "Failed to Back up Links for ($client_get).<br />\r\n".$conn->error;
                                        }
                                    }
                                }
                                if($_POST['refresh'])
                                {
                                    $URLid = $_POST['URLid'];
                                    $refresh = $_POST['refresh_time'];
                                    foreach($URLid as $key=>$id)
                                    {
                                        @$result->free();
                                        $sql = "UPDATE `".$client_get."_links` set `refresh` = '".$refresh[$key]."' WHERE `id` = '$id'";
                                        echo $sql."<br />";
                                        if($conn->query($sql, 1))
                                        {
                                            echo "Updated URL [$id] Refresh Time on Client ($client_get).<br />\r\n";
                                        }else
                                        {
                                            echo "Failed to update URL [$id] status on Client ($client_get).<br />\r\n".$conn->error;
                                        }
                                    }
                                    ?>
                                <script>
                                    setTimeout("location.href = '<?php echo  $admin_url;?>admin/index.php?func=view_client&client=<?php echo $client_get;?>'",<?php echo $page_timeout;?>);
                                </script>
                                    <?php
                                }
                            break;
                        case "add_url_batch":
                            $client_get = filter_input(INPUT_GET, 'client', FILTER_SANITIZE_SPECIAL_CHARS);
                            $urls = filter_input(INPUT_POST, 'URLS', FILTER_SANITIZE_SPECIAL_CHARS);
                            $refresh = filter_input(INPUT_POST, 'refresh', FILTER_SANITIZE_SPECIAL_CHARS);
                            $url_exp = explode("&#13;&#10;", $urls);
                            $i=0;
                            $result->free();
                            foreach($url_exp as $url_)
                            {
                                $url_ = trim($url_);
                                $sql = "INSERT INTO `".$client_get."_links` (`id`, `url`, `disabled`, `refresh`) VALUES ('', '$url_', '0', '$refresh')";
                                if($conn->query($sql))
                                {
                                    echo "Added: $url_<br />\r\n";
                                    $i++;
                                }else
                                {
                                    echo "Failed to add URL....<br />".$conn->error;
                                }
                            }
                            $sql = "SELECT friendly FROM `friendly` WHERE `client` like '$client_get'";
                            $result = $conn->query($sql, 1);
                            $friendly = $result->fetch_array(1);
                            if($i > 0)
                            {
                                echo "Added ($i) New URL for Client. (".$friendly['friendly'].")<br />";
                                ?>
                    <script>
                        setTimeout("location.href = '<?php echo  $admin_url;?>admin/index.php?func=view_client&client=<?php echo $client_get;?>'",<?php echo $page_timeout;?>);
                    </script>
                                <?php
                            }else
                            {
                                echo "None Passed.... :-(";
                            }
                            break;
                        case "save_new":
                            $name = filter_input(INPUT_POST, 'name', FILTER_SANITIZE_SPECIAL_CHARS);
                            $url_imp = filter_input(INPUT_POST, 'urls', FILTER_SANITIZE_SPECIAL_CHARS);
                            $details = filter_input(INPUT_POST, 'details', FILTER_SANITIZE_SPECIAL_CHARS);
                            $url_exp = explode("|", $url_imp);
                            $result->free();
                            $links = array();
                            foreach($url_exp as $id)
                            {
                                $sql = "SELECT * FROM `".$client_get."_links` WHERE `id` like '$id'";
                                $result = $conn->query($sql, 1);
                                $link = $result->fetch_array(1);
                                $links[] = $link['url'].'~'.$link['refresh'];
                                $result->free();
                            }
                            $urls_imp = implode("|", $links);
                            $time = time();
                            $sql = "INSERT INTO `saved_lists` (`id`, `urls`, `name`, `details`, `date`) VALUES ('', '$urls_imp', '$name', '$details', '$time')";
                            if($conn->query($sql))
                            {
                                echo "Saved List. ($name)";
                                ?>
                    <script>
                        setTimeout("location.href = '<?php echo  $admin_url;?>admin/index.php?func=view_client&client=<?php echo $client_get;?>'",<?php echo $page_timeout;?>);
                    </script>
                                <?php
                            }else
                            {
                                echo "Failed to save list....<br />".$conn->error;
                            }
                            break;
                        case "save_append":
                            $urls_imp = filter_input(INPUT_POST, 'urls', FILTER_SANITIZE_SPECIAL_CHARS);
                            $saved = filter_input(INPUT_POST, 'saved', FILTER_SANITIZE_SPECIAL_CHARS);

                            $sql = "SELECT * FROM `saved_lists` WHERE `id` like '$saved'";
                            $result = $conn->query($sql,1);
                            $saved_array = $result->fetch_array(1);

                            $urls_imp = $saved_array['urls']."|".$urls_imp;

                            $result->free();
                            $sql = "UPDATE `saved_lists` SET `urls`='$urls_imp', `date`='$time' WHERE `id` = '$saved'";
                            if($conn->query($sql))
                            {
                                echo "Updated List. ($name)";
                                ?>
                    <script>
                        setTimeout("location.href = ' $proto.<?php echo  $admin_url;?>admin/index.php?func=view_client&client=<?php echo $client_get;?>'",<?php echo $page_timeout;?>);
                    </script>
                                <?php
                            }else
                            {
                                echo "Failed to update list.<br />".$conn->error;
                            }
                            break;
                        case "restore":
                            check_archives($client_get);
                            $urls_imp = filter_input(INPUT_POST, 'urls', FILTER_SANITIZE_SPECIAL_CHARS);
                            $url_exp = explode("|", $urls_imp);
                            $result->free();
                            $friendly = gen_friendly($client_get);
                            $sql = "SELECT * FROM `".$client_get."_links`";
                            $result = $conn->query($sql, 1);
                            while($link = $result->fetch_array(1))
                            {
                                $sql = "DELETE FROM `".$client_get."_links` WHERE `id` = '".$link['id']."'";
                                $mlink = mysqli_connect($server, $username, $password, $db);
                                if(mysqli_query($mlink, $sql))
                                {
                                    echo "Removed Link [".$link['id']."] from ($friendly)'s list.<br />";
                                    $urls[] = $link['url']."~".$link['refresh'];
                                }else
                                {
                                    echo "Failed to Remove Link [".$link['id']."] from ($friendly)'s list.<br />\r\n".mysqli_error($link);
                                }
                            }
                            if(!@is_null($urls))
                            {
                                $url_imp = implode("|", $urls);
                                $time = time();
                                $name = "Automated Backup on ".date("F j, Y, g:i a");
                                $details = "Automated Backup of removed URLs $friendly";
                                $sql = "INSERT INTO `archive_links` (`id`, `client`, `urls`, `name`, `details`, `date`) VALUES ('', '$client_get', '$url_imp', '$name', '$details', '$time')";
                                if($conn->query($sql))
                                {
                                    echo "Backed up Links for ($friendly).";
                                }else
                                {
                                    echo "Failed to Back up Links for ($friendly).<br />\r\n".$conn->error;
                                    $fail = 1;
                                }
                            }else
                            {
                                echo "No need to archive, no URLs for client.";
                            }
                            if(!@$fail)
                            {
                                if($conn->query("TRUNCATE TABLE `".$client_get."_links`"))
                                {
                                    foreach($url_exp as $data)
                                    {
                                        $data_exp = explode("~", $data);
                                        $url = $data_exp[0];
                                        $refresh = $data_exp[1];
                                        $sql = "INSERT INTO `".$client_get."_links` (`id`, `url`,`disabled`, `refresh`) VALUES ('', '$url', '0', '$refresh')";
                                        if($conn->query($sql))
                                        {
                                            echo "Added URL<br />\r\n";
                                        }else
                                        {
                                            echo "Failed to Add URL<br />\r\n";
                                        }
                                    }
                                    ?>
                    <script>
                        setTimeout("location.href = '<?php echo  $admin_url;?>admin/index.php?func=view_client&client=<?php echo $client_get;?>'",<?php echo $page_timeout;?>);
                    </script>
                                    <?php
                                }else
                                {
                                    echo "Failed to truncate.<br />".$conn->error;
                                }
                            }
                            break;
                        case "remove":
                            $id = filter_input(INPUT_POST, 'id', FILTER_SANITIZE_SPECIAL_CHARS);
                            $sql = "DELETE FROM `saved_lists` WHERE `id` = '$id'";
                            $result->free();
                            if($conn->query($sql))
                            {
                                echo "Removed Saved List";
                               ?>
            <script>
                setTimeout("location.href = '<?php echo  $admin_url;?>admin/index.php?func=view_client&client=<?php echo $client_get;?>'",<?php echo $page_timeout;?>);
            </script>
                            <?php
                            }else
                            {
                                echo "Failed to Removed Saved List.<br />\r\n".$conn->error;
                            }
                            break;
                        default:
                             echo "ummmm....... O_o";
                            break;
                    }
                }
            }else
            {
                echo "Ummm, you shouldn't be here.. I think you should leave before the droids come. O_o";
            }
            break;
        case "edit_emerg":
            if($perms['edit_emerg'])
            {
                ?>
                <script type="text/javascript">
                function SetAllCheckBoxes(FormName, FieldName, CheckValue)
                {
                        if(!document.forms[FormName])
                                return;
                        var objCheckBoxes = document.forms[FormName].elements[FieldName];
                        if(!objCheckBoxes)
                                return;
                        var countCheckBoxes = objCheckBoxes.length;
                        if(!countCheckBoxes)
                                objCheckBoxes.checked = CheckValue;
                        else
                                // set the check value for all check boxes
                                for(var i = 0; i < countCheckBoxes; i++)
                                        objCheckBoxes[i].checked = CheckValue;
                }
                </script>
                    <?php
                    $result->free();
                    $result = $conn->query("SELECT `emerg` FROM `settings` LIMIT 1", 1);
                    $settings = $result->fetch_array(1);
                    ?>
                <table border="1px" width="100%">
                    <tr class="client_table_head">
                        <th colspan="6">
                            <form name="emerg_toggle" action="?func=emerg_set" method="POST">
                                <input type="hidden" name="toggle" value="<?php if(!$settings['emerg']){echo '1';}else{ echo '0';}?>">
                                <input type="submit" style="font-size:18;" value="<?php if(!$settings['emerg']){echo 'Enable';}else{ echo 'Disable';}?> Global Emergency Messages?">
                                <br /><font size="4">This will disable normal messages on all Clients.</font>
                            </form>
                        </th>
                    </tr>
                </table>
                <hr />
                <table border="1px" width="100%">
                    <tr class="client_table_head">
                        <th colspan="6">
                            Edit Emergency Messages for all Clients
                        </th>
                    </tr>
                    <tr class="client_table_head">
                        <th width="50px">Enabled?</th><th width="700px">URL</th><th width="90px">Refresh Time</th><th width="90px">Options</th>
                    </tr>
                    <?php
                    $link = mysqli_connect($server, $username, $password, $db);
                    $sql = "SELECT * FROM `emerg`";
                    $result1 = mysqli_query($link,$sql);
                    if(mysqli_num_rows($result1) > 0)
                    {
                        ?><form name="client_edit" action="?func=update_emerg" method="POST"><?php
                        while($emerg_links = mysqli_fetch_array($result1))
                        {
                            ?>
                    <tr class="client_table_body">
                        <td align="center">
                            <?php if($emerg_links['enabled']){echo "&#x2713;";}else{echo "&#x2717;";}?>
                        </td>
                        <td>
                            <?php 
                            echo '<a class="links" href="'.$emerg_links['url'].'" target="_blank">'.$emerg_links['url'].'</a>';
                            $parse_url = parse_url($emerg_links['url']);
                            if(str_replace("/","",$host) == $parse_url['host'])
                            {
                                $exp_url = explode("?", html_entity_decode($emerg_links['url']));
                                $query_url = html_entity_decode($exp_url[1]);

                                $query_ = array();
                                $exp = explode('&',$query_url);
                                foreach($exp as $e)
                                {
                                    $qur = explode("=", $e);
                                    $query_[$qur[0]] = $qur[1];
                                }
                                $id = $query_['id'];
                                switch($query_['type'])
                                {
                                    case "rss":
                                        $sql = "SELECT * FROM `rss_feeds` WHERE `id` = '$id'";
                                        $result2 = mysqli_query($link,$sql);
                                        $rss = mysqli_fetch_array($result2);
                                        echo " (".$rss['name'].")";
                                        break;
                                    case "c_message":
                                        $sql = "SELECT * FROM `c_messages` WHERE `id` = '$id'";
                                        $result2 = mysqli_query($link,$sql);
                                        $c_mesg = mysqli_fetch_array($result2);
                                        echo " (".$c_mesg['name'].")";
                                        break;
                                }
                            }
                            ?>
                        </td>
                        <td align="center">
                                <input type="hidden" name="url_id[]" value="<?php echo $emerg_links['id'];?>">
                                <input type="text" name="refresh_t[]" style="width: 49px" value="<?php echo $emerg_links['refresh'];?>">
                        </td>
                        <td align="center">
                                <input type="hidden" name="url_t[]" value="<?php if($emerg_links['enabled']){echo "0";}else{echo "1";}?>">
                                <input type="checkbox" name="urls[]" value="<?php echo $emerg_links['id'];?>">
                        </td>
                    </tr>
                        <?php
                        }
                    }else
                    {
                        ?>
                    <tr>
                        <td align="center" colspan="5">
                            No URLS, add some.
                        </td>
                    </tr>
                        <?php
                    }
                    ?>
                    <tr class="client_table_tail">
                        <td colspan="2"></td>
                        <td align="center">
                            <input type="submit" name="refresh" value="Update">
                        </td>
                        <td>
                            <table align="center">
                                <tr>
                                    <td align="center">
                                        <input type="submit" name="delete" value="Delete"><br />
                                        <input type="submit" name="toggle" value="Enable/Disable">
                                    </td>
                                    <td align="center">
                                        <input type="button" onclick="SetAllCheckBoxes('client_edit', 'urls[]', true);" value="Check"><br />
                                        <input type="button" onclick="SetAllCheckBoxes('client_edit', 'urls[]', false);" value="Uncheck">
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>
                    </form>
                    <tr class="client_table_tail">
                        <td colspan="6">
                            <form name="save_new" action="?func=add_emerg" method="POST">
                            <table>
                                <tr>
                                    <td valign="center">
                                        URLs:
                                    </td>
                                    <td>
                                        <textarea name="URLS" cols="80" rows="10">http://</textarea>
                                    </td>
                                </tr>
                                <tr>
                                    <td valign="center">
                                        Refresh Times for all:
                                    </td>
                                    <td>
                                        <input type="text" name="refresh" value="<?php echo $refresh; ?>">
                                    </td>
                                </tr>
                                <tr>
                                    <td></td>
                                    <td>
                                        <input type='submit' value='Add URLs'>
                                    </td>
                                </tr>
                            </table>
                            </form>
                        </td>
                    </tr>
                </table>
                <?php
            }else
            {
                echo "Ummm, you shouldn't be here.. I think you should leave before the droids come. O_o";
            }
            break;
        case "emerg_set":
            if($perms['edit_emerg'])
            {
                $toggle = filter_input(INPUT_POST, 'toggle', FILTER_SANITIZE_SPECIAL_CHARS);
                $result->free();
                $sql = "UPDATE `settings` set `emerg` = '$toggle' WHERE `id` = '1'";
                if($conn->query($sql, 1))
                {
                    if($led_blink){emerg_blink($toggle);}
                    if($toggle){echo "Enabled";}else{echo "Disabled";}
                    echo " Global Emergency Messages";
                    ?>
                    <script>
                        setTimeout("location.href = '<?php echo $admin_url;?>admin/index.php?func=edit_emerg'",<?php echo $page_timeout;?>);
                    </script>
                    <?php
                }else
                {
                    echo "Failed to ";
                    if($toggle){echo "Enabled";}else{echo "Disabled";}
                    echo "Global Emergency Messages<br />\r\n".$conn->error;
                }
            }else
            {
                echo "Ummm, you shouldn't be here.. I think you should leave before the droids come. O_o";
            }
            break;
        case "update_emerg":
            if($perms['edit_emerg'])
            {
                if(@$_POST['toggle'] === 'Enable/Disable')
                {
                    if(!@$_POST['urls'])
                    {
                        ?>
                        <script>
                            setTimeout("location.href = '<?php echo $admin_url;?>admin/index.php?func=edit_emerg'",<?php echo $page_timeout;?>);
                        </script>
                        <?php
                        break;
                    }
                    $url_id = filter_input(INPUT_POST, 'url_t', FILTER_SANITIZE_SPECIAL_CHARS);
                    $refresh = filter_input(INPUT_POST, 'refresh', FILTER_SANITIZE_SPECIAL_CHARS);
                    $result->free();
                    foreach($_POST['urls'] as $key=>$id)
                    {
                        $url_t = $_POST['url_t'][$key];
                        $sql = "UPDATE `emerg` set `enabled` = '$url_t' WHERE `id` = '$id'";
                        if($conn->query($sql, 1))
                        {
                            echo "Updated URL [$id].<br />";
                            ?>
                            <script>
                                setTimeout("location.href = '<?php echo $admin_url;?>admin/index.php?func=edit_emerg'",<?php echo $page_timeout;?>);
                            </script>
                            <?php
                        }else
                        {
                            echo "Failed to updated URL [$id].<br />\r\n".$conn->error;
                        }
                    }
                }elseif(@$_POST['delete'] === 'Delete')
                {
                    if(!@$_POST['urls'])
                    {
                        ?>
                        <script>
                            setTimeout("location.href = '<?php echo $admin_url;?>admin/index.php?func=edit_emerg'",<?php echo $page_timeout;?>);
                        </script>
                        <?php
                        break;
                    }
                    $result->free();
                    foreach($_POST['urls'] as $key=>$id)
                    {
                        $sql = "DELETE FROM `emerg` WHERE `id` = '$id'";
                        if($conn->query($sql, 1))
                        {
                            echo "Removed [$id].<br />";
                            ?>
                            <script>
                                setTimeout("location.href = '<?php echo $admin_url;?>admin/index.php?func=edit_emerg'",<?php echo $page_timeout;?>);
                            </script>
                            <?php
                        }else
                        {
                            echo "Failed to Remove [$id].<br />\r\n".$conn->error;
                        }
                    }
                }elseif(@$_POST['refresh'] === 'Update')
                {
                    $result->free();
                    foreach($_POST['url_id'] as $key=>$id)
                    {
                        $refresh = $_POST['refresh_t'][$key];
                        $sql = "UPDATE `emerg` set `refresh` = '$refresh' WHERE `id` = '$id'";
                        echo $sql."<br/>";
                        if($conn->query($sql, 1))
                        {
                            echo "Updated URL [$id] Refresh Time.";
                            ?>
                            <script>
                                setTimeout("location.href = '<?php echo $admin_url;?>admin/index.php?func=edit_emerg'",<?php echo $page_timeout;?>);
                            </script>
                            <?php
                        }else
                        {
                            echo "Failed to updated URL [$id] status<br />\r\n".$conn->error;
                        }
                    }
                }
            }else
            {
                echo "Ummm, you shouldn't be here.. I think you should leave before the droids come. O_o";
            }
            break;
        case "add_emerg":
            if($perms['edit_emerg'])
            {
                $urls = filter_input(INPUT_POST, 'URLS', FILTER_SANITIZE_SPECIAL_CHARS);
                $refresh = filter_input(INPUT_POST, 'refresh', FILTER_SANITIZE_SPECIAL_CHARS);
                $url_exp = explode("&#13;&#10;", $urls);
                $i=0;
                $result->free();
                foreach($url_exp as $url_)
                {
                    $url_ = trim($url_);
                    $sql = "INSERT INTO `emerg` (`id`, `url`, `enabled`, `refresh`) VALUES ('', '$url_', '1', '$refresh')";
                    if($conn->query($sql))
                    {
                        echo "Added: $url_<br />\r\n";
                        $i++;
                    }else
                    {
                        echo "Failed to add URL....<br />".$conn->error;
                    }
                }
                if($i > 0)
                {
                    echo "Added ($i) New Emergency URL's<br />";
                    ?>
        <script>
            setTimeout("location.href = '<?php echo $admin_url;?>admin/index.php?func=edit_emerg'",<?php echo $page_timeout;?>);
        </script>
                    <?php
                }else
                {
                    echo "None Passed.... :-(";
                }
            }else
            {
                echo "Ummm, you shouldn't be here.. I think you should leave before the droids come. O_o";
            }
            break;
        case "rename_client":
            if($perms['edit_urls'])
            {
                $client_get = filter_input(INPUT_GET, 'client', FILTER_SANITIZE_SPECIAL_CHARS);
                $client_name = filter_input(INPUT_POST, 'client_name', FILTER_SANITIZE_SPECIAL_CHARS);
                $client_id = filter_input(INPUT_POST, 'client_id', FILTER_SANITIZE_SPECIAL_CHARS);
                $result->free();
                $sql = "UPDATE `friendly` SET `friendly` = '$client_name' WHERE `id` = '$client_id'";
                if($conn->query($sql, 1))
                {
                    echo "Renamed Client [$client_id] $client_name.";
                    ?>
                    <script>
                        setTimeout("location.href = '<?php echo $admin_url;?>admin/index.php?func=view_client&client=<?php echo $client_get;?>'",<?php echo $page_timeout;?>);
                    </script>
                    <?php
                }else
                {
                    echo "Failed to Rename Client [$client_id]<br />\r\n".$conn->error;
                }
            }else
            {
                echo "Ummm, you shouldn't be here.. I think you should leave before the droids come. O_o";
            }
            break;
        case "client_led_set":
            $cl_id = filter_input(INPUT_POST, 'cl_id', FILTER_SANITIZE_SPECIAL_CHARS);
            $led_id = filter_input(INPUT_POST, 'cl_led_id', FILTER_SANITIZE_SPECIAL_CHARS);
            $result->free();
            $sql = "UPDATE `allowed_clients` SET `led` = '$led_id' WHERE `client_name` = '$cl_id'";
            $result = $conn->query($sql);
            if($result)
            {
                echo "Updated LED Group to #$led_id<br/>";
                ?>
                <script>
                    setTimeout("location.href = '<?php echo $admin_url;?>admin/index.php?func=view_client&client=<?php echo $cl_id;?>'",<?php echo $page_timeout;?>);
                </script>
                <?php
            }else
            {
                echo "failed update<br/>";
            }
            break;
        case "view_client":
            if($perms['edit_urls'])
            {
                $client_get = filter_input(INPUT_GET, 'client', FILTER_SANITIZE_SPECIAL_CHARS);
                ?>
                <script type="text/javascript">
                function SetAllCheckBoxes(FormName, FieldName, CheckValue)
                {
                        if(!document.forms[FormName])
                                return;
                        var objCheckBoxes = document.forms[FormName].elements[FieldName];
                        if(!objCheckBoxes)
                                return;
                        var countCheckBoxes = objCheckBoxes.length;
                        if(!countCheckBoxes)
                                objCheckBoxes.checked = CheckValue;
                        else
                                // set the check value for all check boxes
                                for(var i = 0; i < countCheckBoxes; i++)
                                        objCheckBoxes[i].checked = CheckValue;
                }
                function expandcontract(tbodyid,ClickIcon)
                {
                        if (document.getElementById(ClickIcon).innerHTML == "+")
                        {
                                document.getElementById(tbodyid).style.display = "";
                                document.getElementById(ClickIcon).innerHTML = "-";
                        }else{
                                document.getElementById(tbodyid).style.display = "none";
                                document.getElementById(ClickIcon).innerHTML = "+";
                        }
                }
                </script>
                <?php
                $result->free();
                $sql = "SELECT * FROM `friendly` WHERE `client` like '$client_get'";
                $result = $conn->query($sql,1);
                $friendly = $result->fetch_array(1);
                ?>
                <table border="1px" align="center">
                    <tr valign="center" class="client_table_head">
                        <td>
                            Client Name:
                        </td>
                        <td>
                            <table width="100%">
                                <tr>
                                    <td width="80%">
                                    <br />
                                        <form name="client_rename" action="?func=rename_client&client=<?php echo $client_get;?>" method="POST">
                                            <input type="text" name="client_name" style="width:400px;" value="<?php echo $friendly['friendly']; ?>"/>
                                            <input type="hidden" name="client_id" value="<?php echo $friendly['id']; ?>"/>
                                            <input type="submit" value="Rename"/>
                                        </form>
                                    </td>
                                    <td>
                                        <?php
                                        if($led_blink)
                                        {
                                            $result->free();
                                            $sql = "SELECT led FROM `allowed_clients` WHERE `client_name` like '$client_get'";
                                            $result = $conn->query($sql,1);
                                            $led = $result->fetch_array(1);
                                        ?>
                                        LED Group:<br/>
                                        <form name="client_led" action="?func=client_led_set" method="POST">
                                            <input type="hidden" name="cl_id" value="<?php echo $client_get; ?>"/>
                                            <select name="cl_led_id" onchange='this.form.submit()'>
                                                <option value="1" <?php if($led['led'] == '1')echo "selected='yes'"; ?>>LED 1</option>
                                                <option value="2" <?php if($led['led'] == '2')echo "selected='yes'"; ?>>LED 2</option>
                                                <option value="3" <?php if($led['led'] == '3')echo "selected='yes'"; ?>>LED 3</option>
                                                <option value="4" <?php if($led['led'] == '4')echo "selected='yes'"; ?>>LED 4</option>
                                                <option value="5" <?php if($led['led'] == '5')echo "selected='yes'"; ?>>LED 5</option>
                                                <option value="6" <?php if($led['led'] == '6')echo "selected='yes'"; ?>>LED 6</option>
                                            </select>
                                        </form>
                                        <?php
                                        }
                                        ?>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>
                    <tr class="client_table_body">
                        <td>
                            Client URL:
                        </td>
                        <td>
                            <a class="links" href="<?php echo $reg_url.'index.php?id='.$friendly['client'];?>" target="_blank"><?php echo $reg_url.'index.php?id='.$friendly['client'];?></a>
                        </td>
                    </tr>
                </table>
                <hr />
                <form name="client_edit" action="?func=edit_urls&client=<?php echo $client_get;?>&cl_func=edit_proc" method="POST">
                <table border="1px" width="100%">
                    <tr class="client_table_head">
                        <th colspan="4">
                            Messages
                        </th>
                    </tr>
                    <tr class="client_table_head">
                        <th>URL</th><th>Set Refresh</th><th width="120px">Select</th>
                    </tr>
                    <?php
                    $link = mysqli_connect($server, $username, $password, $db);
                    $sql = "SELECT * FROM `".$client_get."_links` ORDER BY `url` ASC";
                    $result1 = mysqli_query($link,$sql);
                    if(mysqli_num_rows($result1) > 0)
                    {
                        while($links = mysqli_fetch_array($result1))
                        {
                            ?>
                    <tr class="client_table_body">
                        <td>
                            <?php 
                            echo '<a class="links" href="'.$links['url'].'" target="_blank">'.$links['url'].'</a>';
                            $parse_url = parse_url($links['url']);
                            if(str_replace("/","",$host) == $parse_url['host'])
                            {
                                $exp_url = explode("?", html_entity_decode($links['url']));
                                $query_url = $exp_url[1];
                                $query_ = array();
                                $exp = explode('&',$query_url);
                                foreach($exp as $e)
                                {
                                    $qur = explode("=", $e);
                                    $query_[$qur[0]] = $qur[1];
                                }
                                $id = $query_['id'];
                                
                                switch($query_['type'])
                                {
                                    case "rss":
                                        $sql = "SELECT * FROM `rss_feeds` WHERE `id` = '$id'";
                                        $result2 = mysqli_query($link,$sql);
                                        $rss = mysqli_fetch_array($result2);
                                        echo " (".$rss['name'].")";
                                        break;
                                    case "c_message":
                                        $sql = "SELECT * FROM `c_messages` WHERE `id` = '$id'";
                                        $result2 = mysqli_query($link,$sql);
                                        $c_mesg = mysqli_fetch_array($result2);
                                        echo " (".$c_mesg['name'].")";
                                        break;
                                }
                            }
                            ?>
                        </td>
                        <td align="center">
                            <input type='text' style="width:45px;" name="refresh_time[]" value='<?php echo $links['refresh'];?>'>
                            <input type="hidden" name="URLid[]" value="<?php echo $links['id'];?>">
                        </td>
                        <th><input type="checkbox" name="urls[]" value="<?php echo $links['id'];?>"></th>

                    </tr>
                            <?php
                        }
                    }else
                    {
                        ?>
                    <tr class="client_table_body">
                        <td align="center" colspan="4">There are no URLs added yet.</td>
                    </tr>
                        <?php
                    }
                    ?>
                    <tr class="client_table_tail">
                        <td align="center">
                            <input type='submit' name="copy2" value='Copy'>
                            <input type='submit' name="save_list" value='Save To List'>
                            <input type='submit' name="remove" value='Remove'>
                        </td>
                        <td align="center">
                            <input type='submit' name="refresh" value='Set all'>
                        </td>
                        <td align="center">
                            <input type="button" onclick="SetAllCheckBoxes('client_edit', 'urls[]', true);" value="Check">
                            <input type="button" onclick="SetAllCheckBoxes('client_edit', 'urls[]', false);" value="Uncheck">
                        </td>
                    </tr>
                    </form>
                    <tr class="client_table_tail">
                        <td colspan="4">
                            <form name="save_new" action="?func=edit_urls&client=<?php echo $client_get;?>&cl_func=add_url_batch" method="POST">
                            <table style="width: 100%">
                                <tr>
                                    <td style="width: 200px" valign="center">
                                        URLs:
                                    </td>
                                    <td>
                                        <textarea name="URLS" rows="10" style="border:1px; solid #999999; width:90%; margin:5px 0; padding:3px;">http://</textarea>
                                    </td>
                                </tr>
                                <tr>
                                    <td valign="center">
                                        Refresh Times for all:
                                    </td>
                                    <td>
                                        <input type="text" name="refresh" value="<?php echo $refresh; ?>">
                                    </td>
                                </tr>
                                <tr>
                                    <td>
                                    </td>
                                    <td>
                                        <input type='submit' value='Add URLs'>
                                    </td>
                                </tr>
                            </table>
                            </form>
                        </td>
                    </tr>
                </table>
                <hr />
                <table border="1px" class="all_tables">
                    <tr class="client_table_head">
                        <th colspan="5">Saved Lists</th>
                    </tr>
                    <tr class="client_table_head">
                        <th>+/-</th><th>Name</th><th>Date</th><th>Options</th>
                    </tr>
                <?php
                $sql = "SELECT * FROM `saved_lists` ORDER by `id` DESC";
                $result->free();
                $result = $conn->query($sql,1);
                $tablerowid = 0;
                while($client_arc = $result->fetch_array(1))
                {
                    ?>
                    <tr class="client_table_body">
                        <td
                            onclick="expandcontract('SavedRow<?php echo $tablerowid;?>','SavedClickIcon<?php echo $tablerowid;?>')"
                            id="SavedClickIcon<?php echo $tablerowid;?>" style="cursor: pointer; cursor: hand;">+</td>
                        <td>
                            <?php echo $client_arc['name'];?>
                        </td>
                        <td>
                            <?php echo date('F j, Y, g:i a', $client_arc['date']);?>
                        </td>
                        <td>
                            <table>
                                <tr>
                                    <td>
                                        <form name="saved" action="?func=edit_urls&client=<?php echo $client_get;?>&cl_func=restore" method="POST">
                                            <input type="hidden" name="urls" value="<?php echo $client_arc['urls']; ?>">
                                            <input type='submit' value='Restore'>
                                        </form>
                                    </td>
                                    <td>
                                        <form name="saved" action="?func=edit_urls&client=<?php echo $client_get;?>&cl_func=remove" method="POST">
                                            <input type="hidden" name="id" value="<?php echo $client_arc['id']; ?>">
                                            <input type='submit' value='Remove'>
                                        </form>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>
                    <?php
                    $exp = explode("|", $client_arc['urls']);
                    ?>
                    <tbody id="SavedRow<?php echo $tablerowid;?>" style="display:none">
                        <tr>
                            <td colspan="4">
                                <table border="1" width="100%">
                        <?php
                        foreach($exp as $url)
                        {
                            ?>
                        <tr class="client_table_body">
                            <td><?php echo $url;?></td>
                        </tr>
                        <?php
                        }
                        ?>
                                </table>
                            </td>
                        </tr>
                    </tbody>
                    <?php
                    $tablerowid++;
                }
                ?>
                </table>
                <hr />
                <table border="1px" class="all_tables">
                    <tr class="client_table_head">
                        <th colspan="4">Clients Archived Links</th>
                    </tr>
                    <tr class="client_table_head">
                        <th>+/-</th><th>Name</th><th>Date</th><th>Options</th>
                    </tr>
                <?php
                $sql = "SELECT * FROM `archive_links` WHERE `client` = '$client_get' ORDER by `date` ASC";
                $result->free();
                $result = $conn->query($sql,1);
                $tablerowid = 0;
                while($client_arc = $result->fetch_array(1))
                {
                    ?>
                    <tr class="client_table_body">
                        <td onclick="expandcontract('Row<?php echo $tablerowid;?>','ClickIcon<?php echo $tablerowid;?>')"
                            id="ClickIcon<?php echo $tablerowid;?>" style="cursor: pointer; cursor: hand;">+</td>
                        <td><?php echo $client_arc['name'];?></td>
                        <td><?php echo date('F j, Y, g:i a', $client_arc['date']);?></td>
                        <td>
                            <table>
                                <tr>
                                    <td>
                                        <form name="saved" action="?func=edit_urls&client=<?php echo $client_get;?>&cl_func=restore" method="POST">
                                            <input type="hidden" name="urls" value="<?php echo $client_arc['urls']; ?>">
                                            <input type='submit' name="copy" value='Restore'>
                                        </form>
                                    </td>
                                    <td>
                                        <form name="saved" action="?func=rm_arc_urls&client=<?php echo $client_get;?>" method="POST">
                                            <input type="hidden" name="id" value="<?php echo $client_arc['id']; ?>">
                                            <input type='submit' name="copy" value='Remove'>
                                        </form>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>
                    <?php
                    $exp = explode("|", $client_arc['urls']);
                    ?>
                    <tbody id="Row<?php echo $tablerowid;?>" style="display:none">
                        <tr>
                            <td colspan="4">
                                <table border="1" width="100%">
                        <?php
                        foreach($exp as $url)
                        {
                            ?>
                        <tr class="client_table_body">
                            <td><?php echo $url;?></td>
                        </tr>
                        <?php
                        }
                        ?>
                                </table>
                            </td>
                        </tr>
                    </tbody>
                    <?php
                    $tablerowid++;
                }
                ?>
                </table>
                <?php
            }else
            {
                echo "Ummm, you shouldn't be here.. I think you should leave before the droids come. O_o";
            }
            break;
        case "rm_arc_urls":
            if($perms['edit_urls'])
            {
                $client_get = filter_input(INPUT_GET, 'client', FILTER_SANITIZE_SPECIAL_CHARS);
                $id = filter_input(INPUT_POST, 'id', FILTER_SANITIZE_SPECIAL_CHARS);
                $sql = "DELETE FROM `archive_links` WHERE `id` = '$id'";
                $result->free();
                if($conn->query($sql))
                {
                    echo "Removed Archived List";
                   ?>
                    <script>
                        setTimeout("location.href = '<?php echo  $admin_url;?>admin/index.php?func=view_client&client=<?php echo $client_get;?>'",<?php echo $page_timeout;?>);
                    </script>
                <?php
                }else
                {
                    echo "Failed to Removed Archived List.<br />\r\n".$conn->error;
                }

            }else
            {
                echo "Ummm, you shouldn't be here.. I think you should leave before the droids come. O_o";
            }
            break;
        case "add_client":
            if($perms['edit_urls'])
            {
                $friendly = filter_input(INPUT_POST, 'friendly', FILTER_SANITIZE_SPECIAL_CHARS);
                $client_ID = md5(rand(0000000000,9999999999));
                $sql = "INSERT INTO `friendly` (`id`, `friendly`, `client`) VALUES ('', '$friendly', '$client_ID')";
                $result->free();
                if($conn->query($sql))
                {
                    $sql = "INSERT INTO `allowed_clients` (`id`, `client_name`) VALUES ('', '$client_ID')";
                    if($conn->query($sql))
                    {
                        $sql = "CREATE TABLE IF NOT EXISTS `".$client_ID."_links` (
      `id` int(255) NOT NULL AUTO_INCREMENT,
      `url` varchar(255) NOT NULL,
      `disabled` tinyint(4) NOT NULL DEFAULT '0',
      `refresh` int(5) NOT NULL DEFAULT '60',
      PRIMARY KEY (`id`),
      UNIQUE (`url`)
    ) ENGINE=MyISAM  DEFAULT CHARSET=utf8 AUTO_INCREMENT=1";
                        if($conn->query($sql))
                        {
                            echo "Created link table for `$friendly`<br />"
                            ?>
                    <script>
                        setTimeout("location.href = '<?php echo $admin_url;?>admin/index.php'",<?php echo $page_timeout;?>);
                    </script>
                            <?php
                        }else
                        {
                            echo "Failed to create link table for `$friendly`<br />".$conn->error;
                        }
                    }else
                    {
                        echo "Failed to insert into `allowed_clients` table<br />".$conn->error;
                    }
                }else
                {
                    echo "Failed to insert into `friendly` table<br />Probably a Duplicate name, check the SQL error below<br />".$conn->error;
                }
            }else
            {
                echo "Ummm, you shouldn't be here.. I think you should leave before the droids come. O_o";
            }
            break;
        case "view_users":
            if($perms['edit_users'])
            {
                ?>
                <table border="1px" width="100%">
                    <tr class="client_table_head">
                        <th>Username</th>
                        <?php
                        if($LDAP)
                        {?>
                        <th>Domain</th><?php
                        }else{ ?>
                        <th>Password</th><?php
                        }
                        ?><th>Permissions</th><th>Options</th>
                    </tr>
                    <?php
                    $sql = "SELECT * FROM allowed_users WHERE `username` NOT LIKE 'unsadmin'";
                    $result->free();
                    $result = $conn->query($sql,1);
                    if($result->num_rows < 1)
                    {
                        while($array = $result->fetch_array(1))
                        {
                        ?>
                    <tr class="client_table_body">
                        <td align="Center">
                                <?php echo $array['username'];?>
                        </td>
                        <?php
                        $link = mysqli_connect($server, $username, $password, $db);
                        $sql = "SELECT id,password FROM `internal_users` WHERE `username` = '".$array['username']."'";
                        $result1 = mysqli_query($link,$sql);
                        $int_usr = mysqli_fetch_array($result1);
                        if(!$int_usr['password'])
                        {?>
                        <td align="Center">
                                <?php echo $array['domain'];?>
                        </td>
                        <?php
                        }else
                        {
                            ?>
                        <td align="Center">
                            <form action="?func=edit_user&set=reset_pwd" method="POST">
                                <input type="hidden" name="id" value="<?php echo $int_usr['id'];?>"/>
                                <input type="password" name="password" value=""/>
                                <input type="submit" value="Reset Password" />
                            </form>
                        </td>
                        <?php
                        }
                        ?>
                        <td width="500px">
                            <table>
                                <tr>
                                    <td align="center">
                                        <form action="?func=edit_user&set=urls" method="POST">
                                            <input type="hidden" name="id" value="<?php echo $array['id'];?>"/>
                                            <input type="hidden" name="edit_urls" value="<?php if($array['edit_urls']){echo "0";}else{echo "1";}?>"/>
                                            <input type="submit" value="<?php if($array['edit_urls']){echo "Deny";}else{echo "Allow";}?> Edit Clients" />
                                        </form>
                                    </td>
                                    <td align="center">
                                        <form action="?func=edit_user&set=emerg" method="POST">
                                            <input type="hidden" name="id" value="<?php echo $array['id'];?>"/>
                                            <input type="hidden" name="edit_emerg" value="<?php if($array['edit_emerg']){echo "0";}else{echo "1";}?>"/>
                                            <input type="submit" value="<?php if($array['edit_emerg']){echo "Deny";}else{echo "Allow";}?> Edit Emergency" />
                                        </form>
                                    </td>
                                    <td align="center">
                                        <form action="?func=edit_user&set=user" method="POST">
                                            <input type="hidden" name="id" value="<?php echo $array['id'];?>"/>
                                            <input type="hidden" name="edit_user" value="<?php if($array['edit_users']){echo "0";}else{echo "1";}?>"/>
                                            <input type="submit" value="<?php if($array['edit_users']){echo "Deny";}else{echo "Allow";}?> Edit Users" />
                                        </form>
                                    </td>
                                </tr>
                                <tr>
                                    <td align="center">
                                        <form action="?func=edit_user&set=c_messages" method="POST">
                                            <input type="hidden" name="id" value="<?php echo $array['id'];?>"/>
                                            <input type="hidden" name="c_messages" value="<?php if($array['c_messages']){echo "0";}else{echo "1";}?>"/>
                                            <input type="submit" value="<?php if($array['c_messages']){echo "Deny";}else{echo "Allow";}?> Custom Messages" />
                                        </form>
                                    </td>
                                    <td align="center">
                                        <form action="?func=edit_user&set=rss_feeds" method="POST">
                                            <input type="hidden" name="id" value="<?php echo $array['id'];?>"/>
                                            <input type="hidden" name="rss_feeds" value="<?php if($array['rss_feeds']){echo "0";}else{echo "1";}?>"/>
                                            <input type="submit" value="<?php if($array['rss_feeds']){echo "Deny";}else{echo "Allow";}?> Rss Feeds" />
                                        </form>
                                    </td>
                                    <td align="center">
                                        <form action="?func=edit_user&set=edit_options" method="POST">
                                            <input type="hidden" name="id" value="<?php echo $array['id'];?>"/>
                                            <input type="hidden" name="edit_options" value="<?php if($array['edit_options']){echo "0";}else{echo "1";}?>"/>
                                            <input type="submit" value="<?php if($array['edit_options']){echo "Deny";}else{echo "Allow";}?> UNS Options" />
                                        </form>
                                    </td>
                                </tr>
                            </table>
                        </td>
                        <td align="Center">
                            <form action="?func=remove_user" method="POST">
                                <input type="hidden" name="id" value="<?php echo $array['id'];?>"/>
                                <input type="submit" value="Remove" />
                            </form>
                        </td>
                    </tr>
                        <?php
                        }
                    }else
                    {
                        ?>
                    <tr class="client_table_body">
                        <td colspan="6" align="center">There are no Users, lets add some</td>
                    </tr>
                        <?php
                    }
                    ?>
                    <tr class="client_table_tail">
                        <td colspan="6" align="Center">
                            <br />
                            <form name="client_add" action="?func=add_user" method="POST">
                            <table border="1px">
                                <tr class="client_table_body">
                                    <td>
                                        Username:
                                    </td>
                                    <td>
                                        <input type="text" name="user_N" />
                                    </td>
                                </tr>
                                <?php
                                if($LDAP)
                                {
                                ?>
                                <tr class="client_table_body">
                                    <td>
                                        Domain:
                                    </td>
                                    <td>
                                        <input type="text" name="domain_N" />
                                    </td>
                                </tr>
                                <?php
                                }else
                                {
                                    ?>
                                <tr class="client_table_body">
                                    <td>
                                        Password:
                                    </td>
                                    <td>
                                        <input type="hidden" name="internal_user" value="internal_user" />
                                        <input name="pwd_N" type="password" />
                                    </td>
                                </tr>
                                <?php
                                }
                                ?>
                                <tr>
                                    <td colspan="2" align="center" class="client_table_body">
                                        <input type="submit" value="Add User" />
                                    </td>
                                </tr>
                            </table>
                            </form>
                            
                            <table border="1px">
                                <tr class="client_table_body">
                                    <td align="Center">
                                        <form action="?func=edit_user&set=reset_pwd" method="POST">
                                            <?php
                                            $result->free();
                                            $sql = "SELECT id FROM `internal_users` WHERE `username` = 'unsadmin' LIMIT 1";
                                            $result = $conn->query($sql,1);
                                            $admusr = $result->fetch_array(1);
                                            ?>
                                            <input type="hidden" name="id" value="<?php echo $admusr['id'];?>" />
                                            <input type="password" name="password" value="" />
                                            <input type="submit" value="Reset Admin Password" />
                                        </form>
                                    </td>
                                    <td>
                                        <form action="?func=toggle_builtin" method="POST">
                                         <?php
                                        $result->free();
                                        $sql = "SELECT * FROM settings LIMIT 1";
                                        $result = $conn->query($sql,1);
                                        $array1 = $result->fetch_array(1);
                                        ?>
                                            <input type="hidden" name="toggle_admin" value="<?php if($array1['built_in_admin']){echo "0";}else{echo "1";}?>"/>
                                            <input type="submit" value="<?php if($array1['built_in_admin']){echo "Disable";}else{echo "Enable";}?> Built in Admin" />
                                        </form>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>
                </table>
                <?php
            }else
            {
                echo "Ummm, you shouldn't be here.. I think you should leave before the droids come. O_o";
            }
            break;
        case "remove_user":
            if($perms['edit_users'])
            {
                $id = filter_input(INPUT_POST, 'id', FILTER_SANITIZE_SPECIAL_CHARS);
                $result->free();
                $sql = "SELECT username,domain FROM `allowed_users` WHERE `id` = '$id' LIMIT 1";
                $result1 = $conn->query($sql,1);
                $array1 = $result1->fetch_array(1);
                $result1->free();
                $sql = "DELETE FROM `allowed_users` WHERE `id` = '$id'";
                if($conn->query($sql,1))
                {
                    if($array1['domain']=='')
                    {
                        $sql = "DELETE FROM `internal_users` WHERE `username` = '".$array1['username']."'";
                        if($conn->query($sql))
                        {
                            echo "Removed Internal user.";
                            ?>
                            <script>
                                setTimeout("location.href = '<?php echo $admin_url;?>admin/index.php?func=view_users'",<?php echo $page_timeout;?>);
                            </script>
                            <?php
                        }else
                        {
                            echo "Failed to Remove Internal User.<br />".$conn->error;
                            break;
                        }
                    }else
                    {
                        echo "Removed User.";
                        ?>
                        <script>
                            setTimeout("location.href = '<?php echo $admin_url;?>admin/index.php?func=view_users'",<?php echo $page_timeout;?>);
                        </script>
                        <?php
                    }
                }else
                {
                    echo "Failed to remove user ($id).<br />\r\n".$conn->error;
                }
            }else
            {
                echo "Ummm, you shouldn't be here.. I think you should leave before the droids come. O_o";
            }
            break;
        case "toggle_builtin":
            if($perms['edit_users'])
            {
                $toggle_admin = filter_input(INPUT_POST, 'toggle_admin', FILTER_SANITIZE_SPECIAL_CHARS);
                $result->free();
                $sql = "UPDATE `settings` SET `built_in_admin` = '$toggle_admin' WHERE `id` = '1'";
                echo $sql."<br />";
                if($conn->query($sql, 1))
                {
                    if($toggle_admin){echo "Disabled";}else{echo "Enabled";}
                    echo " Built in Admin";
                    ?>
            <script>
                setTimeout("location.href = '<?php echo $admin_url;?>admin/index.php?func=view_users'",<?php echo $page_timeout;?>);
            </script> -->
                    <?php
                }else
                {
                    echo "Failed Update.<br />".$conn->error;
                }
            }else
            {
                echo "Ummm, you shouldn't be here.. I think you should leave before the droids come. O_o";
            }
            break;
        case "add_user":
            if($perms['edit_users'])
            {
                $user = filter_input(INPUT_POST, 'user_N', FILTER_SANITIZE_SPECIAL_CHARS);
                $internal_user = @filter_input(INPUT_POST, 'internal_user', FILTER_SANITIZE_SPECIAL_CHARS);
                $result->free();
                if(!$internal_user)
                {
                    $domain = @filter_input(INPUT_POST, 'domain_N', FILTER_SANITIZE_SPECIAL_CHARS);
                    $sql = "INSERT INTO `allowed_users` (`id`, `username`, `domain`, `edit_urls`, `edit_emerg`, `edit_users`)
                    VALUES ('', '$user', '$domain', '1', '0', '0')";
                    if($conn->query($sql))
                    {
                        echo "Added new User ($domain\\$user).";
                        ?>
                      <script>
                            setTimeout("location.href = '<?php echo $admin_url;?>admin/index.php?func=view_users'",<?php echo $page_timeout;?>);
                        </script>
                        <?php
                    }else
                    {
                        echo "Failed to add new User.<br />\r\n".$conn->error;
                    }
                }else
                {
                    $pwd = @filter_input(INPUT_POST, 'pwd_N', FILTER_SANITIZE_SPECIAL_CHARS);
                    $pwd = md5($pwd.$seed);
                    $sql = "INSERT INTO `allowed_users` (`id`, `username`, `domain`, `edit_urls`, `edit_emerg`, `edit_users`)
                    VALUES ('', '$user', '', '1', '0', '0')";
                    if($conn->query($sql))
                    {
                        $sql = "INSERT INTO `internal_users` (`id`, `username`, `password`, `disabled`, `failed`)
                        VALUES ('', '$user', '$pwd', '0', '0')";
                        if($conn->query($sql))
                        {
                            echo "Added new Internal User ($user).";
                            ?>
                           <script>
                                setTimeout("location.href = '<?php echo $admin_url;?>admin/index.php?func=view_users'",<?php echo $page_timeout;?>);
                            </script>
                            <?php
                        }else
                        {
                            echo "Failed to add new User.<br />\r\n".$conn->error;
                        }
                    }else
                    {
                        echo "Failed to add new User.<br />\r\n".$conn->error;
                    }
                }
            }else
            {
                echo "Ummm, you shouldn't be here.. I think you should leave before the droids come. O_o";
            }
            break;
        case "edit_user":
            if($perms['edit_users'])
            {
                $id = filter_input(INPUT_POST, 'id', FILTER_SANITIZE_SPECIAL_CHARS);
                $set = filter_input(INPUT_GET, 'set', FILTER_SANITIZE_SPECIAL_CHARS);
                switch($set)
                {
                    case "urls":
                        $edit_urls = filter_input(INPUT_POST, 'edit_urls', FILTER_SANITIZE_SPECIAL_CHARS);
                        $result->free();
                        $sql = "UPDATE `allowed_users` SET `edit_urls` = '$edit_urls' WHERE `id` = '$id'";
                        echo $sql."<br />";
                        if($conn->query($sql, 1))
                        {
                            echo "Updated Edit_URL field.";
                            ?>
                    <script>
                        setTimeout("location.href = '<?php echo $admin_url;?>admin/index.php?func=view_users'",<?php echo $page_timeout;?>);
                    </script>
                            <?php
                        }else
                        {
                            echo "Failed Update.<br />".$conn->error;
                        }
                        break;
                    case "emerg":
                        $edit_emerg = filter_input(INPUT_POST, 'edit_emerg', FILTER_SANITIZE_SPECIAL_CHARS);
                        $result->free();
                        $sql = "UPDATE `allowed_users` SET `edit_emerg` = '$edit_emerg' WHERE `id` = '$id'";
                        echo $sql."<br />";
                        if($conn->query($sql, 1))
                        {
                            echo "Updated Edit_Emerg field.";
                            ?>
                    <script>
                        setTimeout("location.href = '<?php echo $admin_url;?>admin/index.php?func=view_users'",<?php echo $page_timeout;?>);
                    </script>
                            <?php
                        }else
                        {
                            echo "Failed Update.<br />".$conn->error;
                        }
                        break;
                    case "user":
                        $edit_users = filter_input(INPUT_POST, 'edit_user', FILTER_SANITIZE_SPECIAL_CHARS);
                        $result->free();
                        $sql = "UPDATE `allowed_users` SET `edit_users` = '$edit_users' WHERE `id` = '$id'";
                        echo $sql."<br />";
                        if($conn->query($sql, 1))
                        {
                            echo "Updated Edit_User field.";
                            ?>
                    <script>
                        setTimeout("location.href = '<?php echo $admin_url;?>admin/index.php?func=view_users'",<?php echo $page_timeout;?>);
                    </script>
                            <?php
                        }else
                        {
                            echo "Failed Update.<br />".$conn->error;
                        }
                        break;
                    case "c_messages":
                        $c_messages = filter_input(INPUT_POST, 'c_messages', FILTER_SANITIZE_SPECIAL_CHARS);
                        $result->free();
                        $sql = "UPDATE `allowed_users` SET `c_messages` = '$c_messages' WHERE `id` = '$id'";
                        echo $sql."<br />";
                        if($conn->query($sql, 1))
                        {
                            echo "Updated c_messages field.";
                            ?>
                    <script>
                        setTimeout("location.href = '<?php echo $admin_url;?>admin/index.php?func=view_users'",<?php echo $page_timeout;?>);
                    </script>
                            <?php
                        }else
                        {
                            echo "Failed Update.<br />".$conn->error;
                        }
                        break;
                    case "rss_feeds":
                        $rss_feeds = filter_input(INPUT_POST, 'rss_feeds', FILTER_SANITIZE_SPECIAL_CHARS);
                        $result->free();
                        $sql = "UPDATE `allowed_users` SET `rss_feeds` = '$rss_feeds' WHERE `id` = '$id'";
                        echo $sql."<br />";
                        if($conn->query($sql, 1))
                        {
                            echo "Updated rss_feeds field.";
                            ?>
                    <script>
                        setTimeout("location.href = '<?php echo $admin_url;?>admin/index.php?func=view_users'",<?php echo $page_timeout;?>);
                    </script>
                            <?php
                        }else
                        {
                            echo "Failed Update.<br />".$conn->error;
                        }
                        break;
                    case "edit_options":
                        $edit_options = filter_input(INPUT_POST, 'edit_options', FILTER_SANITIZE_SPECIAL_CHARS);
                        $result->free();
                        $sql = "UPDATE `allowed_users` SET `edit_options` = '$edit_options' WHERE `id` = '$id'";
                        echo $sql."<br />";
                        if($conn->query($sql, 1))
                        {
                            echo "Updated edit_options field.";
                            ?>
                    <script>
                        setTimeout("location.href = '<?php echo $admin_url;?>admin/index.php?func=view_users'",<?php echo $page_timeout;?>);
                    </script>
                            <?php
                        }else
                        {
                            echo "Failed Update.<br />".$conn->error;
                        }
                        break;
                    case "reset_pwd":
                        $id = filter_input(INPUT_POST, 'id', FILTER_SANITIZE_SPECIAL_CHARS);
                        $reset_pwd = filter_input(INPUT_POST, 'password', FILTER_SANITIZE_SPECIAL_CHARS);
                        $r_pwd = md5($reset_pwd.$seed);
                        $sql = "UPDATE `internal_users` SET `password` = '$r_pwd' WHERE `id` = '$id'";
                        echo $sql."<br />";
                        $result->free();
                        if($conn->query($sql, 1))
                        {
                            echo "Changed User Password.";
                            ?>
                    <script>
                        setTimeout("location.href = '<?php echo $admin_url;?>admin/index.php?func=view_users'",<?php echo $page_timeout;?>);
                    </script>
                            <?php
                        }else
                        {
                            echo "Failed to Update User Password.<br />".$conn->error;
                        }
                        break;
                }
            }else
            {
                echo "Ummm, you shouldn't be here.. I think you should leave before the droids come. O_o";
            }
            break;
        case "remove_cl":
            if($perms['edit_urls'])
            {
                if(!@$_POST['remove'])
                {
                    ?>
                    <script>
                        setTimeout("location.href = '<?php echo $admin_url;?>admin/index.php'",<?php echo $page_timeout;?>);
                    </script>
                    <?php
                    break;
                }
                $result->free();
                foreach($_POST['remove'] as $id)
                {
                    $sql = "DELETE FROM `allowed_clients` WHERE `client_name` = '$id'";
                    if($conn->query($sql))
                    {
                        $sql = "DELETE FROM `friendly` WHERE `client` = '$id'";
                        if($conn->query($sql))
                        {
                            $sql = "DROP TABLE `".$id."_links`";
                            if($conn->query($sql))
                            {
                                echo "Removed client [$id]<br />\r\n";
                                ?>
                    <script>
                        setTimeout("location.href = '<?php echo $admin_url;?>admin/index.php'",<?php echo $page_timeout;?>);
                    </script>
                            <?php
                            }else
                            {
                                echo "Failed to drop table `".$id."_links`<br />".$conn->error;
                            }
                        }else
                        {
                            echo "Failed to remove client [$id] from friendly<br />\r\n".$conn->error;
                        }
                    }else
                    {
                        echo "Failed to remove client [$id] from allowed list<br />\r\n".$conn->error;
                    }
                }
            }else
            {
                echo "Ummm, you shouldn't be here.. I think you should leave before the droids come. O_o";
            }
            break;
        default:
            if($perms['edit_urls'])
            {
                ?>
            <meta http-equiv="refresh" content="240;">
                <script type="text/javascript">
                <!--
                function SetAllCheckBoxes(FormName, FieldName, CheckValue)
                {
                        if(!document.forms[FormName])
                                return;
                        var objCheckBoxes = document.forms[FormName].elements[FieldName];
                        if(!objCheckBoxes)
                                return;
                        var countCheckBoxes = objCheckBoxes.length;
                        if(!countCheckBoxes)
                                objCheckBoxes.checked = CheckValue;
                        else
                                // set the check value for all check boxes
                                for(var i = 0; i < countCheckBoxes; i++)
                                        objCheckBoxes[i].checked = CheckValue;
                }
                // -->
                </script>
                <form name="client_List" action="?func=remove_cl" method="POST">
                <table border="1px" width="100%">
                    <tr class="client_table_head">
                        <th>Client</th><th>Last Connect</th><th>Last URL</th><th>Remove</th>
                    </tr>
                    <?php
                    $sql = "SELECT allowed_clients.id, friendly.friendly, friendly.client
FROM allowed_clients, friendly
WHERE friendly.client = allowed_clients.client_name
ORDER BY friendly+0, friendly";
                    $result->free();
                    $result = $conn->query($sql,1);
                    $rows = 0;
                    while($array = $result->fetch_array(1))
                    {
                        $client = $array['client'];
                        $sql1 = "SELECT * FROM `connections` WHERE `client` LIKE '$client' ORDER by `last_conn` DESC LIMIT 1";
                        #echo $sql1."<BR>";
                        $conn2 = mysqli_connect($server, $username, $password, $db);
                        $result2 = mysqli_query($conn2, $sql1);
                        $array2 = mysqli_fetch_array($result2);
                    ?>
                    <tr class="client_table_body">
                        <td align="center">
                            <a class="links" href="?func=view_client&client=<?php echo $array['client'];?>"><?php echo $array['friendly'];?></a>
                        </td>
                        <td align="Center"><?php
                        echo date("F j, Y, g:i a",$array2['last_conn']);
                        ?>
                        </td>
                        <td align="Center">
                            <?php
                        if($array2['last_conn'])
                        {
                            switch($array2['last_url'])
                            {
                                case "no_urls":
                                    echo "Client Has No URLS";
                                    break;
                                default:
                                    echo '<a class="links" target="_blank" href="'.$array2['last_url'].'">'.$array2['last_url'].'</a>';
                                    break;
                            }
                        }else
                        {
                            echo "Has not connected yet...";
                        }
                        ?>
                        </td>
                        <td align="Center">
                            <input type="checkbox" name="remove[]" value="<?php echo $array['client']; ?>"/>
                        </td>
                    </tr>
                    <?php
                    $rows++;
                    }
                    if($rows == 0)
                    {
                        ?>
                        <tr class="client_table_body">
                            <td colspan="4" align="center">There are no Clients, lets add some</td>
                        </tr>
                        <?php
                    }
                    ?>
                        <tr class="client_table_tail">
                            <td colspan="2">
                            </td>
                            <td align="center">
                                <input type="submit" value="Remove Selected" />
                            </td>
                        <td align="center">
                            <input type="button" onclick="SetAllCheckBoxes('client_List', 'remove[]', true);" value="Check">
                            <input type="button" onclick="SetAllCheckBoxes('client_List', 'remove[]', false);" value="Uncheck">
                            </form>
                        </td>
                        </tr>
                </form>
                        <tr class="client_table_tail">
                            <td colspan="4" align="Center">
                                <br />
                                <form name="client_add" action="?func=add_client" method="POST">
                                <table border="1px">
                                    <tr class="client_table_body">
                                        <td>
                                            Client Name:
                                        </td>
                                        <td>
                                            <input type="text" name="friendly" />
                                        </td>
                                        <td rowspan="3">
                                            <input type="submit" value="Add Client" />
                                        </td>
                                    </tr>
                                </table>
                                </form>
                            </td>
                        </tr>
                        <?php
                    ?>
                </table>
                <?php
            }else
            {
                echo "Ummm, you shouldn't be here.. I think you should leave before the droids come. O_o";
            }
            break;
    }
?>
            </td>
        </tr>
    </table>
<?php
}





function login_check()
{
        global $global_loggedin, $username, $privs_a;
        $cookie_name = 'login_yes';
       # echo "<br>".$cookie_name.":  ".@$_COOKIE[$cookie_name]."<br>";
        if(!@isset($_COOKIE[$cookie_name]))
        {
            return 0;
        }else{
            return 1;
        }
}

function gen_friendly($client)
{
    include '../configs/conn.php';
    $conn = new mysqli($server, $username, $password, $db);
    $sql = "SELECT friendly FROM friendly WHERE `client` like '$client'";
    $result = $conn->query($sql, 1);
    $friendly = $result->fetch_array(1);
    return $friendly['friendly'];
}

function format_bytes($size) {
    $units = array(' B', ' KB', ' MB', ' GB', ' TB');
    for ($i = 0; $size >= 1024 && $i < 4; $i++) $size /= 1024;
    return round($size, 2).$units[$i];
}

function create_cookie($username_login)
{
    include "../configs/vars.php";
    include "../configs/conn.php";
    $proto = $GLOBALS['proto'];;

    $admin_url = $GLOBALS['admin_url'];
    $reg_url = $GLOBALS['reg_url'];
    
    $conn = new mysqli($server, $username, $password, $db);
    $hash = md5(mt_rand(0000000000000000,9999999999999999));
    if($timeout > 0){$time = time()+$timeout;}else{$time = 0;}
    
    if($root == "" or $root == "/"){$path = "/admin";}else{$path = "/".$root."admin";}

    if(setcookie("login_yes", $hash.":".$username_login, $time , $path, '', $SSL, 1))
    {
        echo "Cookie Set\r\n";
        #$time = time()+$timeout;
        $sql = "INSERT INTO `hash_links` (`id`, `hash`, `time`, `user`) VALUES ('', '$hash', '$time', '$username_login')";
        $result = $conn->query($sql);
        if($result)
        {
            echo "<h1>Logged In</h1>";
            ?>
                <script>location.href = '<?php echo $admin_url;?>admin/index.php';</script>
            <?php
            return 1;
        }else
        {
            echo $conn->error."\r\n";
            return 0;
        }
    }else
    {
        echo "cookie eaten\r\n";
        return 0;
    }
}

function check_archives($client)
{
    include "../configs/vars.php";
    include "../configs/conn.php";
    if(!$conn = mysqli_connect($server, $username, $password, $db))
    {return -1;}
    $sql = "SELECT * FROM `archive_links` WHERE `client` = '$client' ORDER BY `date` ASC";
    if(!$result = mysqli_query($conn, $sql))
    {return 0;}
    $rows = mysqli_num_rows($result);
    if($max_archives < $rows)
    {
        while($arcs = mysqli_fetch_array($result))
        {
            if($rows+1 == $max_archives){break;}
            $sql = "DELETE FROM `archive_links` WHERE `id` = '".$arcs['id']."'";
            if(mysqli_query($conn, $sql))
            {
                echo "Removed row [".$arcs['id']."]<br />";
                $rows--;
            }else
            {
                die(mysql_error($conn));
            }
        }
        return 2;
    }else
    {
        return 1;
    }
}

function login_form($mesg)
{
?>
<html>
    <head>
        <title>UNS Admin Panel</title>
        <link rel="stylesheet" href="../configs/styles.css">
    </head>
    <body class="main_body">
        <?php if($mesg != ""){ ?> <p align="center" style="color:red;"> <?php echo $mesg;?> </p> <?php } ?>
    <form method="POST" action="?login=1">
    <table class="navtd" align="center" border="1px" style="color:000; width:30%;">
        <tr align="center" class="client_table_head">
            <td>
                USERNAME:<br /><font size="1">(domain\user)</font>
            </td>
            <td>
                <input type="text" style="width:400px;" name="user">
            </td>
        </tr><!-- username -->
        <tr align="center" class="client_table_body">
            <td>
                PASSWORD:
            </td>
            <td>
                <input type="password" name="pass" style="width:400px;">
            </td>
        </tr><!-- password -->
        <tr align="center" class="client_table_tail">
            <td colspan="2" align="center">
                <input type="submit" value="Login" name="B1">
            </td>
        </tr><!-- submit -->
    </table>
    </form>
<?php
die();
}
?>