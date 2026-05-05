<?php

namespace Branch8\MarketPlaceOrderExportSeller\Observer;

use Ecpay\Invoice\Api\Data\HotaiOrderInvoiceLogsInterface;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;

class AssignSellerInformation implements ObserverInterface
{
    private ResourceConnection $resourceConnection;

    /**
     * @param ResourceConnection $resourceConnection
     */
    public function __construct(
        ResourceConnection $resourceConnection,
    )
    {
        $this->resourceConnection = $resourceConnection;
    }

    /**
     * @param Observer $observer
     * @return void
     */
    public function execute(Observer $observer)
    {
        // TODO: Implement execute() method.
        /**
         * @var $logObject \Ecpay\Invoice\Api\Data\HotaiOrderInvoiceLogsInterface
         */
        $logObject = $observer->getData('data_object');

        if ($logObject
            && empty($logObject->getSellerId())
            && ($orderId = $logObject->getOrderId())
            && ($row = $this->getSeller($orderId))
        ) {
            foreach ($row as $field => $value) {
                $logObject->setData($field, $value);
            }
        }
    }

    /**
     * @param $orderId
     * @return mixed|null
     */
    private function getSeller($orderId)
    {
        try {
            $columns = [
                'seller_id' => 'marketplace_orders.seller_id',
                'seller_code' => 'marketplace_userdata.seller_code',
                'seller_company_name' => 'marketplace_userdata.company_name',
                'seller_shop_name' => 'marketplace_userdata.shop_title'
            ];
            $connection = $this->resourceConnection->getConnection();
            $select = $connection->select()->from(
                'marketplace_orders'
            )->join(
                'marketplace_userdata',
                'marketplace_orders.seller_id = marketplace_userdata.seller_id',
            )->reset('columns')->columns($columns)->where('marketplace_orders.order_id = ?', $orderId);
            return $connection->fetchRow($select);
        } catch (\Exception $exception) {
            return null;
        }

    }
}
