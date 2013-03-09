<?php
global $sql_args;
$sql_args = array(
    'server' => "172.16.1.28",   # SQL Host
    'username' => "uns_user",       # User for UNS
    'password' => "unsadmin",       # User password
    'db' => "uns",                  # Database with UNS tables
    'service' => "mysql"            # SQL Service that PDO needs to use.
);
?>