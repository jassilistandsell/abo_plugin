
                                     
{if $Artikel->FunktionsAttribute['ls_abo'] == '1'}

{block name='productdetails-details-subs'}

    {col class="sub_abbo_weeks"}


<div class="select_purchasetype">
    <input type="radio" id="one-timeradio" name="typeofpurchase" class="typeofpurchase" value="One time Purchase">
    <label  for="one-timeradio">Einmaliger Kauf</label>

    <input type="radio" id="subscriberadio" name="typeofpurchase" class="typeofpurchase" value="Subscribe">
    <label  for="subscriberadio">Abonnieren</label>
</div>

<div class="interval_dropdown" style="display:none;">
<label> {lang key='interval_label' section='Abo_Mollie'}</label>
<select name="subscription_frequency">
<option value="-" data-coupon="-"> {lang key='select_Interval' section='Abo_Mollie'}</option>
{foreach from=$frequencies item=row}
<option value="{$row.frequency}" data-coupon="{$row.coupon}">{$row.frequency}</option>
{/foreach}
</select>

<div class="abo_discount_div">{lang key='abo_discount_text' section='Abo_Mollie'}</div>

</div>

{/col}

{/block}
{/if}