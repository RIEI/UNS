<html>
    <head>
        <title>{$UNS_Title} Admin Panel</title>
        <link rel="stylesheet" href="{$UNS_URL}configs/styles.css">
    </head>
    <body class="main_body">
        <h2>{$message|default:""}</h2>
        <form method="POST" action="{$UNS_URL}admin/?update_password=1">
            <table class="navtd" align="center" border="1px" style="color:000; width:30%;">
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
                        Old Password:
                    </td>
                    <td>
                        <input type="password" name="oldpassword" style="width:400px;">
                    </td>
                </tr>
                <tr align="center" class="client_table_body">
                    <td>
                        New Password:
                    </td>
                    <td>
                        <input type="password" name="newpassword" style="width:400px;">
                    </td>
                </tr>
                <tr align="center" class="client_table_body">
                    <td>
                        New Password Again:
                    </td>
                    <td>
                        <input type="password" name="newpasswordagain" style="width:400px;">
                    </td>
                </tr>
                <tr align="center" class="client_table_tail">
                    <td colspan="2" align="center">
                        <input type="hidden" value="1" name="update_password">
                        <input type="submit" value="Update Password" name="B1">
                    </td>
                </tr>
            </table>
        </form>
    </body>
</html>