<html>
<head>
    <title>UNS Install Page</title>
    <link rel="stylesheet" href="configs/styles.css">
</head>
<?php
$test_ldap = @filter_input(INPUT_GET, 'test_ldap', FILTER_SANITIZE_ENCODED);
if($test_ldap==='1')
{
    ?>
    <body class="main_body" align="center">
    <div align="center">
        <form action="?test_ldap=2" method="post" enctype="multipart/form-data">
            <table border="1" width="75%" class="main_cell">
                <tr>
                    <th>
                        LDAP Server:
                    </th>
                    <th>
                        <input type="text" name="ldapserver" value=""/>
                    </th>
                </tr>
                <tr>
                    <th>
                        Domain\Username:
                    </th>
                    <th>
                        <input type="text" name="user" value=""/>
                    </th>
                </tr>
                <tr>
                    <th>
                        Password:
                    </th>
                    <th>
                        <input type="password" name="pwd" value=""/>
                    </th>
                </tr>
                <tr>
                    <th colspan="2">
                        <input type="submit" value="Submit"/>
                    </th>
                </tr>
            </table>
    </div>
    </body>

    <?php
    die();
}elseif($test_ldap === '2')
{
    $ldapserver = @filter_input(INPUT_POST, 'ldapserver', FILTER_SANITIZE_ENCODED);
    $user = @filter_input(INPUT_POST, 'user', FILTER_SANITIZE_ENCODED);
    $pwd = @filter_input(INPUT_POST, 'pwd', FILTER_SANITIZE_ENCODED);
    $link = ldap_connect("ldap://$ldapserver/", 3268);
    if($bind = @ldap_bind($link, $user, $pwd))
    {$result = "It worked!";}
    else{echo ldap_error($link);$result = "It Failed!";}
    ?>
    <body class="main_body" align="center">
    <div align="center">
        <table border="1" width="75%" class="main_cell">
            <tr>
                <th>
                    <?php echo $result; ?>
                </th>
            </tr>
        </table>
    </div>
    </body>

    <?php
    die();
}
$installing = @filter_input(INPUT_GET, 'installing', FILTER_SANITIZE_ENCODED);
if($installing)
{
?>
<body class="main_body" align="center">
<div align="center">
<table border="1" width="75%" class="main_cell">
<tr class="client_table_head">
    <th colspan="2">
        UNS Install Process
    </th>
</tr>
<tr class="client_table_head">
    <th>
        Step
    </th>
    <th>
        Outcome
    </th>
</tr>
<?php
$sql_host = @filter_input(INPUT_POST, 'sql_host', FILTER_SANITIZE_ENCODED);
$sql_root_usr = @filter_input(INPUT_POST, 'sql_root_usr', FILTER_SANITIZE_ENCODED);
$sql_root_pwd = @filter_input(INPUT_POST, 'sql_root_pwd', FILTER_SANITIZE_SPECIAL_CHARS);
$uns_sql_usr = @filter_input(INPUT_POST, 'uns_sql_usr', FILTER_SANITIZE_ENCODED);
$uns_sql_pwd = @filter_input(INPUT_POST, 'uns_sql_pwd', FILTER_SANITIZE_SPECIAL_CHARS);
$db_name = @filter_input(INPUT_POST, 'db_name', FILTER_SANITIZE_ENCODED);
$hostname = @filter_input(INPUT_POST, 'hostname', FILTER_SANITIZE_ENCODED);
$hostname = str_replace("/", "", $hostname)."/";
$link = new mysqli($sql_host, $sql_root_usr, $sql_root_pwd);
#Create UNS DB User
$sql = "CREATE DATABASE IF NOT EXISTS `$db_name`";
?><tr>
    <td>Created UNS DB and User.</td>
    <?php
    if($result = $link->query($sql))
    {
    ?><td class="Good">Success</td>
</tr><?php
}else
{
    echo "<td class='Emerg'>".$link->error."</td>
                </tr>";
}

#Create UNS
if($sql_host == "localhost" || $sql_host == "127.0.0.1")
{
    $usr_hostname = "localhost";
}else
{
    $usr_hostname = $hostname;
}

$link->query("USE `$db_name`;");

?>
<tr>
    <td>Created UNS tables.</td>
    <?php
    $sql = implode("\r\n", file('setup.sql'));
    if($link->multi_query($sql))
    {
    ?><td class="Good">Success</td>
</tr><?php
}else
{
echo "<td class='Emerg'>".$link->error."<br />";
while($link->next_result())
{echo $link->error."<br />";}
?></td></tr><?php
}

$link->query('FLUSH PRIVILEGES');
$link->close();
?>
<tr>
    <td>Closed Root, Trying to connect with UNS User.</td>
    <?php
    $link1 = new mysqli($sql_host, $uns_sql_usr, $uns_sql_pwd, $db_name);
    if(!@$link1->connect_error)
    {
    ?><td class='Good'>Connected</td>
</tr>
<?php
}
else{
    ?>
    <td class='Emerg'>Failed To connect.<br /><?php echo $link->connect_error; ?></td>
    </tr>
<?php
}
?>
<tr>
    <td>Created UNS Interanal Admin User.</td>
    <?php
    $characters = '0123456789abcdefghijklmnopqrstuvwxyz';
    $seed = '';
    for ($p = 0; $p < 32; $p++)
    {
        $seed .= $characters[mt_rand(0, strlen($characters)-1)];
    }
    $admin_pwd = @filter_input(INPUT_POST, 'uns_admin_pwd', FILTER_SANITIZE_SPECIAL_CHARS);
    $password = md5($admin_pwd.$seed);

    $sql = "INSERT INTO `internal_users` ( `id` , `username` , `password` , `disabled` , `failed` ) VALUES ( '' , 'unsadmin', '$password', '0', '0')";
    if($link1->query($sql))
    {
    $sql = "INSERT INTO `allowed_users` (`id`, `username`, `domain`, `edit_urls`, `edit_emerg`, `edit_users`, `edit_options`, `c_messages`, `rss_feeds`)
            VALUES ('', 'unsadmin', '', '1','1','1','1','1','1')";
    if($link1->query($sql))
    {
    ?><td class="Good">Success</td>
</tr><?php
}else
{
    ?><td class='Emerg'><?php echo $link1->error."<br />"; ?></td>
    </tr> <?php
}
}else
{
    ?><td class='Emerg'><?php echo $link1->error."<br />"; ?></td>
    </tr> <?php
}

$uns_name = @filter_input(INPUT_POST, 'uns_name', FILTER_SANITIZE_SPECIAL_CHARS);
$root = @filter_input(INPUT_POST, 'root', FILTER_SANITIZE_SPECIAL_CHARS);
if($root == "/"){$root = "";}

$timeout = @filter_input(INPUT_POST, 'timeout', FILTER_SANITIZE_ENCODED);
$IST = @filter_input(INPUT_POST, 'IST', FILTER_SANITIZE_ENCODED);
$SSL = @filter_input(INPUT_POST, 'ssl', FILTER_SANITIZE_ENCODED);
if($SSL == '')
{
    $SSL = 0;
}

if(PHP_OS != 'Linux')
{$domain = @filter_input(INPUT_POST, 'ldap_domain', FILTER_SANITIZE_ENCODED);}
else{$domain = @filter_input(INPUT_POST, 'ldap_ipaddr', FILTER_SANITIZE_ENCODED);}

$domain_port = @filter_input(INPUT_POST, 'ldap_port', FILTER_SANITIZE_ENCODED);
if(!$domain_port){$domain_port = '0';}
$page_timeout = @filter_input(INPUT_POST, 'page_timeout', FILTER_SANITIZE_ENCODED);
$refresh = @filter_input(INPUT_POST, 'refresh', FILTER_SANITIZE_ENCODED);
$pwd_seed = @filter_input(INPUT_POST, 'pwd_seed', FILTER_SANITIZE_ENCODED);
$max_arch = @filter_input(INPUT_POST, 'max_arch', FILTER_SANITIZE_ENCODED);
$max_conns = @filter_input(INPUT_POST, 'max_conns', FILTER_SANITIZE_ENCODED);
$ldap = @filter_input(INPUT_POST, 'ldap', FILTER_SANITIZE_ENCODED);
if(!$ldap){$ldap = '0';}

$vars_file = "<?php
$"."name_title     = '$uns_name';               # Name of your Install, Will be displayed on all papes
$"."host           = '$hostname';               # The HTTP server the clients will connect to.
$"."root           = '$root';                   # Folder UNS lives in
$"."timeout        = ($timeout);                # Cookie Time out
$"."SSL            = ".($SSL).";                # Cookie SSL only?
$"."domain         = '$domain';                 # LDAP Domain to connect to for user authentication
$"."port           = $domain_port;              # LDAP Port
$"."TZ             = 'UTC';                     # Local Time Zone
$"."page_timeout   = $page_timeout;             # Refresh time for page to forward in seconds.
$"."refresh        = $refresh;                  # Time for client pages to refresh.
$"."seed           = '$seed';                   # Only used for internal user logins, to hash the password and store that.
$"."LDAP           = $ldap;                     # If this flag is set, internal users will be overridden, except for the Admin.
$"."max_archives   = $max_arch;                 # The Maximum number of Archived URL lists that will be kept before the oldest is killed
$"."max_conn_hist  = $max_conns;                # The Maximum number of Connection histories that will be kept per client.
$"."lpt_set_app    = '/usr/local/sbin/lpt';     # Bin for the LPT LED blinker
$"."lpt_read_app   = '/usr/local/sbin/portctl'; # Bin for LPT value reader
$"."led_blink      = 0;                         # Variable to turn on the LPT LED blinking
$"."mysql_dump_bin = 'mysqldump';     # Can be relitive or absolute location of mysqldump binary

# The Template variables for RSS feeds
$"."template_head_rss = '<html>
    <head>
        <meta http-equiv=\"Content-Type\" content=\"text/html; charset=UTF-8\" />
        <title>Powered by UNS</title>
        <link rel=\"stylesheet\" href=\"../configs/rss_styles.css\">
    </head>
    <body class=\"body\">';
$"."template_foot_rss = '
    </body>
</html>';


# The Template variables for Custom Messages
$"."template_head_cmsg = '<html>
    <head>
        <meta http-equiv=\"Content-Type\" content=\"text/html; charset=UTF-8\" />
        <title>Powered by UNS</title>
        <link rel=\"stylesheet\" href=\"../configs/cmsg_styles.css\">
    </head>
    <body class=\"T_BODY\">';
$"."template_foot_cmsg = '
    </body>
</html>';
?>";
?>
<tr>
    <td>Write config/vars.php file.</td>
    <?php
    if(file_put_contents(getcwd().'/configs/vars.php', $vars_file))
    {
    ?><td class="Good">Success</td>
</tr><?php
}else
{
    ?><td class='Emerg'>Failed.</td>
    </tr><?php
}


$conn_file = "<?php
$"."server = '$sql_host';  # MySQL Host
$"."username = '$uns_sql_usr';      # User for UNS
$"."password = '$uns_sql_pwd';      # Users password
$"."db = '$db_name';            # Database with UNS tables
?>";
?>
<tr>
    <td>Write config/conn.php file.</td>
    <?php
    if(file_put_contents(getcwd().'/configs/conn.php', $conn_file))
    {
    ?><td class="Good">Success</td>
</tr><?php
}else
{
    ?><td class='Emerg'>Failed.</td>
    </tr><?php
}


?>
<tr class="client_table_tail"><td colspan="2" align="center">If all were successful, you can remove the install.php and setup.sql files and start using <a href="<?php if($SSL){echo "https://$hostname";}else{echo "http://$hostname";} if($root != ""){echo "/".$root."/";}else{echo "/";}?>index.php">UNS</a>.</td></tr>
</table>
</div>
</body>
</html>
<?php
die();
}else
{
    ?>
    <body class="main_body" align="center" onload="endisable()">
    <div align="center">
    <script type="text/javascript">
        function endisable( ) {
            document.forms['UNS_Install'].elements['ldap_domain'].disabled =! document.forms['UNS_Install'].elements['ldap'].checked;
            document.forms['UNS_Install'].elements['ldap_port'].disabled =! document.forms['UNS_Install'].elements['ldap'].checked;
        }
    </script>
    <form name="UNS_Install" action="?installing=1" method="post" enctype="multipart/form-data">
    <table border="1" width="75%" class="main_cell">
    <tr class="client_table_head">
        <th colspan="2">
            URL Notification System Installer
        </th>
    </tr>
    <tr class="client_table_head">
        <th colspan="2">
            Checking prerequisites
        </th>
    </tr>
    <tr class="pre">
        <td width="50%">
            PHP Version &gt;5.0?
        </td>
        <td>
            <?php
            if(version_compare(PHP_VERSION, '5.0.0', '>=')){echo "<font color='limegreen'>GOOD! {".PHP_VERSION."}";}else{$die = 1; echo "<font color='red'>PHP Version is too old.<br />".PHP_VERSION;}
            ?></font>
        </td>
    </tr>
    <tr class="pre">
        <td>
            MySQLi Functions?
        </td>
        <td>
            <?php
            if(function_exists("mysql_get_client_info")){echo "<font color='limegreen'>GOOD! {".mysql_get_client_info()."}";}else{$die = 1; echo "<font color='red'>MySQL Version is too old.<br />".mysql_get_client_info();}
            ?></font>
        </td>
    </tr>
    <tr class="pre">
        <td>
            XML Functions?
        </td>
        <td>
            <?php
            if(function_exists("xml_parser_create")){echo "<font color='limegreen'>GOOD!";}else{echo "<font color='red'>XML Functions are not available, RSS Feeds will not work.";}
            ?></font>
        </td>
    </tr>
    <tr class="pre">
        <td>
            Allowed URL fopen()?
        </td>
        <td>
            <?php
            if(@file("http://www.vistumbler.net/wifidb/atomrss.php")){echo "<font color='limegreen'>GOOD!";}else{echo "<font color='red'>URL fopen() is not available, RSS Feeds will not work.";}
            ?></font>
        </td>
    </tr>
    <tr class="pre">
        <td>
            LDAP Functions?

        </td>
        <td>
            <?php
            if(function_exists("ldap_connect"))
            {
                echo "<font color='limegreen'>GOOD!<br/>
                                    <font size='1'><a href='?test_ldap=1' target='_blank'>Test LDAP Functions</a></font>";
            }
            else
            {
                echo "<font color='red'>LDAP Functions not found, Active Directory will not work.";
            }
            ?></font>
        </td>
    </tr>
    <tr class="client_table_head">
        <th colspan="2">
            Setup your SQL Connection <font size="1">( config/conn.php )</font>
        </th>
    </tr>
    <tr class="client_table_body">
        <td>
            SQL Host
        </td>
        <td>
            <input type="text" name="sql_host" style="width:50%" value="localhost"/>
        </td>
    </tr>
    <tr class="client_table_body">
        <td>
            SQL Admin User
        </td>
        <td>
            <input type="text" name="sql_root_usr" style="width:50%" value="root"/>
        </td>
    </tr>
    <tr class="client_table_body">
        <td>
            SQL Admin Password
        </td>
        <td>
            <input type="password" name="sql_root_pwd" style="width:50%" value=""/>
        </td>
    </tr>
    <tr class="client_table_body">
        <td>
            UNS SQL Username
        </td>
        <td>
            <input type="text" name="uns_sql_usr" style="width:50%" value="uns_user"/>
        </td>
    </tr>
    <tr class="client_table_body">
        <td>
            UNS SQL Password
        </td>
        <td>
            <input type="password" name="uns_sql_pwd" style="width:50%" value=""/>
        </td>
    </tr>
    <tr class="client_table_body">
        <td>
            Database Name
        </td>
        <td>
            <input type="text" name="db_name" style="width:50%" value="uns"/>
        </td>
    </tr>
    <tr class="client_table_head">
        <th colspan="2">
            Set Your Variables <font size="1">( config/vars.php )</font>
        </th>
    </tr>
    <tr class="client_table_body">
        <td>
            Instance Name
        </td>
        <td>
            <input type="text" name="uns_name" style="width:50%" value="URL Notification System"/>
        </td>
    </tr>
    <tr class="client_table_body">
        <td>
            Hostname
        </td>
        <td>
            <input type="text" name="hostname" style="width:50%" value="unsserver"/>
        </td>
    </tr>
    <tr class="client_table_body">
        <td>
            HTTP root for UNS
        </td>
        <td>
            <input type="text" name="root" style="width:50%" value="uns/"/>
        </td>
    </tr>
    <tr class="client_table_body">
        <td>
            Session Timeout <font size="1">( Seconds )</font>
        </td>
        <td>
            <input type="text" name="timeout" style="width:50%" value="3600"/>
        </td>
    </tr>
    <tr class="client_table_body">
        <td>
            SSL Admin Folder?
        </td>
        <td>
            <input type="checkbox" name="ssl" value="1"/>
        </td>
    </tr>
    <tr class="client_table_body">
        <td>
            Use LDAP?
        </td>
        <td>
            <input type="checkbox" name="ldap" value="1" onchange="endisable()"/>
        </td>
    </tr>
    <?php
    if(PHP_OS == 'Linux')
    {
        ?>
        <tr class="client_table_body">
            <td>
                LDAP IP Address
            </td>
            <td>
                <input type="text" name="ldap_domain" style="width:50%" value="192.168.1.99" disabled/>
            </td>
        </tr>
    <?php
    }else
    {
        ?>
        <tr class="client_table_body">
            <td>
                LDAP Domain
            </td>
            <td>
                <input type="text" name="ldap_domain" style="width:50%" value="example.lan" disabled/>
            </td>
        </tr>
    <?php
    }
    ?>
    <tr class="client_table_body">
        <td>
            LDAP Port
        </td>
        <td>
            <input type="text" name="ldap_port" style="width:50%" value="3268" disabled/>
        </td>
    </tr>
    <tr class="client_table_body">
        <td>
            Redirect Page Timeout <font size="1">( Zero [0], will be an instant redirect. )</font>
        </td>
        <td>
            <input type="text" name="page_timeout" style="width:50%" value="0"/>
        </td>
    </tr>
    <tr class="client_table_body">
        <td>
            Default Client URL Refresh time
        </td>
        <td>
            <input type="text" name="refresh" style="width:50%" value="30"/>
        </td>
    </tr>
    <tr class="client_table_body">
        <td>
            Internal Admin Password <font size="1">( Needed even if LDAP is used. )</font>
        </td>
        <td>
            <input type="password" name="uns_admin_pwd" style="width:50%" value="<?php
            ?>"/>
        </td>
    </tr>
    <tr class="client_table_body">
        <td>
            Max Number of Archived links per Client
        </td>
        <td>
            <input type="text" name="max_arch" style="width:50%" value="10"/>
        </td>
    </tr>
    <tr class="client_table_body">
        <td>
            Max Number of Connection History per Client.
        </td>
        <td>
            <input type="text" name="max_conns" style="width:50%"s value="10"/>
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
    </body>
    </html>
<?php
}
?>