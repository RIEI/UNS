<html>
    <head>
        <title>{$UNS_Title}</title>
    </head>
    <body style="margin: 0px 0px 0px 0px;" >
        <div align="center">
            <table width="75%">
                <tr>
                    <th>Clients</th>
                </tr>
                {foreach name=outer item=UNS_Client from=$UNS_Clients_All}
                
                <tr>
                    <td>
                        <a href="{$UNS_Client.path}" target="_blank">{$UNS_Client.name}</a>
                    </td>
                </tr>
            {/foreach}
</table>
        </div>
    </body>
</html>
