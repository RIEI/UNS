{include file="header.tpl"}
                <table width="100%">
                    <tr>
                        <td align="center">
                            <form name="toggle_emerg" action="?func=emerg_proc" method="POST">
                                <font size="4"><b>Toggle Gobal Emergency Messages:</b></font>
                                <input type="submit" value="{$Toggle_Label}" name="toggle_global_emerg" >
                            </form>
                        </td>
                    </tr>
                </table>
                <table width="100%" border="1px">
                    <tr class="client_table_head">
                        <th colspan="4" align="center">
                    <form name="all_emerg" action="?func=emerg_proc" method="POST">
                            Edit Emergency Messages
                        </th>
                    </tr>
                    <tr class="client_table_head">
                        <th align="center">Enabled?</th>
                        <th align="center">URL</th>
                        <th align="center" width="50px">Refresh Time</th>
                        <th align="center">Select All: <input type="checkbox" name="toggle_chk" onclick="SetAllCheckBoxes('all_emerg', 'ids[]');"></th>
                    </tr>
                    {foreach from=$all_emerg_msgs item="emerg"}
                    <tr class="client_table_body">
                        <td align="center">{if $emerg.enabled == '1'}&#10003;{else}X{/if}</td>
                        <td align="center">{$emerg.url} {$emerg.name}</td>
                        <td align="center">
                            <input type="text" style="width:45px;" name="refresh_time[]" value="{$emerg.refresh}">
                            <input type="hidden" name="refresh_ids[]" value="{$emerg.id}">
                        </td>
                        <td align="center"><input type="checkbox" name="ids[]" value="{$emerg.id}"></td>
                    </tr>
                    {foreachelse}
                        <tr>
                            <td align="center">
                                No Emergency Messages Yet :/
                            </td>
                        </tr>    

                    {/foreach}
                    <tr class="client_table_tail">
                        <td align="center" colspan="2"></td>
                        <td align="center"><input type="submit" name="jobtodo" value="Update Refresh"></td>
                        <td align="center">
                        <input type="submit" name="jobtodo" value="Delete">
                        <input type="submit" name="jobtodo" value="Enable/Disable"> 
                    </form>

                        </td>
                    </tr>
                    <tr class="client_table_tail">
                        <td colspan="4">
                            <table align="left">
                                <tr>
                                    <td align="right" width="250px">
                                <form name="add_emerg" action="?func=add_emerg" method="POST">
                                        URLS (One per line):
                                    </td>
                                    <td align="left">
                                        <textarea name="new_emerg_urls" rows="10" cols="60" >http://</textarea>
                                    </td>
                                </tr>
                                <tr class="client_table_tail">
                                    <td align="right" >
                                        Refresh Times for All:
                                    </td>
                                    <td align="left" width="250px">
                                        <input type="text" name="refresh" value="30">
                                    </td>
                                </tr>
                                <tr class="client_table_tail">
                                    <td width="250px">
                                    </td>
                                    <td align="left">
                                        <input type="submit" name="newurls" value="Add New URLs">
                                </form>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>
                </table>
{include file="footer.tpl"}