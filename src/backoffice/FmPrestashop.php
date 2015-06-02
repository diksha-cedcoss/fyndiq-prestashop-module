<?php

class FmPrestashop
{

    // FyndiqMerchant PrestaShop Version 1.4|1.5|1.6
    const FMPSV14 = 'FMPSV14';
    const FMPSV15 = 'FMPSV15';
    const FMPSV16 = 'FMPSV16';

    const DEFAULT_LANGUAGE_ID = 1;

    public $version = '';
    public $moduleName = '';
    private $categoryCache = array();

    public function __construct($moduleName)
    {
        $this->moduleName = $moduleName;
        $version = $this->globalGetVersion();

        if (stripos($version, '1.4.') === 0) {
            $this->version = self::FMPSV14;
        }
        if (stripos($version, '1.5.') === 0) {
            $this->version = self::FMPSV15;
        }
        if (stripos($version, '1.6.') === 0) {
            $this->version = self::FMPSV16;
        }
    }

    // Custom
    public function time()
    {
        return time();
    }

    public function getModuleUrl()
    {
        $url = $this->getBaseModuleUrl();
        $url .= substr(strrchr(_PS_ADMIN_DIR_, '/'), 1);
        $url .= "/index.php?controller=AdminModules&configure=fyndiqmerchant&module_name=fyndiqmerchant";
        $url .= '&token=' . Tools::getAdminTokenLite('AdminModules');
        return $url;
    }

    /**
     * Returns the export filename path
     *
     * @return string
     */
    public function getExportPath()
    {
        return _PS_CACHE_DIR_ . $this->moduleName . '/';
    }

    /**
     * Get Category Name
     *
     * @param $categoryId
     * @return string
     */
    public function getCategoryName($categoryId)
    {
        if (!isset($this->categoryCache[$categoryId])) {
            $category = new Category($categoryId, $this->getLanguageId());
            $this->categoryCache[$categoryId] = $category->name;
        }
        return $this->categoryCache[$categoryId];
    }

    public function getLanguageId()
    {
        return $this->contextGetContext()->language->id;
    }

    public function getCurrency($currencyId)
    {
        return new Currency($currencyId);
    }

    public function sleep($seconds)
    {
        return sleep($seconds);
    }

    public function dbEscape($value)
    {
        if ($this->version == self::FMPSV14) {
            return pSQL($value);
        }
        return $this->dbGetInstance()->_escape($value);
    }

    public function getShopUrl()
    {
        if (Shop::getContext() === Shop::CONTEXT_SHOP) {
            $shop = new Shop($this->getCurrentShopId());
            return $shop->getBaseURL();
        }
        // fallback to globals if context is not shop
        return $this->getModuleUrl(false);
    }

    public function getDefaultCurrency()
    {
        return Currency::getDefaultCurrency()->iso_code;
    }

    public function getImageType()
    {
        // get the medium image type
        $imageTypeName = array(
            self::FMPSV16 => 'large_default',
            self::FMPSV15 => 'large_default',
            self::FMPSV14 => 'large'
        );
        $versionName = $imageTypeName[$this->version];
        $imageTypes = ImageType::getImagesTypes();
        foreach ($imageTypes as $type) {
            if ($type['name'] == $versionName) {
                return $type;
            }
        }
        return '';
    }

    public function getImageLink($linkRewrite, $idImage, $imageType)
    {
        if ($this->version == self::FMPSV14) {
            $link = new Link();
            return $link->getImageLink($linkRewrite, $idImage, $imageType);
        }
        if ($this->version == self::FMPSV15 || $this->version == self::FMPSV16) {
            $context = $this->contextGetContext();
            return $context->link->getImageLink($linkRewrite, $idImage, $imageType);
        }
        return '';
    }

    public function getProductAttributes($product, $languageId)
    {
        $getAttrCombinations = array(
            self::FMPSV14 => 'getAttributeCombinaisons',
            self::FMPSV15 => 'getAttributeCombinations',
            self::FMPSV16 => 'getAttributeCombinations'
        );

        # get this products attributes and combination images
        return $product->$getAttrCombinations[$this->version]($languageId);
    }

    public function getPrice($price)
    {
        // $tax_rules_group = new TaxRulesGroup($product->id_tax_rules_group);
        $currency = new Currency(Configuration::get($this->getModuleName() . '_currency'));
        $convertedPrice = $price * $currency->conversion_rate;

        return Tools::ps_round($convertedPrice, 2);
    }

    public function getModuleName($moduleName = '')
    {
        $module = $this->moduleGetInstanceByName($moduleName);
        return $module->config_name;
    }

    public function getModulePath($moduleName = '')
    {
        $module = $this->moduleGetInstanceByName($moduleName);
        return $module->get('_path');
    }


    public function getTableName($moduleName, $tableSuffix, $prefix = false)
    {
        return ($prefix ? $this->globDbPrefix() : '') . $this->getModuleName() . $tableSuffix;
    }

    public function getCountryCode()
    {
        return $this->contextGetContext()->country->iso_code;
    }

    public function forceCreateDir($path, $rights)
    {
        if (!is_writable($path)) {
            $ret &= createDir($path, $rights);
        }
        return true;
    }

    public function markOrderAsDone($orderId, $orderDoneState)
    {
        $objOrder = new Order($orderId);
        $history = new OrderHistory();
        $history->id_order = (int)$objOrder->id;
        return $history->changeIdOrderState($orderDoneState, $objOrder);
    }

    public function getOrderStateName($doneState)
    {
        $currentState = new OrderState($doneState);
        return $currentState->name[1];
    }

    public function getOrderContext()
    {
        // mock the context for PS 1.4
        $context = new stdClass();

        // if the Presta Shop 1.5 and 1.6 is used, use the context class.
        if ($this->version == self::FMPSV15 || $this->version == self::FMPSV16) {
            $context = Context::getContext();
        } else {
            $context->shop = new stdClass();
            $context->country = new stdClass();
            $context->currency = Currency::getDefaultCurrency();
            $context->country = (int)Country::getDefaultCountryId();

        }
        $context->currency = Currency::getDefaultCurrency();
        $context->id_lang = self::DEFAULT_LANGUAGE_ID;
        return $context;
    }

    public function newPrestashopOrder()
    {
        // Create an order
        $prestaOrder = new Order();

        if ($this->version == self::FMPSV15 || $this->version == self::FMPSV16) {
            // create a internal reference for the order.
            $reference = Order::generateReference();
            $prestaOrder->reference = $reference;
        }
        return $prestaOrder;
    }


    public function isPs1516()
    {
        return in_array($this->version, array(self::FMPSV15, self::FMPSV16));
    }

    /**
     * Returns the current shop id
     *
     * @return int
     */
    public function getCurrentShopId()
    {
        $context = Context::getContext();
        if (Shop::isFeatureActive() && $context->cookie->shopContext) {
            $split = explode('-', $context->cookie->shopContext);
            if (count($split) === 2) {
                return intval($split[1]);
            }
        }
        return intval($context->shop->id);
    }

    public function getTaxAddressType()
    {
        return Configuration::get('PS_TAX_ADDRESS_TYPE');
    }

    public function toolsPsRound($number, $round)
    {
        return (float)Tools::ps_round($number, $round);
    }

    // Global variables
    public function globalPsRootDir()
    {
        return _PS_ROOT_DIR_;
    }

    public function getBaseModuleUrl()
    {
        return _PS_BASE_URL_ . __PS_BASE_URI__;
    }

    private function globalGetVersion()
    {
        return _PS_VERSION_;
    }

    public function globDbPrefix()
    {
        return _DB_PREFIX_;
    }

    // Module
    public function moduleGetInstanceByName($name = '')
    {
        $name = $name ? $name : $this->moduleName;
        return  Module::getInstanceByName($name);
    }

    // Tool
    public function toolsIsSubmit($name)
    {
        return Tools::isSubmit($name);
    }

    public function toolsGetValue($name)
    {
        return Tools::getValue($name);
    }

    public function toolsRedirect($url)
    {
        return Tools::redirect($url);
    }

    public function toolsEncrypt($string)
    {
        return Tools::encrypt($string);
    }


    // Configuration
    public function configurationDeleteByName($name)
    {
        return Configuration::deleteByName($name);
    }

    public function configurationGet($name)
    {
        return Configuration::get($name);
    }

    public function configurationUpdateValue($name, $value)
    {
        return Configuration::updateValue($name, $value);
    }

    // Category
    public function categoryGetChildren($categoryId, $languageId)
    {
        return Category::getChildren($categoryId, $languageId);
    }

    // Currency
    public function currencyGetDefaultCurrency()
    {
        return Currency::getDefaultCurrency();
    }

    // OrderState
    public function orderStateGetOrderStates($languageId)
    {
        return OrderState::getOrderStates($languageId);
    }

    public function orderStateInvoiceAvailable($orderStateId)
    {
        return OrderState::invoiceAvailable($orderStateId);
    }

    // Language
    public function languageGetLanguages()
    {
        return Language::getLanguages();
    }

    // Context
    public function contextGetContext()
    {
        return Context::getContext();
    }

    // DB
    public function dbGetInstance()
    {
        return Db::getInstance();
    }

    // Manufacturer
    public function manufacturerGetNameById($manufacturerId)
    {
        return Manufacturer::getNameById($manufacturerId);
    }

    // Product

    public function productNew($id_product = null, $full = false, $id_lang = null, $id_shop = null, $context = null)
    {
        return new Product($id_product, $full, $id_lang, $id_shop, $context);
    }

    public function productGetQuantity($productId)
    {
        return Product::getQuantity($productId);
    }

    // Address
    public function newAddress($id_address = null, $id_lang = null)
    {
        return new Address($id_address, $id_lang);
    }

    // Cart
    public function newCart()
    {
        return new Cart();
    }

    public function cartOnlyProducts()
    {
        return Cart::ONLY_PRODUCTS;
    }

    public function cartBoth()
    {
        return Cart::BOTH;
    }

    // Customer
    public function newCustomer()
    {
        return new Customer();
    }

    // DbQuery
    public function newDbQuery()
    {
        return new DbQuery();
    }

    // OrderHistory
    public function newOrderHistory()
    {
        return new OrderHistory();
    }

    // Message
    public function newMessage()
    {
        return new Message();
    }

    // OrderDetail
    public function newOrderDetail()
    {
        return new OrderDetail();
    }

    // Country
    public function newCountry($countryId, $languageId)
    {
        return new Country($countryId, $languageId);
    }
}
