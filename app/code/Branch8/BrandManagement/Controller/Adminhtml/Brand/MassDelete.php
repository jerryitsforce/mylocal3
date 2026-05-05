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
 * Brand Mass Delete Controller
 */
class MassDelete extends Brand
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
     * Mass delete action
     *
     * @return \Magento\Framework\Controller\ResultInterface
     */
    public function execute()
    {
        $resultRedirect = $this->resultRedirectFactory->create();
        $selected = $this->getRequest()->getParam('selected');
        $excluded = $this->getRequest()->getParam('excluded');

        if ($selected) {
            $optionIds = $selected;
        } elseif ($excluded) {
            $allBrandOptions = $this->brandOptionRepository->getBrandOptions();
            $allOptionIds = array_column($allBrandOptions, 'option_id');
            $optionIds = array_diff($allOptionIds, $excluded);
        } else {
            $this->messageManager->addErrorMessage(__('Please select brand options to delete.'));
            return $resultRedirect->setPath('*/*/');
        }

        $deletedCount = 0;
        $errorCount = 0;

        foreach ($optionIds as $optionId) {
            try {
                // Check if brand option is used by products
                if ($this->brandOptionRepository->isBrandOptionUsed($optionId)) {
                    $this->messageManager->addWarningMessage(
                        __('Brand option ID %1 cannot be deleted because it is currently used by one or more products.', $optionId)
                    );
                    $errorCount++;
                    continue;
                }

                $this->brandOptionRepository->deleteBrandOption($optionId);
                $deletedCount++;
            } catch (LocalizedException $e) {
                $this->logger->error($e);
                $this->messageManager->addErrorMessage($e->getMessage());
                $errorCount++;
            } catch (\Exception $e) {
                $this->logger->error($e);
                $this->messageManager->addExceptionMessage($e, __('Something went wrong while deleting brand option ID %1.', $optionId));
                $errorCount++;
            }
        }

        if ($deletedCount > 0) {
            $this->messageManager->addSuccessMessage(__('A total of %1 brand option(s) have been deleted.', $deletedCount));
        }

        if ($errorCount > 0) {
            $this->messageManager->addErrorMessage(__('A total of %1 brand option(s) could not be deleted.', $errorCount));
        }

        return $resultRedirect->setPath('*/*/');
    }
}
