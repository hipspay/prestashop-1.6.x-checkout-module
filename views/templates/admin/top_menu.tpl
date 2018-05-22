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

<script type="text/javascript" src="{$path|escape:'htmlall':'UTF-8'}views/js/globalBack.js"></script>
<script type="text/javascript" src="{$path|escape:'htmlall':'UTF-8'}views/js/specificBack.js"></script>




<div id="module_top">
    <div id="module_header">
        <div class="module_name_presto">
            {$module_name|escape:'htmlall':'UTF-8'}
            <span class="module_version">{$mod_version|escape:'htmlall':'UTF-8'}</span>
            {if $contactUsLinkPrestoChangeo != ''}
                <div class="module_upgrade {if $upgradeCheck}showBlock{else}hideBlock{/if}">
                    {l s='A new version is available.' mod='hipscheckout'}
                    <a href="{$contactUsLinkPrestoChangeo nofilter}#upgrade">{l s='Upgrade now' mod='hipscheckout'}</a>
                </div>
            {/if}
        </div>
        {if $contactUsLinkPrestoChangeo != ''}   
        <div class="request_upgrade">
            <a href="{$contactUsLinkPrestoChangeo nofilter}#upgrade">{l s='Request an Upgrade' mod='hipscheckout'}</a>
        </div>
        <div class="contact_us">
            <a href="{$contactUsLinkPrestoChangeo nofilter}#customerservice">{l s='Contact us' mod='hipscheckout'}</a>
        </div>

        <div class="presto_logo"><a href="{$contactUsLinkPrestoChangeo nofilter}">{$logoPrestoChangeo nofilter}</a></div>
        <div class="clear"></div>
        {/if}
    </div>
    
    
    <!-- Module upgrade popup -->
    {if $displayUpgradeCheck != ''}
    <a id="open_module_upgrade" href="#module_upgrade"></a>
    <div id="module_upgrade">
        {$displayUpgradeCheck nofilter}
    </div>
    {/if}
    <!-- END - Module upgrade popup -->
    <div class="clear"></div>
    <!-- Main menu - each main menu is connected to a submenu with the data-left-menu value -->
    <div id="main_menu">
        <div id="menu_0" class="menu_item" data-left-menu="secondary_0" data-content="basic_settings">{l s='Configuration' mod='hipscheckout'}</div>
        <div id="menu_1" class="menu_item" data-contact-us="1" data-content="installation_instructions">{l s='Installation Instructions' mod='hipscheckout'}</div> 
        
        <div class="clear"></div>
    </div>
    <!-- END Main menu - each main menu is connected to a submenu with the ALT value -->
</div>
<div class="clear"></div>