<?php
/**
 * Copyright © HotaiConnected. All rights reserved.
 */

namespace HotaiConnected\Logistics\Controller\Adminhtml\Settings;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use HotaiConnected\Logistics\Model\LogisticsSettingsFactory;
use HotaiConnected\Logistics\Model\ResourceModel\LogisticsSettings as LogisticsSettingsResource;

class Toggle extends Action
{
    /**
     * Authorization level of a basic admin session
     */
    const ADMIN_RESOURCE = 'HotaiConnected_Logistics::logistics_overview';

    /**
     * @var LogisticsSettingsFactory
     */
    protected $logisticsSettingsFactory;

    /**
     * @var LogisticsSettingsResource
     */
    protected $logisticsSettingsResource;

    /**
     * @param Context $context
     * @param LogisticsSettingsFactory $logisticsSettingsFactory
     * @param LogisticsSettingsResource $logisticsSettingsResource
     */
    public function __construct(
        Context $context,
        LogisticsSettingsFactory $logisticsSettingsFactory,
        LogisticsSettingsResource $logisticsSettingsResource
    ) {
        parent::__construct($context);
        $this->logisticsSettingsFactory = $logisticsSettingsFactory;
        $this->logisticsSettingsResource = $logisticsSettingsResource;
    }

    /**
     * Toggle status action
     *
     * @return \Magento\Framework\Controller\Result\Redirect
     */
    public function execute()
    {
        $resultRedirect = $this->resultRedirectFactory->create();
        $id = $this->getRequest()->getParam('id');

        if (!$id) {
            $this->messageManager->addErrorMessage(__('無效的ID'));
            return $resultRedirect->setPath('*/*/');
        }

        try {
            $model = $this->logisticsSettingsFactory->create();
            $this->logisticsSettingsResource->load($model, $id);

            if (!$model->getId()) {
                $this->messageManager->addErrorMessage(__('找不到該設定'));
                return $resultRedirect->setPath('*/*/');
            }

            // Toggle the status
            $currentStatus = (int)$model->getIsActive();
            $newStatus = $currentStatus === 1 ? 0 : 1;
            $model->setIsActive($newStatus);
            $this->logisticsSettingsResource->save($model);

            $statusLabel = $newStatus === 1 ? __('啟用') : __('關閉');
            $this->messageManager->addSuccessMessage(__('狀態已更新為: %1', $statusLabel));

        } catch (\Exception $e) {
            $this->messageManager->addErrorMessage(__('更新失敗: %1', $e->getMessage()));
        }

        return $resultRedirect->setPath('*/*/');
    }
}
