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
require_once(_PS_MODULE_DIR_ . 'hipscheckout/PrestoChangeoClasses/init.php');

if (!defined('_PS_VERSION_')) {
    exit;
}

class HipsCheckout extends PaymentModule
{

    protected $html = '';
    protected $postErrors = array();
    public $hips_private = '';
    public $hips_public = '';
    public $hips_type = '';
    public $hips_secure_key = '';
    //public $hips_payment_page = '';
    public $hips_auth_status = '';
    public $hips_ac_status = '';

    /* Failed Transaction */
    public $hips_ft = '';
    public $hips_ft_email = '';
    public $hips_get_address = '';
    public $hips_get_cvm = '';
    public $hips_show_left = '';
    public $hips_visa = '';
    public $hips_mc = '';
    public $hips_amex = '';
    public $hips_discover = '';
    public $hips_jcb = '';
    public $hips_diners = '';
    public $hips_enroute = '';
    //public $hips_enable_disable_modules = '';

    /**
     * ft = failed_transaction
     */
    protected $full_version = 10500;

    public static function enableDisableAllOtherPaymentMethods($enable = false)
    {
        $hook_name = 'displayPayment';
        if (!$module_list = Hook::getHookModuleExecList($hook_name)) {
            return false;
        }

        // Check if hook exists
        if (!$id_hook = Hook::getIdByName($hook_name)) {
            return false;
        }
        /* Enable / disable all other payment modules for all stores */
        foreach ($module_list as $array) {
            $shops = Shop::getContextListShopID();
            foreach ($shops as $id_shop) {
                if ($enable) {
                    // enable back all module hooks 
                    $db_query = new DbQuery();
                    $db_query
                        ->type('DELETE')
                        ->from('hook_module_exceptions');
                    $db_query->where('id_hook=' . (int) $id_hook);
                    $db_query->where('id_shop=' . (int) $id_shop);
                    $db_query->where('id_module=' . (int) $array['id_module']);

                    Db::getInstance()->execute($db_query->build());
                } else {
                    // disable all modules other modules
                    if ($array['module'] != 'hipscheckout') {
                        Db::getInstance()->insert(
                            'hook_module_exceptions', [
                            'id_shop' => (int) $id_shop,
                            'id_module' => (int) $array['id_module'],
                            'id_hook' => (int) $id_hook,
                            'file_name' => 'orderopc'
                            ]
                        );

                        Db::getInstance()->insert(
                            'hook_module_exceptions', [
                            'id_shop' => (int) $id_shop,
                            'id_module' => (int) $array['id_module'],
                            'id_hook' => (int) $id_hook,
                            'file_name' => 'order'
                            ]
                        );
                    }
                }
            }
        }

        return true;
    }

    public function __construct()
    {
        $this->name = 'hipscheckout';
        $this->tab = 'payments_gateways';
        $this->version = '1.0.5';

        //$this->ps_versions_compliancy = array('min' => '1.5', 'max' => _PS_VERSION_);
        $this->author = 'Hips';
        $this->controllers = array('validation');
        $this->is_eu_compatible = 1;

        $this->currencies = true;
        $this->currencies_mode = 'checkbox';

        $this->bootstrap = true;
        parent::__construct();

        $this->_refreshProperties();

        $this->displayName = $this->l('Hips checkout');
        $this->description = $this->l('Receive and Refund payments using Hips Payment');

        if (!count(Currency::checkPaymentCurrencies($this->id))) {
            $this->warning = $this->l('No currency has been set for this module.');
        }
        if ($this->hips_private == '' || $this->hips_public == '') {
            $this->warning = $this->l('You must enter your Hips Payment API infomation, for details on how to get them click "Configure"');
        }
    }

    public function install()
    {
        $secure_key = md5(mt_rand() . time());

        if (!parent::install() ||
            !$this->registerHook('backOfficeHeader') ||
            !$this->registerHook('header') ||
            !$this->registerHook('adminOrder') ||
            !$this->registerHook('updateOrderStatus') ||
            !$this->registerHook('payment') ||
            !$this->registerHook('paymentReturn')
        ) {
            return false;
        }


        if (!Configuration::updateValue('HIPSC_AC_STATUS', '0') ||
            !Configuration::updateValue('HIPSC_AC_STATUS', '0') ||
            !Configuration::updateValue('HIPSC_AUTH_STATUS', '') ||
            !Configuration::updateValue('HIPSC_PAYMENT_PAGE', '1') ||
            !Configuration::updateValue('HIPSC_TYPE', 'AUTH_CAPTURE') ||
            !Configuration::updateValue('HIPSC_VISA', '1') ||
            !Configuration::updateValue('HIPSC_GET_ADDRESS', '0') ||
            !Configuration::updateValue('HIPSC_GET_CVM', '1') ||
            !Configuration::updateValue('HIPSC_SECURE_KEY', $secure_key) ||
            !Configuration::updateValue('HIPSC_MC', '1') ||
            !Configuration::updateValue('HIPSC_FT', '0') ||
            !Configuration::updateValue('HIPSC_FT_EMAIL', '') ||
            !Configuration::updateValue('HIPSC_ADMINHOOK_ADDED', '1')
        ) {
            return false;
        }


        $this->installDBTables();
        return true;
    }

    protected function installDBTables()
    {
        $query = 'CREATE TABLE IF NOT EXISTS `' . _DB_PREFIX_ . 'hips_orders_checkout` (
              `id_cart` int(11) NOT NULL,
			  `id_order_hips` varchar(250) NOT NULL,
              `id_order_ps` int(11) NOT NULL DEFAULT \'0\',
              `checkout_uri` varchar(150) NOT NULL,
              `status` varchar(50) NOT NULL,		
              `products` TEXT NOT NULL,
			  PRIMARY KEY  (`id_cart`)
			) ENGINE=MyISAM;';
        Db::getInstance()->execute($query);

        $query = 'CREATE TABLE IF NOT EXISTS `' . _DB_PREFIX_ . 'hips_refunds_checkout` (
              `id_order` int(11) NOT NULL,
			  `id_cart` int(11) NOT NULL,
              `card_mask` varchar(50) NOT NULL,
              `fingerprint` varchar(50) NOT NULL,			  
              `card_token` varchar(60) NOT NULL,
              `payment_status` varchar(20) NOT NULL,
			  `payment_id` varchar(100) NOT NULL,
			  `order_id` varchar(100) NOT NULL,
			  `captured` TINYINT( 1 ) NOT NULL DEFAULT \'0\',
              `authorization_code` varchar(60) NOT NULL,
			  PRIMARY KEY  (`id_order`)
			) ENGINE=MyISAM;';
        Db::getInstance()->execute($query);

        $query = 'CREATE TABLE IF NOT EXISTS `' . _DB_PREFIX_ . 'hips_refund_history_checkout` (
				`id_refund` int(11) unsigned NOT NULL auto_increment,
				`id_order` int(11) NOT NULL,
                `order_id_hips` varchar(60) NOT NULL,
				`amount` varchar(20) NOT NULL,
				`date` datetime NOT NULL,
				`details` varchar(255) NOT NULL,
				PRIMARY KEY  (`id_refund`)
				) ENGINE=MyISAM;';
        Db::getInstance()->execute($query);
        
        
        $query = 'CREATE TABLE IF NOT EXISTS `' . _DB_PREFIX_ . 'hips_carrier_checkout` (
              `id_carrier_ps` int(11) NOT NULL,
              `id_carrier_hips` varchar(250) NOT NULL,
              `carrier_name` varchar(150) NOT NULL,
              
			  PRIMARY KEY  (`id_carrier_ps`)
			) ENGINE=MyISAM;';
        Db::getInstance()->execute($query);
    }

    protected function isColumnExistInTable($column, $table)
    {
        $sqlExistsColumn = 'SELECT * FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=DATABASE()
                        AND COLUMN_NAME="' . $column . '" AND TABLE_NAME="' . _DB_PREFIX_ . $table . '"; ';
        $exists = Db::getInstance()->ExecuteS($sqlExistsColumn);
        return !empty($exists);
    }

    /**
     * Method for register hook for installed module
     */
    public function registerHookWithoutInstall($hookname, $module_prefix)
    {
        $varName = $module_prefix . '_' . Tools::strtoupper($hookname) . '_ADDED';

        if (Configuration::get($varName) != 1) {
            $hookId = Hook::getIdByName($hookname);
            $isExistModule = Hook::getModulesFromHook($hookId, $this->id);

            if (empty($isExistModule)) {
                if ($this->registerHook($hookname)) {
                    Configuration::updateValue($varName, '1');
                }
            } else {
                // if module already istalled just set variable = 1
                Configuration::updateValue($varName, '1');
            }
        }
    }

    protected function applyUpdatesAlertTable()
    {
        
    }

    private function _refreshProperties()
    {
        $this->hips_private = Configuration::get('HIPSC_PRIVATE');
        $this->hips_public = Configuration::get('HIPSC_PUBLIC');
        $this->hips_type = Configuration::get('HIPSC_TYPE');
        //$this->hips_payment_page = (int) Configuration::get('HIPSC_PAYMENT_PAGE');
        $this->hips_auth_status = (int) Configuration::get('HIPSC_AUTH_STATUS');
        $this->hips_ac_status = (int) Configuration::get('HIPSC_AC_STATUS');
        $this->hips_secure_key = Configuration::get('HIPSC_SECURE_KEY');

        $this->hips_ft = (int) Configuration::get('HIPSC_FT');
        $this->hips_ft_email = Configuration::get('HIPSC_FT_EMAIL');
        //$this->hips_enable_disable_modules = (int) Configuration::get('HIPSC_ENABLE_MODULES');

        $this->hips_get_address = (int) Configuration::get('HIPSC_GET_ADDRESS');
        $this->hips_get_cvm = (int) Configuration::get('HIPSC_GET_CVM');
        $this->hips_show_left = (int) Configuration::get('HIPSC_SHOW_LEFT');
        $this->hips_visa = (int) Configuration::get('HIPSC_VISA');
        $this->hips_mc = (int) Configuration::get('HIPSC_MC');
        $this->hips_amex = (int) Configuration::get('HIPSC_AMEX');
        $this->hips_discover = (int) Configuration::get('HIPSC_DISCOVER');
        $this->hips_jcb = (int) Configuration::get('HIPSC_JCB');
        $this->hips_diners = (int) Configuration::get('HIPSC_DINERS');
        $this->hips_enroute = (int) Configuration::get('HIPSC_ENROUTE');


        $this->_last_updated = Configuration::get('PRESTO_CHANGEO_UC');
    }

    protected function applyUpdates()
    {

        $this->applyUpdatesAlertTable();
        $this->installDBTables();
        /**
         * update hook module without reinstall module
         */
        $this->registerHookWithoutInstall('adminOrder', 'HIPSC');
        $this->registerHookWithoutInstall('header', 'HIPSC');
        $this->registerHookWithoutInstall('backOfficeHeader', 'HIPSC');

        $this->registerHookWithoutInstall('updateOrderStatus', 'HIPSC');
    }

    public function hookUpdateOrderStatus($params)
    {
        $id_order = $params['id_order'];
        $id_new_status = $params['newOrderStatus']->id;
        $ret = $this->doCaptureByOrderState($id_order, $id_new_status);
    }

    public function doRevokeOrder($id_order, $amt)
    {
        $hipsAPI = new HipsCheckoutAPI($this->hips_private, $this->hips_public);

        $result = Db::getInstance()->ExecuteS('SELECT * FROM ' . _DB_PREFIX_ . 'hips_refunds_checkout WHERE id_order=' . (int) $id_order);

        $sqlSelectHipsOrder = 'SELECT * FROM ' . _DB_PREFIX_ . 'hips_orders_checkout WHERE id_cart = ' . (int) $result[0]['id_cart'];

        $resSelectHipsOrder = Db::getInstance()->ExecuteS($sqlSelectHipsOrder);

        $dbProducts = serialize(array());
        if (isset($resSelectHipsOrder) && !empty($resSelectHipsOrder)) {
            $dbProducts = $resSelectHipsOrder[0]['products'];
        }
        $hipsOrderId = $result[0]['payment_id'];
        $post_values = array(
            'hipsOrderId' => $hipsOrderId,
            'amount' => $amt,
            'dbProducts' => $dbProducts
        );

        $dorevokeOrderResp = $hipsAPI->revokeOrder($post_values);


        return $dorevokeOrderResp;
    }

    public function doFullFillOrder($id_order)
    {
        $hipsAPI = new HipsCheckoutAPI($this->hips_private, $this->hips_public);

        $result = Db::getInstance()->ExecuteS('SELECT * FROM ' . _DB_PREFIX_ . 'hips_refunds_checkout WHERE id_order=' . (int) $id_order);

        $hipsOrderId = $result[0]['payment_id'];
        $post_values = array(
            'hipsOrderId' => $hipsOrderId
        );

        $dofulfillOrderResp = $hipsAPI->fulfillOrder($post_values);

        return $dofulfillOrderResp;
    }

    public function doCapture($id_order)
    {
        $hipsAPI = new HipsCheckoutAPI($this->hips_private, $this->hips_public);

        $result = Db::getInstance()->ExecuteS('SELECT * FROM ' . _DB_PREFIX_ . 'hips_refunds_checkout WHERE id_order=' . (int) $id_order);

        $hipsOrderId = $result[0]['payment_id'];
        $post_values = array(
            'hipsOrderId' => $hipsOrderId
        );

        $doRefundResp = $hipsAPI->doCapture($post_values);


        return $doRefundResp;
    }

    public function doCaptureByOrderId($id_order)
    {
        $ret = Db::getInstance()->ExecuteS('SELECT * FROM ' . _DB_PREFIX_ . 'hips_refunds_checkout WHERE id_order=' . (int) $id_order);
        if (!empty($ret) && $ret[0]['captured'] == 0) {

            $payment_id = $ret[0]['payment_id'];
            $hipsOrderId = $ret[0]['order_id'];
            $amount = NULL;


            $hipsAPI = new HipsCheckoutAPI($this->hips_private, $this->hips_public);
            $post_values = array(
                'hipsOrderId' => $payment_id
            );

            $doCaptureResp = $hipsAPI->doCapture($post_values);


            if (isset($doCaptureResp['status']) && $doCaptureResp['status'] == 'successful') {
                Db::getInstance()->Execute('UPDATE ' . _DB_PREFIX_ . 'hips_refunds_checkout SET captured = 1  WHERE id_order=' . (int) $id_order);

                $order = new Order($id_order);
                $message = new Message();
                $message->message = $this->l('Transaction has been captured.') .
                    $this->l('Transaction ID: ') .
                    $doCaptureResp['id'];

                $message->id_customer = $order->id_customer;
                $message->id_order = $order->id;
                $message->private = 1;
                $message->id_employee = $this->getContext()->cookie->id_employee;
                $message->id_cart = $order->id_cart;
                $message->add();

                return $doCaptureResp;
            } else {
                return false;
            }
        }
    }

    public function doCaptureByOrderState($id_order, $id_new_status)
    {
        if ($this->hips_ac_status == $id_new_status && $this->hips_type == 'AUTH_ONLY') {
            return $this->doCaptureByOrderId($id_order);
        }

        return false;
    }

    public function hookAdminOrder()
    {
        $smarty = $this->context->smarty;
        $cookie = $this->context->cookie;

        $orderId = Tools::getValue('id_order');

        $order = new Order($orderId);
        $refundsRecord = Db::getInstance()->ExecuteS('SELECT * FROM  `' . _DB_PREFIX_ . 'hips_refunds_checkout` WHERE id_order = "' . ((int) $orderId ) . '"');

        if (!empty($refundsRecord)) {
            $refundsHistory = Db::getInstance()->ExecuteS('SELECT * FROM  `' . _DB_PREFIX_ . 'hips_refund_history_checkout` WHERE id_order = "' . ((int) $orderId ) . '"');
                                                                                              
            $id_shop = Shop::getContextShopID();

            $smarty->assign(array(
                'order_id' => $orderId,
                'cookie' => $cookie,
                'path' => $this->_path,
                'id_shop' => $id_shop,
                'hipsc_secure_key' => $this->hips_secure_key,
                'module_basedir' => _MODULE_DIR_ . 'hipscheckout/',
                'isCanCapture' => !$refundsRecord[0]['captured'] && $this->hips_type == 'AUTH_ONLY'
            ));
            return $this->display(__FILE__, 'views/templates/admin/adminOrder.tpl');
        }

        return '';
    }

    public function hookHeader()
    {
        $page_name = Dispatcher::getInstance()->getController();
        $smarty = $this->context->smarty;

        $this->context->cookie->hipscall = 0;
        $_COOKIE['hipscall'] = 0;

        if ($page_name == '') {
            $page_name = Configuration::get('PS_FORCE_SMARTY_2') == 0 ? $smarty->tpl_vars['page_name']->value : $smarty->get_template_vars('page_name');
        }

        if (!in_array($page_name, array('order', 'orderopc'))) {
            return;
        }


        $this->context->controller->addJS(__PS_BASE_URI__ . 'modules/hipscheckout/views/js/hipscheckout.js');
        $this->context->controller->addJS(__PS_BASE_URI__ . 'modules/hipscheckout/views/js/statesManagement.js');
        $this->context->controller->addCSS(__PS_BASE_URI__ . 'modules/hipscheckout/views/css/hipscheckout.css');
    }



    public function getContent()
    {
        $this->_postProcess();
        $output = $this->_displayForm();
        return $this->html . $output;
    }

    private function _displayForm()
    {
        $this->applyUpdates();
        $this->prepareAdminVars();

        $topMenuDisplay = $this->display(__FILE__, 'views/templates/admin/top_menu.tpl');
        $leftMenuDisplay = $this->display(__FILE__, 'views/templates/admin/left_menu.tpl');

        $basicSettingsDisplay = $this->display(__FILE__, 'views/templates/admin/basic_settings.tpl');
        $captureTransactionDisplay = $this->display(__FILE__, 'views/templates/admin/capture_transaction.tpl');
        $refundTransactionDisplay = $this->display(__FILE__, 'views/templates/admin/refund_transaction.tpl');

        $installationInstructionsDisplay = $this->display(__FILE__, 'views/templates/admin/installation_instructions.tpl');

        $bottomSettingsDisplay = $this->display(__FILE__, 'views/templates/admin/bottom_menu.tpl');
        return $topMenuDisplay . $leftMenuDisplay . $basicSettingsDisplay . $captureTransactionDisplay . $refundTransactionDisplay . $installationInstructionsDisplay . $bottomSettingsDisplay;
    }

    private function prepareAdminVars()
    {
        $states = OrderState::getOrderStates((int) ($this->context->cookie->id_lang));

        $displayUpgradeCheck = '';
        if (file_exists(dirname(__FILE__) . '/PrestoChangeoClasses/PrestoChangeoUpgrade.php')) {
            if (!in_array('PrestoChangeoUpgrade', get_declared_classes())) {
                require_once(dirname(__FILE__) . '/PrestoChangeoClasses/PrestoChangeoUpgrade.php');
            }
            $initFile = new PrestoChangeoUpgrade($this, $this->_path, $this->full_version);

            $upgradeCheck = $initFile->displayUpgradeCheck('HIPS');
            if (isset($upgradeCheck) && !empty($upgradeCheck)) {
                $displayUpgradeCheck = $upgradeCheck;
            }
        }

        $getModuleRecommendations = '';
        if (file_exists(dirname(__FILE__) . '/PrestoChangeoClasses/PrestoChangeoUpgrade.php')) {

            if (!in_array('PrestoChangeoUpgrade', get_declared_classes())) {
                require_once(dirname(__FILE__) . '/PrestoChangeoClasses/PrestoChangeoUpgrade.php');
            }
            $initFile = new PrestoChangeoUpgrade($this, $this->_path, $this->full_version);

            $getModuleRecommendations = $initFile->getModuleRecommendations('HIPS');
        }

        $logoPrestoChangeo = '';
        $contactUsLinkPrestoChangeo = '';
        if (file_exists(dirname(__FILE__) . '/PrestoChangeoClasses/PrestoChangeoUpgrade.php')) {
            if (!in_array('PrestoChangeoUpgrade', get_declared_classes())) {
                require_once(dirname(__FILE__) . '/PrestoChangeoClasses/PrestoChangeoUpgrade.php');
            }
            $initFile = new PrestoChangeoUpgrade($this, $this->_path, $this->full_version);


            $logoPrestoChangeo = $initFile->getPrestoChangeoLogo();
            $contactUsLinkPrestoChangeo = $initFile->getContactUsOnlyLink();
        }

        $ps_version = $this->getPSV();
        $checkInstalledCart = $this->fileCheckLines('/override/controllers/front/ParentOrderController.php', '/override/controllers/front/ParentOrderController.php', array('12-19'), $ps_version);

        $checkInstalledPaymentModule = $this->fileCheckLines('/override/classes/PaymentModule.php', '/override/classes/PaymentModule.php', array('59-64', '180-187', '214-226', '234-246', 680), $ps_version);

        
        $aw_ps_version = Tools::substr(_PS_VERSION_, 0, 6);
        $aw_ps_version = str_replace('.', '', $aw_ps_version);

        $id_shop = Shop::getContextShopID();
        $this->context->smarty->assign(array(
            'base_uri' => __PS_BASE_URI__,
            'aw_ps_version' => $this->getPSV(),
            'displayUpgradeCheck' => $displayUpgradeCheck,
            'getModuleRecommendations' => $getModuleRecommendations,
            'id_lang' => $this->context->cookie->id_lang,
            'id_shop' => $id_shop,
            'checkInstalledCart' => $checkInstalledCart,
            'checkInstalledPaymentModule' => $checkInstalledPaymentModule,
            'id_employee' => $this->context->cookie->id_employee,
            'hips_private' => $this->hips_private,
            'hips_public' => $this->hips_public,
            'hips_type' => $this->hips_type,
            'hips_auth_status' => $this->hips_auth_status,
            'hips_ac_status' => $this->hips_ac_status,
            'hips_secure_key' => $this->hips_secure_key,
            //'hips_enable_disable_modules' => $this->hips_enable_disable_modules,
            'hips_ft' => $this->hips_ft,
            'hips_ft_email' => $this->hips_ft_email,
            'hips_get_address' => $this->hips_get_address,
            'hips_get_cvm' => $this->hips_get_cvm,
            'hips_show_left' => $this->hips_show_left,
            'hips_visa' => $this->hips_visa,
            'hips_mc' => $this->hips_mc,
            'hips_amex' => $this->hips_amex,
            'hips_discover' => $this->hips_discover,
            'hips_jcb' => $this->hips_jcb,
            'hips_diners' => $this->hips_diners,
            //'hips_enroute' => $this->hips_enroute,
            'states' => $states,
            'path' => $this->_path,
            'module_name' => $this->displayName,
            'module_dir' => _MODULE_DIR_,
            'module_basedir' => _MODULE_DIR_ . 'hipscheckout/',
            'request_uri' => $_SERVER['REQUEST_URI'],
            'mod_version' => $this->version,
            'upgradeCheck' => (isset($upgradeCheck) && !empty($upgradeCheck) ? true : false),
            'logoPrestoChangeo' => $logoPrestoChangeo,
            'contactUsLinkPrestoChangeo' => $contactUsLinkPrestoChangeo
        ));
    }

    private function fileCheckLines($lfile, $mfile, $lines, $ps_version, $extra = "")
    {
        $return = array();

        if (!file_exists(_PS_ROOT_DIR_ . $lfile)) {
            $return[$lfile]['file_not_found'] = false;
        } else {
            $return[$lfile]['file_not_found'] = true;
        }

        $return[$lfile]['file_installed'] = false;

        $server_file = Tools::file_get_contents(_PS_ROOT_DIR_ . $lfile);
        $all_good = true;
        $module_lines = file(_PS_ROOT_DIR_ . "/modules/hipscheckout/modified_" . $ps_version . $mfile);

               
      
        $fullyInstalled = true;

        foreach ($lines as $line) {
            if (sizeof($module_lines) <= 1) {
                $all_good = false;
                $line_good = false;

                break;
            }
            $start = "";
            $end = "";
            if (strpos($line, "-") === false) {
                $start = max($line - 1, 0);
                $end = min($line + 1, sizeof($module_lines) - 1);
            } else {
                $tmp_arr = explode("-", $line);
                $start = max((int) ($tmp_arr[0]) - 1, 0);
                $end = min((int) ($tmp_arr[1] + 1), sizeof($module_lines) - 1);
            }
           
            $line_good = true;
            for ($i = $start; $i <= $end; $i++) {
                
                if (trim($module_lines[$i]) == "" || strpos($server_file, trim($module_lines[$i])) !== false) {
                    if (trim($module_lines[$i]) != "") {
                        $server_file = Tools::substr($server_file, strpos($server_file, trim($module_lines[$i])) + 1);
                    }
                } else {
                    $all_good = false;
                    $line_good = false;
                    break;
                }
            }
            if ($fullyInstalled && $all_good) {
                $fullyInstalled = true;
            } else {
                $fullyInstalled = false;
            }
            $return[$lfile][$line] = $line_good;
        }
        $return[$lfile]['file_installed'] = $fullyInstalled;

        return $return;
    }

    private function _postProcess()
    {
        if (Tools::isSubmit('submitChanges')) {
            if (Tools::getValue('hips_type') == 'AUTH_ONLY') {
                $hips_ac_status = Tools::getValue('hips_ac_status');
            } else {
                $_POST['hips_ac_status'] = 0;
                $hips_ac_status = 0;
            }

            //$hips_enable_disable_modules = Tools::getValue('hips_enable_disable_modules', 0);
            //$this->enableDisableAllOtherPaymentMethods(!$hips_enable_disable_modules);

            if (!Configuration::updateValue('HIPSC_AC_STATUS', $hips_ac_status) ||
                !Configuration::updateValue('HIPSC_AUTH_STATUS', Tools::getValue('hips_auth_status')) ||
                //!Configuration::updateValue('HIPSC_PAYMENT_PAGE', Tools::getValue('hips_payment_page')) ||
                !Configuration::updateValue('HIPSC_PRIVATE', Tools::getValue('hips_private')) ||
                !Configuration::updateValue('HIPSC_PUBLIC', Tools::getValue('hips_public')) ||
                !Configuration::updateValue('HIPSC_TYPE', Tools::getValue('hips_type')) ||
                //!Configuration::updateValue('HIPSC_ENABLE_MODULES', Tools::getValue('hips_enable_disable_modules')) ||
                !Configuration::updateValue('HIPSC_FT', Tools::getValue('hips_ft')) ||
                !Configuration::updateValue('HIPSC_FT_EMAIL', Tools::getValue('hips_ft_email')) ||
                !Configuration::updateValue('HIPSC_GET_ADDRESS', Tools::getValue('hips_get_address')) ||
                !Configuration::updateValue('HIPSC_GET_CVM', Tools::getValue('hips_get_cvm')) ||
                !Configuration::updateValue('HIPSC_SHOW_LEFT', Tools::getValue('hips_show_left')) ||
                !Configuration::updateValue('HIPSC_VISA', Tools::getValue('hips_visa')) ||
                !Configuration::updateValue('HIPSC_MC', Tools::getValue('hips_mc')) ||
                !Configuration::updateValue('HIPSC_AMEX', Tools::getValue('hips_amex')) ||
                !Configuration::updateValue('HIPSC_DISCOVER', Tools::getValue('hips_discover')) ||
                !Configuration::updateValue('HIPSC_JCB', Tools::getValue('hips_jcb')) ||
                !Configuration::updateValue('HIPSC_DINERS', Tools::getValue('hips_diners')) ||
                !Configuration::updateValue('HIPSC_ENROUTE', Tools::getValue('hips_enroute'))
            ) {
                $this->html .= $this->displayError($this->l('Cannot update settings'));
            } else {
                $this->html .= $this->displayConfirmation($this->l('Settings updated'));
            }
            
        }
        $this->_refreshProperties();
    }

    /**
     * Add the CSS & JavaScript files you want to be loaded in the BO.
     */
    public function hookBackOfficeHeader()
    {
        if (Tools::getValue('configure') == $this->name) {
            $this->context->controller->addCSS($this->_path . 'views/css/globalBack.css');
            $this->context->controller->addCSS($this->_path . 'views/css/specificBack.css');
        }
    }

    /**
     * Return path to http module directory.
     */
    public function getHttpPathModule()
    {
        return (Configuration::get('PS_SSL_ENABLED') ? 'https://' : 'http://') . htmlspecialchars($_SERVER['HTTP_HOST'], ENT_COMPAT, 'UTF-8') . __PS_BASE_URI__ . 'modules/' . $this->name . '/';
    }

    public function getRedirectBaseUrl()
    {


        $redirect_url = Context::getContext()->link->getPageLink('order-confirmation');
        return $redirect_url = strpos($redirect_url, '?') !== false ? $redirect_url . '&' : $redirect_url . '?';
    }

    public function getHipsFilename()
    {
        return 'validation';
    }

    /**
     * Retrun validation for all version prestashop
     */
    public function getValidationLink($file = 'validation')
    {

        $validationLink = Context::getContext()->link->getModuleLink($this->name, $file, array(), true);

        return $validationLink;
    }

    public function getCartRulesTaxCloud($id_cart, $id_lang, $filter = CartRule::FILTER_ACTION_ALL)
    {
        // If the cart has not been saved, then there can't be any cart rule applied
        if (!CartRule::isFeatureActive() || !$id_cart)
            return array();

        $cache_key = 'Cart::getCartRules' . $id_cart . '-' . $filter;
        if (!Cache::isStored($cache_key)) {
            $result = Db::getInstance()->executeS('
				SELECT *
				FROM `' . _DB_PREFIX_ . 'cart_cart_rule` cd
				LEFT JOIN `' . _DB_PREFIX_ . 'cart_rule` cr ON cd.`id_cart_rule` = cr.`id_cart_rule`
				LEFT JOIN `' . _DB_PREFIX_ . 'cart_rule_lang` crl ON (
					cd.`id_cart_rule` = crl.`id_cart_rule`
					AND crl.id_lang = ' . (int) $id_lang . '
				)
				WHERE `id_cart` = ' . (int) $id_cart . '
				' . ($filter == CartRule::FILTER_ACTION_SHIPPING ? 'AND free_shipping = 1' : '') . '
				' . ($filter == CartRule::FILTER_ACTION_GIFT ? 'AND gift_product != 0' : '') . '
				' . ($filter == CartRule::FILTER_ACTION_REDUCTION ? 'AND (reduction_percent != 0 OR reduction_amount != 0)' : '')
            );
            Cache::store($cache_key, $result);
        }
        $result = Cache::retrieve($cache_key);

        // Define virtual context to prevent case where the cart is not the in the global context
        $virtual_context = Context::getContext()->cloneContext();
        $virtual_context->cart = new Cart($id_cart);

        foreach ($result as &$row) {
            $row['obj'] = new CartRule($row['id_cart_rule'], (int) $id_lang);
            //$row['value_real'] = $row['obj']->getContextualValue(true, $virtual_context, $filter);
            //$row['value_tax_exc'] = $row['obj']->getContextualValue(false, $virtual_context, $filter);
            // Retro compatibility < 1.5.0.2
            $row['id_discount'] = $row['id_cart_rule'];
            $row['description'] = $row['name'];
        }

        return $result;
    }

    public function hookPayment($params)
    {
        if (!$this->active) {
            return;
        }

        $method = Tools::getValue('getAddressBlockAndCarriersAndPayments');
        $ajax = Tools::getValue('ajax');
        if (isset($ajax) && $ajax)
            $hipsCheckoutCount = 1;
        else
            $hipsCheckoutCount = 2;





        $this->context->smarty->assign(array(
            'hipsCheckoutCount' => $hipsCheckoutCount));

        if (!$this->checkCurrency($params['cart'])) {
            return;
        }

        $cart = $this->context->cart;
        $cookie = $this->context->cookie;

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

        if ($this->getPSV() >= 1.4) {
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
        $redirectConformation = (($psv < 1.5 ) ? (Configuration::get('PS_SSL_ENABLED') ? 'https://' : 'http://') . htmlspecialchars($_SERVER['HTTP_HOST'], ENT_COMPAT, 'UTF-8') . __PS_BASE_URI__ . 'order-confirmation.php' : $link->getPageLink('order-confirmation.php'));
        $redirectConformation = strpos(rtrim($redirectConformation, '?'), '?') !== false ? $redirectConformation . '&' : $redirectConformation . '';

        $redirectFailed = (Configuration::get('PS_SSL_ENABLED') ? 'https://' : 'http://') . htmlspecialchars($_SERVER['HTTP_HOST'], ENT_COMPAT, 'UTF-8') . __PS_BASE_URI__ . 'modules/hipscheckout/orderFailed.php';

        $post_values = array(
            'redirectConformation' => $redirectConformation,
            'redirectFailed' => $redirectFailed,
            'cart_id' => $cart->id,
            'purchase_currency' => $currencyOrder->iso_code,
            'amount' => $x_amount,
            'capture' => ($this->hips_type == 'AUTH_CAPTURE' ? true : false),
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
                "vat_amount" => number_format($tax_amount, 2, '.', '') * 100,
                "meta_data_1" => $product['attributes']
            ];
        }
        $id_carrier = $this->context->cart->id_carrier;
        $carrier = new Carrier((int) $id_carrier);

        $carrierName = $carrier->name;

        $carrier_tax = $carrier->getTaxesRate($address_delivery);

        $shippingCostWT = number_format($cart->getOrderTotal(true, Cart::ONLY_SHIPPING), 2, '.', '');
        $shippingCostWithoutTax = number_format($cart->getOrderTotal(false, Cart::ONLY_SHIPPING), 2, '.', '');
        $carrier_tax = $shippingCostWT - $shippingCostWithoutTax;
        $cartProducts[] = [
            "id_product" => 0,
            "id_product_attribute" => 0,
            "type" => "shipping_fee",
            "sku" => '1',
            "name" => $carrierName,
            "quantity" => '1',
            "unit_price" => number_format($shippingCostWT, 2, '.', '') * 100,
            "discount_rate" => 0,
            "vat_amount" => number_format($carrier_tax, 2, '.', '') * 100,
        ];

        $post_values['cartProducts'] = $cartProducts;
        $hipsAPI = new HipsCheckoutAPI($this->hips_private, $this->hips_public);


        $sqlSelectHipsOrder = 'SELECT * FROM ' . _DB_PREFIX_ . 'hips_orders_checkout WHERE id_cart = ' . (int) $cart->id;
        $resSelectHipsOrder = Db::getInstance()->ExecuteS($sqlSelectHipsOrder);

        $id_order_hips = '';
        $err = '';

        $updateHips = false;

        $createHips = false;
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

                //echo $sqlUpdateHips;
                Db::getInstance()->execute($sqlUpdateHips);
                $id_order_hips = $updateOrderResp['id'];
            }

            $updateHips = true;
        } else {
         * */
         
            // Create new HIPS order
            $doOrderResp = $hipsAPI->doOrder($post_values);

            if (isset($doOrderResp['error']) && !empty($doOrderResp['error'])) {
                $err = '<td align="left" style="font-weight:bold;font-size:12px;color:red;" nowrap>';
                $err .= $doOrderResp['error']['type'] . ' - ' . $doOrderResp['error']['message'];
                $err .= '</td>';
            } elseif (isset($doOrderResp['status'])) {

                if (isset($resSelectHipsOrder) && !empty($resSelectHipsOrder)) {
                    // Update Order
                    $post_values['hipsOrderId'] = $resSelectHipsOrder[0]['id_order_hips'];

                    $post_values['dbProducts'] = $resSelectHipsOrder[0]['products'];
            
                    $sqlUpdateHips = 'UPDATE ' . _DB_PREFIX_ . 'hips_orders_checkout '
                    . 'SET id_order_hips = "' . pSQL($doOrderResp['id']) . '",'
                    . 'checkout_uri = "' . pSQL($doOrderResp['checkout_uri']) . '",'
                    . 'status = "' . pSQL($doOrderResp['status']) . '", '
                    . 'products = "' . pSQL(serialize($doOrderResp['cart']['items'])) . '" '
                    . 'WHERE id_cart = ' . (int) $cart->id;

                    //echo $sqlUpdateHips;
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

            $createHips = true;
        //}

        $this->context->smarty->assign(array(
            'createHips' => $createHips,
            'updateHips' => $updateHips));


        require_once('controllers/front/validation.php');
        // hack for presta 1.5
        $_POST['module'] = 'hipscheckout';
        $addresses = $this->context->customer->getAddresses($this->context->language->id);
        $this->context->smarty->assign('addresses', $addresses);

        hipscheckoutvalidationModuleFrontController::prepareVarsView($this->context, $this, $hips_cc_err = '', time());
        $this->context->smarty->assign(array(
            'hips_payment_page' => 1,
            'hips_public' => $this->hips_public,
            'err' => $err,
            'id_order_hips' => $id_order_hips
        ));

        return $this->display(__FILE__, 'views/templates/front/validation.tpl');



        /*        $currencies = Currency::getCurrencies();


          $cart = $this->context->cart;
          $address = new Address((int) ($cart->id_address_invoice));
          $customer = new Customer((int) ($cart->id_customer));
          $state = new State((int) $address->id_state);
          $selectedCountry = (int) ($address->id_country);
          $address_delivery = new Address((int) ($cart->id_address_delivery));
          $countries = Country::getCountries((int) ($this->context->cookie->id_lang), true);
          $countriesList = '';
          foreach ($countries as $country) {
          $countriesList .= '<option value="' . ($country['id_country']) . '" ' . ($country['id_country'] == $selectedCountry ? 'selected="selected"' : '') . '>' . htmlentities($country['name'], ENT_COMPAT, 'UTF-8') . '</option>';
          }
          if ($address->id_state) {
          $this->context->smarty->assign('id_state', $state->iso_code);
          }


          $hips_cards = '';
          if ($this->hips_visa)
          $hips_cards .= $this->l('Visa') . ', ';
          if ($this->hips_mc)
          $hips_cards .= $this->l('Mastercard') . ', ';
          if ($this->hips_amex)
          $hips_cards .= $this->l('Amex') . ', ';
          if ($this->hips_discover)
          $hips_cards .= $this->l('Discover') . ', ';
          if ($this->hips_jcb)
          $hips_cards .= $this->l('JCB') . ', ';
          if ($this->hips_diners)
          $hips_cards .= $this->l('Diners') . ', ';

          $currencies = Currency::getCurrencies();
          $this->context->smarty->assign('countries_list', $countriesList);
          $this->context->smarty->assign('countries', $countries);
          $this->context->smarty->assign('address', $address);
          $this->context->smarty->assign('currencies', $currencies);

          $hips_filename = 'validation';
          $this->context->smarty->assign(array(
          'err' => $err,
          'hips_payment_page' => $this->hips_payment_page,
          'currencies' => $currencies,
          'active' => ($this->hips_private != '' && $this->hips_public != '') ? true : false,
          'hips_visa' => $this->hips_visa,
          'hips_mc' => $this->hips_mc,
          'hips_amex' => $this->hips_amex,
          'hips_discover' => $this->hips_discover,
          'hips_jcb' => $this->hips_jcb,
          'hips_diners' => $this->hips_diners,
          'hipsc_filename' => $hips_filename,
          'hips_get_address' => $this->hips_get_address,
          'hips_get_cvm' => $this->hips_get_cvm,
          'hips_cards' => $hips_cards,
          'this_path' => __PS_BASE_URI__ . 'modules/' . $this->name . '/',
          'this_validation_link' => $this->getValidationLink($hips_filename) . '',
          'hips_public' => $this->hips_public,
          'id_order_hips' => $id_order_hips
          ));


          return $this->display(__FILE__, 'views/templates/hook/payment.tpl');
         * *
         */
    }

    public function checkCurrency($cart)
    {
        $currency_order = new Currency($cart->id_currency);
        $currencies_module = $this->getCurrency($cart->id_currency);

        if (is_array($currencies_module)) {
            foreach ($currencies_module as $currency_module) {
                if ($currency_order->id == $currency_module['id_currency']) {
                    return true;
                }
            }
        }
        return false;
    }

    /**
     * Send email error
     * $email - email which will be sent error
     * $cartObj - PS cart object
     * $errorText - text that return payment gateway
     */
    public function sendErrorEmail($email, $cartObj, $errorText, $template = 'error', $cartInfo = array(), $isCustomAddress = 0)
    {
        $customerObj = new Customer($cartObj->id_customer);
        $address = new Address((int) ($cartObj->id_address_invoice));

        $addressHTML = '';
        $addressHTML .= $this->l('Cart ') . '# ' . $cartObj->id . '<br /><br />' . '\n\r' . '\n\r';

        if (!empty($cartInfo['number'])) {
            $addressHTML .= $this->l('Card Number') . ': XXXX XXXX XXXX ' . $cartInfo['number'] . '<br /><br />' . '\n\r' . '\n\r';
        }
        if ($isCustomAddress) {
            $addressHTML .= $cartInfo['firstname'] . ' ' . $cartInfo['lastname'] . '<br />' . '\n\r';
            $addressHTML .= $cartInfo['address'] . '<br />' . '\n\r';
            $addressHTML .= $cartInfo['city'] . ' ' . $cartInfo['zip'] . '<br />' . '\n\r';

            if (!empty($cartInfo['country'])) {
                $country = new Country($cartInfo['country']);
                $addressHTML .= $this->l('Country') . ': ' . $country->name[$cartObj->id_lang] . '<br />' . '\n\r';
            } elseif (!empty($cartInfo['country_name']))
                $addressHTML .= $this->l('Country') . ': ' . $cartInfo['country_name'] . '<br />' . '\n\r';

            if (!empty($cartInfo['state'])) {
                $state = new State($cartInfo['state']);
                $addressHTML .= $this->l('State') . ': ' . $state->name . '<br />' . '\n\r';
            } elseif (!empty($cartInfo['state_name']))
                $addressHTML .= $this->l('State') . ': ' . $cartInfo['state_name'] . '<br />' . '\n\r';
        } else {
            $addressHTML .= $address->firstname . ' ' . $address->lastname . '<br />' . '\n\r';
            $addressHTML .=!empty($address->company) ? $address->company . '<br />' . '\n\r' : '';
            $addressHTML .= $address->address1 . ' ' . $address->address2 . '<br />' . '\n\r';
            $addressHTML .= $address->postcode . ' ' . $address->city . '<br />' . '\n\r';

            if (!empty($address->country)) {
                $addressHTML .= $this->l('Country') . ': ' . $address->country . '<br />' . '\n\r';
            }
            if (!empty($address->id_state)) {
                $state = new State($address->id_state);
                $addressHTML .= $this->l('State') . ': ' . $state->name . '<br />' . '\n\r';
            }
        }

        $cartHTML = '<table cellpadding="2">' . '\n\r';
        foreach ($cartObj->getProducts() as $product) {
            $cartHTML .= '<tr>';
            $cartHTML .= '<td> ' . $product['quantity'] . '</td>';
            $cartHTML .= '<td>x</td>';
            $cartHTML .= '<td> ' . Tools::displayPrice($product['price']) . '</td>';
            $cartHTML .= '<td> ' . Tools::displayPrice($product['total']) . '</td>';

            $cartHTML .= '<td> ' . $product['name'] . '</td>';
            $cartHTML .= '</tr>' . '\n\r';
        }

        $cartHTML .= '<tr>';
        $cartHTML .= '<td colspan="2"></td>';

        $cartHTML .= '<td align="right"> ' . $this->l('Total') . '</td>';
        $cartHTML .= '<td> ' . Tools::displayPrice($cartObj->getOrderTotal()) . '</td>';
        $cartHTML .= '</tr>' . '\n\r';

        $cartHTML .= '</table>';
        Mail::Send(Language::getIdByIso('en'), $template, $this->l('Transaction failed'), array(
            '{customer_email}' => $customerObj->email,
            '{customer_ip}' => $_SERVER['REMOTE_ADDR'],
            '{error}' => $errorText,
            '{cartHTML}' => $cartHTML,
            '{cartTXT}' => strip_tags($cartHTML),
            '{addressHTML}' => $addressHTML,
            '{addressTXT}' => strip_tags($addressHTML)
            ), $email, null, null, null, null, null, _PS_MODULE_DIR_ . Tools::strtolower($this->name) . '/views/templates/emails/'
        );
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

