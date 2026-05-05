<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Branch8\BrandManagement\Controller\Adminhtml\Brand;

use Magento\Backend\App\Action\Context;
use Magento\Framework\Exception\LocalizedException;
use Branch8\BrandManagement\Controller\Adminhtml\Brand;
use Psr\Log\LoggerInterface;

/**
 * Brand Delete Controller
 */
class Delete extends Brand
{
    /**
     * @var LoggerInterface
     */
    private LoggerInterface $logger;

    /**
     * @param Context $context
     * @param \Magento\Framework\Registry $coreRegistry
     * @param \Branch8\BrandManagement\Api\BrandOptionRepositoryInterface $brandOptionRepository
     * @param LoggerInterface $logger
     */
    public function __construct(
        Context $context,
        \Magento\Framework\Registry $coreRegistry,
        \Branch8\BrandManagement\Api\BrandOptionRepositoryInterface $brandOptionRepository,
        LoggerInterface $logger
    ) {
        $this->logger = $logger;
        parent::__construct($context, $coreRegistry, $brandOptionRepository);
    }

    /**
     * Delete action
     *
     * @return \Magento\Framework\Controller\ResultInterface
     */
    public function execute()
    {
        $resultRedirect = $this->resultRedirectFactory->create();
        $optionId = $this->getRequest()->getParam('id');

        if ($optionId) {
            try {
                // Check if brand option is used by products
                if ($this->brandOptionRepository->isBrandOptionUsed($optionId)) {
                    $this->messageManager->addErrorMessage(
                        __('Cannot delete this brand option because it is currently used by one or more products.')
                    );
                    return $resultRedirect->setPath('*/*/edit', ['id' => $optionId]);
                }

                $this->brandOptionRepository->deleteBrandOption($optionId);
                $this->messageManager->addSuccessMessage(__('You deleted the brand option.'));
            } catch (LocalizedException $e) {
                $this->logger->error($e);
                $this->messageManager->addErrorMessage($e->getMessage());
            } catch (\Exception $e) {
                $this->logger->error($e);
                $this->messageManager->addExceptionMessage($e, __('Something went wrong while deleting the brand option.'));
            }
        } else {
            $this->messageManager->addErrorMessage(__('We can\'t find a brand option to delete.'));
        }

        return $resultRedirect->setPath('*/*/');
    }
}
