<?php

declare(strict_types=1);

namespace HotaiConnected\CheckoutManagement\Controller\Adminhtml\ExportOrderCheckoutData;

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

        $resultPage->getConfig()->getTitle()->prepend(__('Export Order Checkout Data'));

        // 取得 filtersData 和 actionsData 並傳遞給 Block
        $filtersData = $this->getFiltersData();
        $actionsData = $this->getActionsData();
        $block = $resultPage->getLayout()->getBlock('export_order_checkout_data_content');
        if ($block) {
            $block->setData('filters_data', $filtersData);
            $block->setData('actions_data', $actionsData);
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
        if (!$this->_authorization->isAllowed('HotaiConnected_FinancialReconciliation::order_checkout_export_list')
            || !$this->_authorization->isAllowed('HotaiConnected_FinancialReconciliation::export_order_checkout_data_filter')) {
            return [];
        }

        $data = [
            [
                'note' => '訂單結帳序號異動日(起/訖)',
                'type' => 'date-range',
                'label' => __('Order Checkout Number Modification Date (From-To)'),
                'name' => 'checkout_date',
                'date_from' => [
                    'name' => 'invoice_created_from',
                    'placeholder' => 'YYYY-MM-DD',
                    'validate' => false,
                    'required' => false,
                    'msg_required' => 'This is a required field.',
                ],
                'date_to' => [
                    'name' => 'invoice_created_to',
                    'placeholder' => 'YYYY-MM-DD',
                    'validate' => false,
                    'required' => false,
                    'msg_required' => 'This is a required field.',
                ],
                'permissions' => $this->_authorization->isAllowed('HotaiConnected_FinancialReconciliation::export_order_checkout_data_filter_checkout_date'),
            ],
            [
                'note' => '特約商名稱',
                'type' => 'multiselect',
                'label' => __('Authorized Dealer Name'),
                'name' => 'shop_title',
                'placeholder' => __('Please select %1', __('Authorized Dealer Name')),
                'options' => [],
                'permissions' => $this->_authorization->isAllowed('HotaiConnected_FinancialReconciliation::export_order_checkout_data_filter_shop_title'),
            ],
            [
                'note' => '館長姓名',
                'type' => 'multiselect',
                'label' => __('Principal Curator'),
                'name' => 'sales_person',
                'placeholder' => __('Please select %1', __('Curator name')),
                'options' => [],
                'permissions' => $this->_authorization->isAllowed('HotaiConnected_FinancialReconciliation::export_order_checkout_data_filter_sales_person'),
            ]
        ];

        // 過濾：只返回 permissions = true 的項目
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
        if (!$this->_authorization->isAllowed('HotaiConnected_FinancialReconciliation::export_order_checkout_data_actions')) {
            return [];
        }

        $data = [
            [
                'id' => 'download-search-data',
                'label' => __('Download %1', __('Search Data')),
                'permissions' => $this->_authorization->isAllowed('HotaiConnected_FinancialReconciliation::order_checkout_export_download'),
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
        return $this->_authorization->isAllowed('HotaiConnected_FinancialReconciliation::order_checkout_export') 
            && $this->_authorization->isAllowed('HotaiConnected_FinancialReconciliation::order_checkout_export_list')
            && $this->_authorization->isAllowed('HotaiConnected_FinancialReconciliation::export_order_checkout_data_filter');
    }
}
