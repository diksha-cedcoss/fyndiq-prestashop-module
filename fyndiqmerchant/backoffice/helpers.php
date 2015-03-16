<?php

class FyndiqAPIDataInvalid extends Exception{}

class FyndiqAPIConnectionFailed extends Exception{}

class FyndiqAPIPageNotFound extends Exception{}

class FyndiqAPIAuthorizationFailed extends Exception{}

class FyndiqAPITooManyRequests extends Exception{}

class FyndiqAPIServerError extends Exception{}

class FyndiqAPIBadRequest extends Exception{}

class FyndiqAPIUnsupportedStatus extends Exception{}

class FyndiqProductSKUNotFound extends Exception{}

function pd($v)
{
    echo '<pre>';
    var_dump($v);
    echo '</pre>';
}

function startsWith($haystack, $needle)
{
    return $needle === "" || strpos($haystack, $needle) === 0;
}

function endsWith($haystack, $needle)
{
    return $needle === "" || substr($haystack, -strlen($needle)) === $needle;
}

# FyndiqMerchant PrestaShop Version 1.4|1.5|1.6
define('FMPSV14', 'FMPSV14');
define('FMPSV15', 'FMPSV15');
define('FMPSV16', 'FMPSV16');
if (startswith(_PS_VERSION_, '1.4.')) {
    define('FMPSV', FMPSV14);
}
if (startswith(_PS_VERSION_, '1.5.')) {
    define('FMPSV', FMPSV15);
}
if (startswith(_PS_VERSION_, '1.6.')) {
    define('FMPSV', FMPSV16);
}

class FmHelpers
{

    const HTTP_SUCCESS_DEFAULT = 200;
    const HTTP_SUCCESS_CREATED = 201;
    const HTTP_SUCCESS_NONCONTENT = 204;
    const HTTP_ERROR_DEFAULT = 404;
    const HTTP_ERROR_UNAUTHORIZED = 401;
    const HTTP_ERROR_TOOMANY = 429;
    const HTTP_ERROR_SERVER = 500;
    const HTTP_ERROR_CUSTOM = 400;

    const EXPORT_FILE_NAME_PATTERN = 'feed-%d.csv';


    public static function api_connection_exists($module = null)
    {
        $ret = true;
        $ret = $ret && FmConfig::get('username') !== false;
        $ret = $ret && FmConfig::get('api_token') !== false;

        return $ret;
    }

    public static function all_settings_exist($module = null)
    {
        $ret = true;
        $ret = $ret && FmConfig::get('language') !== false;

        return $ret;
    }

    ## wrappers around FyndiqAPI
    # uses stored connection credentials for authentication
    public static function call_api($method, $path, $data = array())
    {
        $username = FmConfig::get('username');
        $api_token = FmConfig::get('api_token');

        return FmHelpers::call_api_raw($username, $api_token, $method, $path, $data);
    }

    # add descriptive error messages for common errors, and re throw same exception
    public static function call_api_raw($username, $api_token, $method, $path, $data = array())
    {
        $module = Module::getInstanceByName('fyndiqmerchant');

        $response = FyndiqAPI::call($module->user_agent, $username, $api_token, $method, $path, $data);


        if ($response['status'] == self::HTTP_ERROR_DEFAULT) {
            throw new FyndiqAPIPageNotFound('Not Found: ' . $path);
        }

        if ($response['status'] == self::HTTP_ERROR_UNAUTHORIZED) {
            throw new FyndiqAPIAuthorizationFailed('Unauthorized');
        }

        if ($response['status'] == self::HTTP_ERROR_TOOMANY) {
            throw new FyndiqAPITooManyRequests('Too Many Requests');
        }

        if ($response['status'] == self::HTTP_ERROR_SERVER) {
            throw new FyndiqAPIServerError('Server Error');
        }
        // if json_decode failed
        if (json_last_error() != JSON_ERROR_NONE) {
            throw new FyndiqAPIDataInvalid('Error in response data');
        }

        // 400 may contain error messages intended for the user
        if ($response['status'] == self::HTTP_ERROR_CUSTOM) {
            $message = '';

            // if there are any error messages, save them to class static member
            if (property_exists($response["data"], 'error_messages')) {
                $error_messages = $response["data"]->error_messages;

                // if it contains several messages as an array
                if (is_array($error_messages)) {

                    foreach ($response["data"]->error_messages as $error_message) {
                        self::$error_messages[] = $error_message;
                    }

                    // if it contains just one message as a string
                } else {
                    self::$error_messages[] = $error_messages;
                }
            }

            throw new FyndiqAPIBadRequest('Bad Request');
        }

        $success_http_statuses = array(self::HTTP_SUCCESS_DEFAULT, self::HTTP_SUCCESS_CREATED, self::HTTP_SUCCESS_NONCONTENT);

        if (!in_array($response['status'], $success_http_statuses)) {
            throw new FyndiqAPIUnsupportedStatus('Unsupported HTTP status: ' . $response['status']);
        }

        return $response;
    }

    public static function db_escape($value)
    {
        if (FMPSV == FMPSV15 OR FMPSV == FMPSV16) {
            return Db::getInstance()->_escape($value);
        }
        if (FMPSV == FMPSV14) {
            return pSQL($value);
        }
    }

    public static function get_module_url($withadminurl = true)
    {

        $url = _PS_BASE_URL_ . __PS_BASE_URI__;
        if ($withadminurl) {
            $url .= substr(strrchr(_PS_ADMIN_DIR_, '/'), 1);
            $url .= "/index.php?controller=AdminModules&configure=fyndiqmerchant&module_name=fyndiqmerchant";
            $url .= '&token=' . Tools::getAdminTokenLite('AdminModules');
        }

        return $url;
    }

    public static function get_shop_url()
    {
        if (Shop::getContext() === Shop::CONTEXT_SHOP) {
            $shop = new Shop(Shop::getCurrentShop());
            return $shop->getBaseURL();
        }
        // fallback to globals if context is not shop
        return self::get_module_url(false);
    }

    /**
     * Returns export file name depending on the shop context
     *
     * @return string export file name
     */
    public static function getExportFileName()
    {
        if (Shop::getContext() === Shop::CONTEXT_SHOP) {
            return sprintf(self::EXPORT_FILE_NAME_PATTERN, Shop::getCurrentShop());
        }
        // fallback to 0 for non-multistore setups
        return sprintf(self::EXPORT_FILE_NAME_PATTERN, 0);
    }
}
