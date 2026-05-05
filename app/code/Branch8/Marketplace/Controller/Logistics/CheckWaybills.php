<?php
/**
 * Branch8 Marketplace
 * Check Waybill Status for Order Items
 */

namespace Branch8\Marketplace\Controller\Logistics;

use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Framework\Controller\Result\JsonFactory;
use HotaiConnected\Logistics\Model\ResourceModel\LogisticsWaybill\CollectionFactory as WaybillCollectionFactory;
use Branch8\Marketplace\Service\MarketplaceLogger;
use Magento\Framework\App\ObjectManager;

class CheckWaybills extends Action
{
    /**
     * @var JsonFactory
     */
    protected $resultJsonFactory;

    /**
     * @var WaybillCollectionFactory
     */
    protected $waybillCollectionFactory;

    /**
     * @var MarketplaceLogger
     */
    private MarketplaceLogger $marketplaceLogger;

    /**
     * @param Context $context
     * @param JsonFactory $resultJsonFactory
     * @param WaybillCollectionFactory $waybillCollectionFactory
     * @param MarketplaceLogger|null $marketplaceLogger
     */
    public function __construct(
        Context $context,
        JsonFactory $resultJsonFactory,
        WaybillCollectionFactory $waybillCollectionFactory,
        MarketplaceLogger $marketplaceLogger = null
    ) {
        parent::__construct($context);
        $this->resultJsonFactory = $resultJsonFactory;
        $this->waybillCollectionFactory = $waybillCollectionFactory;
        $this->marketplaceLogger = $marketplaceLogger
            ?: ObjectManager::getInstance()->get(MarketplaceLogger::class);
    }

    /**
     * Execute
     *
     * @return \Magento\Framework\Controller\Result\Json
     */
    public function execute()
    {
        $result = $this->resultJsonFactory->create();
        $orderItemIds = $this->getRequest()->getParam('order_items', []);

        if (empty($orderItemIds)) {
            return $result->setData([
                'success' => false,
                'message' => 'No order items provided'
            ]);
        }

        try {
            $waybills = $this->getWaybillsByOrderItems($orderItemIds);

            return $result->setData([
                'success' => true,
                'waybills' => $waybills,
                'has_waybills' => !empty($waybills)
            ]);
        } catch (\Exception $e) {
            $this->marketplaceLogger->logException('CheckWaybills', $e, [
                'order_items' => $orderItemIds,
            ]);
            return $result->setData([
                'success' => false,
                'message' => $e->getMessage()
            ]);
        }
    }

    /**
     * Get waybills by order item IDs
     *
     * @param array $orderItemIds
     * @return array
     */
    protected function getWaybillsByOrderItems($orderItemIds)
    {
        $collection = $this->waybillCollectionFactory->create();
        $collection->addFieldToFilter('sales_order_item_id', ['in' => $orderItemIds])
                   ->setOrder('created_at', 'DESC');

        $waybills = [];
        foreach ($collection as $waybill) {
            $waybills[] = [
                'id' => $waybill->getId(),
                'order_item_id' => $waybill->getSalesOrderItemId(),
                'waybill_number' => $waybill->getWaybillNumber(),
                'tracking_number' => $waybill->getTrackingNumber()
            ];
        }

        return $waybills;
    }
}
