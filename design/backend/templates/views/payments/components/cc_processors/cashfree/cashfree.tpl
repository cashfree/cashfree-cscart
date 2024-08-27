{* $Id$ *}

{include file="views/payments/components/cc_processors/cashfree/cf_currency.tpl"}
{assign var="currencies" value=""|fn_get_currencies}

<div class="form-field">
    <label for="app_id">{__("cf_app_id")}:</label>
    <input type="text" name="payment_data[processor_params][app_id]" id="app_id" value="{$processor_params.app_id}" class="input-text" />
</div>

<div class="form-field">
    <label for="secret_key">{__("cf_secret_key")}:</label>
    <input type="text" name="payment_data[processor_params][secret_key]" id="secret_key" value="{$processor_params.secret_key}" class="input-text" />
</div>

<div class="form-field">
    <label  for="currency">{__("currency")}:</label>
        <select name="payment_data[processor_params][currency]" id="currency">
            {foreach from=$cf_currencies key="key" item="currency"}
                <option value="{$key}" {if !isset($currencies.$key)} disabled="disabled"{/if} {if $processor_params.currency == $key} selected="selected"{/if}>{__({$currency})}{$currencies.$key}</option>
            {/foreach}
        </select>
</div>

<div class="form-field">
    <label for="order_id_prefix_text">{__("cf_order_id_prefix_text")}:</label>
    <select name="payment_data[processor_params][order_id_prefix_text]" id="order_id_prefix_text">
        <option value="0" {if $processor_params.order_id_prefix_text == "0"}selected="selected"{/if}>{__("no")}</option>
        <option value="1" {if $processor_params.order_id_prefix_text == "1"}selected="selected"{/if}>{__("yes")}</option>
     </select>
</div>

<div class="form-field">
    <label for="order_in_context">{__("cf_order_in_context")}:</label>
    <select name="payment_data[processor_params][order_in_context]" id="order_in_context">
        <option value="0" {if $processor_params.order_in_context == "0"}selected="selected"{/if}>{__("no")}</option>
        <option value="1" {if $processor_params.order_in_context == "1"}selected="selected"{/if}>{__("yes")}</option>
     </select>
</div>

<div class="form-field">
    <label for="enabled_test_mode">{__("cf_enabled_test_mode")}:</label>
    <select name="payment_data[processor_params][enabled_test_mode]" id="enabled_test_mode">
        <option value="0" {if $processor_params.enabled_test_mode == "0"}selected="selected"{/if}>{__("no")}</option>
        <option value="1" {if $processor_params.enabled_test_mode == "1"}selected="selected"{/if}>{__("yes")}</option>
     </select>
</div>