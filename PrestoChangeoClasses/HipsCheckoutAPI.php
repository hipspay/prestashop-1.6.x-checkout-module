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
class HipsCheckoutAPI
{

    protected $hips_private = '';
    protected $hips_public = '';

    public function __construct($hips_private, $hips_public)
    {
        $this->hips_private = $hips_private;
        $this->hips_public = $hips_public;
    }

    public function revokeOrder($post_values)
    {
        $post_values['amount'] = Tools::ps_round($post_values['amount'], 2);
        $json = '{
                "id":"' . $post_values['hipsOrderId'] . '"' . ',
                "amount":' . $post_values['amount'] * 100 . '' . ',
                "items": [';

        $post_values['dbProducts'] = unserialize($post_values['dbProducts']);

        // Update all products from cart - updates price, qty & vat

        foreach ($post_values['dbProducts'] as $cachedHipProduct) {
            $arrayIds = explode("_", $cachedHipProduct['ean']);
            if (isset($cachedHipProduct['ean']) && !empty($cachedHipProduct['ean']) && count($arrayIds) > 1) {
                $hipIdProduct = $arrayIds[0];
                $hipsIdProductAttribute = $arrayIds[1];

                $hipsIdProduct = $cachedHipProduct['id'];
                // Update product based on ID
                $json .= '
                        {
                        "id":"' . $hipsIdProduct . '",
                        "sku":"' . utf8_encode($cachedHipProduct['sku']) . '",
                        "name":"' . utf8_encode(strip_tags($cachedHipProduct['name'])) . '",
                        "quantity":' . $cachedHipProduct['quantity'] . ',
                        "ean":"' . utf8_encode($cachedHipProduct['ean']) . '",
                        "unit_price":' . $cachedHipProduct['unit_price'] . (isset($cachedHipProduct['meta_data_1']) ? ',
                        "meta_data_1":"' . $cachedHipProduct['meta_data_1'] . '"' : '' ) . '
                        },';
            }
        }

        // Remove last ,
        if (substr($json, -1, 1) == ',') {
            $json = substr($json, 0, -1);
        }
        $json .= '
                ]
            }';

        //print_r($json);

        $ch = curl_init('https://api.hips.com/v1/orders/' . $post_values['hipsOrderId'] . '/revoke');
        curl_setopt($ch, CURLOPT_USERPWD, $this->hips_private . ":");
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
        curl_setopt($ch, CURLOPT_POSTFIELDS, $json);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array(
            'Content-Type: application/json',
            'Content-Length: ' . strlen($json))
        );


        $result = curl_exec($ch);

        //print_r($json);
        //echo '<br/><br/>';
        //print_r($result);
        $info = curl_getinfo($ch);
        //var_dump($info);
        if ($result === false) {
            $return = array();
            $return['error']['type'] = 'Unkown error!';
            $return['error']['message'] = 'Unkown error!';
            return $return;
        } else {
            $json_result = json_decode($result, true);
            return $json_result;
        }
    }

    public function fulfillOrder($post_values)
    {
        $json = '';
        $ch = curl_init('https://api.hips.com/v1/orders/' . $post_values['hipsOrderId'] . '/fulfill');
        $json = '{"id":"' . $post_values['hipsOrderId'] . '"}';
        //echo $json;
        curl_setopt($ch, CURLOPT_USERPWD, $this->hips_private . ":");
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
        curl_setopt($ch, CURLOPT_POSTFIELDS, $json);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array(
            'Content-Type: application/json',
            'Content-Length: ' . strlen($json))
        );

        $result = curl_exec($ch);
//print_r($result);
        if ($result === false) {
            $return = array();
            $return['error']['type'] = 'Unkown error!';
            $return['error']['message'] = 'Unkown error!';
            return $return;
        } else {
            $json_result = json_decode($result, true);
            //print_r($json_result);
            return $json_result;
        }
    }
    /* View Order */

    public function viewOrder($post_values)
    {
        $json = '';
        $ch = curl_init('https://api.hips.com/v1/orders/' . $post_values['hipsOrderId']);
        curl_setopt($ch, CURLOPT_USERPWD, $this->hips_private . ":");
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "GET");
        curl_setopt($ch, CURLOPT_POSTFIELDS, $json);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array(
            'Content-Type: application/json',
            'Content-Length: ' . strlen($json))
        );


        $result = curl_exec($ch);

        if ($result === false) {
            $return = array();
            $return['error']['type'] = 'Unkown error!';
            $return['error']['message'] = 'Unkown error!';
            return $return;
        } else {
            $json_result = json_decode($result, true);
            return $json_result;
        }
    }
    /* Update Order  */

    public function updateOrder($post_values)
    {
        $json = '{
            "order_id":"' . $post_values['cart_id'] . '",
            "purchase_currency":"' . $post_values['purchase_currency'] . '",
            "user_identifier":"' . $post_values['id_customer'] . '",            
            "cart":{
               "items":[';

        /*
         * 
         * "shipping_address": {  
          "given_name":"' . $post_values['shipping_address_firstname'] . '",
          "family_name":"' . $post_values['shipping_address_lastname'] . '",
          "street_address":"' . $post_values['shipping_address_addr'] . '",
          "postal_code":"' . $post_values['shipping_address_postal_code'] . '",
          "state":"' . $post_values['shipping_address_state'] . '",
          "city":"' . $post_values['shipping_address_city'] . '",
          "country":"' . $post_values['shipping_address_country'] . '",
          "email":"' . $post_values['shipping_address_email'] . '",
          "phone":"' . $post_values['shipping_address_phone'] . '"
          },
         */
        /*
         * EAN - contains the actual id_product from PrestaShop to identify the products and resubmit only the difference products
         */
        $post_values['dbProducts'] = unserialize($post_values['dbProducts']);

        // Update all products from cart - updates price, qty & vat
        foreach ($post_values['cartProducts'] as $prod) {
            $found = false;
            foreach ($post_values['dbProducts'] as $cachedHipProduct) {
                $arrayIds = explode("_", $cachedHipProduct['ean']);
                if (isset($cachedHipProduct['ean']) && !empty($cachedHipProduct['ean']) && count($arrayIds) > 1) {
                    $hipIdProduct = $arrayIds[0];
                    $hipsIdProductAttribute = $arrayIds[1];

                    if ($prod['id_product'] == $hipIdProduct && $prod['id_product_attribute'] == $hipsIdProductAttribute) {

                        $hipsIdProduct = $cachedHipProduct['id'];
                        $found = true;
                        // Update product based on ID
                        $json .= '
                        {
                        "id":"' . $hipsIdProduct . '",
                        "type":"' . $prod['type'] . '",
                        "sku":"' . utf8_encode($prod['sku']) . '",
                        "name":"' . utf8_encode(strip_tags($prod['name'])) . '",
                        "quantity":' . $prod['quantity'] . ',
                        "ean":"' . utf8_encode($prod['id_product'] . '_' . $prod['id_product_attribute']) . '",
                        "discount_rate":' . $prod['discount_rate'] . ',
                        "vat_amount":' . $prod['vat_amount'] . ',
                        "unit_price":' . $prod['unit_price'] . (isset($prod['meta_data_1']) ? ',
                        "meta_data_1":"' . $prod['meta_data_1'] . '"' : '' ) . ',
                        "weight_unit":"' . $prod['weight_unit'] . '",
                        "weight":' . (isset($prod['weight']) && !empty($prod['weight']) ? $prod['weight'] : '0') . '
                        },';
                    }
                }
            }

            // Adds new products to Hips cart
            if (!$found) {
                // New product added to cart
                $json .= '
                    {
                    "type":"' . $prod['type'] . '",
                    "sku":"' . utf8_encode($prod['sku']) . '",
                    "name":"' . utf8_encode(strip_tags($prod['name'])) . '",
                    "quantity":' . $prod['quantity'] . ',
                    "ean":"' . utf8_encode($prod['id_product'] . '_' . $prod['id_product_attribute']) . '",
                    "discount_rate":' . $prod['discount_rate'] . ',
                    "vat_amount":' . $prod['vat_amount'] . ',
                    "unit_price":' . $prod['unit_price'] . (isset($prod['meta_data_1']) ? ',
                    "meta_data_1":"' . $prod['meta_data_1'] . '"' : '' ) . ',
                    "weight_unit":"' . $prod['weight_unit'] . '",
                    "weight":' . (isset($prod['weight']) && !empty($prod['weight']) ? $prod['weight'] : '0') . '
                    },';
            }
        }

        // Removes products from HIPS cart - set QTY = 0
        foreach ($post_values['dbProducts'] as $cachedHipProduct) {
            $found = false;
            $arrayIds = explode("_", $cachedHipProduct['ean']);
            $hipIdProduct = $arrayIds[0];
            
            $hipsIdProductAttribute = (isset($arrayIds[1]) ? $arrayIds[1] : 0);
            if (isset($cachedHipProduct['ean']) && !empty($cachedHipProduct['ean']) && count($arrayIds) > 1) {
                foreach ($post_values['cartProducts'] as $prod) {

                    if ($prod['id_product'] == $hipIdProduct && $prod['id_product_attribute'] == $hipsIdProductAttribute) {
                        $found = true;
                    }
                }
            }

            if (!$found) {
                // Product was not found in PrestaShop cart
                // set qty = 0
                // Update product based on ID
                if ($cachedHipProduct['ean'] == '0_0') {
                    $cachedHipProduct['type'] = 'shipping_fee';
                } else {
                    //"type" => $product['is_virtual'] ? "digital" : "physical",
                    $productPS = new Product((int) $hipIdProduct);
                    if ($productPS->is_virtual) {
                        $cachedHipProduct['type'] = 'digital';
                    } else {
                        $cachedHipProduct['type'] = 'physical';
                    }
                }
                $json .= '
                        {
                        "id":"' . $cachedHipProduct['id'] . '",
                        "type":"' . $cachedHipProduct['type'] . '",
                        "sku":"' . utf8_encode($cachedHipProduct['sku']) . '",
                        "name":"' . utf8_encode(strip_tags($cachedHipProduct['name'])) . '",
                        "quantity":' . 0 . ',
                        "ean":"' . utf8_encode($cachedHipProduct['ean']) . '",
                        "discount_rate":' . $cachedHipProduct['discount_rate'] . ',
                        "vat_amount":' . $cachedHipProduct['vat_amount'] . ',
                        "unit_price":' . $cachedHipProduct['unit_price'] . ',
                        "weight_unit":"' . $cachedHipProduct['weight_unit'] . '",
                        "weight":' . (isset($cachedHipProduct['weight']) && !empty($cachedHipProduct['weight']) ? $cachedHipProduct['weight'] : '0') . '
                        },';
            }
        }

        // Remove last ,
        if (substr($json, -1, 1) == ',') {
            $json = substr($json, 0, -1);
        }

        $json .= '
                ]
            },
            "require_shipping":' . ($post_values['require_shipping'] ? 'true' : 'true') . ',
            "hooks":{
             "user_return_url_on_success":"' . $post_values['redirectConformation'] . '",
             "user_return_url_on_fail":"' . $post_values['redirectFailed'] . '"
            }
         }';


        $ch = curl_init('https://api.hips.com/v1/orders/' . $post_values['hipsOrderId']);
        curl_setopt($ch, CURLOPT_USERPWD, $this->hips_private . ":");
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "PATCH");
        curl_setopt($ch, CURLOPT_POSTFIELDS, $json);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array(
            'Content-Type: application/json',
            'Content-Length: ' . strlen($json))
        );


        $result = curl_exec($ch);

        //print_r($json);
        // echo '<br/><br/>';
        //print_r($result);
        //$info = curl_getinfo($ch);
        //var_dump($info);
        if ($result === false) {
            $return = array();
            $return['error']['type'] = 'Unkown error!';
            $return['error']['message'] = 'Unkown error!';
            return $return;
        } else {
            $json_result = json_decode($result, true);

            return $json_result;
        }
    }
    /* create Order  */

    public function doOrder($post_values)
    {
        $json = '{
            "order_id":"' . $post_values['cart_id'] . '",
            "purchase_currency":"' . $post_values['purchase_currency'] . '",           
            "ecommerce_module": "Hips Checkout Module 1.0.0",
            "cart":{
               "items":[';

        /*  "user_identifier":"' . $post_values['id_customer'] . '",
          "shipping_address": {
          "given_name":"' . $post_values['shipping_address_firstname'] . '",
          "family_name":"' . $post_values['shipping_address_lastname'] . '",
          "street_address":"' . $post_values['shipping_address_addr'] . '",
          "postal_code":"' . $post_values['shipping_address_postal_code'] . '",
          "state":"' . $post_values['shipping_address_state'] . '",
          "city":"' . $post_values['shipping_address_city'] . '",
          "country":"' . $post_values['shipping_address_country'] . '",
          "email":"' . $post_values['shipping_address_email'] . '",
          "phone":"' . $post_values['shipping_address_phone'] . '"
          },
         */

        /*
         * EAN - contains the actual id_product from PrestaShop to identify the products and resubmit only the difference products
         */
        $i = 0;
        foreach ($post_values['cartProducts'] as $prod) {
            $json .= '
                    {
                    "type":"' . $prod['type'] . '",
                    "sku":"' . utf8_encode($prod['sku']) . '",
                    "name":"' . utf8_encode($prod['name']) . '",
                    "quantity":' . $prod['quantity'] . ',
                    "ean":"' . utf8_encode($prod['id_product'] . '_' . $prod['id_product_attribute']) . '",
                    "unit_price":' . $prod['unit_price'] . ',
                    "discount_rate":' . $prod['discount_rate'] . ',
                    "vat_amount":' . $prod['vat_amount'] . (isset($prod['meta_data_1']) ? ',
                    "meta_data_1":"' . $prod['meta_data_1'] . '"' : '' ) . ',
                    "weight_unit":"' . $prod['weight_unit'] . '",
                    "weight":' . (isset($prod['weight']) && !empty($prod['weight']) ? $prod['weight'] : '0') . '
                    }' . ($i < count($post_values['cartProducts']) - 1 ? ',' : '' ) . '';
            $i ++;
        }
        $json .= '
                ]
            },
            "checkout_settings": {
                "extended_cart": true
            },
            "require_shipping":' . ($post_values['require_shipping'] ? 'true' : 'true') . ',
            "hooks":{
             "user_return_url_on_success":"' . $post_values['redirectConformation'] . '",
             "user_return_url_on_fail":"' . $post_values['redirectFailed'] . '"
            },
            "fulfill":' . ($post_values['capture'] ? 'true' : 'false') . '
         }';


        $ch = curl_init('https://api.hips.com/v1/orders');
        curl_setopt($ch, CURLOPT_USERPWD, $this->hips_private . ":");
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
        curl_setopt($ch, CURLOPT_POSTFIELDS, $json);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array(
            'Content-Type: application/json',
            'Content-Length: ' . strlen($json))
        );
        // print_r($json);
        $result = curl_exec($ch);

        if ($result === false) {
            $return = array();
            $return['error']['type'] = 'Unkown error!';
            $return['error']['message'] = 'Unkown error!';
            return $return;
        } else {
            if (isset($result) && !empty($result)) {
                $json_result = json_decode($result, true);
                return $json_result;
            } else {
                $return = array();
                $return['error']['type'] = 'Unkown error!';
                $return['error']['message'] = 'Unkown error!';
                return $return;
            }
        }
    }
    /* Payment */

    public function doPayment($post_values)
    {
        $json = '{
            "source":"card_token",
            "order_id":"' . $post_values['cart_id'] . '",
            "purchase_currency":"' . $post_values['purchase_currency'] . '",
            "amount":"' . $post_values['amount'] * 100 . '",
            "card_token":"' . $post_values['token'] . '",
            "capture":"' . ($post_values['capture'] ? 'true' : 'false') . '",
            "customer":{
                "email":"' . $post_values['email'] . '",
                "name":"' . utf8_encode($post_values['name']) . '",
                "street_address":"' . utf8_encode($post_values['street']) . '",
                "postal_code":"' . utf8_encode($post_values['postal_code']) . '",
                "country":"' . $post_values['country'] . '",
                "ip_address":"' . $post_values['ip_address'] . '"
            },
            "preflight":"true"
         }';


        $ch = curl_init('https://api.hips.com/v1/payments');
        curl_setopt($ch, CURLOPT_USERPWD, $this->hips_private . ":");
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
        curl_setopt($ch, CURLOPT_POSTFIELDS, $json);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array(
            'Content-Type: application/json',
            'Content-Length: ' . strlen($json))
        );
        $result = curl_exec($ch);
        if ($result === false) {
            $return = array();
            $return['error']['type'] = 'Unkown error!';
            $return['error']['message'] = 'Unkown error!';
            return $return;
        } else {
            $json_result = json_decode($result, true);
            return $json_result;
        }
    }
    /* Capture if module is set to Authorize only */

    public function doCapture($post_values)
    {
        $json = '';

        $ch = curl_init('https://api.hips.com/v1/payments/' . $post_values['hipsOrderId'] . '/capture');
        curl_setopt($ch, CURLOPT_USERPWD, $this->hips_private . ":");
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
        curl_setopt($ch, CURLOPT_POSTFIELDS, $json);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array(
            'Content-Type: application/json',
            'Content-Length: ' . strlen($json))
        );
        $result = curl_exec($ch);

        if ($result === false) {
            $return = array();
            $return['error']['type'] = 'Unkown error!';
            $return['error']['message'] = 'Unkown error!';
            return $return;
        } else {

            $json_result = json_decode($result, true);

            return $json_result;
        }
    }
    /* Refund Order */

    public function doRefund($post_values)
    {
        $post_values['amount'] = Tools::ps_round($post_values['amount'], 2);
        $json = '{
            "id":"' . $post_values['hipsOrderId'] . '"' . ($post_values['is_void'] ? '' : ',
            "amount":"' . $post_values['amount'] * 100 . '"' ) . '
         }';

        $ch = curl_init('https://api.hips.com/v1/payments/' . $post_values['hipsOrderId'] . '/refund');
        curl_setopt($ch, CURLOPT_USERPWD, $this->hips_private . ":");
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
        curl_setopt($ch, CURLOPT_POSTFIELDS, $json);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array(
            'Content-Type: application/json',
            'Content-Length: ' . strlen($json))
        );
        $result = curl_exec($ch);
        if ($result === false) {
            $return = array();
            $return['error']['type'] = 'Unkown error!';
            $return['error']['message'] = 'Unkown error!';
            return $return;
        } else {

            $json_result = json_decode($result, true);

            return $json_result;
        }
    }
}

