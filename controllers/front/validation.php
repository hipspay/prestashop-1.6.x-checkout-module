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
class hipscheckoutvalidationModuleFrontController extends ModuleFrontController
{

    public $ssl = true;

    public function setMedia()
    {
        parent::setMedia();
        $this->addJS('https://cdn.hips.com/js/v1/hips.js');
        $this->addJS(__PS_BASE_URI__ . 'modules/hipscheckout/views/js/hipscheckout.js');
        $this->addJS(__PS_BASE_URI__ . 'modules/hipscheckout/views/js/statesManagement.js');
        $this->addCSS(__PS_BASE_URI__ . 'modules/hipscheckout/views/css/hipscheckout.css');
    }

    public function initContent()
    {
        if (Configuration::get('HIPSC_SHOW_LEFT') == 0) {
            $this->display_column_left = false;
        }
        parent::initContent();
    }

    public function init()
    {
        if (Configuration::get('HIPSC_SHOW_LEFT') == 0) {
            $this->display_column_left = false;
        }
        parent::init();
    }

    public function getAddressInformation($id_address)
    {
        $address = new Address($id_address);
        $state = new State($address->id_state);

        return array(
            'email' => $this->context->customer->email,
            'lastname' => Tools::htmlentitiesUTF8($address->lastname),
            'firstname' => Tools::htmlentitiesUTF8($address->firstname),
            'vat_number' => Tools::htmlentitiesUTF8($address->vat_number),
            'dni' => Tools::htmlentitiesUTF8($address->dni),
            'address1' => Tools::htmlentitiesUTF8($address->address1),
            'address2' => Tools::htmlentitiesUTF8($address->address2),
            'company' => Tools::htmlentitiesUTF8($address->company),
            'postcode' => Tools::htmlentitiesUTF8($address->postcode),
            'city' => Tools::htmlentitiesUTF8($address->city),
            'phone' => Tools::htmlentitiesUTF8($address->phone),
            'phone_mobile' => Tools::htmlentitiesUTF8($address->phone_mobile),
            'id_country' => (int) ($address->id_country),
            'name_country' => $address->country,
            'id_state' => (int) ($address->id_state),
            'name_state' => ($state->name),
            'id_address' => $address->id
        );
    }

    public function sendError($hips, $error_code, $error_message)
    {
        $hips_cc_err = $hips->l('There was an error processing your payment') .
            '<br />Details: ' . $error_code .
            ' <br/> ' . $error_message;

        if ($hips->hips_ft == 1 && !empty($hips->hips_ft_email)) {
            $cartInfo = array();

            if ($hips->hips_get_address) {
                $cartInfo = array(
                    'firstname' => Tools::getValue('hips_cc_fname'),
                    'lastname' => Tools::getValue('hips_cc_lname'),
                    'address' => Tools::getValue('hips_cc_address'),
                    'city' => Tools::getValue('hips_cc_city'),
                    'state' => Tools::getValue('hips_id_state'),
                    'country' => Tools::getValue('hips_id_country'),
                    'zip' => Tools::getValue('hips_cc_zip')
                );
            }

            $cartInfo['number'] = Tools::substr(Tools::getValue('hips_cc_number'), -4);

            $hips->sendErrorEmail($hips->hips_ft_email, $cart, $error_code . ' - ' . $error_message, 'error', $cartInfo, $hips->hips_get_address);
        }

        //if ($hips->hips_payment_page) {
        echo $hips_cc_err;
        exit();
        //} else {
        //    echo $hips_cc_err;
        //   exit();
        //}
    }

    public function postProcess()
    {
        $cart = $this->context->cart;
        $link = new Link();
        $psv = (float) (Tools::substr(_PS_VERSION_, 0, 3));

        //$_COOKIE['hipscall'] += 1;

        $confirm = false; //Tools::getValue('confirm');
        $updateHips = Tools::getValue('updateHips');

        $hips = new HipsCheckout();

        /* Validate order */
        $time = time();
        $hips_cc_err = '';

        $hipsAPI = new HipsCheckoutAPI($hips->hips_private, $hips->hips_public);

        $sqlSelectHipsOrder = 'SELECT * FROM ' . _DB_PREFIX_ . 'hips_orders_checkout WHERE id_cart = ' . (int) $cart->id;
        $resSelectHipsOrder = Db::getInstance()->ExecuteS($sqlSelectHipsOrder);

        $id_order_hips = '';
        $err = '';

        $viewOrderResp = null;
        if (isset($resSelectHipsOrder) && !empty($resSelectHipsOrder)) {
            // Update Order
            $post_values['hipsOrderId'] = $resSelectHipsOrder[0]['id_order_hips'];

            $viewOrderResp = $hipsAPI->viewOrder($post_values);

            if (isset($viewOrderResp['status']) && !empty($viewOrderResp['status']) && ($viewOrderResp['status'] == 'successful')) {
                $confirm = true;
            } else {
                $confirm = false;
            }
        }

        if ($confirm) {
            
            if (isset($viewOrderResp) && !empty($viewOrderResp)) {
               
                $orderToken = $viewOrderResp['id'];
                $cardFingerprint = ''; //Tools::getValue('fingerprint');
                $cardMask = ''; //Tools::getValue('mask');

                if (isset($viewOrderResp['status']) && !empty($viewOrderResp['status']) && ($viewOrderResp['status'] != 'successful')) {
                    $this->sendError($hips, $viewOrderResp['status'], 'Cart Id # ' . $cart->id . ' is in status: ' . $viewOrderResp['status']);
                }
                if (isset($viewOrderResp['status']) && (($viewOrderResp['status'] == 'successful') )) {
                    /* Success */
                    
                    $customer = new Customer();
                    
                   
                    if (!isset($viewOrderResp['billing_address']) || empty($viewOrderResp['billing_address']['id'])) {
                    
                        $viewOrderResp['billing_address'] = $viewOrderResp['shipping_address'];
                    }
                    $customer->firstname = $viewOrderResp['billing_address']['given_name'];
                    $customer->lastname = $viewOrderResp['billing_address']['family_name'];
                    $customer->email = $viewOrderResp['billing_address']['email'];
                    $customer->active = true;
                    $customer->is_guest = true;
                    $customer->passwd = md5(time() . _COOKIE_KEY_);
                    $customer->id_default_group = (int)Configuration::get('PS_GUEST_GROUP');
                    $customer->birthday = '';
                    $customer->add();

                    $billingAddress = new Address();
                    $billingAddress->id_customer = $customer->id;

                    $iso_code = $viewOrderResp['billing_address']['country'];
                    $id_country = Country::getByIso($iso_code, false);

                    if (isset($id_country) && $id_country > 0) {
                        $billingAddress->id_country = $id_country; //$viewOrderResp['billing_address']['country'];
                    } else {
                        // SET default country id
                        $billingAddress->id_country = Context::getContext()->country->id;
                    }
                    if (isset($viewOrderResp['billing_address']['state']) && !empty($viewOrderResp['billing_address']['state'])) {
                        $iso_code_state = $viewOrderResp['billing_address']['state'];
                        $id_state = State::getIdByIso($iso_code_state);
                        if (isset($id_state) && $id_state > 0) {
                            $billingAddress->id_state = $id_state; //$viewOrderResp['billing_address']['state'];
                        }
                    }
                    /* Country Name */
                    $billingAddress->country = $viewOrderResp['billing_address']['country'];
                    $billingAddress->alias = $viewOrderResp['billing_address']['given_name'] . ' ' . $viewOrderResp['billing_address']['family_name'];
                    $billingAddress->company = $viewOrderResp['billing_address']['company_name'];
                    $billingAddress->lastname = $viewOrderResp['billing_address']['family_name'];
                    $billingAddress->firstname = $viewOrderResp['billing_address']['given_name'];
                    $billingAddress->address1 = $viewOrderResp['billing_address']['street_address'];
                    $billingAddress->address2 = $viewOrderResp['billing_address']['street_number'];
                    $billingAddress->postcode = $viewOrderResp['billing_address']['postal_code'];
                    $billingAddress->city = $viewOrderResp['billing_address']['city'];
                    $billingAddress->other = $viewOrderResp['billing_address']['status'];
                    $billingAddress->phone = $viewOrderResp['billing_address']['phone_mobile'];
                    $billingAddress->mobile_phone = $viewOrderResp['billing_address']['phone_mobile'];

                    $billingAddress->save();

                    $shippingDifferent = false;
                    if (isset($viewOrderResp['shipping_address']) && !empty($viewOrderResp['shipping_address']['id'])) {
                        // Shipping address is different than billing address
                        // save it
                        $shippingAddress = new Address();
                        $shippingAddress->id_customer = $customer->id;

                        $iso_code = $viewOrderResp['shipping_address']['country'];
                        $id_country = Country::getByIso($iso_code, false);

                        if (isset($id_country) && $id_country > 0) {
                            $shippingAddress->id_country = $id_country; //$viewOrderResp['shipping_address']['country'];
                        } else {
                            // SET default country id
                            $shippingAddress->id_country = Context::getContext()->country->id;
                        }
                        if (isset($viewOrderResp['shipping_address']['state']) && !empty($viewOrderResp['shipping_address']['state'])) {

                            $iso_code_state = $viewOrderResp['shipping_address']['state'];
                            $id_state = State::getIdByIso($iso_code_state);
                            if (isset($id_state) && $id_state > 0) {
                                $shippingAddress->id_state = $id_state; //$viewOrderResp['shipping_address']['state'];
                            }
                        }
                        /* Country Name */
                        $shippingAddress->country = $viewOrderResp['shipping_address']['country'];
                        $shippingAddress->alias = $viewOrderResp['shipping_address']['given_name'] . ' ' . $viewOrderResp['shipping_address']['family_name'];
                        $shippingAddress->company = $viewOrderResp['shipping_address']['company_name'];
                        $shippingAddress->lastname = $viewOrderResp['shipping_address']['family_name'];
                        $shippingAddress->firstname = $viewOrderResp['shipping_address']['given_name'];
                        $shippingAddress->address1 = $viewOrderResp['shipping_address']['street_address'];
                        $shippingAddress->address2 = $viewOrderResp['shipping_address']['street_number'];
                        $shippingAddress->postcode = $viewOrderResp['shipping_address']['postal_code'];
                        $shippingAddress->city = $viewOrderResp['shipping_address']['city'];
                        $shippingAddress->other = $viewOrderResp['shipping_address']['status'];
                        $shippingAddress->phone = $viewOrderResp['shipping_address']['phone_mobile'];

                        $shippingAddress->mobile_phone = $viewOrderResp['shipping_address']['phone_mobile'];

                        $shippingAddress->save();
                        $shippingDifferent = true;
                    }

                    $this->updateContext($customer);
                    $cart->id_address_invoice = $billingAddress->id;
                    if ($shippingDifferent) {
                        $cart->id_address_delivery = $shippingAddress->id;
                    } else {
                        $cart->id_address_delivery = $billingAddress->id;
                    }
                    $cart->id_customer = $customer->id;
                    $cart->secure_key = $customer->secure_key;
                    $cart->update();


                    $customer = new Customer((int) ($cart->id_customer));

                    //print_r($viewOrderResp);
                   // die();
                    if ($hips->getPSV() >= 1.4) {

                        $x_amount = number_format($cart->getOrderTotal(true, Cart::BOTH), 2, '.', '');
                    } else {

                        $x_amount = number_format($cart->getOrderTotal(true, 3), 2, '.', '');
                    }
                    $x_amount = ($viewOrderResp['total_payments_amount'] - $viewOrderResp['shipping']['fee']) / 100;

                    $x_amount = $viewOrderResp['total_payments_amount'] / 100;

                    $total = $x_amount;

                    $hips->validateOrder(
                        (int) ($cart->id), $hips->hips_type == 'AUTH_ONLY' ? $hips->hips_auth_status : _PS_OS_PAYMENT_, $total, $hips->displayName, null, array(), null, false, $this->context->cart->secure_key);

                    $order = new Order((int) ($hips->currentOrder));


                    $shippingTaxExcl = (float) $viewOrderResp['shipping']['fee'] / 100 - (float) $viewOrderResp['shipping']['vat'] / 100;
                    $order->total_shipping_tax_excl = (float) $shippingTaxExcl;
                    $order->total_shipping_tax_incl = (float) $viewOrderResp['shipping']['fee'] / 100;
                    $order->total_shipping = (float) $viewOrderResp['shipping']['fee'] / 100;

                    $order->total_paid_tax_excl = (float) $viewOrderResp['cart']['total_amount'] / 100 - (float) $viewOrderResp['cart']['total_vat_amount'] / 100;
					$order->total_paid_tax_incl = (float) $viewOrderResp['total_payments_amount'] / 100;
					$order->total_paid = $order->total_paid_tax_incl;
                    
                    
                    $order->update(); 
                    //$this->updateOrder($cart, (int) ($hips->currentOrder), $context, $hips);

                    $message = new Message();
                    $message->message = ($hips->hips_type == 'AUTH_ONLY' ? $hips->l('Authorization Only - ') : '') .
                        $hips->l('Order Token ID: ') .
                        $viewOrderResp['id'];
                    $message->id_customer = $cart->id_customer;
                    $message->id_order = $order->id;
                    $message->private = 1;
                    $message->id_employee = 0;
                    $message->id_cart = $cart->id;
                    $message->add();

                    Db::getInstance()->Execute('INSERT INTO `' . _DB_PREFIX_ . "hips_refunds_checkout` VALUES " .
                        "('$order->id','" .
                        (int) ($cart->id) . "','" .
                        pSQL($cardMask) . "','" .
                        pSQL($cardFingerprint) . "','" .
                        pSQL($orderToken) . "','" .
                        pSQL($viewOrderResp['status']) . "','" .
                        pSQL($viewOrderResp['id']) . "','" .
                        pSQL($viewOrderResp['id']) . "','" .
                        ($hips->hips_type == 'AUTH_ONLY' ? '0' : '1') . "','" .
                        pSQL('') .
                        "')");


                    $sqlUpdateHipsCheckout = 'UPDATE ' . _DB_PREFIX_ . 'hips_orders_checkout SET id_order_ps = ' . (int) ($hips->currentOrder) . ' WHERE id_cart = ' . (int) ($cart->id) . ' AND id_order_hips = "' . pSQL($viewOrderResp['id']) . '"';
                    Db::getInstance()->Execute($sqlUpdateHipsCheckout);


                    Tools::redirectLink($hips->getRedirectBaseUrl() . 'key=' . $customer->secure_key . '&id_cart=' . (int) ($cart->id) . '&id_module=' . (int) ($hips->id) . '&id_order=' . (int) ($hips->currentOrder));
                } else {
                    /* Unknown error */
                    $this->sendError($hips, $hips->l('Unknown Error'), $hips->l('Unknown Error - no error message or ok result from HIPSs'));
                }
            } else {
                /* Unknown error */
                $this->sendError($hips, $hips->l('Unknown Error 2'), $hips->l('Unknown Error 2 - no error message or ok result from HIPSs'));
            }

            $time = mktime(0, 0, 0, Tools::getValue('hips_cc_Month'), 1, Tools::getValue('hips_cc_Year'));
            $address = new Address((int) ($cart->id_address_invoice));
            $selectedState = (int) (Tools::getValue('hips_id_state'));
            $selectedCountry = (int) (Tools::getValue('hips_id_country'));
            $this->context->smarty->assign('id_state', $selectedState);
        }

        self::prepareVarsView($this->context, $hips, $hips_cc_err, $time);

        $this->setTemplate('validation.tpl');
    }

    protected function updateContext(Customer $customer)
    {
        $this->context->customer = $customer;
        $this->context->cookie->id_customer = (int) $customer->id;
        $this->context->cookie->customer_lastname = $customer->lastname;
        $this->context->cookie->customer_firstname = $customer->firstname;
        $this->context->cookie->passwd = $customer->passwd;
        $this->context->cookie->logged = 1;
        // if register process is in two steps, we display a message to confirm account creation

        $customer->logged = 1;
        $this->context->cookie->email = $customer->email;
        $this->context->cookie->is_guest = true; //!Tools::getValue('is_new_customer', 1);
        // Update cart address
        $this->context->cart->secure_key = $customer->secure_key;
    }

    public static function updateOrder($cart, $id_order_ps, $context = null, $hips = null, $hips_cc_err, $time)
    {
        $cookie = $context->cookie;

        $address_delivery = new Address((int) $cart->id_address_delivery);
        $address_billing = new Address((int) $cart->id_address_invoice);
        $customer = new Address();

        // get default currency
        $currency_module = (int) Configuration::get('PS_CURRENCY_DEFAULT');
        // recalculate currency if Currency: User Selected
        if ($cart->id_currency != $currency_module) {
            $old_id = $cart->id_currency;
            $cart->id_currency = $currency_module;
            if (is_object($cookie))
                $cookie->id_currency = $currency_module;

            if ($this->getPSV() >= 1.5)
                $this->context->currency = new Currency($currency_module);

            $cart->update();
        }

        // get cart currency for set to ADN request
        $currencyOrder = new Currency($cart->id_currency);

        $products = $cart->getProducts();

        if ($hips->getPSV() >= 1.4) {
            $shippingCost = number_format($cart->getOrderTotal(!Product::getTaxCalculationMethod(), Cart::ONLY_SHIPPING), 2, '.', '');

            if ($cart->getOrderTotal(true, Cart::BOTH_WITHOUT_SHIPPING) == $cart->getOrderTotal(true, Cart::BOTH)) {
                $shippingCost = 0;
            }
            $x_amount_wot = number_format($cart->getOrderTotal(false, Cart::BOTH), 2, '.', '');
            $x_amount = number_format($cart->getOrderTotal(true, Cart::BOTH), 2, '.', '');

            $tax = $x_amount - $x_amount_wot;
        } else {
            $shippingCost = number_format($cart->getOrderTotal(!Product::getTaxCalculationMethod(), 5), 2, '.', '');

            if ($cart->getOrderTotal(true, 3) == $cart->getOrderTotal(true, 4)) {
                $shippingCost = 0;
            }
            $x_amount_wot = number_format($cart->getOrderTotal(false, 3), 2, '.', '');
            $x_amount = number_format($cart->getOrderTotal(true, 3), 2, '.', '');

            $tax = $x_amount - $x_amount_wot;
        }

        $country = new Country(Tools::getValue('obp_id_country'), (int) (Configuration::get('PS_LANG_DEFAULT')));
        $state = Tools::getIsset('obp_id_state') ? new State(Tools::getValue('obp_id_state')) : '';

        $del_state = new State($address_delivery->id_state);
        $address_delivery->state = $del_state->iso_code;
        $i = 1;
        $id_lang = 0;
        $languages = Language::getLanguages();
        foreach ($languages as $language) {
            if ($language['iso_code'] == 'en') {
                $id_lang = $language['id_lang'];
            }
        }
        if ($id_lang == $cart->id_lang) {
            $id_lang = 0;
        }


        $customerObj = new Customer($cart->id_customer);
        $x_email = $customerObj->email;

        $billingCountry = new Country($address_billing->id_country);
        $shippingCountry = new Country($address_delivery->id_country);
        $ip_address = $_SERVER['REMOTE_ADDR'];

        if ($cart->id_carrier > 0) {
            $require_shipping = true;
        } else {
            $require_shipping = false;
        }
        $link = new Link();
        $psv = (float) (Tools::substr(_PS_VERSION_, 0, 3));

        $redirectConformation = (Configuration::get('PS_SSL_ENABLED') ? 'https://' : 'http://') . htmlspecialchars($_SERVER['HTTP_HOST'], ENT_COMPAT, 'UTF-8') . __PS_BASE_URI__ . 'modules/hipscheckout/validation.php';

        $redirectFailed = (Configuration::get('PS_SSL_ENABLED') ? 'https://' : 'http://') . htmlspecialchars($_SERVER['HTTP_HOST'], ENT_COMPAT, 'UTF-8') . __PS_BASE_URI__ . 'modules/hipscheckout/orderFailed.php';

        $post_values = array(
            'redirectConformation' => $redirectConformation,
            'redirectFailed' => $redirectFailed,
            'cart_id' => $id_order_ps,
            'purchase_currency' => $currencyOrder->iso_code,
            'amount' => $x_amount,
            'capture' => ($hips->hips_type == 'AUTH_CAPTURE' ? true : false),
            'email' => $customerObj->email,
            'name' => Tools::getValue('hips_cc_fname'),
            'street' => !empty($address_billing->address1) ? $address_billing->address1 : $address_billing->address2,
            'postal_code' => !empty($address_billing->postcode) ? $address_billing->postcode : '',
            'country' => $billingCountry->iso_code,
            'ip_address' => $ip_address,
            'id_customer' => $cart->id_customer,
            'require_shipping' => $require_shipping,
            'shipping_address_firstname' => $address_delivery->firstname,
            'shipping_address_lastname' => $address_delivery->lastname,
            'shipping_address_addr' => !empty($address_delivery->address1) ? $address_delivery->address1 : $address_delivery->address2,
            'shipping_address_postal_code' => !empty($address_delivery->postcode) ? $address_delivery->postcode : '',
            'shipping_address_state' => $address_delivery->state,
            'shipping_address_city' => $address_delivery->city,
            'shipping_address_country' => $shippingCountry->iso_code,
            'shipping_address_email' => $customerObj->email,
            'shipping_address_phone' => !empty($address_delivery->phone) ? $address_delivery->phone : $address_delivery->phone_mobile,
        );

        $products = $cart->getProducts();
        $cartProducts = array();
        foreach ($products as $product) {
            $name = $product['name'];
            if ($id_lang > 0) {
                $eng_product = new Product($product['id_product']);
                $name = $eng_product->name[$id_lang];
            }
            $name = utf8_decode($name);
            $tax_amount = $product['price_wt'] - $product['price'];
            $cartProducts[] = [
                "id_product" => $product['id_product'],
                "id_product_attribute" => $product['id_product_attribute'],
                "type" => $product['is_virtual'] ? "digital" : "physical",
                "sku" => $product['reference'],
                "name" => $name,
                "quantity" => $product['cart_quantity'],
                "unit_price" => number_format($product['price_wt'], 2, '.', '') * 100,
                "discount_rate" => 0,
                "tax_amount" => number_format($tax_amount, 2, '.', '') * 100,
                "meta_data_1" => (isset($product['attributes']) ? $product['attributes'] : '')
            ];
        }
        $id_carrier = $cart->id_carrier;
        $carrier = new Carrier((int) $id_carrier);

        $carrierName = $carrier->name;

        $carrier_tax = $carrier->getTaxesRate($address_delivery);

        $shippingCostWT = number_format($cart->getOrderTotal(true, Cart::ONLY_SHIPPING), 2, '.', '');
        $shippingCostWithoutTax = number_format($cart->getOrderTotal(false, Cart::ONLY_SHIPPING), 2, '.', '');
        $carrier_tax = $shippingCostWT - $shippingCostWithoutTax;

        /* $cartProducts[] = [
          "id_product" => 0,
          "id_product_attribute" => 0,
          "type" => "shipping_fee",
          "sku" => '1',
          "name" => $carrierName,
          "quantity" => '1',
          "unit_price" => number_format($shippingCostWT, 2, '.', '') * 100,
          "discount_rate" => 0,
          "vat_amount" => number_format($carrier_tax, 2, '.', '') * 100
          ]; */

        $post_values['cartProducts'] = $cartProducts;
        $hipsAPI = new HipsCheckoutAPI($hips->hips_private, $hips->hips_public);


        $sqlSelectHipsOrder = 'SELECT * FROM ' . _DB_PREFIX_ . 'hips_orders_checkout WHERE id_cart = ' . (int) $cart->id;
        $resSelectHipsOrder = Db::getInstance()->ExecuteS($sqlSelectHipsOrder);

        $id_order_hips = '';
        $err = '';
        if (isset($resSelectHipsOrder) && !empty($resSelectHipsOrder)) {
            // Update Order
            $post_values['hipsOrderId'] = $resSelectHipsOrder[0]['id_order_hips'];
            $post_values['dbProducts'] = $resSelectHipsOrder[0]['products'];

            $updateOrderResp = $hipsAPI->updateOrder($post_values);

            if (isset($updateOrderResp['error']) && !empty($updateOrderResp['error'])) {
                $err .= '<td align="left" style="font-weight:bold;font-size:12px;color:red;" nowrap>';
                $err .= $updateOrderResp['error']['type'] . ' - ' . $updateOrderResp['error']['message'];
                $err .= '</td>';
                $id_order_hips = $resSelectHipsOrder[0]['id_order_hips'];
            } elseif (isset($updateOrderResp['status'])) {
                $sqlUpdateHips = 'UPDATE ' . _DB_PREFIX_ . 'hips_orders_checkout '
                    . 'SET id_order_hips = "' . pSQL($updateOrderResp['id']) . '",'
                    . 'checkout_uri = "' . pSQL($updateOrderResp['checkout_uri']) . '",'
                    . 'status = "' . pSQL($updateOrderResp['status']) . '", '
                    . 'products = "' . pSQL(serialize($updateOrderResp['cart']['items'])) . '" '
                    . 'WHERE id_cart = ' . (int) $cart->id;

                Db::getInstance()->execute($sqlUpdateHips);

                $id_order_hips = $updateOrderResp['id'];
            }
        }
    }

    public static function prepareVarsView($context = null, $hips = null, $hips_cc_err, $time)
    {
        $cart = $context->cart;
        $cookie = $context->cookie;
        
       

        $address_delivery = new Address((int) $cart->id_address_delivery);
        $address_billing = new Address((int) $cart->id_address_invoice);
        $customer = new Address();

        // get default currency
        $currency_module = (int) Configuration::get('PS_CURRENCY_DEFAULT');
        // recalculate currency if Currency: User Selected
        if ($cart->id_currency != $currency_module) {
            $old_id = $cart->id_currency;
            $cart->id_currency = $currency_module;
            if (is_object($cookie))
                $cookie->id_currency = $currency_module;

            if ($hips->getPSV() >= 1.5)
                $context->currency = new Currency($currency_module);

            $cart->update();
        }
        // get cart currency for set to ADN request
        $currencyOrder = new Currency($cart->id_currency);

        $products = $cart->getProducts();

        if ($hips->getPSV() >= 1.4) {
            $shippingCost = number_format($cart->getOrderTotal(!Product::getTaxCalculationMethod(), Cart::ONLY_SHIPPING), 2, '.', '');

            if ($cart->getOrderTotal(true, Cart::BOTH_WITHOUT_SHIPPING) == $cart->getOrderTotal(true, Cart::BOTH)) {
                $shippingCost = 0;
            }
            $x_amount_wot = number_format($cart->getOrderTotal(false, Cart::BOTH), 2, '.', '');
            $x_amount = number_format($cart->getOrderTotal(true, Cart::BOTH), 2, '.', '');

            $tax = $x_amount - $x_amount_wot;
        } else {
            $shippingCost = number_format($cart->getOrderTotal(!Product::getTaxCalculationMethod(), 5), 2, '.', '');

            if ($cart->getOrderTotal(true, 3) == $cart->getOrderTotal(true, 4)) {
                $shippingCost = 0;
            }
            $x_amount_wot = number_format($cart->getOrderTotal(false, 3), 2, '.', '');
            $x_amount = number_format($cart->getOrderTotal(true, 3), 2, '.', '');

            $tax = $x_amount - $x_amount_wot;
        }

        $country = new Country(Tools::getValue('obp_id_country'), (int) (Configuration::get('PS_LANG_DEFAULT')));
        $state = Tools::getIsset('obp_id_state') ? new State(Tools::getValue('obp_id_state')) : '';

        $del_state = new State($address_delivery->id_state);
        $address_delivery->state = $del_state->iso_code;
        $i = 1;
        $id_lang = 0;
        $languages = Language::getLanguages();
        foreach ($languages as $language) {
            if ($language['iso_code'] == 'en') {
                $id_lang = $language['id_lang'];
            }
        }
        if ($id_lang == $cart->id_lang) {
            $id_lang = 0;
        }


        $customerObj = new Customer($cart->id_customer);
        $x_email = $customerObj->email;

        $billingCountry = new Country($address_billing->id_country);
        $shippingCountry = new Country($address_delivery->id_country);
        $ip_address = $_SERVER['REMOTE_ADDR'];

        if ($cart->id_carrier > 0) {
            $require_shipping = true;
        } else {
            $require_shipping = false;
        }
        $link = new Link();
        $psv = (float) (Tools::substr(_PS_VERSION_, 0, 3));

        $redirectConformation = (Configuration::get('PS_SSL_ENABLED') ? 'https://' : 'http://') . htmlspecialchars($_SERVER['HTTP_HOST'], ENT_COMPAT, 'UTF-8') . __PS_BASE_URI__ . 'modules/hipscheckout/validation.php';

        $redirectFailed = (Configuration::get('PS_SSL_ENABLED') ? 'https://' : 'http://') . htmlspecialchars($_SERVER['HTTP_HOST'], ENT_COMPAT, 'UTF-8') . __PS_BASE_URI__ . 'modules/hipscheckout/orderFailed.php';

        $post_values = array(
            'psv' => $hips->getPSV(),
            'redirectConformation' => $redirectConformation,
            'redirectFailed' => $redirectFailed,
            'cart_id' => $cart->id,
            'purchase_currency' => $currencyOrder->iso_code,
            'amount' => $x_amount,
            'capture' => ($hips->hips_type == 'AUTH_CAPTURE' ? true : false),
            'email' => $customerObj->email,
            'name' => Tools::getValue('hips_cc_fname'),
            'street' => !empty($address_billing->address1) ? $address_billing->address1 : $address_billing->address2,
            'postal_code' => !empty($address_billing->postcode) ? $address_billing->postcode : '',
            'country' => $billingCountry->iso_code,
            'ip_address' => $ip_address,
            'id_customer' => $cart->id_customer,
            'require_shipping' => $require_shipping,
            'shipping_address_firstname' => $address_delivery->firstname,
            'shipping_address_lastname' => $address_delivery->lastname,
            'shipping_address_addr' => !empty($address_delivery->address1) ? $address_delivery->address1 : $address_delivery->address2,
            'shipping_address_postal_code' => !empty($address_delivery->postcode) ? $address_delivery->postcode : '',
            'shipping_address_state' => $address_delivery->state,
            'shipping_address_city' => $address_delivery->city,
            'shipping_address_country' => $shippingCountry->iso_code,
            'shipping_address_email' => $customerObj->email,
            'shipping_address_phone' => !empty($address_delivery->phone) ? $address_delivery->phone : $address_delivery->phone_mobile,
        );

        $products = $cart->getProducts();
        $cartProducts = array();
        foreach ($products as $product) {
            $name = $product['name'];
            if ($id_lang > 0) {
                $eng_product = new Product($product['id_product']);
                $name = $eng_product->name[$id_lang];
            }
            $name = utf8_decode($name);
            $tax_amount = $product['price_wt'] - $product['price'];
            
            $weigthUnit = Configuration::get('PS_WEIGHT_UNIT');
            $hipsUnit = $weigthUnit;
            if (strtoupper($weigthUnit) == 'KG' || strtoupper($weigthUnit) == 'KGS') {
                $hipsUnit = 'kg';
            } elseif (strtoupper($weigthUnit) == 'LB' || strtoupper($weigthUnit) == 'LBS') {
                $hipsUnit = 'lb';
            }elseif (strtoupper($weigthUnit) == 'ST' || strtoupper($weigthUnit) == 'STONE') {
                $hipsUnit = 'stone';
            }elseif (strtoupper($weigthUnit) == 'G' || strtoupper($weigthUnit) == 'GRAM') { 
                $hipsUnit = 'gram';
            }
           
            $cartProducts[] = [
                "id_product" => $product['id_product'],
                "id_product_attribute" => $product['id_product_attribute'],
                "type" => $product['is_virtual'] ? "digital" : "physical",
                "sku" => $product['reference'],
                "name" => $name,
                "quantity" => $product['cart_quantity'],
                "unit_price" => number_format($product['price_wt'], 2, '.', '') * 100,
                "discount_rate" => 0,
                "weight" => $product['weight'],
                "weight_unit" => $hipsUnit,
                "vat_amount" => number_format($tax_amount, 2, '.', '') * 100,
                "meta_data_1" => (isset($product['attributes']) ? $product['attributes'] : '')
            ];
        }
        $id_carrier = $context->cart->id_carrier;
        $carrier = new Carrier((int) $id_carrier);

        $carrierName = $carrier->name;

        $carrier_tax = $carrier->getTaxesRate($address_delivery);
        $shippingCostWT = number_format($cart->getOrderTotal(true, Cart::ONLY_SHIPPING), 2, '.', '');
        $shippingCostWithoutTax = number_format($cart->getOrderTotal(false, Cart::ONLY_SHIPPING), 2, '.', '');
        $carrier_tax = $shippingCostWT - $shippingCostWithoutTax;
        /* $cartProducts[] = [
          "id_product" => 0,
          "id_product_attribute" => 0,
          "type" => "shipping_fee",
          "sku" => '1',
          "name" => (isset($carrierName) && !empty($carrierName) ? $carrierName : 'HIPS Carrier name'),
          "quantity" => '1',
          "unit_price" => number_format($shippingCostWT, 2, '.', '') * 100,
          "discount_rate" => 0,
          "vat_amount" => number_format($carrier_tax, 2, '.', '') * 100,
          ]; */

        $post_values['cartProducts'] = $cartProducts;
        $hipsAPI = new HipsCheckoutAPI($hips->hips_private, $hips->hips_public);


        $sqlSelectHipsOrder = 'SELECT * FROM ' . _DB_PREFIX_ . 'hips_orders_checkout WHERE id_cart = ' . (int) $cart->id;
        $resSelectHipsOrder = Db::getInstance()->ExecuteS($sqlSelectHipsOrder);

        $id_order_hips = '';
        $err = '';
        /*
        if (isset($resSelectHipsOrder) && !empty($resSelectHipsOrder)) {
            // Update Order
            $post_values['hipsOrderId'] = $resSelectHipsOrder[0]['id_order_hips'];
            $post_values['dbProducts'] = $resSelectHipsOrder[0]['products'];

            $updateOrderResp = $hipsAPI->updateOrder($post_values);

            if (isset($updateOrderResp['error']) && !empty($updateOrderResp['error'])) {
                $err .= '<td align="left" style="font-weight:bold;font-size:12px;color:red;" nowrap>';
                $err .= $updateOrderResp['error']['type'] . ' - ' . $updateOrderResp['error']['message'];
                $err .= '</td>';
                $id_order_hips = $resSelectHipsOrder[0]['id_order_hips'];
            } elseif (isset($updateOrderResp['status'])) {
                $sqlUpdateHips = 'UPDATE ' . _DB_PREFIX_ . 'hips_orders_checkout '
                    . 'SET id_order_hips = "' . pSQL($updateOrderResp['id']) . '",'
                    . 'checkout_uri = "' . pSQL($updateOrderResp['checkout_uri']) . '",'
                    . 'status = "' . pSQL($updateOrderResp['status']) . '", '
                    . 'products = "' . pSQL(serialize($updateOrderResp['cart']['items'])) . '" '
                    . 'WHERE id_cart = ' . (int) $cart->id;

                Db::getInstance()->execute($sqlUpdateHips);

                $id_order_hips = $updateOrderResp['id'];
            }
        } else {
            */
            // Create new HIPS order
            $doOrderResp = $hipsAPI->doOrder($post_values);

            if (isset($doOrderResp['error']) && !empty($doOrderResp['error'])) {
                $err = '<td align="left" style="font-weight:bold;font-size:12px;color:red;" nowrap>';
                $err .= $doOrderResp['error']['type'] . ' - ' . $doOrderResp['error']['message'];
                $err .= '</td>';
            } elseif (isset($doOrderResp['status'])) {

                if (isset($resSelectHipsOrder) && !empty($resSelectHipsOrder)) {
                    $sqlUpdateHips = 'UPDATE ' . _DB_PREFIX_ . 'hips_orders_checkout '
                    . 'SET id_order_hips = "' . pSQL($doOrderResp['id']) . '",'
                    . 'checkout_uri = "' . pSQL($doOrderResp['checkout_uri']) . '",'
                    . 'status = "' . pSQL($doOrderResp['status']) . '", '
                    . 'products = "' . pSQL(serialize($doOrderResp['cart']['items'])) . '" '
                    . 'WHERE id_cart = ' . (int) $cart->id;

                    Db::getInstance()->execute($sqlUpdateHips);
                } else {
                    Db::getInstance()->insert(
                        'hips_orders_checkout', [
                        'id_cart' => (int) $cart->id,
                        'id_order_hips' => pSQL($doOrderResp['id']),
                        'checkout_uri' => pSQL($doOrderResp['checkout_uri']),
                        'status' => pSQL($doOrderResp['status']),
                        'products' => pSQL(serialize($doOrderResp['cart']['items'])),
                        ]
                    );
                }
                $id_order_hips = $doOrderResp['id'];
            }
        /*}*/

        $hips_filename = 'validation';
        $context->smarty->assign(array(
            'hips_public' => $hips->hips_public,
            'err' => $err,
            'id_order_hips' => $id_order_hips,
            //'hips_payment_page' => $hips->hips_payment_page,
            'empty_cart' => (count($cartProducts) == 0 ? true : false),
            'hips_psv' => $psv,
            'hipsc_filename' => $hips_filename,
            'this_path' => __PS_BASE_URI__ . 'modules/' . $hips->name . '/',
        ));

        if ((int) (ceil(number_format($cart->getOrderTotal(true, 3), 2, '.', ''))) == 0) {
            //Tools::redirect('order.php?step=1');
        }
        $context->smarty->assign('this_path', __PS_BASE_URI__ . 'modules/hipscheckout/');
    }
    
        /**
     * get version of PrestaShop
     * return float value version
     */
    public function getPSV()
    {
        return (float) Tools::substr($this->getRawPSV(), 0, 3);
    }

    /**
     * get raw version of PrestaShop
     */
    private function getRawPSV()
    {
        return _PS_VERSION_;
    }
}

