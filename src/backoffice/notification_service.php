<?php
/*
This file handles incoming requests from the automated notification system at Fyndiq.
*/

require_once('./service_init.php');
require_once('./FmConfig.php');
require_once('./includes/shared/src/FyndiqOutput.php');
require_once('./FmOutput.php');
require_once('./models/FmApiModel.php');
require_once('./models/FmModel.php');
require_once('./models/FmOrder.php');
require_once('./models/FmProduct.php');
require_once('./models/FmProductExport.php');
require_once('./FmProductInfo.php');
require_once('./includes/fyndiqAPI/fyndiqAPI.php');

class FmNotificationService
{

    public function __construct($fmPrestashop, $fmConfig, $fmOutput, $fmApiModel)
    {
        $this->fmPrestashop = $fmPrestashop;
        $this->fmConfig = $fmConfig;
        $this->fmOutput = $fmOutput;
        $this->fmApiModel = $fmApiModel;
        $this->fmPrestashop->initHeadlessScript();
    }

    /**
     * Handle request
     *
     * @param array $params GET Params
     * @return mixed
     */
    public function handleRequest($params)
    {
        $eventName = isset($params['event']) ? $params['event'] : false;
        if ($eventName) {
            $storeId = $this->fmPrestashop->getStoreId();
            switch($eventName) {
                case 'order_created':
                    return $this->orderCreated($params, $storeId);
                case 'ping':
                    return $this->ping($params, $storeId);
                case 'debug':
                    return $this->debug($params, $storeId);
            }
        }
        return $this->fmOutput->showError(400, 'Bad Request', '400 Bad Request');
    }

    /**
     * Processes new order notifications
     *
     * @param array $params
     * @return bool
     */
    private function orderCreated($params, $storeId)
    {
        $importOrdersStatus = $this->fmConfig->get('disable_orders', $storeId);
        if ($importOrdersStatus == FmUtils::ORDERS_DISABLED) {
            return $this->getFyndiqOutput()->showError(403, 'Forbidden', 'Forbidden');
        }
        $orderId = isset($params['order_id']) && is_numeric($params['order_id']) ? $params['order_id'] : 0;
        if ($orderId) {
            $url = 'orders/' . $orderId . '/';
            try {
                $ret = $this->fmApiModel->callApi('GET', $url);
                $order = $ret['data'];
                $fmOrder = new FmOrder($this->fmPrestashop, $this->fmConfig);
                $idOrderState = $this->fmConfig->get('import_state', $storeId);
                $skuTypeId = $this->fmConfig->get('sku_type_id', $storeId);
                $taxAddressType = $this->fmPrestashop->getTaxAddressType();
                if (!$fmOrder->orderExists(intval($order->id))) {
                    $fmOrder->reserve(intval($order->id));
                    $fmOrder->create($order, $idOrderState, $taxAddressType, $skuTypeId);
                }
            } catch (Exception $e) {
                $fmOrder->unreserve($intval($order->id));
                return $this->fmOutput->showError(500, 'Internal Server Error', $e->getMessage());
            }
            return $this->fmOutput->output('OK');
        }
        return $this->fmOutput->showError(400, 'Bad Request', '400 Bad Request');
    }

    /**
     * Generate feed
     *
     * @param $params
     */
    private function ping($params, $storeId)
    {
        $token = isset($params['token']) ? $params['token'] : null;
        if (is_null($token) || $token != $this->fmConfig->get('ping_token', $storeId)) {
            return $this->fmOutput->showError(400, 'Bad Request', 'Invalid token');
        }

        $this->fmOutput->flushHeader('OK');

        $locked = false;
        $lastPing = $this->fmConfig->get('ping_time', $storeId);
        if ($lastPing && $lastPing > strtotime('9 minutes ago')) {
            $locked = true;
        }
        if (!$locked) {
            $this->fmConfig->set('ping_time', time(), $storeId);
            $filePath = $this->fmPrestashop->getExportPath() . $this->fmPrestashop->getExportFileName();
            try {
                $file = fopen($filePath, 'w+');
                $feedWriter = FmUtils::getFileWriter($file);
                $fmProductExport = new FmProductExport($this->fmPrestashop, $this->fmConfig);
                $languageId = $this->fmConfig->get('language', $storeId);
                $stockMin = $this->fmConfig->get('stock_min', $storeId);
                $descriptionType = intval($this->fmConfig->get('description_type', $storeId));
                $skuTypeId = intval($this->fmConfig->get('sku_type_id', $storeId));
                $fmProductExport->saveFile($languageId, $feedWriter, $stockMin, $descriptionType, $skuTypeId, $storeId);
                fclose($file);
                return $this->updateProductInfo();
            } catch (Exception $e) {
                return $this->fmOutput->showError(500, 'Internal Server Error', $e->getMessage());
            }
        }
    }

    private function updateProductInfo()
    {
        $module = $this->fmPrestashop->moduleGetInstanceByName(FmUtils::MODULE_NAME);
        $tableName = $module->config_name . '_products';
        $fmProduct = new FmProduct($this->fmPrestashop, $this->fmConfig);
        $productInfo = new FmProductInfo($fmProduct, $this->fmApiModel, $tableName);
        return $productInfo->getAll();
    }

    private function debug($params, $storeId)
    {
        $token = isset($params['token']) ? $params['token'] : null;
        if (is_null($token) || $token != $this->fmConfig->get('ping_token', $storeId)) {
            return $this->fmOutput->showError(400, 'Bad Request', 'Invalid token');
        }

        FyndiqUtils::debugStart();
        FyndiqUtils::debug('USER AGENT', $this->fmApiModel->getUserAgent());
        $locked = false;
        $lastPing = $this->fmConfig->get('ping_time', $storeId);
        if ($lastPing && $lastPing > strtotime('9 minutes ago')) {
            $locked = true;
        }
        FyndiqUtils::debug('$lastPing', $lastPing);
        FyndiqUtils::debug('$locked', $locked);
        $filePath = $this->fmPrestashop->getExportPath() . $this->fmPrestashop->getExportFileName();
        FyndiqUtils::debug('$filePath', $filePath);
        $file = fopen($filePath, 'w+');
        FyndiqUtils::debug('$file', $file);
        $feedWriter = FmUtils::getFileWriter($file);
        $fmProductExport = new FmProductExport($this->fmPrestashop, $this->fmConfig);
        $languageId = $this->fmConfig->get('language', $storeId);
        FyndiqUtils::debug('$languageId', $languageId);
        $stockMin = $this->fmConfig->get('stock_min', $storeId);
        $descriptionType = intval($this->fmConfig->get('description_type', $storeId));
        $skuTypeId = intval($this->fmConfig->get('sku_type_id', $storeId));
        $fmProductExport->saveFile($languageId, $feedWriter, $stockMin, $descriptionType, $skuTypeId, $storeId);
        fclose($file);
        $result = file_get_contents($filePath);
        FyndiqUtils::debug('$result', $result, true);
        FyndiqUtils::debugStop();
    }
}


$fmConfig = new FmConfig($fmPrestashop);
$fmOutput = new FmOutput($fmPrestashop, null, null);
$storeId = $fmPrestashop->getStoreId();
$fmApiModel = new FmApiModel(
    $fmConfig->get('username', $storeId),
    $fmConfig->get('api_token', $storeId),
    $fmPrestashop->globalGetVersion()
);

$notifications = new FmNotificationService($fmPrestashop, $fmConfig, $fmOutput, $fmApiModel);
$notifications->handleRequest($_GET);
