<?php

declare(strict_types=1);

namespace Branch8\HifiSalesReport\Controller\Adminhtml\Upload;

use Branch8\HotaiCore\Helper\Common as HotaiCoreCommonHelper;
use Magento\Backend\App\Action;
use Branch8\HifiSalesReport\Model\HifiSalesReportRecordRepository;
use Branch8\HifiSalesReport\Helper\Common as CommonHelper;
use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\Controller\Result\Redirect;
use Magento\Framework\Message\ManagerInterface as MessageManager;
use Magento\Framework\Filesystem\Driver\File;
use Magento\Framework\Filesystem;
use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Backend\Model\Auth\Session as AuthSession;

class MainFile extends Action
{
    /** @var HotaiCoreCommonHelper */
    protected $hotaiCoreCommonHelper;

    /** @var HifiSalesReportRecordRepository */
    protected $hifiSalesReportRecordRepository;

    /** @var CommonHelper */
    protected $commonHelper;

    /** @var MessageManager */
    protected $messageManager;

    /** @var File */
    protected $file;

    /** @var Filesystem */
    protected $filesystem;

    /** @var DirectoryList */
    protected $directoryList;

    /** @var \Magento\Framework\Filesystem\Directory\WriteInterface */
    protected $directory;

    /** @var AuthSession */
    protected $authSession;

    protected $record;
    protected $currentCsvData;
    protected $uploadCsvData;
    protected $uploadFileName;
    protected $uploadFilePath;

    public function __construct(
        HotaiCoreCommonHelper $hotaiCoreCommonHelper,
        HifiSalesReportRecordRepository $hifiSalesReportRecordRepository,
        CommonHelper $commonHelper,
        MessageManager $messageManager,
        File $file,
        Filesystem $filesystem,
        DirectoryList $directoryList,
        AuthSession $authSession,
        \Magento\Backend\App\Action\Context $context
    ) {
        $this->hotaiCoreCommonHelper           = $hotaiCoreCommonHelper;
        $this->hifiSalesReportRecordRepository = $hifiSalesReportRecordRepository;
        $this->commonHelper                    = $commonHelper;
        $this->messageManager                  = $messageManager;
        $this->file                            = $file;
        $this->filesystem                      = $filesystem;
        $this->directoryList                   = $directoryList;
        $this->directory                       = $filesystem->getDirectoryWrite(DirectoryList::ROOT);
        $this->authSession                     = $authSession;

        parent::__construct($context);
    }

    public function execute()
    {
        if (!$this->checkUploadFileType()) {
            $this->messageManager->addError(__("Please upload csv file."));
            return $this->returnPreviousPage();
        }

        $this->initParameters();

        if (!$this->checkCsvRowsWithOriginal()) {
            $this->messageManager->addError(__("Csv data row or column count is not correct, please check with original file."));
            return $this->returnPreviousPage();
        }

        $this->deleteCurrentModifiedFile();

        $this->createNewFile();

        $this->updateRecord();

        $this->messageManager->addSuccess(__("Main file upload successfully."));
        return $this->returnPreviousPage();
    }

    /**
     * 檢查上傳的檔案類型
     * @return bool
     */
    protected function checkUploadFileType(): bool
    {
        $fileType = $this->getRequest()->getFiles('file')['type'];

        return $fileType == "text/csv";
    }

    /**
     * 參數初始化
     * @return void
     */
    protected function initParameters(): void
    {
        $record_id            = $this->_request->getParam('record_id');
        $this->record         = $this->hifiSalesReportRecordRepository->get((int) $record_id);
        $this->currentCsvData = $this->commonHelper->getFileContent(
            CommonHelper::FILE_TYPE_ORIGINAL_MAIN_FILE,
            $this->record->getOriginalMainFileName()
        );

        $this->uploadCsvData = $this->handleFormFile();
    }

    /**
     * 接收上傳的csv資料並處理成array
     * @return array
     */
    protected function handleFormFile(): array
    {
        $csvDataArray = [];
        $file         = $this->getRequest()->getFiles('file');

        if (empty($file['tmp_name'])) {
            return [];
        }

        $csvString = file_get_contents($file['tmp_name']);
        $csvString = $this->purifyCsvString($csvString);
        $lines     = explode(\PHP_EOL, $csvString);

        foreach ($lines as $line) {
            $csvDataArray[] = str_getcsv($line, ',', '"');
        }

        return $csvDataArray;
    }

    /**
     * 清除csv字串中可能存在的不可視字元
     * @param string $csvString
     * @return string
     */
    protected function purifyCsvString(string $csvString): string
    {
        $purifiedString = ltrim($csvString, "\xEF\xBB\xBF");

        return rtrim($purifiedString);
    }

    /**
     * 和原始檔案比對資料欄位數目是否正確
     * @return bool
     */
    protected function checkCsvRowsWithOriginal(): bool
    {
        if (count($this->currentCsvData) != count($this->uploadCsvData)) {
            return false;
        }

        foreach ($this->currentCsvData as $key => $rowData) {
            if (count($this->currentCsvData[$key]) != count($this->uploadCsvData[$key])) {
                return false;
            }
        }

        return true;
    }

    /**
     * 刪除當前的檔案
     * @return void
     */
    protected function deleteCurrentModifiedFile()
    {
        $filePath = $this->commonHelper->getFileAbsolutePath(
            CommonHelper::FILE_TYPE_MODIFIED_MAIN_FILE,
            $this->record->getModifiedMainFileName()
        );

        if ($this->file->isExists($filePath)) {
            $this->file->deleteFile($filePath);
        }

        $this->record->setModifiedMainFileName("");

        $this->hifiSalesReportRecordRepository->save($this->record);
    }

    /**
     * 根據上傳的檔案資料建立新的檔案
     * @return void
     */
    protected function createNewFile()
    {
        $fileName         = $this->record->getId() . "_" . date("Ymd_His") . "_modified_main.csv";
        $filepath         = CommonHelper::FILE_FOLDER_PATH_MODIFIED_MAIN_FILE . "/{$fileName}";
        $filepathWithRoot = $this->directoryList->getRoot() . "/" . $filepath;

        $this->directory->create(null);
        $stream = $this->directory->openFile($filepath, 'w+');
        $stream->lock();

        $output = fopen($filepathWithRoot, 'w');

        foreach ($this->uploadCsvData as $rowData) {
            fputcsv($output, $rowData);
        }

        fclose($output);

        $this->uploadFileName = $fileName;
        $this->uploadFilePath = $filepath;
    }

    /**
     * 更新帳務主紀錄
     * @return void
     */
    protected function updateRecord()
    {
        $memo = $this->hotaiCoreCommonHelper->prepareMemoStringForUpdate(
            $this->record->getMemo(),
            [
                "Title"            => "HIFI Sales Report Main File Upload.",
                "Admin ID"         => $this->authSession->getUser()->getId(),
                "Admin name"       => $this->authSession->getUser()->getUserName(),
                "Upload file name" => $this->uploadFileName,
                "Upload file path" => $this->uploadFilePath,
                "Datetime"         => date("Y-m-d H:i:s"),
                "Timestamp"        => time(),
            ]
        );

        $this->record->setModifiedMainFileName($this->uploadFileName);
        $this->record->setMemo($memo);

        $this->hifiSalesReportRecordRepository->save($this->record);
    }

    /**
     * 引導回前頁
     * @return \Magento\Framework\Controller\Result\Redirect
     */
    protected function returnPreviousPage(): Redirect
    {
        /** @var Redirect $resultRedirect */
        $resultRedirect = $this->resultFactory->create(ResultFactory::TYPE_REDIRECT);
        $resultRedirect->setUrl($this->_redirect->getRefererUrl());

        return $resultRedirect;
    }
}
