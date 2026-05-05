<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Branch8\BrandManagement\Controller\Adminhtml\Brand;

use Branch8\BrandManagement\Model\Request\BrandRegistry;
use Magento\Backend\App\Action\Context;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\View\Result\PageFactory;
use Magento\Framework\Exception\NoSuchEntityException;
use Amasty\ShopbyBase\Helper\OptionSetting;
use Branch8\BrandManagement\Controller\Adminhtml\Brand;
use Psr\Log\LoggerInterface;

/**
 * Brand Edit Controller
 */
class Edit extends Brand
{
    /**
     * @var PageFactory
     */
    private $resultPageFactory;

    /**
     * @var OptionSetting
     */
    private $settingHelper;

    /**
     * @var RequestInterface
     */
    private RequestInterface $request;

    /**
     * @var BrandRegistry
     */
    private BrandRegistry $brandRegistry;

    /**
     * @var LoggerInterface
     */
    private LoggerInterface $logger;

    /**
     * Constructor
     *
     * @param Context $context
     * @param PageFactory $resultPageFactory
     * @param \Magento\Framework\Registry $coreRegistry
     * @param \Branch8\BrandManagement\Api\BrandOptionRepositoryInterface $brandOptionRepository
     * @param OptionSetting $optionSetting
     * @param BrandRegistry $brandRegistry
     * @param LoggerInterface $logger
     */
    public function __construct(
        Context $context,
        PageFactory $resultPageFactory,
        \Magento\Framework\Registry $coreRegistry,
        \Branch8\BrandManagement\Api\BrandOptionRepositoryInterface $brandOptionRepository,
        OptionSetting $optionSetting,
        BrandRegistry $brandRegistry,
        LoggerInterface $logger
    ) {
        $this->resultPageFactory = $resultPageFactory;
        $this->settingHelper = $optionSetting;
        $this->request = $context->getRequest();
        $this->brandRegistry = $brandRegistry;
        $this->logger = $logger;
        parent::__construct($context, $coreRegistry, $brandOptionRepository);
    }

    /**
     * Edit action
     *
     * @return \Magento\Backend\Model\View\Result\Page|\Magento\Framework\Controller\Result\Redirect
     */
    public function execute()
    {
        try {
            $model = $this->loadSettingModel();
            $model->setData('id', $model->getData('option_setting_id'));
            $this->brandRegistry->set($model);
            /** @var \Magento\Backend\Model\View\Result\Page $result */
            $result = $this->resultPageFactory->create();
            $result->setActiveMenu('Branch8_BrandManagement::brand_management');
            $result->addBreadcrumb(__('Brand Management'), __('Brand Management'));
            $result->addBreadcrumb(
                __('Edit Brand'),
                __('Edit Brand')
            );
            $result->getConfig()->getTitle()->prepend(__('Brand Management'));
            $result->getConfig()->getTitle()->prepend($model->getData('title') ?: __('Edit Brand'));
        } catch (\Exception $e) {
            $this->logger->error($e);
            $this->messageManager->addExceptionMessage($e, __('Something went wrong while editing the brand.'));
            $result = $this->resultRedirectFactory->create();
            $result->setPath('*/*/');
        }

        return $result;
    }

    /**
     * Load setting model
     *
     * @return \Amasty\ShopbyBase\Api\Data\OptionSettingInterface
     * @throws NoSuchEntityException
     */
    private function loadSettingModel()
    {
        // Support both 'id' (existing) and 'option_id' (Amasty style) parameters
        $optionId = (int) ($this->request->getParam('option_id') ?: $this->request->getParam('id'));
        $attributeCode = $this->request->getParam('attribute_code') ?: 'brand';
        $storeId = (int) $this->request->getParam('store', 0);
        
        if (!$optionId) {
            throw new NoSuchEntityException(__('Option ID is required.'));
        }
        
        $model = $this->settingHelper->getSettingByOption($optionId, $attributeCode, $storeId);
        if (!$model->getId()) {
            throw new NoSuchEntityException(__('Brand option setting not found.'));
        }
        $model->setCurrentStoreId($storeId);

        return $model;
    }
}
