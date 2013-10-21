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

if(@$_GET['test'] === "test")
{
    $ldapserver = @filter_input(INPUT_POST, 'ldapserver', FILTER_SANITIZE_ENCODED);
    $link = ldap_connect("ldap://$ldapserver/", 3268);
    if($bind = @ldap_bind($link, $_POST['user'], $_POST['pwd']))
    {$result = "It worked!";}
    else
    {$result = "It Failed! Reason: ".ldap_error($link);}
    $smarty->assign("result", $result);
    $smarty->display('ldap_test_results.tpl');
}else
{
    $smarty->display('ldap_test.tpl');
}