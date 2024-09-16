<form id="frequencyForm" method="post">
    <div id="frequency-container">
        {foreach from=$existingFrequencies item=row}
            <div class="frequency-row" data-frequency="{$row.frequency}">
                <label for="frequency[]">Lieferintervall:</label>
                <select name="frequency[]" class="frequency-input">
                    <option value="7 Tage" {if $row.frequency == '7 Tage'}selected{/if}>7 Tage</option>
                    <option value="14 Tage" {if $row.frequency == '14 Tage'}selected{/if}>14 Tage</option>
                    <option value="30 Tage" {if $row.frequency == '30 Tage'}selected{/if}>30 Tage</option>
                </select>
                <label for="coupon[]">Coupon:</label>
                <input type="text" name="coupon[]" class="coupon-input" placeholder="Enter coupon" value="{$row.coupon}" />
                <input type="hidden" name="kFrequency[]" value="{$row.kFrequency|default:''}" /> <!-- Hidden input for ID -->
            </div>
        {/foreach}

        {if count($existingFrequencies) < 3}
            {section name=extra start=count($existingFrequencies) loop=3}
                <div class="frequency-row" data-frequency="new">
                    <label for="frequency[]">Lieferintervall:</label>
                    <select name="frequency[]" class="frequency-input">
                        <option value="7 Tage">7 Tage</option>
                        <option value="14 Tage">14 Tage</option>
                        <option value="30 Tage">30 Tage</option>
                    </select>
                    <label for="coupon[]">Coupon:</label>
                    <input type="text" name="coupon[]" class="coupon-input" placeholder="Enter coupon" value="" />
                    <input type="hidden" name="kFrequency[]" value="" /> <!-- Hidden input for ID -->
                </div>
            {/section}
        {/if}
    </div>
    <button type="submit">Einreichen</button>
</form>

<div class="run_cronjob">
<h3>Überprüfen Sie die Bestellung für Abo!</h3>
<button name="runcron" class="runcron" type="button">Cron-Job ausführen</button>
</div>


<script src="../../plugins/Abo_Mollie/frontend/js/main.js"></script>
<style>
.run_cronjob {
    margin: 15px 0px;
    border-top: 1px solid #ddd;
    padding: 10px 0px;
}
.run_cronjob button {
    margin: 10px 0px;
    background: #c29ed1;
    border: 3px solid #000000;
    border-radius: 10px;
    font-weight: bold;
}
#frequencyForm button {
    border: 2px solid #000;
    border-radius: 10px;
    font-weight: bold;
}
</style>