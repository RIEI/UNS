{include file="header.tpl"}
<form name="client_copy" action="?func=edit_urls&client={$client}&cl_func=copy2_proc" method="POST">
    <table>
        <tr>
            <th>Choose Clients to Copy URLs to:</th>
        </tr>
        <tr>
            <td>
                <select name="copy_clients[]" style="width:100%;" size="10" multiple="multiple">
                {foreach from=$clients item="client"}
                    <option value="{$client.0}">{$client.1}</option>
                {/foreach}
                </select>
                <input type="hidden" name="urls" value="{$urls_imp}">
            </td>
        </tr>
        <tr>
            <td align="center">
                <input type='submit' name="submit" value='submit'>
            </td>
        </tr>
    </table>
</form>
{include file="footer.tpl"}