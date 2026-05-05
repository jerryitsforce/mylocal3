<?php

/**
 * Copyright © Hotai Connected Co.,Ltd All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace HotaiConnected\Product\Model;

use Magento\Framework\Webapi\Rest\Request;
use Magento\Framework\Webapi\Rest\Response;
use Psr\Log\LoggerInterface as Logger;
use Magento\Framework\App\ResourceConnection;

class ProductSerialNoManagement implements \HotaiConnected\Product\Api\ProductSerialNoManagementInterface
{
    private $connection = null;
    private $resourceConnection;
    private $logger;
    private $request;
    private $response;
    private $settingId;
    private $snId;

    private const ATTRIBUTE_ARY = [
        11 => 5,
        12 => 6,
        13 => 2,
        14 => 4
    ];
    private const TABLE_SETTING_ARY = [
        5 => "general_notify_ticket_batch_setting",
        6 => "general_non_notify_ticket_batch_setting",
        2 => "yoxi_batch_setting_v2",
        4 => "family_bonus_pin_batch_setting_v2"
    ];
    private const TABLE_SN_ARY = [
        5 => "general_notify_ticket_record",
        6 => "general_non_notify_ticket_record",
        2 => "yoxi_ticket_record_v2",
        4 => "family_bonus_pin_ticket_record_v2"
    ];

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
     * {@inheritdoc}
     */
    public function updateProductSerialNo()
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

            $body = array_map(
                function ($val) {
                    $batchCode = substr($val['sku'], 5, strlen($val['sku']));

                    $result = $this->findDataBySku($val['sku']) ;
                    $mpResult = $this->findMarketProductById($result['entity_id']);
                    if (!array_key_exists($result['attribute_set_id'], self::ATTRIBUTE_ARY)) {
                        return $val;
                    }
                    $val['type'] = self::ATTRIBUTE_ARY[$result['attribute_set_id']];
                    $val['setting_table'] = self::TABLE_SETTING_ARY[self::ATTRIBUTE_ARY[$result['attribute_set_id']]];
                    $val['sn_table'] = self::TABLE_SN_ARY[self::ATTRIBUTE_ARY[$result['attribute_set_id']]];
                    $val['setting']['belong_to_product_id'] = $result['entity_id'];
                    $val['setting']['seller_id'] = $mpResult['seller_id'];
                    $val['setting']['batch_code'] = $batchCode;
                    $val['serial_no']['belong_to_product_id'] = $result['entity_id'];
                    $val['serial_no']['seller_id'] = $mpResult['seller_id'];

                    if (in_array($val['serial_no']['status'], array(2,3,-3))) {
                        $orderResult = $this->getOrderResult($val['orderNo'], $val['sku']);
                        if ($orderResult == false) {
                            $this->logger->info($val['orderNo'] . ' order_no not found. skip (update)');
                            return $val;
                        }
                        $val['serial_no']['sales_order_item_id'] = $orderResult['item_id'];
                        $val['customer_ticket']['type'] = self::ATTRIBUTE_ARY[$result['attribute_set_id']];
                        $val['customer_ticket']['ticket_table_name'] = self::TABLE_SN_ARY[self::ATTRIBUTE_ARY[$result['attribute_set_id']]];
                        $val['customer_ticket']['customer_id'] = $orderResult['customer_id'];
                        $val['customer_ticket']['sales_order_item_id'] = $orderResult['item_id'];
                        $val['customer_ticket']['belong_to_product_id'] = $result['entity_id'];
                        $val['customer_ticket']['seller_id'] = $mpResult['seller_id'];
                        $val['customer_ticket']['ticket_unique_content'] = $val['serial_no']['serial_number'];
                        $val['customer_ticket']['status'] = $val['serial_no']['status'];
                        $val['customer_ticket']['created_at'] = $val['serial_no']['created_at'];
                        $val['customer_ticket']['updated_at'] = $val['serial_no']['updated_at'];
                        $val['customer_ticket']['batch_code'] = $batchCode;
                        $val['customer_ticket']['use_start_time'] = $val['setting']['use_start_time'];
                        $val['customer_ticket']['use_end_time'] = $val['setting']['use_end_time'];
                        $val['customer_ticket']['memo'] = $val['serial_no']['memo'];
                        $val['customer_ticket']['redeemed_at'] = $val['serial_no']['used_date'];
                    }

                    return $val;
                }, $body['data']
            );

            foreach ($body as $k => $v) {
                $result = $this->findDataBySku($v['sku']) ;
                $this->updateSerialNo($v['serial_no'], $v['sn_table']);

                $v['customer_ticket']['ticket_table_record_id'] = $this->snId;
                $result = $this->findCustomerTicketBySNId($this->snId);

                if (in_array($v['serial_no']['status'], array(2,3,-3))) {
                    $this->updateCustomerTicket($v['customer_ticket']);
                }

                if (in_array($v['serial_no']['status'], array(2))) {
                    if (!empty($result)) {
                        $this->updateOrderStatus($v['orderNo'], $v['serial_no']['sales_order_item_id']);
                    }
                }
            }

            $this->connection->commit();

            $this->response->setHeader('Content-Type', 'application/json', true)
                ->setBody(json_encode(['status' => 1, 'code' => 'success']))
                ->sendResponse();
        } catch (\Exception $e) {
            $this->connection->rollBack();
            $this->logger->critical('update serial number fail: ' . $e->getMessage());
            $this->response->setHeader('Content-Type', 'application/json', true)
                ->setBody(json_encode(['status' => 0, 'code' => 'update serial number fail. ' . $e->getMessage()]))
                ->sendResponse();
        }
    }

    /**
     * {@inheritdoc}
     */
    public function postProductSerialNo()
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

            $body = array_map(
                function ($val) {
                    $batchCode = substr($val['sku'], 5, strlen($val['sku']));
                    $result = $this->findDataBySku($val['sku']) ;
                    $mpResult = $this->findMarketProductById($result['entity_id']);
                    if (!array_key_exists($result['attribute_set_id'], self::ATTRIBUTE_ARY)) {
                        return $val;
                    }
                    $val['type'] = self::ATTRIBUTE_ARY[$result['attribute_set_id']];
                    $val['setting_table'] = self::TABLE_SETTING_ARY[self::ATTRIBUTE_ARY[$result['attribute_set_id']]];
                    $val['sn_table'] = self::TABLE_SN_ARY[self::ATTRIBUTE_ARY[$result['attribute_set_id']]];
                    $val['setting']['belong_to_product_id'] = $result['entity_id'];
                    $val['setting']['seller_id'] = $mpResult['seller_id'];
                    $val['setting']['batch_code'] = $batchCode;
                    $val['serial_no']['belong_to_product_id'] = $result['entity_id'];
                    $val['serial_no']['seller_id'] = $mpResult['seller_id'];

                    if (in_array($val['serial_no']['status'], array(2,3,-3))) {
                        $orderResult = $this->getOrderResult($val['orderNo'], $val['sku']);
                        if ($orderResult == false) {
                            $this->logger->info($val['orderNo'] . ' order_no not found. skip (insert)');
                            return $val;
                        }
                        $val['serial_no']['sales_order_item_id'] = $orderResult['item_id'];
                        $val['customer_ticket']['type'] = self::ATTRIBUTE_ARY[$result['attribute_set_id']];
                        $val['customer_ticket']['ticket_table_name'] = self::TABLE_SN_ARY[self::ATTRIBUTE_ARY[$result['attribute_set_id']]];
                        $val['customer_ticket']['customer_id'] = $orderResult['customer_id'];
                        $val['customer_ticket']['sales_order_item_id'] = $orderResult['item_id'];
                        $val['customer_ticket']['belong_to_product_id'] = $result['entity_id'];
                        $val['customer_ticket']['seller_id'] = $mpResult['seller_id'];
                        $val['customer_ticket']['ticket_unique_content'] = $val['serial_no']['serial_number'];
                        $val['customer_ticket']['status'] = $val['serial_no']['status'];
                        $val['customer_ticket']['created_at'] = $val['serial_no']['created_at'];
                        $val['customer_ticket']['updated_at'] = $val['serial_no']['updated_at'];
                        $val['customer_ticket']['batch_code'] = $batchCode;
                        $val['customer_ticket']['use_start_time'] = $val['setting']['use_start_time'];
                        $val['customer_ticket']['use_end_time'] = $val['setting']['use_end_time'];
                        $val['customer_ticket']['memo'] = $val['serial_no']['memo'];
                        $val['customer_ticket']['redeemed_at'] = $val['serial_no']['used_date'];
                    }

                    return $val;
                }, $body['data']
            );

            foreach ($body as $k => $v) {
                $result = $this->findDataBySku($v['sku']) ;
                $tbl = self::TABLE_SETTING_ARY[self::ATTRIBUTE_ARY[$result['attribute_set_id']]];
                $settingResult = $this->findSettingByBatchCode($v['setting']['batch_code'], $tbl);

                if (empty($settingResult)) {
                    $this->setSetting($v['setting'], $v['setting_table']);
                    $v['serial_no']['batch_setting_id'] = $this->settingId;
                } else {
                    $v['serial_no']['batch_setting_id'] = $settingResult['setting_id'];
                }

                $rowSN = $this->findRowBySerialNo($v['setting']['seller_id'], $v['serial_no']['serial_number']);
                if (!empty($rowSN)) {
                    continue;
                }

                $this->setSerialNo($v['serial_no'], $v['sn_table']);
                $v['customer_ticket']['ticket_table_record_id'] = $this->snId;
                if (in_array($v['serial_no']['status'], array(2,3,-3))) {
                    $this->setCustomerTicket($v['customer_ticket']);
                }

                if (in_array($v['serial_no']['status'], array(2))) {
                    if (!empty($result)) {
                        $this->updateOrderStatus($v['orderNo'], $v['serial_no']['sales_order_item_id']);
                    }
                }
            }

            $this->connection->commit();

            $this->response->setHeader('Content-Type', 'application/json', true)
                ->setBody(json_encode(['status' => 1, 'code' => 'success']))
                ->sendResponse();
        } catch (\Exception $e) {
            $this->connection->rollBack();
            $this->logger->critical('serial_no insert fail: ' . $e->getMessage());
            $this->response->setHeader('Content-Type', 'application/json', true)
                ->setBody(json_encode(['status' => 0, 'code' => 'serial_no insert fail. ' . $e->getMessage()]))
                ->sendResponse();
        }
    }

    /**
     * Find row by serial no
     * @param string $sn
     * @param int $itemId
     * @return void
     */
    protected function findRowBySerialNo($seller_id, $sn)
    {
        $sqlSelect = 'SELECT record_id FROM customer_ticket WHERE ticket_unique_content = :ticket_unique_content AND seller_id = :seller_id';
        $result = $this->connection->fetchAll($sqlSelect, array('ticket_unique_content' => $sn, 'seller_id' => $seller_id));

        return $result;
    }

    /**
     * Find setting by batch_code
     * @param string $orderNo
     * @param int $itemId
     * @return void
     */
    protected function updateOrderStatus($orderNo, $itemId)
    {
        $sql = 'UPDATE `sales_order`
            SET state = :state,
                status = :status
            WHERE increment_id = :increment_id;';

        $binds = array(
            'state' => 'processing',
            'status'=> 'arrived',
            'increment_id' => 'hotai_order_' . $orderNo
        );
        $this->connection->query($sql, $binds);

        //$this->logger->info(__FUNCTION__ . ' orderNo: ' . $orderNo);
        $sql = 'UPDATE `sales_order_item`
            SET flow_status = :flow_status
            WHERE item_id = :item_id;';
        
        $binds = array(
            'flow_status' => 'arrived',
            'item_id' => $itemId
        );

        //$this->logger->info(__FUNCTION__ . ' itemId: ' . $itemId);
        $this->connection->query($sql, $binds);
    }

    protected function findCustomerTicketBySNId($snId)
    {
        $sqlSelect = 'SELECT record_id FROM customer_ticket WHERE ticket_table_record_id = :ticket_table_record_id';
        $result = $this->connection->fetchAll($sqlSelect, array('ticket_table_record_id' => $snId));

        return $result;
    }

    /**
     * Find setting by batch_code
     * @param string $batchCode
     * @param string $tbl
     * @return array
     */
    protected function findSettingByBatchCode($batchCode, $tbl)
    {
        $sqlSelect = 'SELECT setting_id FROM ' . $tbl . ' WHERE batch_code = :batch_code';
        $result = $this->connection->fetchAll($sqlSelect, array('batch_code' => $batchCode));
        if (empty($result)) {
            return [];
        }

        return $result[0];
    }

    /**
     * get seller_id result by product_id
     * @param int $id
     * @return array $result
     */
    protected function findMarketProductById($id)
    {
        $sqlSelect = 'select seller_id from marketplace_product where mageproduct_id = :mageproduct_id';
        $result = $this->connection->fetchAll($sqlSelect, array('mageproduct_id' => $id));
        if (empty($result)) {
            $this->response->setHeader('Content-Type', 'application/json', true)
                ->setBody(json_encode(['status' => 0, 'code' => 'marketplace_product not found']))
                ->sendResponse();
            exit();
        }

        return $result[0];
    }

    /**
     * get order result by sku, orderNo
     * @param string $orderNo
     * @param string $sku
     * @return array $result
     */
    public function getOrderResult($orderNo, $sku)
    {
        $customerId = '';
        $sqlSelect = 'select 
            entity_id, customer_id
            from 
            sales_order 
            where 
            increment_id = :increment_id';
        $result = $this->connection->fetchAll($sqlSelect, array('increment_id' => 'hotai_order_' . $orderNo));

        //$this->logger->info('test setSerialNo: orderNo:' . $orderNo);
        if (!empty($result)) {
            $customerId = $result[0]['customer_id'];
            //$this->logger->info('test setSerialNo: order_id:' . $result[0]['entity_id']);
            $orderId = $result[0]['entity_id'];
            $sqlSelect = 'select 
            item_id
            from 
            sales_order_item
            where 
            order_id = :order_id
            and 
            sku = :sku';
            $result = $this->connection->fetchAll($sqlSelect, array('order_id' => $orderId, 'sku' => $sku));

            $result[0]['customer_id'] = $customerId ;
            //$this->logger->info('test setSerialNo: sku:' . $sku);
            //$this->logger->info('test setSerialNo: item_id:' . $result[0]['item_id']);
            return $result[0];
        } else {
            return false;
        }
    }

    /**
     * get attribute by sku
     * @param string $sku
     * @return array $result
     */
    protected function findDataBySku($sku)
    {
        $sqlSelect = 'select type_id, attribute_set_id, entity_id from catalog_product_entity where sku = :sku';
        $result = $this->connection->fetchAll($sqlSelect, array('sku' => $sku));
        if (empty($result)) {
            $this->response->setHeader('Content-Type', 'application/json', true)
                ->setBody(json_encode(['status' => 0, 'code' => 'sku not found, sku:' . $sku]))
                ->sendResponse();
        }

        return $result[0];
    }

    /**
     * update Setting
     *
     * @param array $setting
     * @param string $tblName
     * @return array $body
     */
    protected function updateSetting($setting, $tblName)
    {
        $sqlSettingAry = array_map(
            function ($column) {
                return $column . '=:' . $column;
            }, $this->reqColSetting()
        );

        $sqlMain = 'UPDATE `' . $tblName . '`
            SET ' . implode(',', $sqlSettingAry) . '
            WHERE batch_code = :batch_code;';

        foreach ($setting as $k => $v) {
            $binds[$k] = $v;
        }
        $this->connection->query($sqlMain, $binds);
    }

    /**
     * post Setting
     *
     * @param array $setting
     * @param string $tblName
     * @return array $body
     */
    protected function setSetting($setting, $tblName)
    {
        $sqlSettingAry = array_map(
            function ($column) {
                return ':' . $column;
            }, $this->reqColSetting()
        );

        $sqlMain = 'INSERT INTO `' . $tblName . '`
            (
                ' . implode(',', $this->reqColSetting()) . '
            )
            VALUES (
                ' . implode(',', $sqlSettingAry) . '
            );';

        foreach ($setting as $k => $v) {
            $binds[$k] = $v;
        }

        $this->connection->query($sqlMain, $binds);
        $this->settingId = $this->connection->lastInsertId();
    }

    /**
     * updateSerialNo
     *
     * @param array $sn
     * @param string $tblName
     * @return array $body
     */
    protected function updateSerialNo($sn, $tblName)
    {
        $sqlSelect = "SELECT record_id FROM $tblName WHERE serial_number = :serial_number";
        $result = $this->connection->fetchAll($sqlSelect, array('serial_number' => strval($sn['serial_number'])));
        if (!empty($result)) {
            $this->snId = $result[0]['record_id'];
        }
    
        $sqlSNAry = array_map(
            function ($column) {
                return $column . '=:' . $column;
            }, array_diff($this->reqColSerialNo(), array('batch_setting_id'))
        );

        unset($sn['batch_setting_id']);
        $sqlSN = 'UPDATE `' . $tblName . '`
            SET ' . implode(',', $sqlSNAry) . '
            WHERE serial_number=:serial_number;';

        foreach ($sn as $k => $v) {
            $binds[$k] = $v;
        }

        $this->connection->query($sqlSN, $binds);
    }

    /**
     * post setSerialNo
     *
     * @param array $sn
     * @param string $tblName
     * @return array $body
     */
    protected function setSerialNo($sn, $tblName)
    {
        $sqlSNAry = array_map(
            function ($column) {
                return ':' . $column;
            }, $this->reqColSerialNo()
        );

        $sqlSN = 'INSERT INTO `' . $tblName . '`
            (
                ' . implode(',', $this->reqColSerialNo()) . '
            )
            VALUES (
                ' . implode(',', $sqlSNAry) . '
            );';

        foreach ($sn as $k => $v) {
            $binds[$k] = $v;
        }

        $this->connection->query($sqlSN, $binds);
        $this->snId = $this->connection->lastInsertId();
    }

    /**
     * update customerTicket
     *
     * @param array $customerTicket
     * @return array $body
     */
    protected function updateCustomerTicket($customerTicket)
    {
        $sqlCTAry = array_map(
            function ($column) {
                return $column . '=:' . $column;
            }, $this->reqCustomerTicket()
        );

        $sqlCT = 'UPDATE `customer_ticket`
            SET
                ' . implode(',', $sqlCTAry) . '
            WHERE seller_id=:seller_id 
            AND ticket_unique_content=:ticket_unique_content;';

        foreach ($customerTicket as $k => $v) {
            $binds[$k] = $v;
        }

        $this->connection->query($sqlCT, $binds);
    }

    /**
     * post customerTicket
     *
     * @param array $customerTicket
     * @return array $body
     */
    protected function setCustomerTicket($customerTicket)
    {
        $sqlCTAry = array_map(
            function ($column) {
                return ':' . $column;
            }, $this->reqCustomerTicket()
        );

        $sqlCT = 'INSERT INTO `customer_ticket`
            (
                ' . implode(',', $this->reqCustomerTicket()) . '
            )
            VALUES (
                ' . implode(',', $sqlCTAry) . '
            );';

        foreach ($customerTicket as $k => $v) {
            $binds[$k] = $v;
        }

        $this->connection->query($sqlCT, $binds);
    }

    /**
     * Check post raw body format
     *
     * @param array $body
     * @return array $body
     */
    protected function checkParams($body)
    {
        if (!isset($body['data'])) {
            return false;
        }

        foreach ($body['data'] as $k => $v) {
            if (!isset($v['sku']) || !isset($v['type']) || !isset($v['setting']) || !isset($v['serial_no'])) {
                return false ;
            }

            if (in_array($v['serial_no']['status'], array(2,3,-3)) && !isset($v['orderNo'])) {
                return false ;
            }
        }

        return $body;
    }

    /**
     * Return required customer_ticket columns
     * @return array
     */
    protected function reqCustomerTicket()
    {
        return [
            "type",
            "ticket_table_name",
            "ticket_table_record_id",
            "batch_code",
            "customer_id",
            "sales_order_item_id",
            "belong_to_product_id",
            "seller_id",
            "ticket_unique_content",
            "use_start_time",
            "use_end_time",
            "status",
            "memo",
            "redeemed_at",
            "created_at",
            "updated_at"
        ];
    }

    /**
     * Return required setting columns
     * @return array
     */
    protected function reqColSetting()
    {
        return [
            "belong_to_product_id",
            "seller_id",
            "batch_code",
            "sale_start_time",
            "sale_end_time",
            "created_at",
            "updated_at",
            "use_start_time",
            "use_end_time",
            "due_days",
            "safety_stock"
        ];
    }

    /**
     * Return required ticket_record columns
     * @return array
     */
    protected function reqColSerialNo()
    {
        return [
            "batch_setting_id",
            "serial_number",
            "seller_id",
            "belong_to_product_id",
            "used_count",
            "status",
            "created_at",
            "updated_at",
            "quote_item_id",
            "sales_order_item_id",
            "use_start_time",
            "use_end_time",
            "due_days",
            "used_date",
            "used_transaction_no",
            "returned_date",
            "memo"
        ];
    }
}
