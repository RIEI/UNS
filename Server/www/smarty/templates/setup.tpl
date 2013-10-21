<html>
<head>
    <title>UNS Setup Page</title>
    <link rel="stylesheet" href="res/styles.css">
    <script src="res/javascript.js"></script>
</head>
<body class="main_body" align="center" onload="endisable()">
    <div align="center">
        <form name="UNS_Install" action="?f=setup_proc" method="post" enctype="multipart/form-data">
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
                        <font color='{$result.phpv.color}'>
                            {$result.phpv.result}
                        </font>
                    </td>
                </tr>
                <tr class="pre">
                    <td>
                        PDO Functions?
                    </td>
                    <td>
                        <font color='{$result.pdo.color}'>
                            {$result.pdo.result}
                        </font>
                    </td>
                </tr>
                <tr class="pre">
                    <td>
                        XML Functions?
                    </td>
                    <td>
                        <font color='{$result.xml.color}'>
                            {$result.xml.result}
                        </font>

                    </td>
                </tr>
                <tr class="pre">
                    <td>
                        Allowed URL fopen()?
                    </td>
                    <td>
                        <font color='{$result.fopen.color}'>
                            {$result.fopen.result}
                        </font>

                    </td>
                </tr>
                <tr class="pre">
                    <td>
                        LDAP Functions?
                    </td>
                    <td>
                        <font color='{$result.ldap.color}'>{$result.ldap.result}</font>
                        {if $result.ldap.result eq 'Good!'}
                            <font size='1'><a href='ldap_test.php' target='_blank'>Test LDAP Functions</a></font>
                        {else}
                            <font color='red'>LDAP Functions not found, Active Directory will not work.</font>
                        {/if}
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
                        UNS SQL Username <font size='1'>( Must already be setup. )</font>
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
                        Database Name <font size='1'> ( Must already be setup with the UNS user having full data permissions and create permissions.)</font>
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
                        Hostname <font size='1'>( DNS name Or IP address )</font>
                    </td>
                    <td>
                        <input type="text" name="http_host" style="width:50%" value="unsserver"/>
                    </td>
                </tr>
                <tr class="client_table_body">
                    <td>
                        HTTP root for UNS
                    </td>
                    <td>
                        <input type="text" name="http_base" style="width:50%" value="uns/"/>
                    </td>
                </tr>
                <tr class="client_table_body">
                    <td>
                        Admin panel login session timeout <font size="1">( Seconds )</font>
                    </td>
                    <td>
                        <input type="text" name="session_timeout" style="width:50%" value="3600"/>
                    </td>
                </tr>
                <tr class="client_table_body">
                    <td>
                        SSL Admin Folder? <font size='1'>( You will need to set this up manually with your HTTP server. )</font>
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
                <tr class="client_table_body">
                    <td>
                        LDAP IP Address or DNS name
                    </td>
                    <td>
                       <input type="text" name="ldap_host" style="width:50%" value="192.168.nnn.nnn" disabled/>
                    </td>
                </tr>
                <tr class="client_table_body">
                    <td>
                        LDAP Port <font size='1'>(Usually not to be changed)</font>
                    </td>
                    <td>
                        <input type="text" name="ldap_port" style="width:50%" value="3268" disabled/>
                    </td>
                </tr>
                <tr class="client_table_body">
                    <td>
                        Redirect Page Timeout <font size="1">( For changes made in the admin panel. )</font>
                    </td>
                    <td>
                        <input type="text" name="page_timeout" style="width:50%" value="0"/>
                    </td>
                </tr>
                <tr class="client_table_body">
                    <td>
                        Default client URL refresh time
                    </td>
                    <td>
                        <input type="text" name="client_refresh" style="width:50%" value="30"/>
                    </td>
                </tr>
                <tr class="client_table_body">
                    <td>
                        Internal Admin Name
                    </td>
                    <td>
                        <input type="text" name="uns_admin_name" style="width:50%" />
                    </td>
                </tr>
                <tr class="client_table_body">
                    <td>
                        Internal Admin Password <font size="1">( Needed even if LDAP is used. )</font>
                    </td>
                    <td>
                        <input type="password" name="uns_admin_pwd" style="width:50%" />
                    </td>
                </tr>
                <tr class="client_table_body">
                    <td>
                        Internal Admin email
                    </td>
                    <td>
                        <input type="text" name="uns_admin_email" style="width:50%" />
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