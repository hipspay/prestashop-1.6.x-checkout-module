/*
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



function updateOrder() {

    Hips.checkout.suspend();

    $.ajax({
        url: ajax_hipsc_url,
        type: "post",
        dataType: "html",
        data: 'updateHips=1',
        success: function (strData) {

            Hips.checkout.resume();

        }
    });

}

$(document).ready(function () {
    $('.cart_quantity_input, #id_address_delivery').change(function () {
        updateOrder();
    });

    $('.cart_quantity_delete, .cart_quantity_up, .cart_quantity_down, .ajax_cart_block_remove_link, .delivery_option_radio').click(function () {
        updateOrder();
    });

});


