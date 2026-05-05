<?php

declare(strict_types=1);

namespace HotaiConnected\CheckoutManagement\Controller\Adminhtml\CheckoutArea;

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
     * Index action for Checkout Area
     *
     * @return \Magento\Framework\Controller\ResultInterface
     */
    public function execute()
    {
        /** @var \Magento\Backend\Model\View\Result\Page $resultPage */
        $resultPage = $this->resultPageFactory->create();

        $resultPage->getConfig()->getTitle()->prepend(__('Checkout Area'));

        // 取得 filtersData、batchActions 和 actionsData 並傳遞給 Block
        $filtersData = $this->getFiltersData();
        $batchActions = $this->getBatchActions();
        $actionsData = $this->getActionsData();
        // 新增廠商發票權限
        $canEditInvoice = $this->_authorization->isAllowed('HotaiConnected_FinancialReconciliation::checkout_area_invoice_insert');
        // 例外授權子選單權限
        $canExceptionAuthorizationSubTable = $this->_authorization->isAllowed('HotaiConnected_FinancialReconciliation::checkout_area_exception_select');

        $block = $resultPage->getLayout()->getBlock('checkout.management.checkout.index');
        if ($block) {
            $block->setData('filters_data', $filtersData);
            $block->setData('batch_actions', $batchActions);
            $block->setData('actions_data', $actionsData);
            $block->setData('can_edit_invoice', $canEditInvoice);
            $block->setData('can_exception_authorization_subtable', $canExceptionAuthorizationSubTable);
        }

        return $resultPage;
    }

    /**
     * Get batch actions configuration
     *
     * @return array
     */
    protected function getBatchActions()
    {
        // 先檢查父資源權限，如果沒有權限則直接返回空陣列
        if (!$this->_authorization->isAllowed('HotaiConnected_FinancialReconciliation::checkout_batch_actions')) {
            return [];
        }

        // 下拉選單
        $data = [
            [
                // 下載廠商對帳單
                'value' => 'download_vendor_statement',
                'label' => __('Download Vendor Statement'),
                'permissions' => $this->_authorization->isAllowed('HotaiConnected_FinancialReconciliation::checkout_area_detail_export'),
            ],
            [
                // 下載月結總表
                'value' => 'download_monthly_summary',
                'label' => __('Download Monthly Summary'),
                'permissions' => $this->_authorization->isAllowed('HotaiConnected_FinancialReconciliation::checkout_area_summarize'),
            ],
            [
                // 解鎖結帳
                'value' => 'unlock_checkout',
                'label' => __('Unlock Checkout'),
                'permissions' => $this->_authorization->isAllowed('HotaiConnected_FinancialReconciliation::checkout_area_delete'),
            ],
            [
                // 寄出對帳單
                'value' => 'send_statement',
                'label' => __('Send Statement'),
                'permissions' => $this->_authorization->isAllowed('HotaiConnected_FinancialReconciliation::checkout_area_detail_send'),
            ],
            [
                'value' => 'exception_authorization',
                'label' => __('Exception Authorization'),
                'permissions' => $this->_authorization->isAllowed('HotaiConnected_FinancialReconciliation::checkout_area_exception_insert'),
            ]
        ];

        return $this->getItemsByPermissions($data);
    }

    /**
     * Get filters data configuration
     *
     * @return array
     */
    protected function getFiltersData()
    {
        // 先檢查父資源權限，如果沒有權限則直接返回空陣列
        if (!$this->_authorization->isAllowed('HotaiConnected_FinancialReconciliation::checkout_area_view')
            || !$this->_authorization->isAllowed('HotaiConnected_FinancialReconciliation::checkout_area_filter')) {
            return [];
        }

        $data = [
            [
                'note' => '結帳批次',
                'type' => 'text',
                'label' => __('Checkout Batch'),
                'name' => 'batch_num',
                'placeholder' => __('Please enter %1', __('Checkout Batch')) . __('Number'),
                'maxlength' => 20,
                'permissions' => $this->_authorization->isAllowed('HotaiConnected_FinancialReconciliation::checkout_area_filter_batch_num'),
            ],
            [
                'note' => '館長姓名',
                'type' => 'multiselect',
                'label' => __('Principal Curator'),
                'name' => 'sales_person',
                'placeholder' => __('Please select %1', __('Curator name')),
                'options' => [],
                'permissions' => $this->_authorization->isAllowed('HotaiConnected_FinancialReconciliation::checkout_area_filter_sales_person'),
            ],
            [
                'note' => '特約商名稱',
                'type' => 'multiselect',
                'label' => __('Authorized Dealer Name'),
                'name' => 'shop_title',
                'placeholder' => __('Please select %1', __('Authorized Dealer Name')),
                'options' => [],
                'permissions' => $this->_authorization->isAllowed('HotaiConnected_FinancialReconciliation::checkout_area_filter_shop_title'),
            ],
            [
                'note' => '特約商代號',
                'type' => 'multiselect',
                'label' => __('Authorized Dealer Code'),
                'name' => 'seller_code',
                'placeholder' => __('Please select %1', __('Authorized Dealer Code')),
                'options' => [],
                'permissions' => $this->_authorization->isAllowed('HotaiConnected_FinancialReconciliation::checkout_area_filter_seller_code'),
            ],
            [
                'note' => '訂單結帳序號異動日(起/訖)',
                'type' => 'date-range',
                'label' => __('Order Checkout Number Modification Date (From-To)'),
                'name' => 'checkout_date',
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
                'permissions' => $this->_authorization->isAllowed('HotaiConnected_FinancialReconciliation::checkout_area_filter_checkout_date'),
            ],
            [
                'note' => '資料來源',
                'type' => 'multiselect',
                'label' => __('Resource Source'),
                'name' => 'data_source',
                'placeholder' => __('Please select %1', __('Resource Source')),
                'options' => $this->getDataSourceOptions(),
                'permissions' => $this->_authorization->isAllowed('HotaiConnected_FinancialReconciliation::checkout_area_filter_data_source'),
            ],
            [
                'note' => '票券狀態',
                'type' => 'multiselect',
                'label' => __('Ticket Status'),
                'name' => 'ticket_status',
                'placeholder' => __('Please select %1', __('Ticket Status')),
                'options' => [],
                'permissions' => $this->_authorization->isAllowed('HotaiConnected_FinancialReconciliation::checkout_area_filter_ticket_status'),
            ],
            [
                'note' => '商品物流狀態',
                'type' => 'multiselect',
                'label' => __('Product Shipping Status'),
                'name' => 'shipping_status',
                'placeholder' => __('Please select %1', __('Product Shipping Status')),
                'options' => [],
                'permissions' => $this->_authorization->isAllowed('HotaiConnected_FinancialReconciliation::checkout_area_filter_shipping_status'),
            ],
            [
                'note' => '發票狀態',
                'type' => 'multiselect',
                'label' => __('Invoice Status'),
                'name' => 'invoice_status',
                'placeholder' => __('Please select %1', __('Invoice Status')),
                'options' => [],
                'permissions' => $this->_authorization->isAllowed('HotaiConnected_FinancialReconciliation::checkout_area_filter_invoice_status'),
            ],
            [
                'note' => '統一編號',
                'type' => 'text',
                'label' => __('Tax ID Number'),
                'name' => 'tax_id',
                'placeholder' => __('Enter %1', __('Tax ID Number')),
                'maxlength' => 8,
                'permissions' => $this->_authorization->isAllowed('HotaiConnected_FinancialReconciliation::checkout_area_filter_tax_id'),
            ],
            [
                'note' => '廠商發票號碼',
                'type' => 'text',
                'label' => __('Vendor Invoice Number'),
                'name' => 'invoice_number',
                'placeholder' => __('Enter %1', __('Vendor Invoice Number')),
                'maxlength' => 10,
                'permissions' => $this->_authorization->isAllowed('HotaiConnected_FinancialReconciliation::checkout_area_filter_invoice_number'),
            ]
        ];

        // 過濾：只返回 permissions = true 的項目
        return $this->getItemsByPermissions($data);
    }

    /**
     * Get data source options configuration
     *
     * @return array
     */
    protected function getDataSourceOptions()
    {
        // 先檢查父資源權限，如果沒有權限則直接返回空陣列
        if (!$this->_authorization->isAllowed('HotaiConnected_FinancialReconciliation::checkout_area_filter_data_source')) {
            return [];
        }

        $data = [
            [
                // 系統歷程(結帳紀錄)
                'label' => __('Audit Trail'),
                'value' => 1,
                'permissions' => true,
            ],
            [
                // 例外授權
                'label' => __('Exception Authorization'),
                'value' => 2,
                'permissions' => $this->_authorization->isAllowed('HotaiConnected_FinancialReconciliation::checkout_area_exception_select')
            ],
        ];

        return $this->getItemsByPermissions($data);
    }

    /**
     * Get actions data configuration
     *
     * @return array
     */
    protected function getActionsData()
    {
        // 先檢查父資源權限，如果沒有權限則直接返回空陣列
        if (!$this->_authorization->isAllowed('HotaiConnected_FinancialReconciliation::checkout_area_actions')) {
            return [];
        }

        $data = [
            [
                'id' => 'create_batch',
                'label' => __('Create Monthly Checkout Batch'),
                'url' => $this->getUrl('checkout_management/checkoutarea/create'),
                'permissions' => $this->_authorization->isAllowed('HotaiConnected_FinancialReconciliation::checkout_area_insert'),
            ]
        ];

        // 過濾：只返回 permissions = true 的項目
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
        return $this->_authorization->isAllowed('HotaiConnected_FinancialReconciliation::finance_automation') 
            && $this->_authorization->isAllowed('HotaiConnected_FinancialReconciliation::checkout_area_view') 
            && $this->_authorization->isAllowed('HotaiConnected_FinancialReconciliation::checkout_area_filter');
    }
}

