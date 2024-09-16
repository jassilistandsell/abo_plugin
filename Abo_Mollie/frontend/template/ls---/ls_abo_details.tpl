<li>
{block name='account-order-details-shipping-address'}
    {lang key='aboheading' section='checkout'}:
    <span class="order-details-data-item">

    {if $aboDetails.startDate}
    {block name='account-order-details-abo-details'}
                <ul class="list-unstyled inc-abo-details">
                <li> <b>Interval:</b> <span class="start_date">{$aboDetails.interval}</span></li>
                <li> <b>Start Date:</b> <span class="start_date">{$aboDetails.startDate}</span></li>
                <li> <b>Next Date:</b> <span class="next_date">{$aboDetails.nextStartDate} </span></li><br>
                <li><a class="btn btn-primary abbestellen ff-secondary" href="#" data-orderid="{$Bestellung->cBestellNr}">Abbestellen</a></li>
                </ul>
    {/block}
   {else}
    <p>{lang key='noaboerrormessage' section='checkout'}</p>
  {/if}
    </span>
{/block}
</li>



       
