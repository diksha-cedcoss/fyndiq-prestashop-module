<?php

if (!defined('_PS_VERSION_')) {
    exit;
}

require_once('messages.php');
require_once('backoffice/models/config.php');
require_once('backoffice/includes/fyndiqAPI/fyndiqAPI.php');
require_once 'backoffice/includes/shared/src/init.php';
require_once('backoffice/helpers.php');
require_once('backoffice/controllers.php');
require_once('backoffice/models/product_export.php');
require_once('backoffice/models/order.php');

class FyndiqMerchant extends Module
{

    public function __construct()
    {

        $this->config_name = 'FYNDIQMERCHANT';
        $this->name = 'fyndiqmerchant';
        $this->tab = 'market_place';
        $this->version = '0.1';
        $this->author = 'Fyndiq AB';
        $this->need_instance = 0;
        $this->ps_versions_compliancy = array('min' => '1.5.0', 'max' => '1.6.7');

        parent::__construct();

        $this->displayName = $this->l('Fyndiq');
        $this->description = $this->l('dfasdf');
        $this->confirmUninstall = $this->l(FmMessages::get('uninstall-confirm'));

        if (FmHelpers::api_connection_exists($this)) {
            $this->warning = $this->l(FmMessages::get('not-authenticated-warning'));
        }

        // custom properties specific to this module
        // determines which prestashop language should be used when getting from database
        $this->language_id = 1;
        // used as user agent string when calling the API
        $this->user_agent = $this->name . '-' . $this->version;
    }

    public function install()
    {
        $ret = true;

        $ret &= (bool)parent::install();

        // Create tab
        $ret &= $this->installTab();

        // create product mapping database
        $ret &= FmProductExport::install();

        // create order mapping database
        $ret &= FmOrder::install();

        return (bool)$ret;
    }

    public function uninstall()
    {
        $ret = true;

        $ret &= (bool)parent::uninstall();

        // delete configuration
        $ret &= (bool)FmConfig::delete('username');
        $ret &= (bool)FmConfig::delete('api_token');
        $ret &= (bool)FmConfig::delete('language');
        $ret &= (bool)FmConfig::delete('price_percentage');
        $ret &= (bool)FmConfig::delete('import_date');
        $ret &= (bool)FmConfig::delete('order_import_state');
        $ret &= (bool)FmConfig::delete('order_done_state');

        // drop product table
        $ret &= FmProductExport::uninstall();

        $ret &= $this->uninstallTab();
        // drop order table
        // TODO: Should we remove the order? the order in prestashop will still be there and if reinstall it will be duplicates if this is removed.
        $ret &= FmOrder::uninstall();

        return (bool)$ret;
    }

    /**
     * Install tab to the menu
     *
     * @return mixed
     */
    private function installTab()
    {
        $tab = new Tab();
        $tab->active = 1;
        $tab->class_name = 'AdminPage';
        $tab->name = array();
        foreach (Language::getLanguages(true) as $lang) {
            $tab->name[$lang['id_lang']] = 'Fyndiq';
        }
        $tab->id_parent = (int)Tab::getIdFromClassName('AdminParentModules');
        $tab->module = $this->name;
        return $tab->add();
    }

    /**
     * Remove tab from menu
     *
     * @return mixed
     */
    private function uninstallTab()
    {
        $id_tab = (int)Tab::getIdFromClassName('AdminPage');
        if ($id_tab) {
            $tab = new Tab($id_tab);
            return $tab->delete();
        }
        return false;
    }

    public function getContent()
    {
        return FmBackofficeControllers::main($this);
    }

    public function get($name)
    {
        return $this->$name;
    }
}
