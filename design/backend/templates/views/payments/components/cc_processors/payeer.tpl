{* $Id: payeer.tpl  $cas *}

<div class="control-group">
	<label class="control-label" for="m_url">{__("payeer_url")}:</label>
	<div class="controls">
		<input type="text" name="payment_data[processor_params][m_url]" id="m_url" value="{if $processor_params.m_url == ""}//payeer.com/merchant/{/if}{$processor_params.m_url}" class="input-text" />
	</div>
</div>

<div class="control-group">
	<label class="control-label" for="m_shop">{__("payeer_id")}:</label>
	<div class="controls">
		<input type="text" name="payment_data[processor_params][m_shop]" id="m_shop" value="{$processor_params.m_shop}" class="input-text" size="100" />
	</div>
</div>

<div class="control-group">
	<label class="control-label" for="m_key">{__("payeer_secret_key")}:</label>
	<div class="controls">
		<input type="text" name="payment_data[processor_params][m_key]" id="m_key" value="{$processor_params.m_key}" class="input-text" size="100" />
	</div>
</div>

<div class="control-group">
    <label class="control-label" for="currency">{__("payeer_currency")}:</label>
    <div class="controls">
        <select name="payment_data[processor_params][currency]" id="currency">
            <option value="EUR" {if $processor_params.currency == "EUR"}selected="selected"{/if}>{__("currency_code_eur")}</option>
            <option value="USD" {if $processor_params.currency == "USD"}selected="selected"{/if}>{__("currency_code_usd")}</option>
            <option value="RUB" {if $processor_params.currency == "RUB"}selected="selected"{/if}>{__("currency_code_rur")}</option>
        </select>
    </div>
</div>

<div class="control-group">
	<label class="control-label" for="m_desc">{__("payeer_comment")}:</label>
	<div class="controls">
		<input type="text" name="payment_data[processor_params][m_desc]" id="m_desc" value="{$processor_params.m_desc}" class="input-text" size="100" />
	</div>
</div>

<div class="control-group">
	<label class="control-label" for="pathlog">{__("payeer_pathlog")}:</label>
	<div class="controls">
		<input type="text" name="payment_data[processor_params][pathlog]" id="pathlog" value="{$processor_params.pathlog}" class="input-text" size="100" />
	</div>
</div>

<div class="control-group">
	<label class="control-label" for="ipfilter">{__("payeer_ipfilter")}:</label>
	<div class="controls">
		<input type="text" name="payment_data[processor_params][ipfilter]" id="ipfilter" value="{$processor_params.ipfilter}" class="input-text" size="100" />
	</div>
</div>

<div class="control-group">
	<label class="control-label" for="emailerr">{__("payeer_emailerr")}:</label>
	<div class="controls">
		<input type="text" name="payment_data[processor_params][emailerr]" id="emailerr" value="{$processor_params.emailerr}" class="input-text" size="100" />
	</div>
</div>