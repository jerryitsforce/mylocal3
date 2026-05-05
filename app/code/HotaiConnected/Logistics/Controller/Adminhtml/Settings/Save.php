<?php
/**
 * Copyright © HotaiConnected. All rights reserved.
 */

namespace HotaiConnected\Logistics\Controller\Adminhtml\Settings;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\Exception\LocalizedException;
use HotaiConnected\Logistics\Model\LogisticsSettings;
use HotaiConnected\Logistics\Model\LogisticsSettingsFactory;
use HotaiConnected\Logistics\Model\ResourceModel\LogisticsSettings as LogisticsSettingsResource;
use HotaiConnected\Logistics\Model\Config\LogisticsCompany;

class Save extends Action
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
     * Save action
     *
     * @return \Magento\Framework\Controller\Result\Redirect
     */
    public function execute()
    {
        $resultRedirect = $this->resultRedirectFactory->create();
        $data = $this->getRequest()->getPostValue();

        if ($data) {
            try {
                // Validate required fields
                if (empty($data['seller_id']) || empty($data['logistics_company_id'])) {
                    throw new LocalizedException(__('請填寫必填欄位'));
                }

                // Get company name from constant
                $companyName = LogisticsCompany::getCompanyName($data['logistics_company_id']);
                if (!$companyName) {
                    throw new LocalizedException(__('無效的物流公司'));
                }

                // Prepare auth data
                $authData = [];
                if ($data['logistics_company_id'] == LogisticsCompany::COMPANY_HCT) {
                    // 新竹物流需要帳號密碼
                    if (empty($data['hct_account']) || empty($data['hct_password'])) {
                        throw new LocalizedException(__('請輸入新竹物流帳號和密碼'));
                    }
                    $authData = [
                        'account' => $data['hct_account'],
                        'password' => $data['hct_password']
                    ];
                }

                /** @var LogisticsSettings $model */
                $model = $this->logisticsSettingsFactory->create();
                $model->setSellerId($data['seller_id']);
                $model->setLogisticsCompanyId($data['logistics_company_id']);
                $model->setLogisticsCompanyName($companyName);
                $model->setAuthType('account');
                $model->setAuthData(json_encode($authData));
                $model->setIsActive(isset($data['is_active']) ? (int)$data['is_active'] : 1);
                $model->setPickupCount(0);

                $this->logisticsSettingsResource->save($model);

                $this->messageManager->addSuccessMessage(__('物流設定已成功儲存'));
                return $resultRedirect->setPath('*/*/');

            } catch (LocalizedException $e) {
                $this->messageManager->addErrorMessage($e->getMessage());
            } catch (\Exception $e) {
                $this->messageManager->addErrorMessage(
                    __('儲存時發生錯誤: %1', $e->getMessage())
                );
            }

            return $resultRedirect->setPath('*/*/new');
        }

        return $resultRedirect->setPath('*/*/');
    }
}
