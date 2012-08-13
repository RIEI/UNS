{include file="header.tpl"}
            <form action="?func=edit_urls&client={$client_name}&cl_func={$cl_func}" >
                <table>
                        {foreach from=$all_messages item="message"}
                    <tr>
                        <td class="td">{$message.time}</td>
                        <td class="msg">{$message.msg}</td>
                    </tr>
                        {/foreach}
                    <tr>
                        <td>
                        {$Question}
                        </td>
                    </tr>    
                    <tr>
                        <td>
                            <input type="submit" value="Yes?">
                        </td>
                        
                        <td>
                            <input type="submit" value="No?">
                        </td>
                    </tr>
                </table>
            </form>
{include file="footer.tpl"}