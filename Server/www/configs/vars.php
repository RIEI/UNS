<?php
global $config;
$config = array(
    'name_title' => 'URL Notification System DEV',              # Name of your Install, Will be displayed on all papes
    'host' => '172.16.1.77/',                                   # The HTTP server the clients will connect to. (needs to be an IP or DNS name)
    'path' => '/var/www/',                                      # HTTP Server root folder. (usually /var/www/ )
    'root' => 'UNS/',                                          # Folder UNS lives in (usually uns/ )
    'SSL' => 0,                                                 # Cookie SSL only?
    'LDAP' => 0,                                                # If this flag is set, internal users will be overridden, except for the Admin.
    'LDAP_domain' => '',                                        # LDAP Domain to connect to for user authentication
    'LDAP_port' => 0,                                           # LDAP Port
    'timezone' => 'UTC',                                        # Local Time Zone
    'cookie_timeout' => (356*(24*3600)),                        # Cookie Time out
    'page_timeout' => 3,                                        # Refresh time for page to forward in seconds.
    'page_refresh' => 30,                                       # Time for client pages to refresh.
    'global_login_seed' => 'z90cwmg0ic1rc74ssec2ifj0wtcz231y',  # Only used for internal user logins, to hash the password and store that.
    'max_archives' => 10,                                       # The Maximum number of Archived URL lists that will be kept before the oldest is killed
    'max_conn_history' => 10,                                   # The Maximum number of Connection histories that will be kept per client.
    'lpt_read_bin' => '/usr/local/sbin/portctl',                # Location of the LPT Read binary
    'lpt_write_bin' => '/usr/local/sbin/lpt',                   # Location of the LPT Write binary
    'led_blink' => 0,                                           # Flag for turning LED Blinking on or off.
    'mysql_dump_bin' => 'mysqldump',                            # Name or location of the MySQL Dump binary.
    'max_bad_logins' => 3,                                      # Maximum number of failed logins before a user is locked.
    'password_hash_timeout' => 3600,                            # Time in seconds till the hash for a password reset will be valid, so time()+3600 or 2:00 PM + 1 hour
);
?>