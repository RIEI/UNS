<html>
    <head>
        <title>{$UNS_Title} Admin Panel</title>
        <link rel="stylesheet" href="../res/styles.css">
        <script type="text/javascript" src="../res/javascript.js"></script>
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
                        {if $UNS_Timezones == ""}    
                        {else}
                        <select name="cl_timezone" onchange='this.form.submit()'>
                            {foreach from=$UNS_Timezones item="timezone"}
                                <option value="{$timezone.value}" {$timezone.selected|default:""}>{$timezone.label}</option>
                            {/foreach}
                        </select>
                        {/if}
                    </form>
                </td>
            </tr>
        </table>
    {if $emerg_flag == '1'}<table width="100%"><tr><td bgcolor="red" align="center"><font size="5">Emergency Messages are Enabled!</font></td></tr></table>{/if}
        <table border="1px" width="100%">
            <tr>
                <td class="side_bar" valign="top" width="16%">
                {$side_bar|default: ":("}
                </td>
                <td valign="top" class="main_cell">
                    <table border="1px" width="100%">
                        <tr class="nav_bar">
                        {$nav_bar}
                        </tr>
                    </table>
                