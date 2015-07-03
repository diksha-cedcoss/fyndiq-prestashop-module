<?php

class FmServiceController
{

    protected $fmPrestashop;
    protected $fmOutput;
    protected $fmConfig;

    public function __construct($fmPrestashop, $fmOutput, $fmConfig, $fmApiModel)
    {
        $this->fmPrestashop = $fmPrestashop;
        $this->fmOutput = $fmOutput;
        $this->fmConfig = $fmConfig;
        $this->fmApiModel = $fmApiModel;
    }

    public function handleRequest($params)
    {
        if (!isset($params['action'])) {
            return $this->fmOutput->responseError('Bad Request', '400 Bad Request');
        }
        $action = $params['action'];
        $args = isset($params['args']) && is_array($params['args']) ? $params['args'] : array();
        $response = $this->routeRequest($action, $args);
        if (!is_null($response)) {
            return $this->fmOutput->renderJSON($response);
        }
        return true;
    }

    public function routeRequest($action, $args)
    {
        try {
            switch($action) {
                case 'get_categories':
                    return $this->getCategories($args);
                case 'get_products':
                    return $this->getProducts($args);
                case 'export_products':
                    return $this->exportProducts($args);
                case 'delete_exported_products':
                    return $this->deleteExportedProducts($args);
                case 'update_order_status':
                    return $this->updateOrderStatus($args);
                case 'load_orders':
                    return $this->loadOrders($args);
                case 'get_delivery_notes':
                    return $this->getDeliveryNotes($args);
                case 'import_orders':
                    return $this->importOrders($args);
                case 'update_product_status':
                    return $this->updateProductStatus($args);
                default:
                    return $this->fmOutput->responseError(
                        'Not Found',
                        'Acion ' . $action . ' could not be found'
                    );
            }
        } catch (Exception $e) {
            $this->fmOutput->responseError(
                FyndiqTranslation::get('unhandled-error-title'),
                FyndiqTranslation::get('unhandled-error-message') . ' (' . $e->getMessage() . ')'
            );
            return null;
        }
    }

    protected function loadModel($modelName)
    {
        if (class_exists($modelName)) {
            return new $modelName($this->fmPrestashop, $this->fmConfig);
        }
        throw new Exception('Model ' . $modelName . ' is not defined');
    }

    /**
     * Get the categories.
     *
     * @param $args
     */
    private function getCategories($args)
    {
        $languageId = $this->fmConfig->get('language');
        $fmCategory = $this->loadModel('FmCategory');
        return $fmCategory->getSubcategories($languageId, intval($args['category_id']));
    }

    /**
     * Get the products.
     *
     * @param $args
     */
    private function getProducts($args)
    {
        $products = array();
        $fmProduct = $this->loadModel('FmProduct');
        $fmProductExport = $this->loadModel('FmProductExport');
        // get currency
        $currentCurrency = $this->fmPrestashop->getDefaultCurrency();

        $page = (isset($args['page']) and $args['page'] > 0) ? intval($args['page']) : 1;
        $rows = $fmProduct->getByCategory($args['category'], $page, FyndiqUtils::PAGINATION_ITEMS_PER_PAGE);

        $fyndiqDiscountPercentage = $this->fmConfig->get('price_percentage');
        $languageId = $this->fmConfig->get('language');

        foreach ($rows as $row) {
            $discountPercentage = $fyndiqDiscountPercentage;
            $product = $fmProductExport->getStoreProduct($languageId, $row['id_product']);
            // Don't show deactivated products
            if (empty($product)) {
                continue;
            }

            if (FyndiqFeedWriter::isColumnTooLong('product-title', $product['name'])) {
                $product['name_short'] = FyndiqFeedWriter::sanitizeColumn('product-title', $product['name']);
            }

            $product['currency'] = $currentCurrency;
            $product['fyndiq_quantity'] = $product['quantity'];
            $product['fyndiq_status'] = 'noton';

            $fynProduct = $fmProductExport->getProduct($row['id_product']);
            if ($fynProduct) {
                $discountPercentage = $fynProduct['exported_price_percentage'];
                $product['fyndiq_exported'] = true;
                switch ($fynProduct['state']) {
                    case FmProductExport::FOR_SALE: $product['fyndiq_status'] = 'on';
                        break;
                    default: $product['fyndiq_status'] = 'pending';
                }
            }

            $product['fyndiq_percentage'] = $discountPercentage;
            $product['expected_price'] = number_format(
                (float)FyndiqUtils::getFyndiqPrice($product['price'], $discountPercentage),
                2,
                '.',
                ''
            );
            $products[] = $product;
        }
        $page = isset($args['page']) ? intval($args['page']) : 1;
        $total = $fmProduct->getAmount($args['category']);

        return array(
            'products' => $products,
            'pagination' => FyndiqUtils::getPaginationHTML(
                $total,
                $page,
                FyndiqUtils::PAGINATION_ITEMS_PER_PAGE,
                FyndiqUtils::PAGINATION_PAGE_FRAME
            )
        );
    }

    protected function prepareOrders($orders)
    {
        $orderDoneState = $this->fmConfig->get('done_state');

        $result = array();
        foreach ($orders as $order) {
            $orderArray = $order;
            $newOrder = $this->fmPrestashop->newOrder((int)$order['order_id']);
            $products = $newOrder->getProducts();
            $currentStateName = $this->fmPrestashop->getOrderStateName($newOrder->getCurrentState());
            $quantity = 0;
            foreach ($products as $product) {
                $quantity += $product['product_quantity'];
            }
            $url = 'index.php?tab=AdminOrders&id_order=' . $order['order_id'] . '&vieworder';
            if ($this->fmPrestashop->isPs1516()) {
                $url = 'index.php?controller=AdminOrders&id_order=' . $order['order_id'] . '&vieworder';
            }
            $url .= '&token=' . $this->fmPrestashop->getAdminTokenLite();
            $orderArray['created_at'] = date('Y-m-d', strtotime($newOrder->date_add));
            $orderArray['created_at_time'] = date('G:i:s', strtotime($newOrder->date_add));
            $orderArray['price'] = $newOrder->total_paid_real;
            $orderArray['state'] = $currentStateName;
            $orderArray['total_products'] = $quantity;
            $orderArray['is_done'] = $newOrder->getCurrentState() == $orderDoneState;
            $orderArray['link'] = $url;
            $result[] = $orderArray;
        }
        return $result;
    }

    private function loadOrders($args)
    {
        $fmOrder = $this->loadModel('FmOrder');
        $total = $fmOrder->getTotal();
        $page = (isset($args['page']) && $args['page'] > 0) ? $args['page']: 1;
        return array(
            'orders' => $this->prepareOrders(
                $fmOrder->getImportedOrders($page, FyndiqUtils::PAGINATION_ITEMS_PER_PAGE)
            ),
            'pagination' => FyndiqUtils::getPaginationHTML(
                $total,
                $page,
                FyndiqUtils::PAGINATION_ITEMS_PER_PAGE,
                FyndiqUtils::PAGINATION_PAGE_FRAME
            )
        );
    }

    private function updateOrderStatus($args)
    {
        $doneStateName = '';
        if (isset($args['orders']) && is_array($args['orders'])) {
            $doneState = '';
            $doneState = $this->fmConfig->get('done_state');
            $fmOrder = $this->loadModel('FmOrder');
            foreach ($args['orders'] as $order) {
                if (is_numeric($order)) {
                    $fmOrder->markOrderAsDone($order, $doneState);
                }
            }
            $doneStateName = $this->fmPrestashop->getOrderStateName($doneState);
        }
        return $doneStateName;
    }

    protected function getTime()
    {
        return time();
    }

    /**
     * Getting the orders to be saved in PrestaShop.
     *
     * @param $args
     * @throws PrestaShopException
     */
    private function importOrders()
    {
        $fmOrder = $this->loadModel('FmOrder');
        $orderFetch = new FmOrderFetch(
            $this->fmPrestashop,
            $this->fmConfig,
            $fmOrder,
            $this->fmApiModel
        );
        $orderFetch->getAll();
        $time = $this->getTime();
        $newDate = date('Y-m-d H:i:s', $time);
        $this->fmConfig->set('import_date', $newDate);
        return date('G:i:s', $time);
    }

    /**
     * Exporting the products from PrestaShop
     *
     * @param mixed $args
     */
    private function exportProducts($args)
    {
        $result = true;
        if (isset($args['products']) && is_array($args['products'])) {
            $fmProductExport = $this->loadModel('FmProductExport');
            foreach ($args['products'] as $row) {
                $product= $row['product'];
                if ($fmProductExport->productExist($product['id'])) {
                    $result &= $fmProductExport->updateProduct($product['id'], $product['fyndiq_percentage']);
                    continue;
                }
                $result &= $fmProductExport->addProduct($product['id'], $product['fyndiq_percentage']);
            }
        }
        return (bool)$result;
    }

    private function deleteExportedProducts($args)
    {
        $result = true;
        if (isset($args['products']) && is_array($args['products'])) {
            $fmProductExport = $this->loadModel('FmProductExport');
            foreach ($args['products'] as $row) {
                $product = $row['product'];
                $fmProductExport->deleteProduct($product['id']);
            }
        }
        return $result;
    }

    private function getDeliveryNotes($args)
    {
        if (isset($args['orders']) && is_array($args['orders'])) {
            $orderIds = $args['orders'];
            $request = array(
                'orders' => array()
            );
            foreach ($orderIds as $orderId) {
                $request['orders'][] = array('order' => intval($orderId));
            }
            try {
                $ret = $this->fmApiModel->callApi('POST', 'delivery_notes/', $request);
                $fileName = 'delivery_notes-' . implode('-', $orderIds) . '.pdf';

                if ($ret['status'] == 200) {
                    $file = fopen('php://temp', 'wb+');
                    // Saving data to file
                    fputs($file, $ret['data']);
                    $this->fmOutput->streamFile($file, $fileName, 'application/pdf', strlen($ret['data']));
                    fclose($file);
                    return null;
                }
                return FyndiqTranslation::get('unhandled-error-message');
            } catch (Exception $e) {
                $this->fmOutput->output($e->getMessage());
                return null;
            }
        }
        $this->fmOutput->output('Please, pick at least one order');
        return null;
    }

    private function updateProductStatus()
    {
        $tableName = $this->fmPrestashop->getTableName(FmUtils::MODULE_NAME, '_products');
        $fmProduct = $this->loadModel('FmProduct');
        $productInfo = new FmProductInfo($fmProduct, $this->fmApiModel, $tableName);
        return $productInfo->getAll();
    }
}