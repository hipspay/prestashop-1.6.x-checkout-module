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


function changeAPI()
{

}
function changeCIM()
{


}

function type_change()
{
    if ($("#hips_type").val() == "AUTH_CAPTURE")
    {
        $(".capture_transaction").hide();
        $("#cap_stat").hide();
        $(".hips_ac_status").hide();
        $("#hips_ac_status").val("0");
    } else
    {
        $(".capture_transaction").show();
        $("#cap_stat").show();
        $(".hips_ac_status").show();
    }
}

function search_orders(type)
{
    var orderId = "";
    if (type == 1)
        orderId = $("#capture_order_id").val();
    if (type == 2)
        orderId = $("#refund_order_id").val();
    
    if (type == 3)
    {
        $.ajax({
            type: "POST",
            url: baseDir + "hips-trans-ajax.php",
            async: true,
            cache: false,
            data: "&id_lang=" + id_lang + "&id_employee=" + id_employee + "&type=" + type + "&secure_key=" + hips_secure_key + "",
            success: function (html) {
                $("#endofday_order_details").html(html);
                return;
            },
            error: function () {
                alert("ERROR:");
                return;
            }
        });
        return;
    }
    
    if (orderId == "")
    {
        alert("Please Enter a Valid Order ID.");
        if (type == 1)
            $("#capture_order_id").focus();
        else if (type == 2)
            $("#refund_order_id").focus();
        return;
    }
    if (type == 1)
    {
        $.ajax({
            type: "POST",
            url: baseDir + "hips-ajax.php",
            async: true,
            cache: false,
            data: "orderId=" + orderId + "&id_lang=" + id_lang + "&id_employee=" + id_employee + "&type=" + type + "&secure_key=" + hips_secure_key + "",
            success: function (html) {
                $("#capture_order_details").html(html);
            },
            error: function () {
                alert("ERROR:");
            }
        });
    }
    if (type == 2)
    {
        $.ajax({
            type: "POST",
            url: baseDir + "hips-ajax.php",
            async: true,
            cache: false,
            data: "orderId=" + orderId + "&id_lang=" + id_lang + "&id_employee=" + id_employee + "&type=" + type + "&secure_key=" + hips_secure_key + "",
            success: function (html) {
                $("#refund_order_details").html(html);
            },
            error: function () {
                alert("ERROR:");
            }
        });
    }
}

function clear_orders(type)
{
    if (type == 1)
    {
        $("#capture_order_id").val("");
        $("#capture_order_details").html("");
    }
    if (type == 2)
    {
        $("#refund_order_id").val("");
        $("#refund_order_details").html("");
    }
}

$(function () {

    type_change();


});


function update_ft() {
    if ($("#hips_ft").is(":checked")) {
        document.getElementById("hips_ft_email").readOnly = false;
        $("#hips_ft_email").css("background-color", "white");
    } else {
        document.getElementById("hips_ft_email").readOnly = true;
        $("#hips_ft_email").css("background-color", "#e6e6e6");
    }
}

$(document).ready(function () {
    update_ft();
});