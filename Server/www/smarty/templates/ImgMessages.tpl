{include file="header.tpl"}
                <table width="100%" border="1px">
                    <tr class="client_table_head">
                        <th colspan="5" align="center">
                    <form name="all_img" action="?func=img_proc" method="POST">
                            Edit Image Messages
                        </th>
                    </tr>
                    <tr class="client_table_head">
                        <th align="center" colspan="2">Enabled?</th>
                        <th align="center">Name</th>
                        <th align="center" width="50px">Refresh Time</th>
                        <th align="center">Select All: <input type="checkbox" name="toggle_chk" onclick="SetAllCheckBoxes('all_emerg', 'ids[]');"></th>
                    </tr>
                    {foreach from=$AllImgMessages item="img"}
                    <tr class="client_table_body">
                        <td onclick="expandcontract('mesgRow{$img.id}','mesgClickIcon{$img.id}')"
                            id="mesgClickIcon{$img.id}" style="cursor: pointer; cursor: hand;">+</td>
                        </td>
                        <td align="center">
                            {if $img.enabled == '1'}
                                <font style="color:greenyellow;"><b>&#10003;</b></font>
                            {else}
                                <font style="color:red;"><b>X</b></font>
                            {/if}
                        </td>
                        <td style="width:25%;">
                            <input type="hidden" name="id[]" value="{$img.id}"/>
                            <input type="text" name="name[]" style="width:90%;" value="{$img.name}"/>
                        </td>
                        <td>
                            <a class="links" href="{$host_path}?out=html&amp;type=img&amp;msgid={$img.id}" target="_blank">{$host_path}?out=html&amp;type=img&amp;msgid={$img.id}</a>
                        </td>
                        <td align="center">
                            <input type="checkbox" name="remove_[]" value="{$img.id}"/>
                        </td>
                        <tbody id="mesgRow{$img.id}" style="display:none">
                        <tr>
                            <td colspan="5">
                                {if $img.wrapper == '1'}
                                    <input type="text" name="body[]" style="width:100%" value="{$img.body}" />
                                {else}
                                    <textarea cols="64" rows="32" id="img_message_body_text">{$img.body}</textarea>
                                {/if}
                                <br />
                                <br />
                            </td>
                        </tr>
                        </tbody>
                    </tr>
                    {foreachelse}
                        <tr>
                            <td align="center">
                                No Image Messages Yet :/
                            </td>
                        </tr>    

                    {/foreach}
                    <tr class="client_table_tail">
                        <td align="center" colspan="3"></td>
                        <td align="center"><input type="submit" name="jobtodo" value="Update Refresh"></td>
                        <td align="center">
                        <input type="submit" name="jobtodo" value="Delete">
                        <input type="submit" name="jobtodo" value="Enable/Disable"> 
                    </form>

                        </td>
                    </tr>
                    <tr class="client_table_tail">
                        <td colspan="5">
                            <form name="add_img" action="?func=add_img" method="POST">
                            <table align="left">
                                <tr>
                                    <td align="right" width="250px">
                                        URLS (One per line):
                                    </td>
                                    <td align="left">
                                        <textarea name="new_img_urls" rows="10" cols="60" >http://</textarea>
                                    </td>
                                </tr>
                                <tr class="client_table_tail">
                                    <td align="right" >
                                        HTML Wrapper:
                                    </td>
                                    <td align="left" width="250px">
                                        <input type="checkbox" name="wrapper">
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
                                    </td>
                                </tr>
                            </table>
                            </form>
                        </td>
                    </tr>
                </table>
{include file="footer.tpl"}