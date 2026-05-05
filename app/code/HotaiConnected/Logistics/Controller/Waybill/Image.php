<?php
/**
 * Copyright © HotaiConnected. All rights reserved.
 */

namespace HotaiConnected\Logistics\Controller\Waybill;

use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Framework\App\Action\HttpGetActionInterface;
use Magento\Framework\Controller\ResultInterface;
use HotaiConnected\Logistics\Model\LogisticsWaybillFactory;
use HotaiConnected\Logistics\Helper\ImageConverter;
use Psr\Log\LoggerInterface;

/**
 * Display/Download Waybill Image
 */
class Image extends Action implements HttpGetActionInterface
{
    /**
     * @var LogisticsWaybillFactory
     */
    protected $waybillFactory;

    /**
     * @var ImageConverter
     */
    protected $imageConverter;

    /**
     * @var LoggerInterface
     */
    protected $logger;

    /**
     * @param Context $context
     * @param LogisticsWaybillFactory $waybillFactory
     * @param ImageConverter $imageConverter
     * @param LoggerInterface $logger
     */
    public function __construct(
        Context $context,
        LogisticsWaybillFactory $waybillFactory,
        ImageConverter $imageConverter,
        LoggerInterface $logger
    ) {
        parent::__construct($context);
        $this->waybillFactory = $waybillFactory;
        $this->imageConverter = $imageConverter;
        $this->logger = $logger;
    }

    /**
     * Display or download waybill image
     *
     * @return ResultInterface
     */
    public function execute()
    {
        $waybillId = $this->getRequest()->getParam('id');
        $action = $this->getRequest()->getParam('action', 'display'); // display or download

        if (!$waybillId) {
            $this->logger->error('Waybill ID not provided');
            return $this->_redirect('*/*/');
        }

        try {
            // Load waybill
            $waybill = $this->waybillFactory->create()->load($waybillId);

            if (!$waybill->getId()) {
                $this->logger->error('Waybill not found: ' . $waybillId);
                $this->messageManager->addErrorMessage(__('運單不存在'));
                return $this->_redirect('*/*/');
            }

            $imageHexString = $waybill->getImage();

            if (empty($imageHexString) || $imageHexString === '-') {
                $this->logger->error('Waybill image not available: ' . $waybillId);
                $this->messageManager->addErrorMessage(__('運單圖片不存在'));
                return $this->_redirect('*/*/');
            }

            // Convert hex string to image
            $image = $this->imageConverter->hexStringToImage($imageHexString);

            if ($image === false) {
                $this->logger->error('Failed to convert image: ' . $waybillId);
                $this->messageManager->addErrorMessage(__('圖片轉換失敗'));
                return $this->_redirect('*/*/');
            }

            // Set headers
            if ($action === 'download') {
                // Download as file
                $filename = 'waybill_' . $waybill->getWaybillNumber() . '.png';
                header('Content-Disposition: attachment; filename="' . $filename . '"');
            } else {
                // Display inline
                header('Content-Disposition: inline');
            }

            // Output image
            $this->imageConverter->outputImage($image, 'png');

            // Prevent any further output
            exit;

        } catch (\Exception $e) {
            $this->logger->error('Error displaying waybill image: ' . $e->getMessage());
            $this->messageManager->addErrorMessage(__('顯示圖片時發生錯誤'));
            return $this->_redirect('*/*/');
        }
    }
}
