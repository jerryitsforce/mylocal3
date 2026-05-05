<?php

namespace Branch8\HifiSalesReport\Helper;

use Branch8\HifiSalesReport\Model\HifiSalesReportRecordRepository as MainRecordRepository;
use Branch8\HifiSalesReport\Model\HifiSalesReportSubRecord as SubRecordModel;
use Branch8\HifiSalesReport\Model\HifiSalesReportSubRecordRepository as SubRecordRepository;
use Magento\Framework\App\ResponseInterface;
use Magento\Framework\App\Response\Http\FileFactory;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\File\Csv;
use Branch8\HifiSalesReport\Controller\Adminhtml\Create\Submit;
use Branch8\HifiSalesReport\Helper\Report as ReportHelper;

class Common
{
    const TIMEZONE = "Asia/Taipei";

    const FILE_FOLDER_PATH_ORIGINAL_MAIN_FILE   = "var/HifiSalesReport/OriginalMainFile";
    const FILE_FOLDER_PATH_MODIFIED_MAIN_FILE   = "var/HifiSalesReport/ModifiedMainFile";
    const FILE_FOLDER_PATH_ORIGINAL_DETAIL_FILE = "var/HifiSalesReport/OriginalDetailFile";
    const FILE_FOLDER_PATH_MODIFIED_DETAIL_FILE = "var/HifiSalesReport/ModifiedDetailFile";

    const FILE_TYPE_ORIGINAL_MAIN_FILE   = "original_main_file";
    const FILE_TYPE_MODIFIED_MAIN_FILE   = "modified_main_file";
    const FILE_TYPE_ORIGINAL_DETAIL_FILE = "original_detail_file";
    const FILE_TYPE_MODIFIED_DETAIL_FILE = "modified_detail_file";

    const CONFIG_PATH_PAYMENT_METHODS_BELONG_TO_CREDIT_CARD      = "hifi_sales_report/general/payment_methods_belong_to_credit_card";
    const CONFIG_PATH_PAYMENT_METHODS_BELONG_TO_CONVENIENT_STORE = "hifi_sales_report/general/payment_methods_belong_to_convenient_store";

    const DEFAULT_PAYMENT_METHODS_BELONG_TO_CREDIT_CARD = ["hotaipay"];

    const ITEM_DESC_STRING_LENGTH_LIMIT = 99;

    /** @var ScopeConfigInterface */
    protected $scopeConfig;

    /** @var FileFactory */
    protected $fileFactory;

    /** @var MainRecordRepository */
    protected $mainRecordRepository;

    /** @var SubRecordRepository */
    protected $subRecordRepository;

    /** @var ResponseInterface */
    protected $response;

    protected $csv;

    /**
     * @var \Magento\Framework\Session\SessionManagerInterface
     */
    private \Magento\Framework\Session\SessionManagerInterface $sessionManager;

    public function __construct(
        ScopeConfigInterface $scopeConfig,
        FileFactory $fileFactory,
        MainRecordRepository $mainRecordRepository,
        SubRecordRepository $subRecordRepository,
        Csv $csv,
        ResponseInterface $response,
        \Magento\Framework\Session\SessionManagerInterface $sessionManager
    ) {
        $this->scopeConfig          = $scopeConfig;
        $this->fileFactory          = $fileFactory;
        $this->mainRecordRepository = $mainRecordRepository;
        $this->subRecordRepository  = $subRecordRepository;
        $this->csv                  = $csv;
        $this->response             = $response;
        $this->sessionManager = $sessionManager;
    }

    public function getItemDescStringLengthLimit(): int
    {
        $limitSetting = $this->scopeConfig->getValue("hifi_sales_report/general/item_desc_string_length_limit");

        if (empty($limitSetting)) {
            return self::ITEM_DESC_STRING_LENGTH_LIMIT;
        }

        if (!is_numeric($limitSetting)) {
            return self::ITEM_DESC_STRING_LENGTH_LIMIT;
        }

        if ($limitSetting <= 0) {
            return self::ITEM_DESC_STRING_LENGTH_LIMIT;
        }

        return (int) $limitSetting;
    }

    /**
     * 獲取訂單檔案的絕對位置
     * @param string $fileType
     * @param string $fileName
     * @throws \Exception
     * @return string
     */
    public function getFileAbsolutePath(string $fileType, string $fileName): string
    {
        switch ($fileType) {
            case self::FILE_TYPE_ORIGINAL_MAIN_FILE:
                return BP . '/' . self::FILE_FOLDER_PATH_ORIGINAL_MAIN_FILE . "/{$fileName}";

            case self::FILE_TYPE_MODIFIED_MAIN_FILE:
                return BP . '/' . self::FILE_FOLDER_PATH_MODIFIED_MAIN_FILE . "/{$fileName}";

            case self::FILE_TYPE_ORIGINAL_DETAIL_FILE:
                return BP . '/' . self::FILE_FOLDER_PATH_ORIGINAL_DETAIL_FILE . "/{$fileName}";

            case self::FILE_TYPE_MODIFIED_DETAIL_FILE:
                return BP . '/' . self::FILE_FOLDER_PATH_MODIFIED_DETAIL_FILE . "/{$fileName}";

            default:
                throw new \Exception(__("Invalid file type given."));
        }
    }

    /**
     * 下載訂單檔案
     * @param string $fileType
     * @param string $fileName
     * @throws \Exception
     * @return \Magento\Framework\App\ResponseInterface
     */
    public function getFileDownloadResponse(string $fileType, string $fileName): ResponseInterface
    {
        $targetFolderPath = "";

        switch ($fileType) {
            case self::FILE_TYPE_ORIGINAL_MAIN_FILE:
                $targetFolderPath = self::FILE_FOLDER_PATH_ORIGINAL_MAIN_FILE;
                break;

            case self::FILE_TYPE_MODIFIED_MAIN_FILE:
                $targetFolderPath = self::FILE_FOLDER_PATH_MODIFIED_MAIN_FILE;
                break;

            case self::FILE_TYPE_ORIGINAL_DETAIL_FILE:
                $targetFolderPath = self::FILE_FOLDER_PATH_ORIGINAL_DETAIL_FILE;
                break;

            case self::FILE_TYPE_MODIFIED_DETAIL_FILE:
                $targetFolderPath = self::FILE_FOLDER_PATH_MODIFIED_DETAIL_FILE;
                break;

            default:
                throw new \Exception(__("Invalid file type given."));
        }

        $trimedFolderPath = trim($targetFolderPath, '/');
        $trimedFileName   = trim($fileName, '/');

        return $this->fileFactory->create($fileName, [
            'type'  => 'filename',
            'value' => "{$trimedFolderPath}/{$trimedFileName}",
            'rm'    => 0
        ]);
    }

    public function getCsvDownloadResponse(\Magento\Framework\App\Action\Action $controller, array $fileData, string $fileName): void
    {
        $this->response->setHttpResponseCode(200)
            ->setHeader('Pragma', 'public', true)
            ->setHeader('Cache-Control', 'must-revalidate, post-check=0, pre-check=0', true)
            ->setHeader('Content-type', 'text/csv; charset=UTF-8', true)
            ->setHeader('Content-Disposition', 'attachment; filename=' . $fileName, true)
            ->setHeader('Strict-Transport-Security', 'max-age=31536000; includeSubDomains', true);

        $this->response->clearBody();
        $this->response->sendHeaders();

        $this->sessionManager->writeClose();

        $output = fopen('php://output', 'w');

        fprintf($output, chr(0xEF) . chr(0xBB) . chr(0xBF));

        foreach ($fileData as $rowContent) {
            fputcsv($output, $rowContent);
        }

        fclose($output);

        exit(0);
    }

    /**
     * 獲取訂單檔案內容
     * @param string $fileType
     * @param string $fileName
     * @throws \Exception
     * @return array
     */
    public function getFileContent(string $fileType, string $fileName): array
    {
        $targetFolderPath = "";

        switch ($fileType) {
            case self::FILE_TYPE_ORIGINAL_MAIN_FILE:
                $targetFolderPath = self::FILE_FOLDER_PATH_ORIGINAL_MAIN_FILE;
                break;

            case self::FILE_TYPE_MODIFIED_MAIN_FILE:
                $targetFolderPath = self::FILE_FOLDER_PATH_MODIFIED_MAIN_FILE;
                break;

            case self::FILE_TYPE_ORIGINAL_DETAIL_FILE:
                $targetFolderPath = self::FILE_FOLDER_PATH_ORIGINAL_DETAIL_FILE;
                break;

            case self::FILE_TYPE_MODIFIED_DETAIL_FILE:
                $targetFolderPath = self::FILE_FOLDER_PATH_MODIFIED_DETAIL_FILE;
                break;

            default:
                throw new \Exception(__("Invalid file type given."));
        }

        $trimedFolderPath = trim($targetFolderPath, '/');
        $trimedFileName   = trim($fileName, '/');

        return $this->csv->getData(BP . "/{$trimedFolderPath}/{$trimedFileName}");
    }

    public function getFilteredMainFileContent(array $fileContentArray, SubRecordModel $subRecord): array
    {
        $invoiceChangeDate = $subRecord->getInvoiceChangeDate();
        $invoiceStatus     = $subRecord->getInvoiceStatus();

        $headerRow = array_shift($fileContentArray);
        $totalRow  = array_pop($fileContentArray);

        foreach ($fileContentArray as $index => $fileRow) {
            $dateChecker          = $fileRow[Submit::MAIN_FILE_INDEX_INVOICE_CHANGE_DATE] == $invoiceChangeDate;
            $invoiceStatusChecker = $fileRow[Submit::MAIN_FILE_INDEX_INVOICE_STATUS] == $this->getInvoiceStatusLabel((int) $invoiceStatus);

            if ($dateChecker && $invoiceStatusChecker) {
                continue;
            }

            unset($fileContentArray[$index]);
        }

        $invoiceWithTax    = 0;
        $invoiceTax        = 0;
        $invoiceWithoutTax = 0;
        foreach ($fileContentArray as $key => $fileRow) {
            $fileContentArray[$key][Submit::MAIN_FILE_INDEX_INVOICE_INCL_TAX] = (int) $fileContentArray[$key][Submit::MAIN_FILE_INDEX_INVOICE_INCL_TAX];
            $fileContentArray[$key][Submit::MAIN_FILE_INDEX_INVOICE_TAX]      = (int) $fileContentArray[$key][Submit::MAIN_FILE_INDEX_INVOICE_TAX];
            $fileContentArray[$key][Submit::MAIN_FILE_INDEX_INVOICE_EXCL_TAX] = (int) $fileContentArray[$key][Submit::MAIN_FILE_INDEX_INVOICE_EXCL_TAX];

            $invoiceWithTax += (int) $fileRow[Submit::MAIN_FILE_INDEX_INVOICE_INCL_TAX];
            $invoiceTax += (int) $fileRow[Submit::MAIN_FILE_INDEX_INVOICE_TAX];
            $invoiceWithoutTax += (int) $fileRow[Submit::MAIN_FILE_INDEX_INVOICE_EXCL_TAX];
        }

        $totalRow[Submit::MAIN_FILE_INDEX_INVOICE_INCL_TAX] = $invoiceWithTax;
        $totalRow[Submit::MAIN_FILE_INDEX_INVOICE_TAX]      = $invoiceTax;
        $totalRow[Submit::MAIN_FILE_INDEX_INVOICE_EXCL_TAX] = $invoiceWithoutTax;

        array_unshift($fileContentArray, $headerRow);
        $fileContentArray[] = $totalRow;

        return $fileContentArray;
    }

    public function getFilteredDetailFileContent(array $fileContentArray, SubRecordModel $subRecord): array
    {
        $invoiceChangeDate = $subRecord->getInvoiceChangeDate();
        $invoiceStatus     = $subRecord->getInvoiceStatus();

        $headerRow = array_shift($fileContentArray);
        $totalRow  = array_pop($fileContentArray);

        foreach ($fileContentArray as $index => $fileRow) {
            $dateChecker          = $fileRow[Submit::DETAIL_FILE_INDEX_INVOICE_CHANGE_DATE] == $invoiceChangeDate;
            $invoiceStatusChecker = $fileRow[Submit::DETAIL_FILE_INDEX_INVOICE_STATUS] == $this->getInvoiceStatusLabel((int) $invoiceStatus);

            if ($dateChecker && $invoiceStatusChecker) {
                continue;
            }

            unset($fileContentArray[$index]);
        }

        $invoiceWithTax    = 0;
        $invoiceTax        = 0;
        $invoiceWithoutTax = 0;
        foreach ($fileContentArray as $key => $fileRow) {
            $fileContentArray[$key][Submit::DETAIL_FILE_INDEX_INVOICE_INCL_TAX] = (int) $fileContentArray[$key][Submit::DETAIL_FILE_INDEX_INVOICE_INCL_TAX];
            $fileContentArray[$key][Submit::DETAIL_FILE_INDEX_INVOICE_TAX]      = (int) $fileContentArray[$key][Submit::DETAIL_FILE_INDEX_INVOICE_TAX];
            $fileContentArray[$key][Submit::DETAIL_FILE_INDEX_INVOICE_EXCL_TAX] = (int) $fileContentArray[$key][Submit::DETAIL_FILE_INDEX_INVOICE_EXCL_TAX];


            $invoiceWithTax += (int) $fileRow[Submit::DETAIL_FILE_INDEX_INVOICE_INCL_TAX];
            $invoiceTax += (int) $fileRow[Submit::DETAIL_FILE_INDEX_INVOICE_TAX];
            $invoiceWithoutTax += (int) $fileRow[Submit::DETAIL_FILE_INDEX_INVOICE_EXCL_TAX];
        }

        $totalRow[Submit::DETAIL_FILE_INDEX_INVOICE_INCL_TAX] = $invoiceWithTax;
        $totalRow[Submit::DETAIL_FILE_INDEX_INVOICE_TAX]      = $invoiceTax;
        $totalRow[Submit::DETAIL_FILE_INDEX_INVOICE_EXCL_TAX] = $invoiceWithoutTax;

        array_unshift($fileContentArray, $headerRow);
        $fileContentArray[] = $totalRow;

        return $fileContentArray;
    }

    public function getInvoiceStatusLabel(int $invoiceStatus): string
    {
        switch ($invoiceStatus) {
            case 1:
                return ReportHelper::INVOICE_STATUS_LABEL_ISSUE;

            case 2:
                return ReportHelper::INVOICE_STATUS_LABEL_CANCEL;

            case 3:
                return ReportHelper::INVOICE_STATUS_LABEL_ALLOWANCES;

            case 4:
                return ReportHelper::INVOICE_STATUS_LABEL_NO_INVOICE;

            default:
                return "Fail mapping: {$invoiceStatus}";
        }
    }

    /**
     * 取得歸屬於"信用卡"的支付方式
     * @return array
     */
    public function getPaymentMethodsBelongToCreditCard(): array
    {
        $value = $this->scopeConfig->getValue(self::CONFIG_PATH_PAYMENT_METHODS_BELONG_TO_CREDIT_CARD);

        if (empty($value)) {
            return self::DEFAULT_PAYMENT_METHODS_BELONG_TO_CREDIT_CARD;
        }

        return explode(",", $value);
    }

    /**
     * 取得歸屬於"超商收款"的支付方式
     * @return array
     */
    public function getPaymentMethodsBelongToConvenientStore(): array
    {
        $value = $this->scopeConfig->getValue(self::CONFIG_PATH_PAYMENT_METHODS_BELONG_TO_CONVENIENT_STORE);

        if (empty($value)) {
            return [];
        }

        return explode(",", $value);
    }

    /**
     * 確認主紀錄是否允許上傳
     * @param int $mainRecordId
     * @return bool
     */
    public function checkIfMainRecordAllowUpload(int $mainRecordId): bool
    {
        $mainRecord = $this->mainRecordRepository->get($mainRecordId);

        $subRecordCollection = $this->subRecordRepository->getRecordsByParentId($mainRecord->getId());

        /** @var SubRecordModel $subRecord */
        foreach ($subRecordCollection->getItems() as $subRecord) {
            if ($subRecord->getSyncStatus() == SubRecordModel::SYNC_STATUS_SYNCED) {
                return false;
            }
        }

        return true;
    }

    /**
     * 確認主紀錄是否允許刪除
     * @param int $mainRecordId
     * @return bool
     */
    public function checkIfMainRecordAllowDelete(int $mainRecordId): bool
    {
        $mainRecord = $this->mainRecordRepository->get($mainRecordId);

        $subRecordCollection = $this->subRecordRepository->getRecordsByParentId($mainRecord->getId());

        /** @var SubRecordModel $subRecord */
        foreach ($subRecordCollection->getItems() as $subRecord) {
            if ($subRecord->getSyncResult() == SubRecordModel::SYNC_RESULT_SUCCESS) {
                return false;
            }
        }

        return true;
    }
}
