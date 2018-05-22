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


$secure_key = Tools::getValue('secure_key');

$orderId = Tools::getValue('orderId');
$adminOrder = Tools::getValue('adminOrder');

$amt = (float) (Tools::getValue('amt'));
$type = (int) (Tools::getValue('type'));
$id_employee = (int) (Tools::getValue('id_employee'));
$hips_capture_status = (int) (Tools::getValue('hips_capture_status'));
$hips_refund_status = (int) (Tools::getValue('hips_refund_status'));
$hips = new HipsCheckout();
$order = new Order($orderId);

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
    echo $html;
    return false;
}

$html = ' 
	<table cellspacing="10" width="100%">
    <tr> ' . ( $adminOrder != 1 ? '<td align="left" width="155px" style="font-weight:bold;font-size:12px" nowrap>
        	&nbsp;
		</td>' : '');

/**
  Do Capture
 * */
if ($type == 1) {
    if ($orderId != '' && isset($order)) {
        $doCaptureResp = $hips->doFullFillOrder($orderId);
        if (isset($doCaptureResp['error']) && !empty($doCaptureResp['error'])) {
            $html .= '<td align="left" style="font-weight:bold;font-size:12px;color:red;" nowrap>';
            $html .= $doCaptureResp['error']['type'] . ' - ' . $doCaptureResp['error']['message'];
        } elseif (isset($doCaptureResp['success']) && $doCaptureResp['success'] == true) {


            Db::getInstance()->Execute('UPDATE `' . _DB_PREFIX_ . 'hips_refunds_checkout` SET captured = \'1\' WHERE `id_order` = \'' . (int) $orderId . '\'');

            // Db::getInstance()->Execute('UPDATE ' . _DB_PREFIX_ . 'hipsrefunds SET trx_ref_id = ' . $doCaptureResp->MarkForCaptureResp->TxRefIdx . '  WHERE id_order=' . (int) $id_order);

            $html .= '<td align="left" style="font-weight:bold;font-size:12px;color:Green;" nowrap>
             ' . $hips->l('The transaction Capture was successful.') . '';
            $message = new Message();
            $message->message = $hips->l('Transaction FullFill of') . ' $' . $amt;
            $message->id_customer = $order->id_customer;
            $message->id_order = $order->id;
            $message->private = 1;
            $message->id_employee = $id_employee;
            $message->id_cart = $order->id_cart;
            $message->add();
            if ($hips_capture_status != 0) {
                $history = new OrderHistory();
                $history->id_order = $orderId;
                $history->changeIdOrderState((int) ($hips_capture_status), (int) ($orderId));
                $history->id_employee = (int) ($id_employee);
                $carrier = new Carrier((int) ($order->id_carrier), (int) ($order->id_lang));
                $templateVars = array('{followup}' => ($history->id_order_state == _PS_OS_SHIPPING_ && $order->shipping_number) ? str_replace('@', $order->shipping_number, $carrier->url) : '');
                $history->addWithemail(true, $templateVars);
                Configuration::updateValue('HIPSC_CAPTURE_STATUS', $hips_capture_status);
            }
        }
    }
} else if ($type == 2) {
    /**
     * Do Refund
     * */
    if ($orderId != '' && isset($order)) {
        $doRefundResp = $hips->doRevokeOrder($orderId, $amt);
        if (isset($doRefundResp['error']) && !empty($doRefundResp['error'])) {
            $html .= '<td align="left" style="font-weight:bold;font-size:12px;color:red;" nowrap>';
            $html .= $doRefundResp['error']['type'] . ' - ' . $doRefundResp['error']['message'];
        } elseif (isset($doRefundResp['success']) && $doRefundResp['success'] == true) {


            $isVoid = $order->total_paid == $amt ? true : false;
            $ret = Db::getInstance()->ExecuteS('SELECT * FROM ' . _DB_PREFIX_ . 'hips_refunds_checkout WHERE id_order=' . (int) $orderId);
            $order_id_hips = $ret[0]['order_id'];

            Db::getInstance()->Execute('INSERT INTO `' . _DB_PREFIX_ . "hips_refund_history_checkout` (id_order,order_id_hips, "
                . "`amount`,`details`,`date`  ) "
                . "VALUES('" .
                (int) $orderId . "','" .
                (int) $order_id_hips . "','" .
                (float) $amt . "','" .
                (!$isVoid ? 'Credit - ID: ' : 'Void - ID: ') .
                date("Y-m-d h:i:sa") . "',NOW())");

            $html .= '<td align="left" style="font-weight:bold;font-size:12px;color:Green;" nowrap>';
            if (!$isVoid) {
                $html .= $hips->l('The transaction "Credit" was successful.') . ' (Transaction ID :' . $doRefundResp['id'] . ')';
            } else {
                $html .= $hips->l('The transaction "Void" was successful.') . ' (Transaction ID :' . $doRefundResp['id'] . ')';
            }
            $message = new Message();
            //if (isset($doCaptureResp->ReversalResp->ProcStatus) && $doCaptureResp->ReversalResp->ProcStatus == 0) {
            if (!$isVoid) {
                $message->message = $hips->l('Transaction "Credit" of') . ' $' . $amt . ' (Transaction ID :' . $doRefundResp['id'] . ')';
            } else {
                $message->message = $hips->l('Transaction "Void" of') . ' $' . $amt . ' (Transaction ID :' . $doRefundResp['id'] . ')';
            }
            //}
            $message->id_customer = $order->id_customer;
            $message->id_order = $order->id;
            $message->private = 1;
            $message->id_employee = $id_employee;
            $message->id_cart = $order->id_cart;
            $message->add();
            if ($hips_refund_status != 0) {
                $history = new OrderHistory();
                $history->id_order = $orderId;
                $history->changeIdOrderState((int) ($hips_refund_status), (int) ($orderId));
                $history->id_employee = (int) ($id_employee);
                $carrier = new Carrier((int) ($order->id_carrier), (int) ($order->id_lang));
                $templateVars = array('{followup}' => ($history->id_order_state == _PS_OS_SHIPPING_ && $order->shipping_number) ? str_replace('@', $order->shipping_number, $carrier->url) : '');
                $history->addWithemail(true, $templateVars);
                Configuration::updateValue('HIPSC_REFUND_STATUS', $hips_refund_status);
            }
        }
    }
}
$html .= '
		</td>
	</tr>
	</table>';
echo $html;
