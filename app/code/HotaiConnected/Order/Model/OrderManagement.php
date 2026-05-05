<?php

/**
 * Copyright © Hotai Connected Co.,Ltd All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace HotaiConnected\Order\Model;

use DateTime ;
use Magento\Framework\Webapi\Rest\Request;
use Magento\Framework\Webapi\Rest\Response;
use Psr\Log\LoggerInterface as Logger;
use Magento\Framework\App\ResourceConnection;

class OrderManagement implements \HotaiConnected\Order\Api\OrderManagementInterface
{
    private $connection = null;
    private $resourceConnection;
    private $logger;
    private $request;
    private $response;
    private $lastestMainId;
    private $lastestDetailId;
    private $lastestInvoiceLogAry = array();
    private const SQL_PAYMENT_ORDER = 'INSERT INTO `sales_order_payment`
        (
            `parent_id`,
            `method`
        )
        VALUES
        (
            :parent_id,
            :method
        );';
    private const SQL_ADDRESS_SHIPPING = 'INSERT INTO `sales_order_address`(
            `parent_id`,
            `customer_id`,
            `region`,
            `lastname`,
            `postcode`,
            `street`,
            `city`,
            `email`,
            `telephone`,
            `country_id`,
            `firstname`,
            `address_type`
        )
        VALUES
        (
            :parent_id,
            :customer_id,
            :region,
            :lastname,
            :postcode,
            :street,
            :city,
            :email,
            :telephone,
            :country_id,
            :firstname,
            :address_type
        );';

    private const SQL_ADDRESS_STORE = 'INSERT INTO `sales_order_address`(
        `parent_id`,
        `customer_id`,
        `region`,
        `lastname`,
        `postcode`,
        `street`,
        `city`,
        `email`,
        `telephone`,
        `country_id`,
        `firstname`,
        `cvs_store_code`,
        `cvs_store_name`,
        `cvs_store_servicetype`,
        `cvs_store_outside`,
        `store_address_info`,
        `address_type`
    )
    VALUES
    (
        :parent_id,
        :customer_id,
        :region,
        :lastname,
        :postcode,
        :street,
        :city,
        :email,
        :telephone,
        :country_id,
        :firstname,
        :cvs_store_code,
        :cvs_store_name,
        :cvs_store_servicetype,
        :cvs_store_outside,
        :store_address_info,
        :address_type
    );';

    private const DELETE_SALES_ORDER = 'DELETE from sales_order WHERE entity_id = :entity_id';
    private const DELETE_SALES_ORDER_ITEM = 'DELETE from sales_order_item WHERE order_id = :order_id';
    private const DELETE_SALES_ORDER_PAYMENT = 'DELETE from sales_order_payment WHERE parent_id = :parent_id';
    private const DELETE_SALES_ORDER_ADDRESS = 'DELETE from sales_order_address WHERE parent_id = :parent_id';
    private const DELETE_MARKETPLACE_ORDERS = 'DELETE from marketplace_orders WHERE order_id = :order_id';

    public function __construct(
        Logger $logger,
        ResourceConnection $resourceConnection,
        Request $request,
        Response $response,
    ) {
        $this->resourceConnection = $resourceConnection;
        $this->logger = $logger;
        $this->request = $request;
        $this->response = $response;
    }

    /**
     * Insert table:
     * sales_order
     * sales_order_payment
     * marketplace_orders
     * sales_order_address
     * sales_order_item
     *
     * @param array $body
     *
     * @return Magento\Framework\Webapi\Rest\Response
     */
    public function postOrder()
    {
        try {
            if ($this->connection == null) {
                $this->connection = $this->resourceConnection->getConnection();
                $this->connection->beginTransaction();
            }

            $body = $this->request->getBodyParams();

            $body = $this->checkParams($body);
            if (!$body) {
                $this->connection->rollBack();
                $this->logger->error('request format error');
                $this->response->setHeader('Content-Type', 'application/json', true)
                    ->setBody(json_encode(['status' => 0, 'code' => 'request format error']))
                    ->sendResponse();
                    exit();
            }

            $body = $this->findCustomerIdByOneId($body);

            $this->setOrderMain($body)
                 ->setOrderPayment()
                 ->setMarketPlaceOrder($body)
                 ->setOrderAddress($body)
                ->setOrderDetail($body);

            $this->connection->commit();
            $this->response->setHeader('Content-Type', 'application/json', true)
                ->setBody(json_encode(['status' => 1, 'code' => 'success', 'order_id' => $this->lastestMainId]))
                ->sendResponse();
        } catch (\Exception $e) {
            $this->connection->rollBack();
            $this->logger->critical('order test insert fail: ' . $e->getMessage());
            $this->response->setHeader('Content-Type', 'application/json', true)
                ->setBody(json_encode(['status' => 0, 'code' => 'order test insert fail' . $e->getMessage()]))
                ->sendResponse();
        }
    }

    /**
     * Get -8hrs timestamp for 2.0
     */
    protected function getUTC0byTimeStamp($timeStamp)
    {
        if (!empty($timeStamp)) {

            $date = new DateTime($timeStamp);
            $date->modify('-8 hours');
            $timeStamp = $date->format('Y-m-d H:i:s');
        }

        return $timeStamp;
    }

    /**
     * Find Customer Id by One Id
     */
    public function findCustomerIdByOneId($body)
    {
        $sqlSelect = 'select
            entity_id
            from
            customer_entity
            where
            member_seq = :member_seq';
        $bindsAry = array('member_seq' => $body['main']['customer_id']);
        $result = $this->connection->fetchAll($sqlSelect, $bindsAry);
        if (empty($result)) {
            $this->response->setHeader('Content-Type', 'application/json', true)
                ->setBody(json_encode(['code' => 'customer_id not found, oneId:' . $body['main']['customer_id']]))
                ->sendResponse();
            exit();
        }

        $body['main']['customer_id'] = $result[0]['entity_id'];
        $body['address_info']['customer_id'] = $result[0]['entity_id'];
        return $body;
    }
    /**
     * Delete order by checking increment_id
     */
    public function deleteOrder($orderNo)
    {
        $this->connection = $this->resourceConnection->getConnection();
        $sqlSelect = 'select
            entity_id
            from
            sales_order
            where
            increment_id = :increment_id';
        $result = $this->connection->fetchAll($sqlSelect, array('increment_id' => 'hotai_order_' . $orderNo));

        if (count($result) <= 0) {
            $this->response->setHeader('Content-Type', 'application/json', true)
                ->setBody(json_encode(['code' => 'order not found, orderNo:' . $orderNo]))
                ->sendResponse();
            exit();
        }

        $this->deleteRows($result[0]['entity_id']);
        $this->connection->commit();
        $this->response->setHeader('Content-Type', 'application/json', true)
            ->setBody(json_encode(['status' => 1, 'code' => 'success', 'order_id' => $result[0]['entity_id']]))
            ->sendResponse();
    }

    /**
     * Update table sales_order by increment_id
     *
     * @param array $body
     *
     * @return Magento\Framework\Webapi\Rest\Response
     */
    public function updateOrder($orderNo)
    {
        try {
            $body = $this->request->getBodyParams();

            if ($this->connection == null) {
                $this->connection = $this->resourceConnection->getConnection();
                $this->connection->beginTransaction();
            }

            $body = $this->checkParams($body);
            if (!$body) {
                $this->connection->rollBack();
                $this->logger->error('request format error');
                $this->response->setHeader('Content-Type', 'application/json', true)
                    ->setBody(json_encode(['status' => 0, 'code' => 'request format error']))
                    ->sendResponse();
                exit();
            }

            $sqlSelect = 'select
                entity_id
                from
                sales_order
                where
                increment_id = :increment_id';
            $result = $this->connection->fetchAll($sqlSelect, array('increment_id' => 'hotai_order_' . $orderNo));
            if (count($result) <= 0) {
                $this->response->setHeader('Content-Type', 'application/json', true)
                    ->setBody(json_encode(['code' => 'order not found, orderNo:' . $orderNo]))
                    ->sendResponse();
                exit();
            }

            $body = $this->findCustomerIdByOneId($body);
            $this->lastestMainId = $result[0]['entity_id'];
            //$this->logger->info('order update order_id:' . $result[0]['entity_id']);

            $this->updateSalesOrder($body, $orderNo);
            $this->updateSalesOrderItem($body);

            $this->connection->commit();
            $this->response->setHeader('Content-Type', 'application/json', true)
                ->setBody(json_encode(['status' => 1, 'code' => 'success', 'order_id' => $this->lastestMainId]))
                ->sendResponse();
        } catch (\Exception $e) {
            $this->connection->rollBack();
            $this->logger->critical('order update fail: ' . $e->getMessage());
            $this->response->setHeader('Content-Type', 'application/json', true)
                ->setBody(json_encode(['status' => 0, 'code' => 'order test insert fail' . $e->getMessage()]))
                ->sendResponse();
        }
    }

    protected function updateSalesOrderItem($body)
    {
        try {
            $sqlDetailValAry = array_map(
                function ($column) {
                    return $column . ' = ' . ':' . $column;
                }, $this->reqColSalesOrderItem()
            );

            $sqlDetail = 'UPDATE `sales_order_item` SET
            ' . implode(',', $sqlDetailValAry) .
            ' WHERE order_id = :order_id
              AND sku = :sku ;';

            foreach ($body['detail'] as $k => $detail) {
                $sqlSelect = 'select
                    item_id
                    from
                    sales_order_item
                    WHERE order_id = :order_id
                    AND sku = :sku';
                $bindsAry = array(
                    'order_id' => $this->lastestMainId,
                    'sku' => $detail['sku']
                );
                $result = $this->connection->fetchAll($sqlSelect, $bindsAry);
                $this->lastestDetailId = $result[0]['item_id'];

                $sqlSelect = 'select type_id, entity_id from catalog_product_entity where sku = :sku';
                $result = $this->connection->fetchAll($sqlSelect, array('sku' => $detail['sku']));
                if (count($result) <= 0) {
                    $this->logger->error('order test sku not matching product');
                    $this->response->setHeader('Content-Type', 'application/json', true)
                        ->setBody(json_encode(['status' => 0, 'code' => 'order sku not matching product, sku:' . $detail['sku']]))
                        ->sendResponse();
                    exit();
                }
                $productId = $result[0]['entity_id'];
                $productType = $result[0]['type_id'];
                $binds = array();

                foreach ($detail as $key => $value) {
                    $binds[$key] = $value;
                }
                $binds['order_id'] = $this->lastestMainId;
                $binds['product_id'] = $productId;
                $binds['product_type'] = $productType;

                $this->connection->query($sqlDetail, $binds);
            }
        } catch (\Exception $e) {
            $this->connection->rollBack();
            $this->logger->critical('order update fail: ' . $e->getMessage());
            $this->response->setHeader('Content-Type', 'application/json', true)
                ->setBody(json_encode(['status' => 0, 'code' => 'order update fail']))
                ->sendResponse();
        }
    }

    protected function updateSalesOrder($body, $orderNo)
    {
        $sqlMainValAry = array_map(
            function ($column) {
                return $column . ' = ' . ':' . $column;
            }, $this->reqColSalesOrder()
        );
        $sqlMain = 'UPDATE `sales_order` SET
            ' . implode(',', $sqlMainValAry) .
            ' WHERE increment_id = :increment_id ;';

        foreach ($body['main'] as $k => $v) {
            $binds[$k] = $v;
        }
        $binds['increment_id'] = 'hotai_order_' . $orderNo;

        if ($body['main']['grand_total'] == 0 || $body['main']['base_grand_total'] == 0) {
            $binds['ecpay_invoice_status'] = 4;
            //$this->logger->info('grant_total=0, change ecpay_invoice_status to 4');
        }

        $this->connection->query($sqlMain, $binds);
        //$this->logger->info('sales_order update:' . $this->lastestMainId . PHP_EOL);
    }

    /**
     * get order by hotai_child_order_number
     *
     * @return Magento\Framework\Webapi\Rest\Response
     */
    public function getOrder($orderNo)
    {
        $this->connection = $this->resourceConnection->getConnection();
        $sqlSelect = 'select
            increment_id
            from
            sales_order
            where
            increment_id = :increment_id';
        $result = $this->connection->fetchAll($sqlSelect, array('increment_id' => 'hotai_order_' . $orderNo));
        /*code=0 -> order not found, code=1 -> order exists*/
        $code = (count($result) > 0) ? 1 : 0;
        $this->response->setHeader('Content-Type', 'application/json', true)
            ->setBody(json_encode(['code' => $code]))
            ->sendResponse();
    }

    /**
     * delete rows by order_id
     *
     * @return void
     */
    protected function deleteRows($orderId)
    {
        $this->connection = $this->resourceConnection->getConnection();
        $this->connection->beginTransaction();
        $this->connection->query(self::DELETE_SALES_ORDER, array('entity_id' => $orderId));
        $this->connection->query(self::DELETE_SALES_ORDER_ITEM, array('order_id' => $orderId));
        $this->connection->query(self::DELETE_SALES_ORDER_PAYMENT, array('parent_id' => $orderId));
        $this->connection->query(self::DELETE_SALES_ORDER_ADDRESS, array('parent_id' => $orderId));
        $this->connection->query(self::DELETE_MARKETPLACE_ORDERS, array('order_id' => $orderId));
    }

    /**
     * insert sales_order_item
     *
     * @param array $body
     *
     * @return $this
     */
    protected function setOrderDetail($body)
    {
        try {
            $sqlDetailValAry = array_map(
                function ($column) {
                    return ':' . $column;
                }, $this->reqColSalesOrderItem()
            );

            $sqlDetail = 'INSERT INTO `sales_order_item`
            (
                ' . implode(',', $this->reqColSalesOrderItem()) . '
            )
            VALUES
            (
                ' . implode(',', $sqlDetailValAry) . '
            );';

            foreach ($body['detail'] as $k => $detail) {
                $sqlSelect = 'select type_id, entity_id from catalog_product_entity where sku = :sku';
                $result = $this->connection->fetchAll($sqlSelect, array('sku' => $detail['sku']));
                if (count($result) <= 0) {
                    $this->logger->error('order test sku not matching product');
                    $this->response->setHeader('Content-Type', 'application/json', true)
                        ->setBody(json_encode(['status' => 0, 'code' => 'order sku not matching product, sku:' . $detail['sku']]))
                        ->sendResponse();
                    exit();
                }

                $productId = $result[0]['entity_id'];
                $productType = $result[0]['type_id'];
                $binds = array();

                foreach ($detail as $key => $value) {
                    $binds[$key] = $value;
                }
                $binds['order_id'] = $this->lastestMainId;
                $binds['product_id'] = $productId;
                $binds['product_type'] = $productType;

                $this->connection->query($sqlDetail, $binds);
                $lastestDetailId = $this->connection->lastInsertId();
                //$this->logger->info('order detail:' . $lastestDetailId . PHP_EOL);
            }

            return $this;
        } catch (\Exception $e) {
            $this->connection->rollBack();
            $this->logger->critical('order test insert fail: ' . $e->getMessage());
            $this->response->setHeader('Content-Type', 'application/json', true)
                ->setBody(json_encode(['status' => 0, 'code' => 'order test insert fail' . $e->getMessage()]))
                ->sendResponse();
        }
    }

    /**
     * Insert sales_order_address
     *
     * @param array $body
     *
     * @return $this
     */
    protected function setOrderAddress($body)
    {
        $binds = array();
        $binds = $body['address_info'];
        $binds['parent_id'] = $this->lastestMainId;

        if ($body['main']['shipping_method'] == 'hotai_711_hotai_711') {
            $binds['address_type'] = 'billing';
            $binds['store_address_info'] = json_encode($binds['store_address_info']);
            $this->connection->query(self::SQL_ADDRESS_STORE, $binds);

            $binds['address_type'] = 'shipping';
            $this->connection->query(self::SQL_ADDRESS_STORE, $binds);
        } elseif ($body['main']['shipping_method'] == 'hotai_delivery_hotai_delivery') {
            $binds['address_type'] = 'billing';
            $this->connection->query(self::SQL_ADDRESS_SHIPPING, $binds);

            $binds['address_type'] = 'shipping';
            $this->connection->query(self::SQL_ADDRESS_SHIPPING, $binds);
        } else {
            $binds['address_type'] = 'billing';
            $this->connection->query(self::SQL_ADDRESS_SHIPPING, $binds);
        }

        return $this;
    }

    /**
     * insert marketplace_orders
     *
     * @return $this
     */
    protected function setMarketPlaceOrder($body)
    {
        $sqlMarketPlaceOrder = 'INSERT INTO `marketplace_orders` (`order_id`, `seller_id`) VALUES (:order_id, :seller_id);';
        $binds = array();
        $binds['order_id'] = $this->lastestMainId;
        $binds['seller_id'] = $body['detail'][0]['seller_id'];
        $this->connection->query($sqlMarketPlaceOrder, $binds);
        $lastestMPOId = $this->connection->lastInsertId();
        //$this->logger->info('order test marketplace_order:' . $lastestMPOId . PHP_EOL);

        return $this;
    }

    /**
     * insert sales_order_payment
     *
     * @return $this
     */
    protected function setOrderPayment()
    {
        $binds = array();
        $binds['parent_id'] = $this->lastestMainId;
        $binds['method'] = 'hotaipay';
        $this->connection->query(self::SQL_PAYMENT_ORDER, $binds);
        $lastestPaymentId = $this->connection->lastInsertId();
        //$this->logger->info('order test payment:' . $lastestPaymentId . PHP_EOL);
        return $this;
    }

    /**
     * insert sales_order
     *
     * @param array $body
     *
     * @return $this
     */
    protected function setOrderMain($body)
    {
        $sqlMainValAry = array_map(
            function ($column) {
                return ':' . $column;
            }, $this->reqColSalesOrder()
        );
        $sqlMain = 'INSERT INTO `sales_order`
            (
                ' . implode(',', $this->reqColSalesOrder()) . '
            )
            VALUES (
                ' . implode(',', $sqlMainValAry) . '
            );';

        foreach ($body['main'] as $k => $v) {
            $binds[$k] = $v;
        }

        if ($body['main']['grand_total'] == 0 || $body['main']['base_grand_total'] == 0) {
            $binds['ecpay_invoice_status'] = 4;
            //$this->logger->info('grant_total=0, change ecpay_invoice_status to 4');
        }

        $this->connection->query($sqlMain, $binds);
        $this->lastestMainId = $this->connection->lastInsertId();
        //$this->logger->info('order import main:' . $this->lastestMainId . PHP_EOL);
        return $this;
    }

    /**
     * Check post-raw body format
     *
     * @param array $body
     *
     * @return array $body
     */
    protected function checkParams($body)
    {
        if (!isset($body['main']) || !isset($body['detail']) || !isset($body['address_info'])) {
            return false;
        }

        $timeStampMainAry = array(
            'created_at',
            'updated_at',
            'hotai_point_deduction_point_last_handle_time',
            'ecpay_invoice_updated_at',
            'ecpay_invoice_date'
        );

        $timeStampDetailAry = array(
            'created_at',
            'updated_at',
        );

        $reqColMainAry = $this->reqColSalesOrder();
        foreach ($body['main'] as $key => $val) {
            if (!in_array($key, $reqColMainAry)) {
                unset($body['main'][$key]);
            }

            if (in_array($key, $timeStampMainAry)) {
                $body['main'][$key] = $this->getUTC0byTimeStamp($val);
            }
        }

        $nullableDatetimeFields = ['ecpay_invoice_updated_at', 'ecpay_invoice_date'];
        foreach ($nullableDatetimeFields as $field) {
            if (empty($body['main'][$field])) {
                $body['main'][$field] = null;
                continue;
            }
            $val = (string) $body['main'][$field];
            if ($val[0] === '-' || str_contains($val, '0000-00-00')) {
                $body['main'][$field] = null;
            }
        }

        if (count($this->reqColSalesOrder()) != count($body['main'])) {
            return false;
        }

        $reqColDetailAry = $this->reqColSalesOrderItem();
        foreach ($body['detail'] as $key => $value) {
            foreach ($value as $k => $v) {
                if (!in_array($k, $reqColDetailAry)) {
                    unset($value[$k]);
                }

                if (in_array($k, $timeStampDetailAry)) {
                    $body['detail'][$key][$k] = $this->getUTC0byTimeStamp($v);
                }
            }
        }

        if (count($this->reqColSalesOrderItem()) != count($body['detail'][0])) {
            return false;
        }

        return $body;
    }

    /**
     * Return required sales_order columns
     *
     * @return array
     */
    protected function reqColSalesOrder()
    {
        return [
            'rma_status',
            'state',
            'status',
            'shipping_description',
            'customer_id',
            'base_discount_amount',
            'discount_amount',
            'base_discount_canceled',
            'discount_canceled',
            'base_discount_invoiced',
            'discount_invoiced',
            'base_discount_refunded',
            'discount_refunded',
            'base_grand_total',
            'grand_total',
            'base_shipping_amount',
            'shipping_amount',
            'base_shipping_canceled',
            'shipping_canceled',
            'base_shipping_invoiced',
            'shipping_invoiced',
            'base_shipping_refunded',
            'shipping_refunded',
            'base_shipping_tax_amount',
            'shipping_tax_amount',
            'base_shipping_incl_tax',
            'shipping_incl_tax',
            'base_subtotal',
            'subtotal',
            'base_subtotal_canceled',
            'subtotal_canceled',
            'base_subtotal_invoiced',
            'subtotal_invoiced',
            'base_subtotal_refunded',
            'subtotal_refunded',
            'base_tax_amount',
            'tax_amount',
            'base_tax_canceled',
            'tax_canceled',
            'base_tax_invoiced',
            'tax_invoiced',
            'base_tax_refunded',
            'tax_refunded',
            'base_to_global_rate',
            'base_to_order_rate',
            'base_total_paid',
            'total_paid',
            'total_qty_ordered',
            'total_refunded',
            'customer_is_guest',
            'billing_address_id',
            'customer_group_id',
            'email_sent',
            'send_email',
            'quote_id',
            'shipping_address_id',
            'base_shipping_discount_amount',
            'shipping_discount_amount',
            'base_subtotal_incl_tax',
            'subtotal_incl_tax',
            'base_total_due',
            'total_due',
            'increment_id',
            'applied_rule_ids',
            'base_currency_code',
            'customer_email',
            'customer_firstname',
            'discount_description',
            'global_currency_code',
            'order_currency_code',
            'store_currency_code',
            'created_at',
            'updated_at',
            'total_item_count',
            'base_discount_tax_compensation_amount',
            'discount_tax_compensation_amount',
            'base_discount_tax_compensation_invoiced',
            'discount_tax_compensation_invoiced',
            'base_discount_tax_compensation_refunded',
            'discount_tax_compensation_refunded',
            'base_shipping_discount_tax_compensation_amnt',
            'order_approval_status',
            'hotai_point_deduction_point_complete',
            'hotai_point_deduction_point_last_handle_time',
            'hotai_point_add_point_complete',
            'point_used_total',
            'point_discount_total',
            'ecpay_invoice_carruer_type',
            'ecpay_invoice_type',
            'ecpay_invoice_carruer_num',
            'ecpay_invoice_customer_company',
            'ecpay_invoice_number',
            'ecpay_invoice_date',
            'ecpay_invoice_random_number',
            'ecpay_invoice_tag',
            'ecpay_invoice_issue_type',
            'ecpay_invoice_od_sob',
            'shipping_method',
            'hotai_child_order_number',
            'applied_rule_names',
            'seller_borne_catalogrule_amount',
            'seller_borne_salesrule_amount',
            'seller_borne_total_amount',
            'store_id',
            'ecpay_invoice_status',
            'ecpay_invoice_updated_at',
            'hotai_checkout_number',
            'ecpay_invoice_customer_identifier',
            'customer_lastname',
            'has_refund',
            'is_virtual'
        ];
    }

    /**
     * Return required sales_order_item columns
     *
     * @return array
     */
    protected function reqColSalesOrderItem()
    {
        return [
            'order_id',
            'store_id',
            'created_at',
            'updated_at',
            'product_id',
            'product_type',
            'is_virtual',
            'sku',
            'name',
            'applied_rule_ids',
            'is_qty_decimal',
            'no_discount',
            'qty_backordered',
            'qty_ordered',
            'qty_invoiced',
            'qty_shipped',
            'qty_refunded',
            'qty_canceled',
            'base_cost',
            'base_price',
            'price',
            'base_original_price',
            'original_price',
            'tax_percent',
            'base_tax_amount',
            'tax_amount',
            'base_tax_invoiced',
            'tax_invoiced',
            'discount_percent',
            'base_discount_amount',
            'discount_amount',
            'base_discount_invoiced',
            'discount_invoiced',
            'base_amount_refunded',
            'amount_refunded',
            'base_row_total',
            'row_total',
            'base_row_invoiced',
            'row_invoiced',
            'row_weight',
            'base_price_incl_tax',
            'price_incl_tax',
            'base_row_total_incl_tax',
            'row_total_incl_tax',
            'base_discount_tax_compensation_amount',
            'discount_tax_compensation_amount',
            'free_shipping',
            'qty_returned',
            'row_total_point_used',
            'row_total_point_discount',
            'hotai_point_deduction_point_sync_from_quote_item_status',
            'hotai_point_deduction_point_trace_no',
            'hotai_point_deduction_point_progress_status',
            'hotai_point_deduction_point_trans_s_n',
            'hotai_point_deduction_point_trans_datetime',
            'hotai_point_deduction_point_commit_or_cancel_fail_counter',
            'hotai_point_deduction_point_memo',
            'hotai_point_add_point_trace_no',
            'hotai_point_add_point_success',
            'hotai_point_add_point_trans_s_n',
            'hotai_point_add_point_trans_datetime',
            'hotai_point_add_point_memo',
            'hotai_point_return_point_trace_no',
            'hotai_child_order_item_number',
            'category_name',
            'category_id',
            'commission_percent',
            'flow_status',
            'rma_status',
            'seller_id',
            'seller_code',
            'seller_company_name',
            'seller_borne_catalogrule_amount',
            'seller_borne_salesrule_amount'
        ];
    }
}
