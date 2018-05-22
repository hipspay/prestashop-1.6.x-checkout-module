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

$( window ).resize(function() {
    recomputeColumnsHeight();
});

$(document).ready(function () {
    
    
    if ($('#uploadBtn').length > 0) {
        document.getElementById("uploadBtn").onchange = function () {
            document.getElementById("uploadFile").value = this.value;
        };

    }
    /* Fixing height and width for left / right column */
    recomputeColumnsHeight();
    
    $(document).on('click', '#main_menu .menu_item' ,function() {
        /* Add style to selected menu item */
        $('#main_menu .menu_item').removeClass('selected');
        $(this).addClass('selected');
        
        /* Show / hide secondary menu */
        var secondaryMenu = $(this).attr('data-left-menu');        
        $('#secondary_menu .menu').hide();        
        $('#secondary_menu #' + secondaryMenu).show();
        
        var noOfVisibleMenu = false;
        $('#left_menu #secondary_menu .menu').each(function(){
            if ($(this).is(":visible"))
                noOfVisibleMenu = true;
        });
        
        if (noOfVisibleMenu) {
            $('#left_menu #secondary_menu').css('margin-top' , '30px');
            $('#left_menu #secondary_menu').css('border' , '1px solid #d7dbde');
            $('#left_menu #secondary_menu').css('padding-top' , '0px');
        
            
        } else {
            $('#left_menu #secondary_menu').css('margin-top' , '0px');
            $('#left_menu #secondary_menu').css('border' , '0px solid #d7dbde');
            $('#left_menu #secondary_menu').css('padding-top' , '15px');
            
            var contentId = $(this).attr('data-content');
            $('.po_main_content').hide();
            $('#' + contentId).show();
        }
        
        $('.instructions_block').hide();
        
        /* Load secondary Menu functionality */
        var secondary_menu_item = $('#secondary_menu #' + secondaryMenu).find('.secondary_menu_item').first().attr('id');
        $('#'+secondary_menu_item).click();
        
        /* Display Left Contact US */
        $('.contact_form_left_menu').hide();
        if ($(this).attr('data-contact-us') == '1')
            $('.contact_form_left_menu').show();    
            
    });
    
    $(document).on('click', '#secondary_menu .secondary_menu_item' ,function() {        
        var leftMenuItemId = $(this).attr('id');
        leftMenuItemId = leftMenuItemId.replace('secondary_menu_item', '');
        
        /* Add style to selected menu item */
        $('#secondary_menu .secondary_menu_item').removeClass('selected');
        $(this).addClass('selected');
        
        /* Hide / Show Instructions */
        $('.instructions_block').hide();
        var instructionsId = $(this).attr('data-instructions');
        $('#' + instructionsId).show();
        
        /* Hide / Show Block contents */
        
        var contentId = $(this).attr('data-content');
        console.log(contentId);
        $('.po_main_content').hide();
        $('#' + contentId).show();
        
        recomputeColumnsHeight();
    });
    
    $('#main_menu .menu_item').first().click();
    
    $(document).on('click', '.menu_header_text' ,function() {
        var classArrow = $(this).parent().find('#left_menu_arrow').attr('class');
        if (classArrow == 'arrow_up') {
            $(this).parent().find('span.arrow_up').attr('class', 'arrow_down');
            $(this).parent().parent().find('.secondary_submenu').slideToggle('slow');
        } else if (classArrow == 'arrow_down') {
            $(this).parent().find('span.arrow_down').attr('class', 'arrow_up');
            $(this).parent().parent().find('.secondary_submenu').slideToggle('slow');
        
        }
    });
    
    $(document).on('click', '#tiny_mce_all_on' ,function() {
        $('.tiny_mce_on').prop('checked', 'checked');
        $('.tiny_mce_off').removeProp('checked');
        
        $('.autoload_rte').each(function(){
            var id = $(this).attr('id');
            var parentTextarea = $(this).parent();
            var dataID = parentTextarea.attr('data-id');
            if (parentTextarea.find('#mce_' + dataID).length == 0) {
                tinyMCEInit('textarea', '#' + id);
            }
        });
        $(this).parent().css('background-color', '#aab3bb');      
        $('.tiny_mce_off').parent().css('background-color', '#aab3bb'); 
    });
    
    $(document).on('click', '#tiny_mce_all_off' ,function() {
        $('.tiny_mce_on').removeProp('checked');
        $('.tiny_mce_off').prop('checked', 'checked');
        
        $('.autoload_rte').each(function(){
            var id = $(this).attr('id');
            tinyMCE.remove('#' + id);
        });
        $(this).parent().css('background-color', '#86d151');      
        $('.tiny_mce_on').parent().css('background-color', '#86d151');    
    });
    
    $(document).on('click', '.tiny_mce_on' ,function() {
        var id = $(this).attr('data-id');
        
        if ($('#mce_' + id).length == 0) {
            tinyMCEInit('textarea', '#description_' + id);
        }
        $(this).parent().css('background-color', '#aab3bb');   
        
    });
    
    $(document).on('click', '.tiny_mce_off' ,function() {
        var id = $(this).attr('data-id');
        tinyMCE.remove('#description_' + id);
        $(this).parent().css('background-color', '#86d151');
    });
    
    $(document).on('click', '.display_more' ,function() {
        
        if (!$(this).hasClass('hide_more')) {            
            $(this).parent().find('.hideADN').each(function(){                
                if ($(this).hasClass('row_format'))
                    $(this).show();
            });
            
            $(this).hide();
            $(this).parent().find('.hide_more').show();
        } else {            
            $(this).parent().find('.hideADN').each(function(){               
                if ($(this).hasClass('row_format'))
                    $(this).hide();
                if ($(this).hasClass('display_more'))
                    return false;
            });
            $(this).hide();
            $(this).parent().find('.display_more').not('.hide_more').show();
        }
    });
    

     $('#open_module_upgrade').fancybox({
            helpers : {
                overlay : {
                    locked : false,
                    css : {
                        'background' : 'transparent'
                    }
                }
            },
            'padding': 0,
            'closeBtn': false,
            'autoScale': true,
            'transitionIn': 'elastic',
            'transitionOut': 'elastic',
            'speedIn': 500,
            'speedOut': 300,
            'autoDimensions': true
    }).click();
    
    $('.info_alert').fancybox({
            helpers : {
                overlay : {
                    locked : false,
                    css : {
                        'background' : 'transparent'
                    }
                }
            },
            'padding': 0,
            'closeBtn': false,
            'autoScale': true,
            'transitionIn': 'elastic',
            'transitionOut': 'elastic',
            'speedIn': 500,
            'speedOut': 300,
            'autoDimensions': true
    });
    
    /* Sorting Attribute Values */
    if ($('.attribute_values_sort').length > 0) 
        $(".attribute_values_sort").sortable();
    /* Sorting Attribute Groups */
    if ($('.attribute_groups_sort').length > 0) 
        $(".attribute_groups_sort").sortable(); 
    /* Call php to update the sorting of attributes*/
    if ($('.attribute_groups_sort').length > 0) {
        $( ".attribute_groups_sort, .attribute_values_sort" ).on( "sortstop", function( event, ui ) {

            var idsInOrder = $(this).sortable("toArray");
            var firstValue = idsInOrder[0];
            var firstValueOpt = firstValue.split('_');
            if (firstValueOpt[0] == 'row') {

                var groups = [];
                $.each(idsInOrder, function( index, value ) {
                    if (value != '') {
                        var ids = value.split('_');
                        groups.push(ids[1]);
                    }
                });

                params = 'ajaxProductsPositions=true&attribute_group_order=1&modulename_random='+modulename_random+'idsInOrder=' + groups;

                $.ajax({
                    type: 'POST',
                    url: baseDir + 'wizard_json.php',
                    async: true,
                    data: params,
                    success: function(data)
                    {
                    }
                });      
            } else {
                var values = [];
                var id_group = 0;
                $.each(idsInOrder, function( index, value ) {
                    if (value != '') {
                        var ids = value.split('_');
                        values.push(ids[1]);
                        id_group = ids[0];
                    }
                });

                params = 'ajaxProductsPositions=true&attribute_value_order=1&modulename_random='+modulename_random+'&id_group='+id_group+'&idsInOrder=' + values;

                $.ajax({
                    type: 'POST',
                    url: baseDir + 'wizard_json.php',
                    async: true,
                    data: params,
                    success: function(data)
                    {
                    }
                });      
            }


        });
    
    }
    presto_toggle_all(0);
    presto_toggle_all(1, 0);
    
});
    function recomputeColumnsHeight() {

        $(".columns").each(function(){
            $(this).find(".left_column").height('auto');
            $(this).find(".right_column").height('auto');
            var
                $this = $(this),
                $leftColumn = $this.find(".left_column"),
                $rightColumn = $this.find(".right_column"),
                heightLeftColumn = $leftColumn.height(),
                heightRightColumn = $rightColumn.height();

            
            
            $leftColumn.add($rightColumn).css("height", function () {
                return heightLeftColumn > heightRightColumn ? heightLeftColumn : heightRightColumn;
            });
            
            if ($leftColumn.find("a.info_alert").length <= 0)
                $leftColumn.css("padding-right", function () {
                    return $this.closest("#advanced_settings").length > 0 ? "0px" : "25px";
                });
        });
    }
    
    function presto_toggle_all(toggle, stop)
    {
        var i = 0;
        
        while ($("div[data-order='"+i+"']").css('display'))
        {
            $("div[data-order='"+i+"']").each(function() {
                if (toggle == 1)
                    $(this).toggle(true);   
                else
                    $(this).toggle(false);
            });
            if (stop == i)
               return;
            i++;
        }
        if (toggle == 1) {
            $(".expand_all span.expand").toggle(true);
            $(".expand_all span.arrow_up").toggle(true);
            $(".expand_all span.collapse").toggle(false);
            $(".expand_all span.arrow_down").toggle(false);
            
            $(".expand_collapse span.arrow_up").toggle(true);
            $(".expand_collapse span.arrow_down").toggle(false);
        } else {
            $(".expand_all span.expand").toggle(false);
            $(".expand_all span.arrow_up").toggle(false);
            $(".expand_all span.collapse").toggle(true);
            $(".expand_all span.arrow_down").toggle(true);
            
            $(".expand_collapse span.arrow_up").toggle(false);
            $(".expand_collapse span.arrow_down").toggle(true);          
        }
    }
    
    function presto_toggle(id) 
    {

        $("div[data-id='"+id+"']").each(function() {
            $(this).toggle();              
        });
        $("#expand_"+id+" span").toggle();
    }
    
   