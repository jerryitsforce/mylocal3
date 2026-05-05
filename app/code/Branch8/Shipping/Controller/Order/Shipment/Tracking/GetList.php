<?php

declare(strict_types=1);

namespace Branch8\Shipping\Controller\Order\Shipment\Tracking;

use Branch8\Shipping\Model\GetTrackingList;
use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Framework\App\Action\HttpGetActionInterface;
use Magento\Framework\Controller\Result\JsonFactory as ResultJsonFactory;

class GetList extends Action implements HttpGetActionInterface
{
    /**
     * @var ResultJsonFactory
     */
    private ResultJsonFactory $resultJsonFactory;

    private GetTrackingList $getTrackingList;

    /**
     * @param Context $context
     * @param GetTrackingList $getTrackingList
     * @param ResultJsonFactory $resultJsonFactory
     */
    public function __construct(
        Context           $context,
        GetTrackingList   $getTrackingList,
        ResultJsonFactory $resultJsonFactory
    )
    {
        parent::__construct($context);
        $this->resultJsonFactory = $resultJsonFactory;
        $this->getTrackingList = $getTrackingList;
    }
    /**
     * @inheritdoc
     */
    public function execute()
    {
        $result = $this->resultJsonFactory->create();
        $orderId = $this->getRequest()->getParam('order_id');
        if (!$orderId) {
            return $result->setData(['success' => false, 'message' => 'Order ID is missing']);
        }
        try {
            return $result->setData(['success' => true, 'trackingData' => $this->getTrackingList->execute((int)$orderId)]);
        } catch (\Exception $e) {
            return $result->setData(['success' => false, 'message' => $e->getMessage()]);
        }
    }
}
