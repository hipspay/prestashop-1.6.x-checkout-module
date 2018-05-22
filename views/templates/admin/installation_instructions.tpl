{*
 * 2008 - 2017 Presto-Changeo
 *
 * MODULE Attribute Wizard
 *
 * @version   2.0.0
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
<div class="panel po_main_content" id="installation_instructions">
    
    <div class="panel_header">
        <div class="panel_title">{l s='Installation Instructions' mod='hipscheckout'}</div>
        <div class="panel_info_text important">
            <span class="important_alert"> </span>
            {l s='This installation instruction is very important. Please read carefully before continuing to the configuration tab.' mod='hipscheckout'}
        </div>
        <div class="clear"></div>
    </div>
        
    <div class="general_instructions single_column">
        <div class="instructions_title">{l s='General Instructions' mod='hipscheckout'}</div>
        <div class="general_instructions_content">
            <ul>
                <li>
                    <span>{l s='Hips Checkout will fully replace your checkout page on PrestaShop. Please make sure that the override file is properly installed.' mod='hipscheckout'}</span>
                    <span class="important_alert"> </span>                    
                </li>
               
                
            </ul>
        </div>
    </div>
            
    <div class="override_instructions single_column">
        <div class="instructions_title">
            {l s='Override Files' mod='hipscheckout'}
            
        </div>

        <div class="override_content">
            <div class="override_block">
                <div class="override_class">
                    {l s='Copy' mod='hipscheckout'}<br/>
                    <span  class="{if $checkInstalledCart['/override/controllers/front/ParentOrderController.php']['file_installed']}file_installed{else}file_not_installed{/if}">/hipscheckout/override_{$aw_ps_version|floatval}/controllers/front/ParentOrderController.php</span>
                    <br/>
                    {l s='to' mod='hipscheckout'}<br/>
                    /override/controllers/front/
                </div>
                <div class="override_lines">
                    {if $checkInstalledCart['/override/controllers/front/ParentOrderController.php']['file_not_found']}
                        Lines <span class="{if $checkInstalledCart['/override/controllers/front/ParentOrderController.php']['12-19']}file_installed{else}file_not_installed{/if}">#12-19<span>
                    {else}
                        {l s='Copy entire file' mod='hipscheckout'}
                    {/if}                   
                    
                </div>
            </div>
           

            <div class="extra_instructions">
                <span class="important_alert"> </span>
                <span class="important_instructions important"> 
                    {l s='Make sure to clear the cache in Advanced Parameteres->Performance->Clear Cache.' mod='hipscheckout'}
                </span>
            </div>
        </div>
    </div>
     
    <div class="extra_line"></div>                
   
</div>
