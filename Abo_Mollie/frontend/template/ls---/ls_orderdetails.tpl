{block name='account-orders'}
    {block name='heading'}
        <div class="h1">{lang key='yourOrders' section='login'}</div>
    {/block}
    {block name='account-orders-content'}
        {if $Bestellungen|count > 0}
            {block name='account-orders-orders'}
                {get_static_route id='jtl.php' assign='ordersURL'}
  
               {if $suburl == '1' }
                {foreach $Bestellungen as $order}
                    {if in_array($order->cBestellNr, $abotablecheck)}
                    {card no-body=true class='account-orders-item'}
                        {cardheader}
                            {link href="{$ordersURL}?bestellung={$order->kBestellung}"
                                title="{lang key='showOrder' section='login'}: {lang key='orderNo' section='login'} {$order->cBestellNr}"
                                data=["toggle" => "tooltip", "placement" => "bottom"]
                            }
                                {row}
                                    {col cols=6 md=3 order=1}
                                        <strong><i class="far fa-calendar-alt"></i> {$order->dBestelldatum}</strong>
                                    {/col}
                                    {col cols=6 md=2 order=4 order-md=2}
                                        {$order->cBestellwertLocalized}
                                    {/col}
                                    {col cols=4 md=2 order=2 order-md=3}
                                        {$order->cBestellNr}
                                    {/col}
                                    {col cols=6 md=4 order=5 order-md=4}
                                        {lang key='orderStatus' section='login'}: {$order->Status}
                                    {/col}
                                    {col cols=2 md=1 order=3 order-md=5 class="text-right-util"}
                                        <i class="fa fa-eye"></i>
                                    {/col}
                                {/row}
                            {/link}
                        {/cardheader}
                    {/card}
                  
                {/if}
                {/foreach}
               {/if}
            {/block}
        {else}
            {block name='account-orders-alert'}
                {alert variant="info"}{lang key='noEntriesAvailable'}{/alert}
            {/block}
        {/if}
        {block name='account-orders-actions'}
            {row}
                {col md=3 cols=12}
                    {link class="btn btn-outline-primary btn-block" href="{get_static_route id='jtl.php'}"}
                        {lang key='back'}
                    {/link}
                {/col}
            {/row}
        {/block}
    {/block}
{/block}
