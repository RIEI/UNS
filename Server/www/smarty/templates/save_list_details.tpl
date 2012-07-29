{include file="header.tpl"}
            <form name="save_new" action="?func=edit_urls&client={$client}&cl_func=save_new" method="POST">
                <table>
                    <tr>
                        <th>Save to List:</th>
                    </tr>
                    <tr>
                        <td valign="center">
                            Name:
                        </td>
                        <td>
                            <input type="text" name="name" value="">
                            <input type="hidden" name="urls" value="{$urls_imp}">
                        </td>
                    </tr>
                    <tr>
                        <td valign="center">
                            Details:
                        </td>
                        <td>
                            <textarea name="details" cols="40" rows="10"></textarea>
                        </td>
                    </tr>
                    <tr>
                        <td align="center">
                            <input type='submit' name="submit" value='submit'>
                        </td>
                    </tr>
                </table>
                    <hr />
                </form>
                <form name="save_append" action="?func=edit_urls&client={$client}&cl_func=save_append" method="POST">
                <table>
                    <tr>
                        <th>Append to List:</th>
                    </tr>
                    <tr>
                        <td>
                            <select name="saved" style="width:100%;" size="10">
                        {foreach from=$saved_lists item="list"}
                            <option value="{$list.id}>">{$list.name}</option>
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