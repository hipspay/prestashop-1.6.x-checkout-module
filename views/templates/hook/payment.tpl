{*
 * 2008 - 2017 Presto-Changeo
 *
* MODULE Hips Checkout Payment
*
* @version   1.0.0
 * @author    Presto-Changeo <info@presto-changeo.com>
 * @link      http://www.presto-changeo.com
 * @copyright Copyright (c) permanent, Presto-Changeo
 * @license   Addons PrestaShop license limitation
 *
 * NOTICE OF LICENSE
 *
 * Don't use this module on several shops. The license provided by PrestaShop Addons 
 * for all its modules is valid only once for a single shop.
 *}
<div class="row">
	<div class="col-xs-12 col-md-6">
		<p class="payment_module" id="hips_container">
			<a href="{if $active}{$this_validation_link}{else}javascript:alert('{l s='The Merchant has not configured this payment method yet, Order will not be valid' mod='hipscheckout'}');location.href='{$this_validation_link}'{/if}" title="{l s='Pay with a Credit Card' mod='hipscheckout'}">
				<img src="{$this_path|escape:'html'}views/img/combo.jpg" alt="{$hips_cards|escape:'html'}" />
				{l s='Pay with a Credit Card' mod='hipscheckout'}
				<br style="clear:both;" />
			</a>
		</p>
    </div>
</div>