{include file="header.tpl"}
    <table align="center">
        <tr>
            <th colspan=2>{$title_of_job}</th>
        </tr>
        {foreach from=$all_messages item="message"}
        <tr>
            <td class="td">{$message.time}</td>
            <td class="msg">{$message.msg}</td>
        </tr>
        {/foreach}
</table>
{include file="footer.tpl"}