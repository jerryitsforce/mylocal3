<?php

declare(strict_types=1);

namespace HotaiConnected\CheckoutManagement\Controller\Adminhtml\ApprovalFunctionality;

use Magento\Framework\App\Action\HttpGetActionInterface;

class Index extends \Magento\Backend\App\Action implements HttpGetActionInterface
{
    /** @var \Magento\Framework\View\Result\PageFactory */
    protected $resultPageFactory;

    public function __construct(
        \Magento\Backend\App\Action\Context $context,
        \Magento\Framework\View\Result\PageFactory $resultPageFactory
    ) {
        $this->resultPageFactory = $resultPageFactory;
        parent::__construct($context);
    }

    /**
     * Index action
     *
     * @return \Magento\Framework\Controller\ResultInterface
     */
    public function execute()
    {
        /** @var \Magento\Backend\Model\View\Result\Page $resultPage */
        $resultPage = $this->resultPageFactory->create();

        $resultPage->getConfig()->getTitle()->prepend(__('Approval & Authorization Center'));

        // 取得 filtersData 和 batchActions 並傳遞給 Block
        $filtersData = $this->getFiltersData();
        $batchActions = $this->getBatchActions();
        $block = $resultPage->getLayout()->getBlock('approval_functionality_content');
        if ($block) {
            $block->setData('filters_data', $filtersData);
            $block->setData('batch_actions', $batchActions);
        }

        return $resultPage;
    }

    /**
     * Get filters data configuration
     *
     * @return array
     */
    protected function getFiltersData()
    {
        // 先檢查父資源權限，如果沒有權限則直接返回空陣列
        if (!$this->_authorization->isAllowed('HotaiConnected_FinancialReconciliation::review_function_list')
            || !$this->_authorization->isAllowed('HotaiConnected_FinancialReconciliation::approval_functionality_filter')) {
            return [];
        }
     
        // 審核結果選項
        $exceptionStatusOptions = [];
        foreach (\HotaiConnected\FinancialReconciliation\Model\ReconciliationManagement::EXCEPTION_STATUS as $value => $label) {
            $exceptionStatusOptions[] = [
                'value' => $value,
                'label' => __($label),
            ];
        }

        $data = [
            [
                'note' => '館長姓名',
                'type' => 'multiselect',
                'label' => __('Principal Curator'),
                'name' => 'sales_person',
                'placeholder' => __('Please select %1', __('Curator name')),
                'options' => [],
                'permissions' => $this->_authorization->isAllowed('HotaiConnected_FinancialReconciliation::approval_functionality_filter_sales_person'),
            ],
            [
                'note' => '申請時間(起/訖)',
                'type' => 'date-range',
                'label' => __('Application Date (From-To)'),
                'name' => 'application_date',
                'date_from' => [
                    'name' => 'from_date',
                    'placeholder' => 'YYYY-MM-DD',
                    'validate' => false,
                    'required' => false,
                    'msg_required' => 'This is a required field.',
                ],
                'date_to' => [
                    'name' => 'to_date',
                    'placeholder' => 'YYYY-MM-DD',
                    'validate' => false,
                    'required' => false,
                    'msg_required' => 'This is a required field.',
                ],
                'permissions' => $this->_authorization->isAllowed('HotaiConnected_FinancialReconciliation::approval_functionality_filter_application_date'),
            ],
            [
                'note' => '審核結果',
                'type' => 'multiselect',
                'label' => __('Approval Result'),
                'name' => 'exception_status',
                'placeholder' => __('Please select %1', __('Approval Result')),
                'options' => $exceptionStatusOptions,
                'permissions' => $this->_authorization->isAllowed('HotaiConnected_FinancialReconciliation::approval_functionality_filter_exception_status'),
            ],
            [
                'note' => '結帳批次',
                'type' => 'text',
                'label' => __('Checkout Batch'),    
                'name' => 'batch_num',
                'placeholder' => __('Please enter %1', __('Checkout Batch')),
                'maxlength' => 255,
                'permissions' => $this->_authorization->isAllowed('HotaiConnected_FinancialReconciliation::approval_functionality_filter_batch_num'),
            ],
            [
                'note' => '特約商名稱',
                'type' => 'multiselect',
                'label' => __('Authorized Dealer Name'),
                'name' => 'shop_title',
                'placeholder' => __('Please select %1', __('Authorized Dealer Name')),
                'options' => [],
                'permissions' => $this->_authorization->isAllowed('HotaiConnected_FinancialReconciliation::approval_functionality_filter_shop_title'),
            ],
            [
                'note' => '特約商代號',
                'type' => 'multiselect',
                'label' => __('Authorized Dealer Code'),
                'name' => 'seller_code',
                'placeholder' => __('Please select %1', __('Authorized Dealer Code')),
                'options' => [],
                'permissions' => $this->_authorization->isAllowed('HotaiConnected_FinancialReconciliation::approval_functionality_filter_seller_code'),
            ],
        ];
    
        // 過濾：只返回 permissions = true 的項目
        return $this->getItemsByPermissions($data);
    }

    /**
     * Get batch actions configuration
     *
     * @return array
     */
    protected function getBatchActions()
    {
        // 先檢查父資源權限，如果沒有權限則直接返回空陣列
        if (!$this->_authorization->isAllowed('HotaiConnected_FinancialReconciliation::approval_functionality_actions')) {
            return [];
        }

        // 下拉選單
        $data = [
            [
                'value' => 'batch_approve',
                'label' => __('Batch Approve'),
                'permissions' => $this->_authorization->isAllowed('HotaiConnected_FinancialReconciliation::review_function_approvement')
                              && $this->_authorization->isAllowed('HotaiConnected_FinancialReconciliation::approval_functionality_batch_approve'),
            ],
            [
                'value' => 'batch_reject',
                'label' => __('Batch Reject'),
                'permissions' => $this->_authorization->isAllowed('HotaiConnected_FinancialReconciliation::review_function_approvement')
                              && $this->_authorization->isAllowed('HotaiConnected_FinancialReconciliation::approval_functionality_batch_reject'),
            ],
            [
                'value' => 'download_report',
                'label' => __('Download %1', __('Report')),
                'permissions' => $this->_authorization->isAllowed('HotaiConnected_FinancialReconciliation::review_function_export'),
            ]
        ];

        return $this->getItemsByPermissions($data);
    }

    /**
     * Filter items by permissions
     *
     * @param array $data Array of items with 'permissions' key
     * @return array Filtered array containing only items with permissions = true
     */
    protected function getItemsByPermissions(array $data): array
    {
        $result = [];
        foreach ($data as $item) {
            if (isset($item['permissions']) && $item['permissions'] === true) {
                $result[] = $item;
            }
        }

        return $result;
    }

    /**
     * Check if user has permissions to access this controller
     *
     * @return bool
     */
    protected function _isAllowed()
    {
        return $this->_authorization->isAllowed('HotaiConnected_FinancialReconciliation::review_function') 
            && $this->_authorization->isAllowed('HotaiConnected_FinancialReconciliation::review_function_list')
            && $this->_authorization->isAllowed('HotaiConnected_FinancialReconciliation::approval_functionality_filter');
    }
}
