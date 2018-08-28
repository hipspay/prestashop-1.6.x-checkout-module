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

require_once(dirname(__FILE__) . '/../../config/config.inc.php');
require_once(dirname(__FILE__) . '/../../init.php');
require_once('PrestoChangeoClasses/init.php');
require_once('controllers/front/validation.php');

$_POST['module'] = 'hipscheckout';

$controller = new hipscheckoutvalidationModuleFrontController('hipscheckout');
$hips = new HipsCheckout();

$id_cart = Tools::getValue('id_cart');

//if (!$hips->hips_payment_page) {
//    $controller->run();
//} else {
    $controller->postProcessWithoutCart($id_cart);
//}
