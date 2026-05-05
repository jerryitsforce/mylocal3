<?php

declare(strict_types=1);

namespace Branch8\HifiSalesReport\Controller\Adminhtml\Display;

use Magento\Backend\App\Action;
use Branch8\HifiSalesReport\Model\HifiSalesReportRecordRepository;
use Branch8\HifiSalesReport\Helper\Common as CommonHelper;
use Magento\Sales\Api\OrderRepositoryInterface as OrderRepository;
use Magento\Framework\Exception\NoSuchEntityException;
use Ecpay\Invoice\Model\ResourceModel\HotaiOrderInvoiceLogs\CollectionFactory as OrderLogCollectionFactory;

class OrderMissingChecker extends Action
{
    /** @var HifiSalesReportRecordRepository */
    protected $hifiSalesReportRecordRepository;

    /** @var CommonHelper */
    protected $commonHelper;

    /** @var OrderRepository */
    protected $orderRepository;

    /** @var OrderLogCollectionFactory */
    protected $orderLogCollectionFactory;


    public function __construct(
        HifiSalesReportRecordRepository $hifiSalesReportRecordRepository,
        CommonHelper $commonHelper,
        OrderRepository $orderRepository,
        OrderLogCollectionFactory $orderLogCollectionFactory,
        \Magento\Backend\App\Action\Context $context
    ) {
        $this->hifiSalesReportRecordRepository = $hifiSalesReportRecordRepository;
        $this->commonHelper                    = $commonHelper;
        $this->orderRepository                 = $orderRepository;
        $this->orderLogCollectionFactory       = $orderLogCollectionFactory;

        parent::__construct($context);
    }

    /**
     * Index action
     *
     * @return \Magento\Framework\View\Result\Page
     */
    public function execute()
    {
        $record = $this->hifiSalesReportRecordRepository->get((int) $this->_request->getParam('record_id'));

        $orderLogCollection = $this->orderLogCollectionFactory->create();
        $orderLogCollection->getSelect()->joinLeft(
            ['sales_order' => 'sales_order'],
            'main_table.order_id = sales_order.entity_id',
            [
                'entity_id',
            ]
        );
        $orderLogCollection
            ->addFieldToFilter(
                'main_table.created_at',
                ['gteq' => $record->getInvoiceChangeStartDate()]
            )
            ->addFieldToFilter(
                'main_table.created_at',
                ['lteq' => $record->getInvoiceChangeEndDate()]
            )
            ->addFieldToFilter(
                'sales_order.entity_id',
                ['null' => null]
            );
        $orderLogCollection->load();

        $missingOrderIds = [];
        foreach ($orderLogCollection->getItems() as $orderLog) {
            $missingOrderIds[] = $orderLog->getOrderId();
        }

        echo "從log表回頭去看sales_order表, 確認order紀錄有沒有缺失 SQL:";
        echo '<br>';
        echo $orderLogCollection->getSelect();
        echo '<br>';
        echo '<br>';

        echo "Missing Order IDs:";
        echo "<br>";
        echo json_encode($missingOrderIds);
        die();
    }
}
