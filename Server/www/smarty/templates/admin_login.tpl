<html>
    <head>
        <title>{$UNS_Title} Admin Panel Login</title>
        <link rel="stylesheet" href="{$UNS_URL}configs/styles.css">
        {$redirect|default:''}
    </head>
    <body class="main_body">
        <h2>{$message|default:""}</h2>
        <form method="POST" action="{$UNS_URL}admin/?login=1">
            <table width="100%" height="95%">
                <tr>
                    <td align="center">
                        <img src="../res/logo.png" >
                        <table class="navtd" align="center" valign="center" border="1px" style="color:000; width:30%;">
                            <tr align="center" class="client_table_head">
                                <td>
                                    Username:<br />{$ldap}
                                </td>
                                <td>
                                    <input type="text" style="width:400px;" name="username">
                                </td>
                            </tr>
                            <tr align="center" class="client_table_body">
                                <td>
                                    Password:
                                </td>
                                <td>
                                    <input type="password" name="password" style="width:400px;">
                                </td>
                            </tr>
                            <tr align="center" class="client_table_tail">
                                <td colspan="2" align="center">
                                    <input type="submit" value="Login" name="B1">
                                </td>
                            </tr>
                        </table>
                    </td>
                </tr>
            </table>
        </form>
    </body>
</html>