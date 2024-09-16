{* {assign var ="abotrue" value="0"} *}
                  
{* {foreach $Bestellung->Positionen as $Position}
    {if $Position->Artikel->FunktionsAttribute['ls_abo'] == '1'}
       {assign var ="abotrue" value="1"}
       {break}
    {/if}
{/foreach} *}
    {if isset($smarty.session.Zahlungsart->nWaehrendBestellung) && $smarty.session.Zahlungsart->nWaehrendBestellung == 1}
        <h2>{lang key='orderCompletedPre' section='checkout'}</h2>
    {elseif $Bestellung->Zahlungsart->cModulId !== 'za_lastschrift_jtl'}
        {* {if $abotrue == 1 }  *}
        <h2>{lang key='aboorderCompletedPost' section='checkout'}</h2>
        {* {else}
        <h2>{lang key='orderCompletedPost' section='checkout'}</h2>
         {/if} *}
    {/if}
{/container}
{/block}
{block name='checkout-order-completed-include-extension'}
{include file='snippets/extension.tpl'}
{/block}
{* {if $abotrue == '1' }  *}
<input type='hidden' id='abo_details' name='abo_details'
data-orderid='{$Bestellung->cBestellNr}'
data-customerID='{$Bestellung->kKunde}'
data-productsprise='{$Bestellung->fGesamtsumme}'>
{* {/if} *}