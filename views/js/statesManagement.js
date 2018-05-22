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
$(document).ready(function () {
    $('select#hips_id_country').change(function () {
        updateStateHips();
        updateNeedIDNumberHisps();
    });
    
    if (typeof hips_countries != 'undefined') {
        updateStateHips();
        updateNeedIDNumberHips();
    }
});

function updateStateHips()
{
    $('select#hips_id_state option:not(:first-child)').remove();
    var states = hips_countries[$('select#hips_id_country').val()];

    if (typeof (states) != 'undefined')
    {
        $(states).each(function (key, item) {
            $('select#hips_id_state').append('<option value="' + item.id + '"' + (hips_idSelectedCountry == item.id ? ' selected="selected"' : '') + '">' + item.name + '</option>');
        });

        $('#hips_id_state:hidden').slideDown('slow');
    } else
        $('#hips_id_state').slideUp('fast');
}

function updateNeedIDNumberHips()
{
    var idCountry = parseInt($('select#hips_id_country').val());

    if ($.inArray(idCountry, hips_countriesNeedIDNumber) >= 0)
        $('fieldset.dni').slideDown('slow');
    else
        $('fieldset.dni').slideUp('fast');
}
