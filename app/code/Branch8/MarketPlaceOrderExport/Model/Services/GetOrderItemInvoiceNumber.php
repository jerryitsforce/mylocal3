<?php

namespace Branch8\MarketPlaceOrderExport\Model\Services;

use Magento\Framework\App\ResourceConnection;
use Magento\Store\Model\StoreManager;

class GetOrderItemInvoiceNumber
{
    private $cached = [];
    private ResourceConnection $resourceConnection;
    private StoreManager $storeManager;
    private GetTZOffsetTransitions $getTZOffsetTransitions;
    private \Magento\Framework\Stdlib\DateTime\Timezone $timezone;

    /**
     * @param ResourceConnection $resourceConnection
     * @param StoreManager $storeManager
     * @param GetTZOffsetTransitions $getTZOffsetTransitions
     * @param \Magento\Framework\Stdlib\DateTime\Timezone $timezone
     */
    public function __construct(
        ResourceConnection                          $resourceConnection,
        StoreManager                                $storeManager,
        GetTZOffsetTransitions                      $getTZOffsetTransitions,
        \Magento\Framework\Stdlib\DateTime\Timezone $timezone
    )
    {
        $this->storeManager = $storeManager;
        $this->resourceConnection = $resourceConnection;
        $this->getTZOffsetTransitions = $getTZOffsetTransitions;
        $this->timezone = $timezone;
    }

    /**
     * @param $orderId
     * @return array|mixed
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function get($orderId)
    {
        if (isset($this->cached[$orderId])) {
            return $this->cached[$orderId];
        }
        /*******************************
         * SELECT * FROM ecpay_invoice_hotai_order_invoice_logs
         INNER JOIN ecpay_invoice_hotai_order_item_invoice_logs ON `ecpay_invoice_hotai_order_invoice_logs`.`hotai_order_invoice_logs_id` = `ecpay_invoice_hotai_order_item_invoice_logs`.`hotai_order_invoice_log_id`
         *WHERE order_id = 1828;
         * *****************************/
        $connection = $this->resourceConnection->getConnection();
        $select = $connection->select()
            ->from(
                'ecpay_invoice_hotai_order_invoice_logs',
                []
            )->join('ecpay_invoice_hotai_order_item_invoice_logs', '`ecpay_invoice_hotai_order_invoice_logs.hotai_order_invoice_logs_id=ecpay_invoice_hotai_order_item_invoice_logs.hotai_order_invoice_log_id')
            ->where('order_id = ?', $orderId);
        $row = $connection->fetchRow($select);
        if ($row) {
            $this->cached[$orderId] = $row;
        } else {
            $this->cached[$orderId] = [];
        }
        return $this->cached[$orderId];
    }
}
