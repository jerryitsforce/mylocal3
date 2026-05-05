<?php

declare(strict_types=1);

namespace Branch8\Shipping\Controller\Adminhtml\Order\Shipment\Tracking;

use Branch8\Shipping\Model\GetTrackingList;
use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
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
     * @param ResultJsonFactory $resultJsonFactory
     * @param GetTrackingList $getTrackingList
     */
    public function __construct(
        Context                  $context,
        ResultJsonFactory        $resultJsonFactory,
        GetTrackingList          $getTrackingList
    )
    {
        parent::__construct($context);
        $this->getTrackingList = $getTrackingList;
        $this->resultJsonFactory = $resultJsonFactory;
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

    /**
     * @inheritDoc
     */
    protected function _isAllowed(): bool
    {
        return $this->_authorization->isAllowed('Branch8_Shipping::shipment_tracking_getlist');
    }
}
