<?xml version="1.0" encoding="utf-8"?>
<uns>
    <emerg>{$UNS_Emerg_Flag}</emerg>
    <error>{$UNS_Error_flag}</error>
    <clients>
        {foreach from=$UNS_Clients_All item="client"}
        <client ref="{$client.path}" id="{$client.id}">{$client.name}</client>
        {/foreach}
    </clients>
</uns>