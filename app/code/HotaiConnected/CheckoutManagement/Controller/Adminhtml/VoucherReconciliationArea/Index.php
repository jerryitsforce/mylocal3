<?php

declare(strict_types=1);

namespace HotaiConnected\CheckoutManagement\Controller\Adminhtml\VoucherReconciliationArea;

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

        $resultPage->getConfig()->getTitle()->prepend(__('Voucher Reconciliation Area'));

        // 取得 filtersData 和 actionsData 並傳遞給 Block
        $filtersData = $this->getFiltersData();
        $batchActions = $this->getBatchActions();
        $block = $resultPage->getLayout()->getBlock('voucher_reconciliation_area_content');
        if ($block) {
            $block->setData('filters_data', $filtersData);
            $block->setData('batch_actions', $batchActions);
        }

        return $resultPage;
    }

    /**
     * Get actions data configuration
     *
     * @return array
     */
    protected function getBatchActions()
    {
        // 先檢查父資源權限，如果沒有權限則直接返回空陣列
        if (!$this->_authorization->isAllowed('HotaiConnected_FinancialReconciliation::voucher_reconciliation_area_batch_actions')
            || !$this->_authorization->isAllowed('HotaiConnected_FinancialReconciliation::ticket_reconciliation_list')
            || !$this->_authorization->isAllowed('HotaiConnected_FinancialReconciliation::ticket_reconciliation_export')
        ) {
            return [];
        }

        $data = [
            [
                'value' => 'event',
                'label' => __('Ball Pit Ticket Redemption Report'),
                'permissions' => $this->_authorization->isAllowed('HotaiConnected_FinancialReconciliation::voucher_reconciliation_area_batch_actions_event'),
            ],
            [
                'value' => 'ticket',
                'label' => __('2.0 Standard Ticket Redemption Report'),
                'permissions' => $this->_authorization->isAllowed('HotaiConnected_FinancialReconciliation::voucher_reconciliation_area_batch_actions_ticket'),
            ]
        ];

        // 過濾：只返回 permissions = true 的項目
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
        if (!$this->_authorization->isAllowed('HotaiConnected_FinancialReconciliation::ticket_reconciliation_list')
            || !$this->_authorization->isAllowed('HotaiConnected_FinancialReconciliation::voucher_reconciliation_area_filter')) {
            return [];
        }

        $data = [
            [
                'note' => '訂單成立時間(起/訖)',
                'type' => 'date-range',
                'label' => __('%1 (From-To)', __('Order Creation Date Range')),
                'name' => 'checkout_date',
                'date_from' => [
                    'name' => 'order_created_from',
                    'placeholder' => 'YYYY-MM-DD',
                    'validate' => false,
                    'required' => false,
                    'msg_required' => 'This is a required field.',
                ],
                'date_to' => [
                    'name' => 'order_created_to',
                    'placeholder' => 'YYYY-MM-DD',
                    'validate' => false,
                    'required' => false,
                    'msg_required' => 'This is a required field.',
                ],
                'permissions' => $this->_authorization->isAllowed('HotaiConnected_FinancialReconciliation::voucher_reconciliation_area_filter_type_ticket')
                              && $this->_authorization->isAllowed('HotaiConnected_FinancialReconciliation::voucher_reconciliation_area_filter_order_creation_date'),
            ],
            [
                'note' => '門市名稱',
                'type' => 'multiselect',
                'label' => __('Shop Name'),
                'name' => 'shop_title',
                'placeholder' => __('Please select %1', __('Shop Name')),
                'options' => [],
                'permissions' => $this->_authorization->isAllowed('HotaiConnected_FinancialReconciliation::voucher_reconciliation_area_filter_shop_title'),
            ],
            [
                'note' => '票券狀態',
                'type' => 'multiselect',
                'label' => __('Ticket Status'),
                'name' => 'ticket_status',
                'placeholder' => __('Please select %1', __('Ticket Status')),
                'options' => [],
                'permissions' => $this->_authorization->isAllowed('HotaiConnected_FinancialReconciliation::voucher_reconciliation_area_filter_ticket_status'),
            ],
            [
                'note' => '報表歸屬',
                'type' => 'select',
                'label' => __('Report Attribution'),
                'name' => 'type',
                'placeholder' => __('Please select %1', __('Report Attribution')),
                'options' => $this->getItemsByPermissions([
                    [
                        'value' => 'event',
                        'label' => __('Ball Pit Ticket Redemption Report'),
                        'permissions' => $this->_authorization->isAllowed('HotaiConnected_FinancialReconciliation::voucher_reconciliation_area_filter_type_event'),
                    ],
                    [
                        'value' => 'ticket',
                        'label' => __('2.0 Standard Ticket Redemption Report'),
                        'permissions' => $this->_authorization->isAllowed('HotaiConnected_FinancialReconciliation::voucher_reconciliation_area_filter_type_ticket'),
                    ],
                ]),
                'validate' => true,
                'required' => true,
                'msg_required' => __('This is a required field.'),
                'permissions' => $this->_authorization->isAllowed('HotaiConnected_FinancialReconciliation::voucher_reconciliation_area_filter_type'),
            ],
            [
                'note' => '訂單狀態',
                'type' => 'multiselect',
                'label' => __('Order Status'),
                'name' => 'order_status',
                'placeholder' => __('Please select %1', __('Order Status')),
                'options' => [],
                'permissions' => $this->_authorization->isAllowed('HotaiConnected_FinancialReconciliation::voucher_reconciliation_area_filter_order_status'),
            ],
            [
                'note' => '票券序號',
                'type' => 'text',
                'label' => __('Ticket Serial Number'),
                'name' => 'serial_number',
                'placeholder' => __('Enter %1', __('Ticket Serial Number')),
                'permissions' => $this->_authorization->isAllowed('HotaiConnected_FinancialReconciliation::voucher_reconciliation_area_filter_serial_number'),
            ],
            [
                'note' => '核銷到期日',
                'type' => 'date-range',
                'label' => __('Verification Expiration Date (From-To)'),
                'name' => 'verification_expiration_date',
                'date_from' => [
                    'name' => 'use_start_time',
                    'placeholder' => 'YYYY-MM-DD',
                    'validate' => false,
                    'required' => false,
                    'msg_required' => 'This is a required field.',
                ],
                'date_to' => [
                    'name' => 'use_end_time',
                    'placeholder' => 'YYYY-MM-DD',
                    'validate' => false,
                    'required' => false,
                    'msg_required' => 'This is a required field.',
                ],
                'permissions' => $this->_authorization->isAllowed('HotaiConnected_FinancialReconciliation::voucher_reconciliation_area_filter_verification_expiration_date'),
            ],
            [
                'note' => '核銷日期',
                'type' => 'date-range',
                'label' => __('%1 (From-To)', __('Verification Date')),
                'name' => 'verification_date',
                'date_from' => [
                    'name' => 'redeemed_from',
                    'placeholder' => 'YYYY-MM-DD',
                    'validate' => false,
                    'required' => false,
                    'msg_required' => 'This is a required field.',
                ],
                'date_to' => [
                    'name' => 'redeemed_to',
                    'placeholder' => 'YYYY-MM-DD',
                    'validate' => false,
                    'required' => false,
                    'msg_required' => 'This is a required field.',
                ],
                'permissions' => $this->_authorization->isAllowed('HotaiConnected_FinancialReconciliation::voucher_reconciliation_area_filter_verification_date'),
            ],
            [
                'note' => '訂單結帳序號異動日',
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
                'permissions' => $this->_authorization->isAllowed('HotaiConnected_FinancialReconciliation::voucher_reconciliation_area_filter_type_ticket')
                              && $this->_authorization->isAllowed('HotaiConnected_FinancialReconciliation::voucher_reconciliation_area_filter_checkout_date'),
            ],
            [
                'note' => '球池建立日',
                'type' => 'date-range',
                'label' => __('%1 (From-To)', __('Ball Pit Creation Date')),
                'name' => 'ball_pit_date',
                'date_from' => [
                    'name' => 'event_created_from',
                    'placeholder' => 'YYYY-MM-DD',
                    'validate' => false,
                    'required' => false,
                    'msg_required' => 'This is a required field.',
                ],
                'date_to' => [
                    'name' => 'event_created_to',
                    'placeholder' => 'YYYY-MM-DD',
                    'validate' => false,
                    'required' => false,
                    'msg_required' => 'This is a required field.',
                ],
                'permissions' => $this->_authorization->isAllowed('HotaiConnected_FinancialReconciliation::voucher_reconciliation_area_filter_type_event') 
                              &&$this->_authorization->isAllowed('HotaiConnected_FinancialReconciliation::voucher_reconciliation_area_filter_ball_pit_date'),
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
        return $this->_authorization->isAllowed('HotaiConnected_FinancialReconciliation::ticket_reconciliation')
            && $this->_authorization->isAllowed('HotaiConnected_FinancialReconciliation::ticket_reconciliation_list')
            && $this->_authorization->isAllowed('HotaiConnected_FinancialReconciliation::voucher_reconciliation_area_filter');
    }
}
