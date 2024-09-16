<input type="hidden" value="{$Kunde->kKunde}" id="logged_userid" data-url="{$cCanonicalURL}?bestellung=">
      
{col cols=12 lg=6 class="account-data-item account-data-item-orders "}
{block name='account-my-account-orders-content'}
    {card no-body=true}
        {cardheader}
            {block name='account-my-account-orders-content-header'}
                {row class="align-items-center-util"}
                    {col}
                        <span class="h3">
                            {link class='text-decoration-none-util' href="$cCanonicalURL?bestellungen=1&abo=1"}
                            Abonnements
                            {/link}
                        </span>
                    {/col}
                    {col class="col-auto font-size-sm"}
                        {link href="$cCanonicalURL?bestellungen=1&abo=1"}
                            {lang key='showAll'}
                        {/link}
                    {/col}
                {/row}
            {/block}
        {/cardheader}
  
        {if isset($Bestellungen) && $Bestellungen|@count > 0}
      
            {block name='account-my-account-orders-body'}
                <div class="table-responsive">

                    <table class="table table-vertical-middle table-hover abo_orders">
                    
                    <tbody>
                    <tr title="" 
                    class="clickable-row cursor-pointer"
                    data-toggle="tooltip"
                    data-placement="top"
                    data-boundary="window">
                    <td><b>Startdatum</b></td>
                    <td><b>Auftragsnummer</b></td>
                    <td><b>Nächstes Datum</b></td>
                    <td>
                    <b>Zahlungsstatus</b>
                    </td>
                    <td class="text-right-util d-none d-md-table-cell">
                    <b> Sicht</b>
                    </td>
                </tr>

            

                </tbody>
                    </table>
                    </div>
                {/block}
            {else}
                {block name='account-my-account-orders-content-nodata'}
                    {cardbody}
                        {lang key='noOrdersYet' section='account data'}
                    {/cardbody}
                {/block}
            {/if}
        {/card}
    {/block}
    {/col}