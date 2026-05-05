<?php

/**
 * Copyright © Hotai Connected Co.,Ltd All rights reserved.
 * See COPYING.txt for license details.
 */

 declare(strict_types=1);

namespace HotaiConnected\Order\Model;

use Magento\Framework\Webapi\Rest\Request;
use Magento\Framework\Webapi\Rest\Response;
use Psr\Log\LoggerInterface as Logger;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\Exception\ValidatorException;

class OrderExistenceManagement implements \HotaiConnected\Order\Api\OrderExistenceManagementInterface
{

    private $request;
    private $logger;
    private $resourceConnection;
    private $response;
    private $connection;

    /**
     * Initial parameters
     */
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
     * Get order by hotai_child_order_number
     *
     * @return Magento\Framework\Webapi\Rest\Response
     * @throws ValidatorException
     */
    public function postOrderExistence()
    {
        try {
            if ($this->connection == null) {
                $this->connection = $this->resourceConnection->getConnection();
            }
            $orderNoData = $this->request->getBodyParams();

            if (!isset($orderNoData['data']) || empty($orderNoData['data'])) {
                $this->logger->error('order check fail: input empty or format error');
                throw new ValidatorException(
                    __(
                        'order check fail: input empty or format error'
                    )
                );
            }

            $orderNoData = array_unique($orderNoData['data']);
            $orderNoData = array_values($orderNoData);

            $statusAry = array();
            foreach ($orderNoData as $k => $v) {
                $statusAry[$v] = 0;
            }

            $this->logger->info('Request Body: ' . json_encode($orderNoData));
            $incrementIdAry = array_map(function ($orderNo) {
                return 'hotai_order_' . $orderNo;
            }, $orderNoData);
            $placeholders = implode(',', array_fill(0, count($incrementIdAry), '?'));
            $querySalesOrder = "SELECT increment_id from 
                sales_order 
                WHERE 
                increment_id in ({$placeholders})";
            $result = $this->connection->fetchAll($querySalesOrder, $incrementIdAry);

            if (count($result) > 0) {
                foreach ($result as $k => $v) {
                    $statusAry[ltrim($v['increment_id'], 'hotai_order_')] = 1;
                }
            }

            $responseAry = array();
            $increment = 0 ;
            foreach ($statusAry as $k => $v) {
                $responseAry[$increment]['order_no'] = $k;
                $responseAry[$increment]['code'] = $v;
                $increment++;
            }

            $this->response->setHeader('Content-Type', 'application/json', true)
                ->setBody(json_encode(array("data" => $responseAry)))
                ->sendResponse();
        } catch (\Exception $e) {
            $this->logger->critical('order check fail: ' . $e->getMessage());
            $errAry = ['status' => 0, 'code' => 'order check fail,' . $e->getMessage()];
            $this->response->setHeader('Content-Type', 'application/json', true)
                ->setBody(json_encode($errAry))
                ->sendResponse();
        }
    }
}
