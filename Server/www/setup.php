<?php
/**
 * Created by JetBrains PhpStorm.
 * User: sysferland
 * Date: 9/29/13
 * Time: 5:10 PM
 * To change this template use File | Settings | File Templates.
 */

$folders    = explode("/", $_SERVER['SCRIPT_NAME']);

define('WWW_DIR', $_SERVER['DOCUMENT_ROOT']."/{$folders[count($folders)-2]}/");
require_once(WWW_DIR.'smarty/Smarty.class.php');

$smarty = new Smarty();
$smarty->setTemplateDir( WWW_DIR.'smarty/templates/' );
$smarty->setCompileDir( WWW_DIR.'smarty/templates_c/' );
$smarty->setCacheDir( WWW_DIR.'smarty/cache/' );
$smarty->setConfigDir( WWW_DIR.'/smarty/configs/');
$smarty->error_reporting  = 1;

if(@$_GET['f'] === "setup_proc")
{
    $result = setup();
    $smarty->assign("Install_results", $result);
    $smarty->display("setup_result.tpl");
}else
{
    $results = prereq_check();
    $smarty->assign("result", $results);
    $smarty->display("setup.tpl");
}

function prereq_check()
{
    $results = array();
    if(version_compare(PHP_VERSION, '5.0.0', '>='))
    {
        $results['phpv']['color']='limegreen';
        $results['phpv']['result']='Good! '.PHP_VERSION;
    }
    else
    {
        $results['phpv']['color']='red';
        $results['phpv']['result']='Bad! '.PHP_VERSION;
    }

    if(defined('PDO::ATTR_DRIVER_NAME'))
    {
        $results['pdo']['color']='limegreen';
        $results['pdo']['result']='Good! ';
    }
    else
    {
        $results['pdo']['color']='red';
        $results['pdo']['result']='Bad! ';
    }

    if(function_exists("xml_parser_create"))
    {
        $results['xml']['color']='limegreen';
        $results['xml']['result']='Good! ';
    }
    else
    {
        $results['xml']['color']='red';
        $results['xml']['result']='Bad! ';
    }

    if(@file("http://www.google.com"))
    {
        $results['fopen']['color']='limegreen';
        $results['fopen']['result']='Good!';
    }
    else
    {
        $results['fopen']['color']='red';
        $results['fopen']['result']='Bad!';
    }

    if(function_exists("ldap_connect"))
    {
        $results['ldap']['color']='limegreen';
        $results['ldap']['result']='Good!';
    }
    else
    {
        $results['ldap']['color']='red';
        $results['ldap']['result']='Bad!';
    }

    return $results;
}

function setup()
{
    $request = parse_edit_url();
    $errors = array();

    $conn_file = "<?php
global \$sql_args;
\$sql_args = array(
    'server' => \"{$request['sql_host']}\",   # SQL Host
    'username' => \"{$request['uns_sql_usr']}\",       # User for UNS
    'password' => \"{$request['uns_sql_pwd']}\",       # User password
    'db' => \"uns\",                  # Database with UNS tables ***Don't change unless you know what you are doing. Some things might be hard-coded to the DB name of `uns`
    'service' => \"mysql\"            # SQL Service that PDO needs to use.
);
?>";
    if(file_put_contents(WWW_DIR."configs/conn.php", $conn_file))
    {
        $errors['config_conn']['class'] = "good";
        $errors['config_conn']['result'] = "Success";
    }else
    {
        $errors['config_conn']['class'] = "good";
        $errors['config_conn']['result'] = "Success";
    }

    $var_file = "<?php
global \$config;
\$config = array(
    'name_title' => '{$request['uns_name']}',              # Name of your Install, Will be displayed on all papes
    'host' => '{$request['http_host']}',                                   # The HTTP server the clients will connect to. (needs to be an IP or DNS name)
    'path' => '{$_SERVER['DOCUMENT_ROOT']}',                                      # HTTP Server root folder. (usually /var/www/ )
    'root' => '{$request['http_base']}',                                          # Folder UNS lives in (usually uns/ )
    'SSL' => {$request['ssl']},                                                 # Cookie SSL only?
    'LDAP' => {$request['ldap']},                                                # If this flag is set, internal users will be overridden, except for the Admin.
    'LDAP_domain' => '{$request['ldap_host']}',                                        # LDAP Domain to connect to for user authentication
    'LDAP_port' => {$request['ldap_port']},                                           # LDAP Port
    'timezone' => '{$request['tz']}',                                        # Local Time Zone
    'cookie_timeout' => {$request['session_timeout']},                        # Cookie Time out
    'page_timeout' => {$request['page_timeout']},                                        # Refresh time for page to forward in seconds.
    'page_refresh' => {$request['client_refresh']},                                       # Time for client pages to refresh.
    'max_archives' => {$request['mac_arch']},                                       # The Maximum number of Archived URL lists that will be kept before the oldest is killed
    'max_conn_history' => {$request['max_conns']},                                   # The Maximum number of Connection histories that will be kept per client.
    'lpt_read_bin' => '/usr/local/sbin/portctl',                # Location of the LPT Read binary
    'lpt_write_bin' => '/usr/local/sbin/lpt',                   # Location of the LPT Write binary
    'led_blink' => 0,                                           # Flag for turning LED Blinking on or off.
    'mysql_dump_bin' => 'mysqldump',                            # Name or location of the MySQL Dump binary.
    'max_bad_logins' => 3,                                      # Maximum number of failed logins before a user is locked.
    'password_hash_timeout' => 3600,                            # Time in seconds till the hash for a password reset will be valid, so time()+3600 or 2:00 PM + 1 hour
);
?>";

    if(file_put_contents(WWW_DIR."configs/vars.php", $var_file))
    {
        $errors['config_vars']['class'] = "good";
        $errors['config_vars']['result'] = "Success";
    }else
    {
        $errors['config_vars']['class'] = "bad";
        $errors['config_vars']['result'] = "Failure";
    }



}



function parse_edit_url()
{
    $definition = array(
        #sql settings
        'sql_host'=>FILTER_SANITIZE_STRING,
        'uns_sql_usr'=>FILTER_SANITIZE_STRING,
        'uns_sql_pwd'=>FILTER_SANITIZE_STRING,
        'db_name'=>FILTER_SANITIZE_STRING,
        #http settings
        'uns_name'=>FILTER_SANITIZE_STRING,
        'http_host'=>FILTER_SANITIZE_STRING,
        'http_base'=>FILTER_SANITIZE_STRING,
        'ssl'=>FILTER_SANITIZE_NUMBER_INT,
        'ldap'=>FILTER_SANITIZE_NUMBER_INT,
        'ldap_host'=>FILTER_SANITIZE_STRING,
        'ldap_port'=>FILTER_SANITIZE_NUMBER_INT,
        'tz'=>FILTER_SANITIZE_STRING,
        'session_timeout'=>FILTER_SANITIZE_NUMBER_INT,
        'page_timeout'=>FILTER_SANITIZE_NUMBER_INT,
        'client_refresh'=>FILTER_SANITIZE_NUMBER_INT,
        #general settings
        'uns_admin_name'=>FILTER_SANITIZE_STRING,
        'uns_admin_pwd'=>FILTER_SANITIZE_STRING,
        'uns_admin_email'=>FILTER_SANITIZE_EMAIL,
        'max_conns'=>FILTER_SANITIZE_NUMBER_INT,
        'max_arch'=>FILTER_SANITIZE_NUMBER_INT,
    );
    return array_filter(filter_var_array($_REQUEST, $definition));
}