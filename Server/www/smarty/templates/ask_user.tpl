{include file="header.tpl"}
            <form action="?func=edit_urls&client={$client}&cl_func={$cl_func}" method="POST">
                <table>
                    <tr>
                        <td>
                        {$Question}
                        </td>
                    </tr>    
                    <tr>
                        <td>
                            <input type="submit" name="save_q" value="Yes">
                        </td>
                        
                        <td>
                            <input type="submit" name="save_q" value="No">
                        </td>
                    </tr>
                </table>
                        <input type="hidden" name="id" value="{$saved_id}" >
                        <input type="hidden" name="allids" value="{$allids}" >
                        <input type="hidden" name="jobtodo" value="{$jobtodo}" >
            </form>
{include file="footer.tpl"}