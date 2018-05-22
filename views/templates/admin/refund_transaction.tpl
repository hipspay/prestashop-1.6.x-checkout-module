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
<div class="panel po_main_content" id="refund_transaction">
    
    <div class="panel_header">
        <div class="panel_title">{l s='Refund Transaction' mod='hipscheckout'}</div>
        <div class="panel_info_text">
            <span class="simple_alert"> </span>
            {l s='You must click on Update for a change to take effect' mod='hipscheckout'}
        </div>
        <div class="clear"></div>
    </div>
        
    <div class="two_columns">
        <div class="columns">
            <div class="left_column">
                {l s='Order ID' mod='hipscheckout'}
            </div>
            <div class="right_column">
                <input type="text" size="12" id="refund_order_id" name="refund_order_id" />
                

                <br/>
                <input type="button" value="{l s='Lookup Order' mod='hipscheckout'}" name="searchRefundOrders" class="submit_button" onclick="search_orders(2)"/>
                <input type="button" value="{l s='Reset' mod='hipscheckout'}" name="resetRefundOrders" class="submit_button" onclick="clear_orders(2)"/>
                      
            </div>
            
        </div>
        <div id="refund_order_details">
        </div>        
        <div class="clear"></div>
    </div>
    <div class="clear"></div>
</div>