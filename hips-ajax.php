<?php
/**
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
 */
/* SSL Management */
$useSSL = true;

include(dirname(__FILE__) . '/../../config/config.inc.php');
include(dirname(__FILE__) . '/../../init.php');
include(dirname(__FILE__) . '/hipscheckout.php');

$id_shop = (int) Tools::getValue('id_shop');

$orderId = Tools::getValue('orderId');
$adminOrder = Tools::getValue('adminOrder');
$type = (int) (Tools::getValue('type'));
$secure_key = Tools::getValue('secure_key');
$id_employee = (int) (Tools::getValue('id_employee'));
$hips = new HipsCheckout();
$order = new Order((int) ($orderId));
$id = $order->id;
$date = $order->date_add;
$total = $order->total_paid;
$id_lang = (int) (Tools::getValue('id_lang'));
$states = OrderState::getOrderStates((int) ($id_lang));


if ($secure_key != $hips->hips_secure_key) {
    $html = '
        <div class="columns">
            <div class="left_column">               
            </div>
            <div class="right_column">
				' . $hips->l('Your transaction was not processed - Authentication Error') . '
			</div>
		</div>
		';
} else {
    $result = Db::getInstance()->ExecuteS('SELECT * FROM ' . _DB_PREFIX_ . 'hips_refunds_checkout WHERE id_order=' . (int) $orderId);
    if (is_array($result) && count($result) == 1) {
        $paymentStatus = $result[0]['payment_status'];
        $captured = $result[0]['captured'];
    } else {
        $paymentStatus = '';
        $captured = 0;
    }

    if (($type == 1 && ($paymentStatus != 'successful')) || ($type == 2 && ($paymentStatus != 'successful'))) {
        $html = '
            <div class="columns">
                <div class="left_column">               
                </div>
                <div class="right_column">
                    ' . $hips->l('This order was not processed using Hips Payment.') . '
                </div>
            </div>            
        ';
    } else if ($type == 1 && $captured == 1) {
        $html = '
            <div class="columns">
                <div class="left_column">               
                </div>
                <div class="right_column">
                    ' . $hips->l('A capture transaction was already processed for this order.') . '
                </div>
            </div>               
        ';
    } else if ($id == $orderId) {
        $html = '
			<script type="text/javascript">
    		function ajax_call(type) {
	        	var orderId = "";
				var decimal_char =  ".";
				if (type == 1)
				{
					var amt = $("#hips_capture_amt").val();
					if ((amt == "") || (!$("#hips_capture_amt").val().match(/^\d+(?:\.\d+)?$/)))
					{
						alert("' . $hips->l('Please enter a valid capture amount') . '");
						$("#hips_capture_amt").focus();
						return false;
					}
					$.ajax({
						type: "POST",
						url: baseDir + "hips-trans-ajax.php",
						async: false,
						cache: false,
						data: "id_shop=' . $id_shop . '&secure_key=' . $secure_key . '&orderId=' . $orderId . '&id_employee=' . $id_employee . '&adminOrder=' . $adminOrder . '&hips_capture_status="+ $("#hips_capture_status").val() + "&type="+ type + "&amt="+ amt,
						success: function(html){ $("#capture_order_details").html(html); },
						error: function() {alert("ERROR:");}
					});
					$("#capture_order_id").val("");
				}
				if (type == 2)
				{
                    $( "input[name=\'submitRefund\']" ).val("Please Wait...");
                    setTimeout(function(){                        
                    
                        var amt = $("#hips_refund_amt").val();
                        if ((amt == "") || (!$("#hips_refund_amt").val().match(/^\d+(?:\.\d+)?$/)))
                        {
                            alert("' . $hips->l('Please enter a valid refund amount') . '");
                            $("#hips_refund_amt").focus();
                            //$( "input[name=\'submitRefund\']" ).val("Refund");
                            return false;
                        }

                        var vars = new Object();
                        vars["id_shop"] = "' . $id_shop . '";
                        vars["secure_key"] = "' . $secure_key . '";
                        vars["orderId"] = "' . $orderId . '";
                        vars["id_employee"] = "' . $id_employee . '";
                        vars["adminOrder"] = "' . $adminOrder . '";
                        vars["hips_refund_status"] = $("#hips_refund_status").val();
                        vars["type"] = type;
                        vars["amt"] = amt;
                        $.ajax({
                            type: "POST",
                            url: baseDir + "hips-trans-ajax.php",
                            async: false,
                            cache: false,
                            data: vars,
                            success: function(html){ $( "input[name=\'submitRefund\']" ).val("Refund"); $("#refund_order_details").html(html); },
                            error: function() {alert("ERROR:");}
                        });
                        $("#refund_order_id").val("");
                    
                    }, 1000);
            	}
        	}
        	</script>';
        if ($type == 1) {
            $html .= '<div id="capture_order_details">';
        }
        if ($type == 2) {
            $html .= '<div id="refund_order_details">';
        }
        $html .= '
            <div class="columns">
                <div class="left_column">
                    ' . $hips->l('Order Date') . ':
                </div>
                <div class="right_column">
                    ' . $date . '
                </div>
            </div>
			';
        if ($type == 1) {
            $html .= '
                <div class="columns">
                    <div class="left_column">
                        ' . $hips->l('Order Amount') . ':
                    </div>
                    <div class="right_column">
                        $' . $total . '
                    </div>
                </div>
                 <div class="columns">
                    <div class="left_column">
                       ' . $hips->l('Change Order Status') . ':
                    </div>
                    <div class="right_column">
                       <select name="hips_capture_status" id="hips_capture_status" style="width:170px;display:inline;">
						<option value="0" ' . ($hips->hips_ac_status == 0 ? 'selected="selected"' : '') . '>----------</option>';
            foreach ($states as $state) {
                $html .= '<option value="' . $state['id_order_state'] . '" ' . ($hips->hips_ac_status == $state['id_order_state'] ? 'selected="selected"' : '') . '>' . $state['name'] . '</option>';
            }
            $html .= '</select> ' . $hips->l('(Optional)') . '
                    </div>
                </div>

                <div class="columns">
                    <div class="left_column">
                        ' . $hips->l('Capture Amount') . ':
                    </div>
                    <div class="right_column">
                        $&nbsp;<input type="text" style="width:100px;display: inline" id="hips_capture_amt" name="hips_capture_amt" value="' . $total . '" />
                    </div>
                </div>

                <div class="columns">
                    <div class="left_column">
                        &nbsp;<input type="button" value="' . $hips->l('Capture') . '" name="submitCapture" class="submit_button" onclick="if(confirm(\'Are you sure you want to capture the transaction?\')) {ajax_call(1)}" />
                    </div>
                    <div class="right_column">
                   
                    </div>
                </div>
				<div class="clear"></div>
				';
        }
        if ($type == 2) {
            $html .= '
                <div class="columns">
                    <div class="left_column">
                        ' . $hips->l('Order Amount') . ':
                    </div>
                    <div class="right_column">
                        $' . $total . '
                    </div>
                </div>
                <div class="columns">
                    <div class="left_column">
                       ' . $hips->l('Change Order Status') . ':
                    </div>
                    <div class="right_column">
                        	<select name="hips_refund_status" id="hips_refund_status" style="width:170px;display:inline;">
                                <option value="0" ' . (!isset($hips->hips_refund_status) || $hips->hips_refund_status == 0 ? 'selected="selected"' : '') . '>----------</option>';
            foreach ($states as $state) {
                $html .= '<option value="' . $state['id_order_state'] . '" ' . (isset($hips->hips_refund_status) && $hips->hips_refund_status == $state['id_order_state'] ? 'selected="selected"' : '') . '>' . $state['name'] . '</option>';
            }
            $html .= '</select> ' . $hips->l('(Optional)') . '
                    </div>
                </div>
                <div class="columns" style="display: none;">
                    <div class="left_column">
                       ' . $hips->l('Refund Amount') . ':
                    </div>
                    <div class="right_column">
                        $&nbsp;<input type="text" style="width:100px;display: inline" id="hips_refund_amt" name="hips_refund_amt" value="' . $total . '" />
                    </div>
                </div>
                <div class="columns">
                    <div class="left_column">
                       &nbsp;<input type="button" value="' . $hips->l('Refund Total Transaction') . '" name="submitRefund" class="submit_button" onclick="if(confirm(\'Are you sure you want to refund the transaction?\')) {ajax_call(2)}"/>
                    </div>
                    <div class="right_column">
                     
                    </div>
                </div>
			
                <div class="clear"></div>
				';
            $result = Db::getInstance()->ExecuteS('SELECT * FROM ' . _DB_PREFIX_ . 'hips_refund_history_checkout WHERE id_order=' . (int) $orderId);
            if (is_array($result) && count($result) > 0) {
                $html .= '
                <div class="columns">
                    <div class="left_column">
                        ' . $hips->l('Refund History') . '
                    </div>
                    <div class="right_column">
                     
                    </div>
                </div>
                
				';
                foreach ($result as $row) {
                    $html .= ' 
					
                        <div class="columns">
                            <div class="left_column">
                                $' . $row['amount'] . '
                            </div>
                            <div class="right_column">
                                ' . $row['details'] . '
                                <br/>
                                ' . $row['date'] . '
                            </div>
                        </div>
						';
                }
            }
        }
        $html .= '<div class="clear"></div></div>';
    } else {
        $html = ' 
            <div class="columns">
                <div class="left_column">
                  
                </div>
                <div class="right_column">
                  ' . $hips->l('Invalid Order Number') . '
                </div>
            </div>
            <div class="clear"></div>
			';
    }
}
echo $html;
