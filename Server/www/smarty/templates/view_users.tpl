{include file="header.tpl"}
    <table width="100%" border="1">
        <tr class="client_table_head" >
            <th>
                Username / Domain
            </th>
            <th>
                Permissions
            </th>
        </tr>
    {foreach from=$UNS_all_users item="user"}
        <tr class="client_table_body">
            <td>
                <form name="edit_users_{$user.id}" method="POST" action="?func=edit_users">
                <table width="100%">
                    <tr>
                        <td>
                            <input type="text" name="username" value="{$user.username}">
                        </td>
                    </tr>
                    <tr>
                        <td>
                            <input type="text" name="domain" value="{$user.domain}">
                        </td>
                    </tr>
                </table>
            </td>
            <td>
                <table>
                    <tr>
                        <td>
                            Edit Clients:<br>
                            Edit Emergency Messages:<br>
                            Edit Custom Messages:<br>
                            Edit Image Messages:<br>
                        </td>
                        <td align="left">
                            <input type="checkbox" name="edit_clients" {if $user.edit_clients == '1'}Checked{else}{/if}><br>
                            <input type="checkbox" name="edit_emerg" {if $user.edit_emerg == '1'}Checked{else}{/if}><br>
                            <input type="checkbox" name="c_messages" {if $user.c_messages == '1'}Checked{else}{/if}><br>
                            <input type="checkbox" name="img_messages" {if $user.img_messages == '1'}Checked{else}{/if}><br>
                        </td>
                        <td>
                            Edit RSS Feeds:<br>
                            Edit User Permissions:<br>
                            Edit UNS Options:<br>
                        </td>
                        <td align="left">
                            <input type="checkbox" name="rss_feeds" {if $user.rss_feeds == '1'}Checked{else}{/if}><br>
                            <input type="checkbox" name="edit_users" {if $user.edit_users == '1'}Checked{else}{/if}><br>
                            <input type="checkbox" name="edit_options" {if $user.edit_options == '1'}Checked{else}{/if}><br>
                        </td>
                        <td align="center">
                            <input type="submit" name="jobtodo" value="Update Permissions">
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
        <tr class="client_table_head" >
            <th></th>
            <th>
                Other:
            </th>
        </tr>
        <tr class="client_table_tail">
            <td>
            </td>
            <td align="center">
                <input type="hidden" name="failed" value="{$user.failed}">
                <input type="hidden" name="userid" value="{$user.id}">
                
                {if $user.domain == ""}<input type="submit" name="jobtodo" value="Reset Failed Logins"> ({$user.failed}) <---> 
                <input type="submit" name="jobtodo" value="Send Password Reset"> <--->{/if}
                <input type="submit" name="jobtodo" value="Remove"> <--->
                <input type="submit" name="jobtodo" value="{if $user.disabled  == '1'}Enable{else}Disable{/if}">
            </td>
        </tr>
        <tr class="client_table_tail">
            <td colspan="2">
                </form>
            </td>
        </tr>
    {foreachelse}
        <tr class="client_table_tail">
            <td colspan="3">
            There are no Users Odd...
            </td>
        </tr>
    {/foreach}
    </table>
<form name="new_user" method="POST" action="?func=create_user">
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
                <input type="text" name="new_username" value="">
            </td>
        </tr>
        <tr class="client_table_body">
            <td>
                Domain ( blank if none ):
            </td>
            <td>
                <input type="text" name="new_domain" value="">
            </td>
        </tr>
        <tr class="client_table_body">
            <td>
                Password (For non-domain user):
            </td>
            <td>
                <input type="password" name="new_password" value="">
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