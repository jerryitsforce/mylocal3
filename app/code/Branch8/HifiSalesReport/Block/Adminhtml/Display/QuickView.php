<?php

declare(strict_types=1);

namespace Branch8\HifiSalesReport\Block\Adminhtml\Display;

use Magento\Framework\View\Element\Template;
use Branch8\HifiSalesReport\Model\HifiSalesReportSubRecordRepository;
use Branch8\HifiSalesReport\Model\HifiSalesReportRecordRepository;
use Branch8\HifiSalesReport\Helper\Common as CommonHelper;
use Branch8\HifiSalesReport\Controller\Adminhtml\Create\Submit;
use Branch8\HifiSalesReport\Helper\Report;

class QuickView extends Template
{
    /** @var HifiSalesReportRecordRepository */
    protected $hifiSalesReportRecordRepository;

    /** @var HifiSalesReportSubRecordRepository */
    protected $hifiSalesReportSubRecordRepository;

    /** @var CommonHelper */
    protected $commonHelper;

    public $record;
    public $mainFileContent;
    public $detailFileContent;
    public $orderItemProductSummaryData;
    public $orderItemPointSummaryData;
    public $orderItemTotalSummaryData;
    public $orderCreditSummaryData;
    public $orderTotalSummaryData;

    public function __construct(
        HifiSalesReportRecordRepository $hifiSalesReportRecordRepository,
        HifiSalesReportSubRecordRepository $hifiSalesReportSubRecordRepository,
        CommonHelper $commonHelper,
        \Magento\Framework\View\Element\Template\Context $context,
        array $data = []
    ) {
        $this->hifiSalesReportRecordRepository    = $hifiSalesReportRecordRepository;
        $this->hifiSalesReportSubRecordRepository = $hifiSalesReportSubRecordRepository;
        $this->commonHelper                       = $commonHelper;

        parent::__construct($context, $data);
    }

    protected function _prepareLayout(): self
    {
        $record_id    = $this->getRequest()->getParam("record_id");
        $this->record = $this->hifiSalesReportRecordRepository->get((int) $record_id);

        $this->mainFileContent = $this->commonHelper->getFileContent(
            CommonHelper::FILE_TYPE_MODIFIED_MAIN_FILE,
            $this->record->getModifiedMainFileName()
        );

        $this->detailFileContent = $this->commonHelper->getFileContent(
            CommonHelper::FILE_TYPE_MODIFIED_DETAIL_FILE,
            $this->record->getModifiedDetailFileName()
        );

        $subRecordId = $this->getRequest()->getParam("sub_record_id");
        if (!empty($subRecordId)) {
            $subRecord = $this->hifiSalesReportSubRecordRepository->get((int) $subRecordId);

            $this->mainFileContent = $this->commonHelper->getFilteredMainFileContent(
                $this->mainFileContent,
                $subRecord
            );

            $this->detailFileContent = $this->commonHelper->getFilteredDetailFileContent(
                $this->detailFileContent,
                $subRecord
            );
        }

        $this->orderItemProductSummaryData = $this->getOrderItemProductSummaryData();
        $this->orderItemPointSummaryData   = $this->getOrderItemPointSummaryData();
        $this->orderItemTotalSummaryData   = $this->getOrderItemTotalSummaryData();

        $this->orderCreditSummaryData = $this->getOrderCreditSummaryData();
        $this->orderTotalSummaryData  = $this->getOrderTotalSummaryData();

        return parent::_prepareLayout();
    }

    /**
     * 從結帳訂單明細檔獲取產品販售的合計資訊
     * @return array
     */
    protected function getOrderItemProductSummaryData(): array
    {
        $totalSum           = 0;
        $taxSum             = 0;
        $totalWithoutTaxSum = 0;

        foreach ($this->detailFileContent as $orderItemData) {
            if ($orderItemData[Submit::DETAIL_FILE_INDEX_ACCOUNTING_GRADE] != Submit::ACCOUNTING_GRADE_FOR_PRODUCT) {
                continue;
            }

            $totalSum += (float) $orderItemData[Submit::DETAIL_FILE_INDEX_INVOICE_INCL_TAX];
            $taxSum += (float) $orderItemData[Submit::DETAIL_FILE_INDEX_INVOICE_TAX];
            $totalWithoutTaxSum += (float) $orderItemData[Submit::DETAIL_FILE_INDEX_INVOICE_EXCL_TAX];
        }

        return [
            "title"              => Submit::ACCOUNTING_GRADE_FOR_PRODUCT,
            "totalSum"           => $totalSum,
            "taxSum"             => $taxSum,
            "totalWithoutTaxSum" => $totalWithoutTaxSum,
        ];
    }

    /**
     * 從結帳訂單明細檔獲取點數使用的合計資訊
     * @return array
     */
    protected function getOrderItemPointSummaryData(): array
    {
        $totalSum           = 0;
        $taxSum             = 0;
        $totalWithoutTaxSum = 0;

        foreach ($this->detailFileContent as $orderItemData) {
            if ($orderItemData[Submit::DETAIL_FILE_INDEX_ACCOUNTING_GRADE] != Submit::ACCOUNTING_GRADE_FOR_POINT) {
                continue;
            }

            $totalSum += (float) $orderItemData[Submit::DETAIL_FILE_INDEX_INVOICE_INCL_TAX];
            $taxSum += (float) $orderItemData[Submit::DETAIL_FILE_INDEX_INVOICE_TAX];
            $totalWithoutTaxSum += (float) $orderItemData[Submit::DETAIL_FILE_INDEX_INVOICE_EXCL_TAX];
        }

        return [
            "title"              => Submit::ACCOUNTING_GRADE_FOR_POINT,
            "totalSum"           => $totalSum,
            "taxSum"             => $taxSum,
            "totalWithoutTaxSum" => $totalWithoutTaxSum,
        ];
    }

    /**
     * 從結帳訂單明細檔獲取最後的合計資訊
     * @return array
     */
    protected function getOrderItemTotalSummaryData(): array
    {
        return [
            "totalSum"           => $this->orderItemProductSummaryData["totalSum"] + $this->orderItemPointSummaryData["totalSum"],
            "taxSum"             => $this->orderItemProductSummaryData["taxSum"] + $this->orderItemPointSummaryData["taxSum"],
            "totalWithoutTaxSum" => $this->orderItemProductSummaryData["totalWithoutTaxSum"] + $this->orderItemPointSummaryData["totalWithoutTaxSum"],
        ];
    }

    /**
     * 從結帳訂單主檔獲取"付款方式: 信用卡"的合計資訊
     * @return array
     */
    protected function getOrderCreditSummaryData(): array
    {
        $total = 0;

        foreach ($this->mainFileContent as $orderData) {
            if ($orderData[Submit::MAIN_FILE_INDEX_PAYMENT_TITLE] != Report::PAYMENT_METHOD_CREDIT_CARD) {
                continue;
            }

            $total += (float) $orderData[Submit::MAIN_FILE_INDEX_INVOICE_INCL_TAX];
        }

        return [
            "title" => Report::PAYMENT_METHOD_CREDIT_CARD,
            "total" => $total
        ];
    }

    /**
     * 從結帳訂單主檔獲取最後的合計資訊
     * @return array
     */
    protected function getOrderTotalSummaryData(): array
    {
        return [
            "total" => $this->orderCreditSummaryData["total"],
        ];
    }
}
