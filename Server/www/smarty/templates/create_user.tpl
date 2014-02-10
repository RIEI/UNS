{include file="header.tpl"}
<form name="new_user" method="POST" action="?func=create_user&amp;job_to_do=create_proc">
    <table align="center" border="1">
        <tr class="client_table_head">
            <th colspan="2">
                Add New User
            </th>
        </tr>
        <tr class="client_table_body">
            <td>
                Username:
            </td>
            <td>
                <input type="text" name="new_username">
            </td>
        </tr>
        <tr class="client_table_body">
            <td>
                Domain ( blank if none ):
            </td>
            <td>
                <input type="text" name="new_domain" onchange="endisable_pass();">
            </td>
        </tr>
        <tr class="client_table_body">
            <td>
                Password (For non-domain user):
            </td>
            <td>
                <input type="password" name="new_password" id="new_password" onChange="password_check();" >
            </td>
        </tr>
        <tr class="client_table_body">
            <td>
                Password again (For non-domain user):
            </td>
            <td>
                <input type="password" name="new_password_again" id="new_password_again" onChange="password_check();" >
            </td>
        </tr>
        <tr>
            <td colspan="2">
                <div class="divCheckResult" id="divCheckPasswordMatch">

                </div>
            </td>
        </tr>
        <tr class="client_table_tail">
            <td colspan="2" align="center">
                <input type="submit" name="create_new_user" value="Create New User">
            </td>
        </tr>
    </table>
</form>

{include file="footer.tpl"}