<?php
global $config;
$config = array(
    'name_title' => 'URL Notification System DEV',              # Name of your Install, Will be displayed on all papes
    'host' => 'HTTP_HOST/',                                   # The HTTP server the clients will connect to. (needs to be an IP or DNS name)
    'path' => '/var/www/',                                      # HTTP Server root folder. (usually /var/www/ )
    'root' => 'UNS2/',                                          # Folder UNS lives in (usually uns/ )
    'timeout' => (24*3600),                                     # Cookie Time out
    'SSL' => 0,                                                 # Cookie SSL only?
    'LDAP' => 0,                                                # If this flag is set, internal users will be overridden, except for the Admin.
    'LDAP_domain' => '',                                        # LDAP Domain to connect to for user authentication
    'LDAP_port' => 0,                                           # LDAP Port
    'timezone' => 'UTC',                                        # Local Time Zone
    'page_timeout' => 0,                                        # Refresh time for page to forward in seconds.
    'page_refresh' => 30,                                       # Time for client pages to refresh.
    'global_login_seed' => 'z90cwmg0ic1rc74ssec2ifj0wtcz231y',  # Only used for internal user logins, to hash the password and store that.
    'max_archives' => 10,                                       # The Maximum number of Archived URL lists that will be kept before the oldest is killed
    'max_conn_history' => 10,                                   # The Maximum number of Connection histories that will be kept per client.
    'lpt_read_bin' => '/usr/local/sbin/portctl',                # Location of the LPT Read binary
    'lpt_write_bin' => '/usr/local/sbin/lpt',                   # Location of the LPT Write binary
    'led_blink' => 1,                                           # Flag for turning LED Blinking on or off.
    'mysql_dump_bin' => 'mysqldump',                            # Name or location of the MySQL Dump binary.
    'max_bad_logins' => 3                                       # Maximum number of failed logins before a user is locked.
);
?>