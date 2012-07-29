{include file="header.tpl"}
                <script type="text/javascript">
                function SetAllCheckBoxes(FormName, FieldName, CheckValue)
                {
                        if(!document.forms[FormName])
                                return;
                        var objCheckBoxes = document.forms[FormName].elements[FieldName];
                        if(!objCheckBoxes)
                                return;
                        var countCheckBoxes = objCheckBoxes.length;
                        if(!countCheckBoxes)
                                objCheckBoxes.checked = CheckValue;
                        else
                                // set the check value for all check boxes
                                for(var i = 0; i < countCheckBoxes; i++)
                                        objCheckBoxes[i].checked = CheckValue;
                }
                function expandcontract(tbodyid,ClickIcon)
                {
                        if (document.getElementById(ClickIcon).innerHTML == "+")
                        {
                                document.getElementById(tbodyid).style.display = "";
                                document.getElementById(ClickIcon).innerHTML = "-";
                        }else{
                                document.getElementById(tbodyid).style.display = "none";
                                document.getElementById(ClickIcon).innerHTML = "+";
                        }
                }
                </script>
                <table border="1px" align="center">
                    <tr valign="center" class="client_table_head">
                        <td>
                            Client Name:
                        </td>
                        <td>
                            <table width="100%">
                                <tr>
                                    <td width="80%">
                                    <br />
                                        <form name="client_rename" action="?func=rename_client&client={$client_name}" method="POST">
                                            <input type="text" name="client_name" style="width:400px;" value="{$friendly}"/>
                                            <input type="hidden" name="client_id" value="{$client_name}"/>
                                            <input type="submit" value="Rename"/>
                                        </form>
                                    </td>
                                    <td>
                                        LED Group:<br/>
                                        <form name="client_led" action="?func=client_led_set" method="POST">
                                            <input type="hidden" name="cl_id" value="{$client_name}"/>
                                            <select name="cl_led_id" onchange='this.form.submit()'>
                                            {foreach from=$led_groups item="led"}
                                                <option value="{$led.group}" {$led.selected} >LED {$led.group}</option>
                                            {/foreach}
                                            </select>
                                        </form>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>
                    <tr class="client_table_body">
                        <td>
                            Client URL:
                        </td>
                        <td>
                            <a class="links" href="{$client_url}id={$client_name}" target="_blank">{$client_url}id={$client_name}</a>
                        </td>
                    </tr>
                </table>
                <hr />
                <form name="client_edit" action="?func=edit_urls&client={$client_name}&cl_func=edit_proc" method="POST">
                <table border="1px" width="100%">
                    <tr class="client_table_head">
                        <th colspan="4">
                            Messages
                        </th>
                    </tr>
                    <tr class="client_table_head">
                        <th>URL</th><th>Set Refresh</th><th>Disabled</th><th width="120px">Select</th>
                    </tr>
                    {foreach from=$client_urls item="links"}
                    <tr class="client_table_body">
                        <td>
                            <a class="links" href="{$links.url}" target="_blank">{$links.url}</a>
                            <input type="hidden" name="URLid[]" value="{$links.id}">
                        </td>
                        <td align="center">
                            <input type='text' style="width:45px;" name="refresh_time[]" value='{$links.refresh}'>
                        </td>
                        <td align="center">
                            <input type="checkbox" name="disabled[]" value="{$links.disabled}">
                        </td>
                        <th>
                            <input type="checkbox" name="urls[]" value="{$links.id}">
                        </th>

                    </tr>
                    {foreachelse}
                    <tr class="client_table_body">
                        <td align="center" colspan="4">There are no URLs added yet.</td>
                    </tr>
                    {/foreach}
                    <tr class="client_table_tail">
                        <td align="center">
                            <input type='submit' name="jobtodo" value='Copy'>
                            <input type='submit' name="jobtodo" value='Save To List'>
                            <input type='submit' name="jobtodo" value='Remove'>
                            <input type='submit' name="jobtodo" value='Disable'>
                        </td>
                        <td align="center">
                            <input type='submit' name="refresh" value='Set All'>
                        </td>
                        <td></td>
                        <td align="center">
                            <input type="button" onclick="SetAllCheckBoxes('client_edit', 'urls[]', true);" value="Check">
                            <input type="button" onclick="SetAllCheckBoxes('client_edit', 'urls[]', false);" value="Uncheck">
                        </td>
                    </tr>
                    </form>
                    <tr class="client_table_tail">
                        <td colspan="4">
                            <form name="save_new" action="?func=edit_urls&client={$client_name}&cl_func=add_url_batch" method="POST">
                            <table style="width: 100%">
                                <tr>
                                    <td style="width: 200px" valign="center">
                                        URLs <font size=1>(one per line)</font>:
                                    </td>
                                    <td>
                                        <textarea name="NEWURLS" rows="10" style="border:1px; width:90%; margin:5px 0; padding:3px;">http://</textarea>
                                    </td>
                                </tr>
                                <tr>
                                    <td valign="center">
                                        Refresh Times for all:
                                    </td>
                                    <td>
                                        <input type="text" name="refresh" value="{$refresh}">
                                    </td>
                                </tr>
                                <tr>
                                    <td>
                                    </td>
                                    <td>
                                        <input type='submit' value='Add URLs'>
                                    </td>
                                </tr>
                            </table>
                            </form>
                        </td>
                    </tr>
                </table>
                <hr />
                <table border="1px" class="all_tables">
                    <tr class="client_table_head">
                        <th colspan="5">Saved Lists</th>
                    </tr>
                    <tr class="client_table_head">
                        <th>+/-</th><th>Name</th><th>Date</th><th>Options</th>
                    </tr>
                    <tr class="client_table_body">
                        <td
                            onclick="expandcontract('SavedRow<?php echo $tablerowid;?>','SavedClickIcon<?php echo $tablerowid;?>')"
                            id="SavedClickIcon<?php echo $tablerowid;?>" style="cursor: pointer; cursor: hand;">+</td>
                        <td>
                            <?php echo $client_arc['name'];?>
                        </td>
                        <td>
                            <?php echo date('F j, Y, g:i a', $client_arc['date']);?>
                        </td>
                        <td>
                            <table>
                                <tr>
                                    <td>
                                        <form name="saved" action="?func=edit_urls&client=<?php echo $client_get;?>&cl_func=restore" method="POST">
                                            <input type="hidden" name="urls" value="<?php echo $client_arc['urls']; ?>">
                                            <input type='submit' value='Restore'>
                                        </form>
                                    </td>
                                    <td>
                                        <form name="saved" action="?func=edit_urls&client=<?php echo $client_get;?>&cl_func=remove" method="POST">
                                            <input type="hidden" name="id" value="<?php echo $client_arc['id']; ?>">
                                            <input type='submit' value='Remove'>
                                        </form>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>
                    <tbody id="SavedRow<?php echo $tablerowid;?>" style="display:none">
                        <tr>
                            <td colspan="4">
                                <table border="1" width="100%">
                                    <tr class="client_table_body">
                                        <td><?php echo $url;?></td>
                                    </tr>
                                </table>
                            </td>
                        </tr>
                    </tbody>
                </table>
                <hr />
                <table border="1px" class="all_tables">
                    <tr class="client_table_head">
                        <th colspan="4">Clients Archived Links</th>
                    </tr>
                    <tr class="client_table_head">
                        <th>+/-</th><th>Name</th><th>Date</th><th>Options</th>
                    </tr>
                    <tr class="client_table_body">
                        <td onclick="expandcontract('Row<?php echo $tablerowid;?>','ClickIcon<?php echo $tablerowid;?>')"
                            id="ClickIcon<?php echo $tablerowid;?>" style="cursor: pointer; cursor: hand;">+</td>
                        <td><?php echo $client_arc['name'];?></td>
                        <td><?php echo date('F j, Y, g:i a', $client_arc['date']);?></td>
                        <td>
                            <table>
                                <tr>
                                    <td>
                                        <form name="saved" action="?func=edit_urls&client=<?php echo $client_get;?>&cl_func=restore" method="POST">
                                            <input type="hidden" name="urls" value="<?php echo $client_arc['urls']; ?>">
                                            <input type='submit' name="copy" value='Restore'>
                                        </form>
                                    </td>
                                    <td>
                                        <form name="saved" action="?func=rm_arc_urls&client=<?php echo $client_get;?>" method="POST">
                                            <input type="hidden" name="id" value="<?php echo $client_arc['id']; ?>">
                                            <input type='submit' name="copy" value='Remove'>
                                        </form>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>
                    <tbody id="Row<?php echo $tablerowid;?>" style="display:none">
                        <tr>
                            <td colspan="4">
                                <table border="1" width="100%">
                                    <tr class="client_table_body">
                                        <td><?php echo $url;?></td>
                                    </tr>
                                </table>
                            </td>
                        </tr>
                    </tbody>
                </table>
                
                
                
{include file="footer.tpl"}