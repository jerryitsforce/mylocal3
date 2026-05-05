<?php

declare(strict_types=1);

namespace Branch8\HifiSalesReport\Controller\Adminhtml\Display;

use Magento\Backend\App\Action;
use Branch8\HifiSalesReport\Model\HifiSalesReportRecordRepository;
use Branch8\HifiSalesReport\Helper\Common as CommonHelper;

class MoneyDiffChecker extends Action
{
    /** @var HifiSalesReportRecordRepository */
    protected $hifiSalesReportRecordRepository;

    /** @var CommonHelper */
    protected $commonHelper;

    public function __construct(
        HifiSalesReportRecordRepository $hifiSalesReportRecordRepository,
        CommonHelper $commonHelper,
        \Magento\Backend\App\Action\Context $context
    ) {
        $this->hifiSalesReportRecordRepository = $hifiSalesReportRecordRepository;
        $this->commonHelper                    = $commonHelper;

        parent::__construct($context);
    }

    /**
     * Index action
     *
     * @return \Magento\Framework\View\Result\Page
     */
    public function execute()
    {
        $record          = $this->hifiSalesReportRecordRepository->get((int) $this->_request->getParam('record_id'));
        $mainFileContent = $this->commonHelper->getFileContent(
            "original_main_file",
            $record->getOriginalMainFileName()
        );

        $detailFileContent = $this->commonHelper->getFileContent(
            "original_detail_file",
            $record->getOriginalDetailFileName()
        );

        $headerRow    = array_shift($mainFileContent);
        $totalRow     = array_pop($mainFileContent);
        $mainFileSum  = [];
        $mainFileData = [];
        foreach ($mainFileContent as $row) {
            $id    = $row[\Branch8\HifiSalesReport\Controller\Adminhtml\Create\Submit::MAIN_FILE_INDEX_CHILD_ORDER_NUMBER];
            $money = (int) $row[\Branch8\HifiSalesReport\Controller\Adminhtml\Create\Submit::MAIN_FILE_INDEX_INVOICE_INCL_TAX];
            if (!isset($mainFileSum[$id])) {
                $mainFileSum[$id] = 0;
            }
            $mainFileSum[$id] += $money;
            $mainFileData[$id] = [
                "invoiceNumber" => $row[\Branch8\HifiSalesReport\Controller\Adminhtml\Create\Submit::MAIN_FILE_INDEX_INVOICE_NUMBER],
                "interfaceId"   => $row[\Branch8\HifiSalesReport\Controller\Adminhtml\Create\Submit::MAIN_FILE_INDEX_SALES_INTERFACE_ID],
            ];
        }

        $headerRow     = array_shift(array: $detailFileContent);
        $totalRow      = array_pop($detailFileContent);
        $detailFileSum = [];
        foreach ($detailFileContent as $row) {
            $id    = $row[\Branch8\HifiSalesReport\Controller\Adminhtml\Create\Submit::DETAIL_FILE_INDEX_CHILD_ORDER_NUMBER];
            $money = (int) $row[\Branch8\HifiSalesReport\Controller\Adminhtml\Create\Submit::DETAIL_FILE_INDEX_INVOICE_INCL_TAX];
            if (!isset($detailFileSum[$id])) {
                $detailFileSum[$id] = 0;
            }
            $detailFileSum[$id] += $money;
        }

        echo "檢測主檔和明細檔含稅金額對不起來的紀錄:";
        echo '<br>';
        echo '<br>';

        foreach ($mainFileSum as $id => $row) {
            if ($mainFileSum[$id] != $detailFileSum[$id]) {
                echo "訂單編號: " . $id;
                echo "<br>";
                echo "開立發票編號: " . $mainFileData[$id]["invoiceNumber"];
                echo "<br>";
                echo "銷貨介面主檔ID: " . $mainFileData[$id]["interfaceId"];
                echo "<br>";
                echo "main money: " . $mainFileSum[$id];
                echo "<br>";
                echo "detail money: " . $detailFileSum[$id];
                echo "<br>";
                echo "<br>";
            }
        }
        die();
    }
}
