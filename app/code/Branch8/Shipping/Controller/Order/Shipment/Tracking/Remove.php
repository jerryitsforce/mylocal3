<?php

declare(strict_types=1);

namespace Branch8\Shipping\Controller\Order\Shipment\Tracking;

use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Framework\Controller\Result\JsonFactory as ResultJsonFactory;
use Magento\Framework\Exception\LocalizedException;
use Magento\Sales\Model\Order\Shipment\Track;

class Remove extends Action
{
    /**
     * @var ResultJsonFactory
     */
    private ResultJsonFactory $resultJsonFactory;

    /**
     * Constructor.
     *
     * @param Context $context
     * @param ResultJsonFactory $resultJsonFactory
     */
    public function __construct(
        Context           $context,
        ResultJsonFactory $resultJsonFactory
    ) {
        parent::__construct($context);
        $this->resultJsonFactory = $resultJsonFactory;
    }

    /**
     * @inheritdoc
     */
    public function execute()
    {
        $result = $this->resultJsonFactory->create();
        $response = ['success' => false, 'message' => __('Unable to delete tracking.')];

        try {
            $trackId = $this->getRequest()->getParam('track_id');

            if (!$trackId) {
                throw new LocalizedException(__('Missing tracking ID.'));
            }

            $track = $this->_objectManager->create(Track::class)->load($trackId);
            if (!$track->getId()) {
                throw new LocalizedException(__('Cannot load track with retrieving identifier right now.'));
            }

            $track->delete();

            $response = ['success' => true, 'message' => __('Tracking deleted successfully.')];
        } catch (\Exception $e) {
            $response['message'] = $e->getMessage();
        }

        return $result->setData($response);
    }
}
