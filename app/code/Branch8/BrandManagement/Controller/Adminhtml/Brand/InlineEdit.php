<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Branch8\BrandManagement\Controller\Adminhtml\Brand;

use Magento\Backend\App\Action\Context;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\Exception\LocalizedException;
use Branch8\BrandManagement\Controller\Adminhtml\Brand;
use Psr\Log\LoggerInterface;

/**
 * Brand Inline Edit Controller
 */
class InlineEdit extends Brand
{
    /**
     * @var JsonFactory
     */
    protected $jsonFactory;

    /**
     * @var LoggerInterface
     */
    private LoggerInterface $logger;

    /**
     * Constructor
     *
     * @param Context $context
     * @param JsonFactory $jsonFactory
     * @param \Magento\Framework\Registry $coreRegistry
     * @param \Branch8\BrandManagement\Api\BrandOptionRepositoryInterface $brandOptionRepository
     * @param LoggerInterface $logger
     */
    public function __construct(
        Context $context,
        JsonFactory $jsonFactory,
        \Magento\Framework\Registry $coreRegistry,
        \Branch8\BrandManagement\Api\BrandOptionRepositoryInterface $brandOptionRepository,
        LoggerInterface $logger
    ) {
        $this->jsonFactory = $jsonFactory;
        $this->logger = $logger;
        parent::__construct($context, $coreRegistry, $brandOptionRepository);
    }

    /**
     * Inline edit action
     *
     * @return \Magento\Framework\Controller\ResultInterface
     */
    public function execute()
    {
        $resultJson = $this->jsonFactory->create();
        $error = false;
        $messages = [];

        if ($this->getRequest()->getParam('isAjax')) {
            $postItems = $this->getRequest()->getParam('items', []);
            if (!count($postItems)) {
                $messages[] = __('Please correct the data sent.');
                $error = true;
            } else {
                foreach (array_keys($postItems) as $optionId) {
                    $brandOption = $this->brandOptionRepository->getBrandOptionById($optionId);
                    if (!$brandOption) {
                        $messages[] = __('This brand option no longer exists.');
                        $error = true;
                        continue;
                    }

                    try {
                        $brandName = $postItems[$optionId]['value'] ?? '';
                        $sortOrder = $postItems[$optionId]['sort_order'] ?? 0;

                        if (empty($brandName)) {
                            $messages[] = __('Brand name is required.');
                            $error = true;
                            continue;
                        }

                        $this->brandOptionRepository->updateBrandOption($optionId, $brandName, $sortOrder);
                    } catch (LocalizedException $e) {
                        $this->logger->error($e);
                        $messages[] = $e->getMessage();
                        $error = true;
                    } catch (\Exception $e) {
                        $this->logger->error($e);
                        $messages[] = __('Something went wrong while saving the brand option.');
                        $error = true;
                    }
                }
            }
        }

        return $resultJson->setData([
            'messages' => $messages,
            'error' => $error
        ]);
    }
}
