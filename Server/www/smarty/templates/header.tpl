<html>
    <head>
        <title>{$UNS_Title} Admin Panel</title>
        <link rel="stylesheet" href="../configs/styles.css">
        {$redirect|default:''}
    </head>
    <body class="main_body">
        <table width="100%">
            <tr>
                <td width="10px">
                    <img src="{$UNS_URL}res/logo.png" title="UNS Logo">
                </td>
                <td align="left" valign="center">
                    <font size="5">{$UNS_Title} Administration Panel</font>
                </td>
                <td align="right">
                    <form name="tz_change" action="?func=chg_tz" method="POST">
                        <select name="cl_timezone" onchange='this.form.submit()'>
                            {foreach from=$UNS_Timezones item="timezone"}
                                <option value="{$timezone.value}" {$timezone.selected|default:""}>{$timezone.label}</option>
                            {/foreach}
                        </select>
                    </form>
                </td>
            </tr>
        </table>
        <table border="1px" width="100%">
            <tr>
                <td class="side_bar" valign="top" width="16%">
                {$side_bar|default: "No Permissions :-("}
                </td>
                <td valign="top" class="main_cell">
                    <table border="1px" width="100%">
                        <tr class="nav_bar">
                        {$nav_bar}
                        </tr>
                    </table>
                