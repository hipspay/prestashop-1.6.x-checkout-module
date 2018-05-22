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
<script type="text/javascript">
    var baseDir = '{$module_dir nofilter}/';
    var id_lang = '{$id_lang|intval}';
    var id_shop = '{$id_shop|intval}';
    var id_employee = '{$id_employee|intval}';
    var hips_secure_key = '{$hips_secure_key|escape:'htmlall':'UTF-8'}';
</script>



<div class="panel po_main_content" id="basic_settings">
    <form action="{$request_uri nofilter}" method="post">
        <div class="panel_header">
            <div class="panel_title">{l s='Basic Settings' mod='hipscheckout'}</div>
            <div class="panel_info_text">
                <span class="simple_alert"> </span>
                {l s='You must click on Update for a change to take effect' mod='hipscheckout'}
            </div>
            <div class="clear"></div>
        </div>
        <div class="two_columns">
            <div class="columns">
                <div class="left_column">
                    {l s='Private Key' mod='hipscheckout'}
                </div>
                <div class="right_column">
                    <input type="text" style="width:200px;" id="hips_private" name="hips_private" value="{$hips_private|escape:'htmlall':'UTF-8'}" />
                </div>
            </div>
            <div class="columns">
                <div class="left_column">
                    {l s='Public Key' mod='hipscheckout'}
                </div>
                <div class="right_column">
                    <input type="text" style="width:200px;" id="hips_public" name="hips_public" value="{$hips_public|escape:'htmlall':'UTF-8'}" />
                </div>
            </div>
                
      

                
            {*
            <div class="columns">
                <div class="left_column">
                    {l s='Payment page' mod='hipscheckout'}
                </div>
                <div class="right_column">
                       <input type="radio" style="margin:-5px 0 0 0;paddin:0;border:none" name="hips_payment_page" id="hips_payment_page" value="0" {if $hips_payment_page == 0}checked{/if}/>
                        &nbsp; {l s='New page' mod='hipscheckout'} &nbsp;&nbsp;&nbsp;
                        <br/><br/>
                        <input type="radio" style="margin:-5px 0 0 0;paddin:0;border:none" name="hips_payment_page" id="hips_payment_page" value="1" {if $hips_payment_page == 1}checked{/if}/>
                        &nbsp; {l s='Embedded in Checkout' mod='hipscheckout'}  &nbsp;&nbsp;&nbsp;
                </div>
            </div>  
            *}    
            
            <div class="columns">
                <div class="left_column">
                    {l s='Transaction Type' mod='hipscheckout'}
                    
                </div>
                <div class="right_column">
                    <select style="width: 200px;display:inline;" id="hips_type" name="hips_type" onchange="javascript:type_change()">
                    <option value="AUTH_CAPTURE" {if $hips_type == 'AUTH_CAPTURE'} selected {/if}>{l s='Authorize and Capture' mod='hipscheckout'}</option>
                    <option value="AUTH_ONLY" {if $hips_type == 'AUTH_ONLY'} selected{/if}>{l s='Authorize Only' mod='hipscheckout'}</option>
                    </select>
                    
                </div>
            </div>   
            <div id="cap_stat" class="columns" style="{if $hips_type != 'AUTH_ONLY'} display:none;{/if}">
                <div class="left_column">
                    {l s='Authorize Order Status' mod='hipscheckout'}
                    <a class="info_alert" href="#authorize_status_info"></a>
                    <div id="authorize_status_info" class="hideADN info_popup">
                        <div class="panel">
                            <h3>
                                {l s='Authorize Order Status' mod='hipscheckout'}
                                <span class="info_icon"> </span>
                            </h3>
                            <div class="upgrade_check_content">
                                <li>{l s='You can create a new Order Status to use for Authorization only in Orders->Statuses' mod='hipscheckout'}</li>
                                <br/><br/>
                               
                            </div>
                        </div>
                    </div>
                </div>
                <div class="right_column">
                    <select name="hips_auth_status" id="hips_auth_status" style="width:200px;display:inline">
                    
                    {foreach from=$states key=k item=state}
                        <option value="{$state['id_order_state']|intval}" {if $hips_auth_status == $state['id_order_state']} selected="selected"{/if}>{$state['name']|escape:'htmlall':'UTF-8'}</option>
                    {/foreach}
                    </select>
                    <br/><br/>
                </div>
            </div>   
            <div class="columns hips_ac_status" style="display:none;">
                <div class="left_column">
                    {l s='Auto Capture' mod='hipscheckout'}
                </div>
                <div class="right_column">
                    <select name="hips_ac_status" id="hips_ac_status" style="width:200px;display:inline">
                    <option value="0">{l s='Not selected' mod='hipscheckout'}</option>
                    {foreach from=$states key=k item=state}
                        <option value="{$state['id_order_state']|intval}" {if $hips_ac_status == $state['id_order_state']} selected="selected"{/if}>{$state['name']|escape:'htmlall':'UTF-8'}</option>
                    {/foreach}
                    </select>
                    
                </div>
            </div>  
           
                    {*
            <div class="columns">
                <div class="left_column">
                    {l s='Enable / Disable all other payment modules' mod='hipscheckout'}
                </div>
                <div class="right_column">
                    <input type="checkbox" value="1" id="hips_enable_disable_modules" name="hips_enable_disable_modules" {if $hips_enable_disable_modules == 1}checked{/if}/> 
                   
                </div>
            </div>   
                    *}
            <div class="columns">
                <div class="left_column">
                    {l s='Failed Transaction' mod='hipscheckout'}
                </div>
                <div class="right_column">
                    <input type="checkbox" value="1" id="hips_ft" name="hips_ft" {if $hips_ft == 1}checked{/if} onchange="update_ft()"/> 
                    <span style="line-height:20px;">{l s='Send an email to' mod='hipscheckout'}</span>
                    <br/><br/>
                    <input type="text" id="hips_ft_email" style="width:200px;display:inline;" name="hips_ft_email" value="{$hips_ft_email|escape:'htmlall':'UTF-8'}" /> 
                    <span style="line-height:20px;">{l s='whenever a transaction fails' mod='hipscheckout'}</span>

                </div>
            </div>   
            
                    {*
            <div class="columns">
                <div class="left_column">
                    {l s='Accepted Cards' mod='hipscheckout'}
                </div>
                <div class="right_column">
                    <input type="checkbox" value="1" id="hips_visa" name="hips_visa" {if $hips_visa == 1}checked{/if}/>
                    <img src="{$path|escape:'htmlall':'UTF-8'}views/img/visa.gif" />
                    &nbsp;&nbsp;
                    <input type="checkbox" value="1" id="hips_mc" name="hips_mc" {if $hips_mc == 1}checked{/if}/>
                    <img src="{$path|escape:'htmlall':'UTF-8'}views/img/mc.gif" />
                    &nbsp;&nbsp;
                    <input type="checkbox" value="1" id="hips_amex" name="hips_amex" {if $hips_amex == 1}checked{/if}/>
                    <img src="{$path|escape:'htmlall':'UTF-8'}views/img/amex.gif" />
                    <br/><br/>
                    <input type="checkbox" value="1" id="hips_discover" name="hips_discover" {if $hips_discover == 1}checked{/if}/>
                    <img src="{$path|escape:'htmlall':'UTF-8'}views/img/discover.gif" />
                    &nbsp;&nbsp;
                    <input type="checkbox" value="1" id="hips_diners" name="hips_diners" {if $hips_diners == 1}checked{/if}/>
                    <img src="{$path|escape:'htmlall':'UTF-8'}views/img/diners.gif" />
                    &nbsp;&nbsp;
                    <input type="checkbox" value="1" id="hips_jcb" name="hips_jcb" {if $hips_jcb == 1}checked{/if}/>
                    <img src="{$path|escape:'htmlall':'UTF-8'}views/img/jcb.gif" />
                    
                    &nbsp;&nbsp;
                </div>
            </div> 
                    *}
            {*
            <div class="columns">
                <div class="left_column">
                    {l s='Require Address' mod='hipscheckout'}
                </div>
                <div class="right_column">
                    <input type="checkbox" value="1" id="hips_get_address" name="hips_get_address" {if $hips_get_address == 1}checked{/if}/>&nbsp;
                    {l s='User must enter an address (Their billing info will be entered by default)' mod='hipscheckout'}
                </div>
            </div> 
            
            <div class="columns">
                <div class="left_column">
                    {l s='Require CVN' mod='hipscheckout'}
                </div>
                <div class="right_column">
                     <input type="checkbox" value="1" id="hips_get_cvm" name="hips_get_cvm" {if $hips_get_cvm == 1}checked{/if}/>&nbsp;
                    {l s='User must enter the 3-4 digit code from the back of the card.' mod='hipscheckout'}
                </div>
            </div>
            
            *}
            <div class="columns">
                <div class="left_column">
                    {l s='Show Left Sidebar Column ' mod='hipscheckout'}
                </div>
                <div class="right_column">
                     <input type="checkbox" value="1" id="hips_show_left" name="hips_show_left" {if $hips_show_left == 1}checked{/if}/>&nbsp;
                    {l s='Check if you want to see the left sidebar column in the checkout page.' mod='hipscheckout'}
                </div>
            </div>   
            <div class="columns">
                <div class="left_column">
                     <input type="submit" value="{l s='Update' mod='hipscheckout'}" name="submitChanges" class="submit_button" />
                </div>
                <div class="right_column">
                    
                </div>
            </div>
                
                
        </div>
        
        <div class="clear"></div>
    </form>
</div>