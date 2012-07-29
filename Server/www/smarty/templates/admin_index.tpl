{include file="header.tpl"}
<!-- <meta http-equiv="refresh" content="240;">  Should I keep this?-->
                <script type="text/javascript">
                <!--
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
                // -->
                </script>
                <form name="client_List" action="?func=remove_cl" method="POST">
                <table border="1px" width="100%">
                    <tr class="client_table_head">
                        <th>Client</th><th>Last Connect</th><th>Last URL</th><th>Remove</th>
                    </tr>
                    <tr class="client_table_body">
                        
                    {foreach from=$AllClients item="client"}
                        <td align="center">
                            <a class="links" href="?func=view_client&client={$client.name}">{$client.friendly}</a>
                        </td>
                        <td align="Center">
                            {$client.date}
                        </td>
                        <td align="Center">
                            {$client.last_url}
                        </td>
                        <td align="Center">
                            <input type="checkbox" name="remove[]" value="{$client.name}"/>
                        </td>
                    </tr>
                    {/foreach}
                    
                    <tr class="client_table_tail">
                        <td colspan="2">
                        </td>
                        <td align="center">
                            <input type="submit" value="Remove Selected" />
                        </td>
                        <td align="center">
                            <input type="button" onclick="SetAllCheckBoxes('client_List', 'remove[]', true);" value="Check">
                            <input type="button" onclick="SetAllCheckBoxes('client_List', 'remove[]', false);" value="Uncheck">
                        </td>
                    </tr>
                </form>
                    <tr class="client_table_tail">
                        <td colspan="4" align="Center">
                            <br />
                            <form name="client_add" action="?func=add_client" method="POST">
                            <table border="1px">
                                <tr class="client_table_body">
                                    <td>
                                        Client Name:
                                    </td>
                                    <td>
                                        <input type="text" name="friendly" />
                                    </td>
                                    <td rowspan="3">
                                        <input type="submit" value="Add Client" />
                                    </td>
                                </tr>
                            </table>
                            </form>
                        </td>
                    </tr>
            </table>
{include file="footer.tpl"}