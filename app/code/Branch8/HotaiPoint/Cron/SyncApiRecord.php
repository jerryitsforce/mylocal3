<?php

namespace Branch8\HotaiPoint\Cron;

use Branch8\HotaiCore\Helper\Common as HotaiCoreCommonHelper;
use Branch8\HotaiPoint\Helper\Common as CommonHelper;
use Branch8\HotaiPoint\Model\Config\Source\LogOption;
use Branch8\HotaiPoint\Model\HotaiPointApiRecord as HotaiPointApiRecordModel;
use Branch8\HotaiPoint\Model\HotaiPointApiRecordRepository;
use Branch8\HotaiPoint\Model\ResourceModel\HotaiPointApiRecord\CollectionFactory as HotaiPointApiRecordCollectionFactory;
use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\DB\Transaction;
use Magento\Framework\Filesystem;
use Branch8\HotaiCore\Helper\Sftp;

class SyncApiRecord
{
    private const DEBUG_LOG_OPTION = LogOption::LOG_SYNC_API_RECORD;

    const DEFAULT_HOTAI_FTP_SYNC_FOLDER_PATH = '/DT/';
    const SYNC_BACKUP_FOLDER                 = '/hotai_point_sync/';
    const LOG_FOLDER_NAME                    = 'HotaiPoint/Cron/SyncApiRecord';
    const FIELD_SEPARATOR                    = "|";
    const FIELD_SEPARATOR_REPLACEMENT        = "_";
    const LINE_ENDING                        = "\r\n";
    const FILE_END_STRING                    = "end";

    protected HotaiCoreCommonHelper                $hotaiCoreCommonHelper;
    protected HotaiPointApiRecordRepository        $repository;
    protected HotaiPointApiRecordCollectionFactory $apiRecordCollectionFactory;
    protected CommonHelper                         $commonHelper;
    protected Filesystem                           $fileSystem;
    protected Sftp                                 $sftpClient;
    protected Transaction                          $transaction;

    protected $buNo;
    protected $syncFileName;
    protected $logFileName;
    protected $targetTransDateObj;
    protected $forceExecute       = false;
    protected $ignoreSyncStatus   = false;

    public function __construct(
        HotaiCoreCommonHelper $hotaiCoreCommonHelper,
        HotaiPointApiRecordRepository $repository,
        HotaiPointApiRecordCollectionFactory $apiRecordCollectionFactory,
        CommonHelper $commonHelper,
        Filesystem $fileSystem,
        Sftp $sftpClient,
        Transaction $transaction
    ) {
        $this->hotaiCoreCommonHelper      = $hotaiCoreCommonHelper;
        $this->repository                 = $repository;
        $this->apiRecordCollectionFactory = $apiRecordCollectionFactory;
        $this->commonHelper               = $commonHelper;
        $this->fileSystem                 = $fileSystem;
        $this->sftpClient                 = $sftpClient;
        $this->transaction                = $transaction;

        $this->buNo = $this->hotaiCoreCommonHelper->getHotaiCoreConfig(HotaiCoreCommonHelper::HOTAI_CORE_CONFIG_PATH_BU_NO);
    }

    public function execute()
    {
        $this->commonHelper->writeLogIfEnabled(
            "SyncApiRecord cron start.",
            self::LOG_FOLDER_NAME,
            self::DEBUG_LOG_OPTION
        );

        if (!$this->checkExecuteCondition()) {
            $this->commonHelper->writeLogIfEnabled(
                "SyncApiRecord cron end due to checkExecuteCondition is false.",
                self::LOG_FOLDER_NAME,
                self::DEBUG_LOG_OPTION
            );
            return;
        }

        try {
            $this->initTargetTransDateObj();

            $recordCollection = $this->getYetSyncedRecords();

            if (empty($recordCollection->getItems())) {
                $this->commonHelper->writeLogIfEnabled(
                    "DT sync cron success, nothing to sync.",
                    self::LOG_FOLDER_NAME,
                    self::DEBUG_LOG_OPTION
                );

                return;
            }

            $recordIds = [];
            /** @var HotaiPointApiRecordModel $record */
            foreach ($recordCollection->getItems() as $record) {
                $recordIds[] = $record->getId();
            }

            $this->commonHelper->writeLogIfEnabled(
                "Ready to sync record IDs: " . \implode(",", $recordIds),
                self::LOG_FOLDER_NAME,
                self::DEBUG_LOG_OPTION
            );

            $fileContent = $this->createSyncFileContent($recordCollection);

            // 在伺服器建立一份作為備份
            $this->createSyncFile($fileContent);

            $this->commonHelper->writeLogIfEnabled(
                "Sync file name: " . $this->syncFileName,
                self::LOG_FOLDER_NAME,
                self::DEBUG_LOG_OPTION
            );

            $this->commonHelper->writeLogIfEnabled(
                "Create sync file done, ready to upload file to FTP.",
                self::LOG_FOLDER_NAME,
                self::DEBUG_LOG_OPTION
            );

            $result = $this->uploadSyncFileToHotaiFtp($fileContent);

            if (!$result) {
                throw new \Exception("Something went wrong while uploading file to Hotai FTP.");
            }

            $this->commonHelper->writeLogIfEnabled(
                "Upload file to FTP done.",
                self::LOG_FOLDER_NAME,
                self::DEBUG_LOG_OPTION
            );

            $this->updateSyncStatus($recordCollection);

            $this->commonHelper->writeLogIfEnabled(
                "Update sync status done.",
                self::LOG_FOLDER_NAME,
                self::DEBUG_LOG_OPTION
            );

            $this->writeSuccessLog($recordCollection);
        } catch (\Exception $e) {
            $this->commonHelper->writeLogIfEnabled(
                json_encode([
                    "Title"             => "DT sync cron fail.",
                    "Exception message" => $e->getMessage(),
                    "SFTP Errors"       => $this->sftpClient->getSFTPErrors(),
                ]),
                self::LOG_FOLDER_NAME,
                self::DEBUG_LOG_OPTION
            );
        }

        $this->commonHelper->writeLogIfEnabled(
            "SyncApiRecord cron end.",
            self::LOG_FOLDER_NAME,
            self::DEBUG_LOG_OPTION
        );
    }

    /**
     * @return \Branch8\HotaiPoint\Model\ResourceModel\HotaiPointApiRecord\Collection
     */
    public function getYetSyncedRecords()
    {
        /** @var \Branch8\HotaiPoint\Model\ResourceModel\HotaiPointApiRecord\Collection $apiRecordCollection */
        $apiRecordCollection = $this->apiRecordCollectionFactory->create();

        $this->commonHelper->writeLogIfEnabled(
            "Sync date range from: " . $this->targetTransDateObj->format('Y-m-d 00:00:00'),
            self::LOG_FOLDER_NAME,
            self::DEBUG_LOG_OPTION
        );
        $this->commonHelper->writeLogIfEnabled(
            "Sync date range to: " . $this->targetTransDateObj->format('Y-m-d 23:59:59'),
            self::LOG_FOLDER_NAME,
            self::DEBUG_LOG_OPTION
        );

        $fromDateTime = $this->targetTransDateObj->format('Y-m-d 00:00:00');
        $toDateTime   = $this->targetTransDateObj->format('Y-m-d 23:59:59');

        if (!$this->ignoreSyncStatus) {
            $this->commonHelper->writeLogIfEnabled(
                "Filter records by (sync_status, trans_type, is_paid) with sales_order joins.",
                self::LOG_FOLDER_NAME,
                self::DEBUG_LOG_OPTION
            );

            $salesOrderItemTable = $apiRecordCollection->getTable('sales_order_item');
            $salesOrderTable     = $apiRecordCollection->getTable('sales_order');

            $connection = $apiRecordCollection->getConnection();
            $select = $apiRecordCollection->getSelect();

            $apiRecordCollection
                ->join(
                    ['soi' => $salesOrderItemTable],
                    'soi.hotai_point_deduction_point_trace_no = main_table.' . HotaiPointApiRecordModel::TRACE_NO,
                    ['item_id', 'order_id']
                )->join(
                    ['so' => $salesOrderTable],
                    'so.entity_id = soi.order_id',
                    ['is_paid']
                );

            // HTGO2-3354 對於扣點的紀錄, 以sales_order是否已付款來決定是否要同步
            $conditionGroup1 = $connection->prepareSqlCondition(
                'main_table.' . HotaiPointApiRecordModel::TRANS_DATETIME,
                ['gteq' => $fromDateTime]
            ) . ' AND ';
            $conditionGroup1 .= $connection->prepareSqlCondition(
                'main_table.' . HotaiPointApiRecordModel::TRANS_DATETIME,
                ['lteq' => $toDateTime]
            ) . ' AND ';
            $conditionGroup1 .= $connection->prepareSqlCondition(
                'main_table.' . HotaiPointApiRecordModel::SYNC_STATUS,
                ['eq' => HotaiPointApiRecordModel::SYNC_STATUS_YET_SYNCED]
            ) . ' AND ';
            $conditionGroup1 .= $connection->prepareSqlCondition(
                'main_table.' . HotaiPointApiRecordModel::TRANS_TYPE,
                ['eq' => HotaiPointApiRecordModel::TRANS_TYPE_DEDUCTION_POINT]
            ) . ' AND ';
            $conditionGroup1 .= $connection->prepareSqlCondition(
                'so.is_paid',
                ['eq' => 1]
            );

            // HTGO2-3354 對於扣點交易以外的紀錄, 直接看同步狀態來決定是否要同步
            $conditionGroup2 = $connection->prepareSqlCondition(
                'main_table.' . HotaiPointApiRecordModel::TRANS_DATETIME,
                ['gteq' => $fromDateTime]
            ) . ' AND ';
            $conditionGroup2 .= $connection->prepareSqlCondition(
                'main_table.' . HotaiPointApiRecordModel::TRANS_DATETIME,
                ['lteq' => $toDateTime]
            ) . ' AND ';
            $conditionGroup2 .= $connection->prepareSqlCondition(
                'main_table.' . HotaiPointApiRecordModel::SYNC_STATUS,
                ['eq' => HotaiPointApiRecordModel::SYNC_STATUS_YET_SYNCED]
            ) . ' AND ';
            $conditionGroup2 .= $connection->prepareSqlCondition(
                'main_table.' . HotaiPointApiRecordModel::TRANS_TYPE,
                ['neq' => HotaiPointApiRecordModel::TRANS_TYPE_DEDUCTION_POINT]
            );

            $select->where($conditionGroup1);
            $select->orWhere($conditionGroup2);
        }

        return $apiRecordCollection;
    }

    /**
     * @param \Branch8\HotaiPoint\Model\ResourceModel\HotaiPointApiRecord\Collection $recordCollection
     * @return string $fileContent
     */
    public function createSyncFileContent($recordCollection)
    {
        $fileContent = "";

        /** @var \Branch8\HotaiPoint\Model\HotaiPointApiRecord $record */
        foreach ($recordCollection->getItems() as $record) {
            try {
                $dateTimeObj = \DateTime::createFromFormat(
                    "Y-m-d H:i:s",
                    $record->getTransDatetime(),
                    new \DateTimeZone("Asia/Taipei")
                );
                $transDateYmd = $dateTimeObj->format('Ymd');
                $transTimeHis = $dateTimeObj->format('His');

                $dataArray = [
                    "trans_type" => $record->getTransType(),
                    "trace_no" => $record->getTraceNo(),
                    "bu_no" => $record->getBuNo(),
                    "oneid" => $record->getOneid(),
                    "member_account" => $record->getMemberAccount(),
                    "oneid_type" => $record->getOneidType(),
                    "rs_no" => (in_array($record->getTransType(), HotaiPointApiRecordModel::TRANS_TYPE_NEED_RS_NO)) ? $record->getRsNo() : "",
                    "pos_no" => (in_array($record->getTransType(), HotaiPointApiRecordModel::TRANS_TYPE_NEED_POS_NO)) ? $record->getPosNo() : "",
                    "trans_sn" => $record->getTransSN(),
                    "trans_datetime_ymd" => $transDateYmd,
                    "trans_datetime_his" => $transTimeHis,
                    "trans_desc" => $this->purifyString($record->getTransDesc()),
                    "add_type" => (in_array($record->getTransType(), HotaiPointApiRecordModel::TRANS_TYPE_NEED_ADD_TYPE)) ? $record->getAddType() : "",
                    "amt" => (in_array($record->getTransType(), HotaiPointApiRecordModel::TRANS_TYPE_NEED_AMT)) ? $record->getAmt() : "",
                    "point_amt" => (in_array($record->getTransType(), HotaiPointApiRecordModel::TRANS_TYPE_NEED_POINT_AMT)) ? $record->getPointAmt() : "",
                    "deduction_point" => (in_array($record->getTransType(), HotaiPointApiRecordModel::TRANS_TYPE_NEED_DEDUCTION_POINT)) ? $record->getDeductionPoint() : "",
                    "activity_codes" => (in_array($record->getTransType(), HotaiPointApiRecordModel::TRANS_TYPE_NEED_ACTIVITY_CODES)) ? $record->getActivityCodes() : "",
                    "source_trace_no" => (in_array($record->getTransType(), HotaiPointApiRecordModel::TRANS_TYPE_NEED_SOURCE_TRACE_NO)) ? $record->getSourceTraceNo() : "",
                    "app_id" => (in_array($record->getTransType(), HotaiPointApiRecordModel::TRANS_TYPE_NEED_APP_ID)) ? $record->getAppId() : "",
                    "add_point" => (in_array($record->getTransType(), HotaiPointApiRecordModel::TRANS_TYPE_NEED_ADD_POINT)) ? $record->getAddPoint() : "",
                    "point_valid_type" => (in_array($record->getTransType(), HotaiPointApiRecordModel::TRANS_TYPE_NEED_POINT_VALID_TYPE)) ? $record->getPointValidType() : "",
                    "point_valid_date" => (in_array($record->getTransType(), HotaiPointApiRecordModel::TRANS_TYPE_NEED_POINT_VALID_DATE)) ? $record->getPointValidDate() : "",
                ];

                $fileContent .= \implode(self::FIELD_SEPARATOR, $dataArray) . self::LINE_ENDING;
            } catch (\Exception $e) {
                $this->commonHelper->writeLogIfEnabled(
                    json_encode([
                        "Title"             => "Error while creating sync file content for record ID: " . $record->getId(),
                        "Record ID"         => $record->getId(),
                        "Trans datetime"    => $record->getTransDatetime(),
                        "Exception message" => $e->getMessage(),
                        "Exception trace"   => $e->getTraceAsString(),
                    ]),
                    self::LOG_FOLDER_NAME,
                    self::DEBUG_LOG_OPTION
                );
                continue;
            }
        }

        $fileContent .= self::FILE_END_STRING;

        return $fileContent;
    }

    public function purifyString(?string $string): string
    {
        if (empty($string)) {
            return "";
        }

        $modifiedString = str_replace(self::FIELD_SEPARATOR, self::FIELD_SEPARATOR_REPLACEMENT, $string);
        $modifiedString = str_replace("\r", "", $modifiedString);
        $modifiedString = str_replace("\n", "", $modifiedString);

        return $modifiedString;
    }

    /**
     * @param string $fileContent
     * @return void
     */
    public function createSyncFile($fileContent)
    {
        $varFolder = $this->fileSystem->getDirectoryWrite(DirectoryList::VAR_DIR);
        $varFolder->writeFile(self::SYNC_BACKUP_FOLDER . $this->getSyncFileName(), $fileContent);
    }

    /**
     * @return string
     */
    public function getSyncFileName()
    {
        if (!empty($this->syncFileName)) {
            return $this->syncFileName;
        }

        // fileName example: DT_AA_20210502015811.txt
        // fileName explanation: "DT"_{特約商通路代碼}_{上傳檔案當下時間 YmdHis}.txt
        $this->syncFileName = "DT";
        $this->syncFileName .= "_";
        $this->syncFileName .= $this->buNo;
        $this->syncFileName .= "_";
        $this->syncFileName .= $this->hotaiCoreCommonHelper->getTaiwanDateTimeObject()->format("YmdHis");
        $this->syncFileName .= ".txt";

        return $this->syncFileName;
    }

    /**
     * @param \Branch8\HotaiPoint\Model\ResourceModel\HotaiPointApiRecord\Collection $recordCollection
     * @return void
     */
    public function updateSyncStatus($recordCollection)
    {
        /** @var \Branch8\HotaiPoint\Model\HotaiPointApiRecord $record */
        foreach ($recordCollection->getItems() as $record) {
            $record->setSyncStatus(HotaiPointApiRecordModel::SYNC_STATUS_SYNCED);
            $this->transaction->addObject($record);
        }

        $this->transaction->save();
    }

    /**
     * @param string $fileContent
     * @return boolean
     */
    public function uploadSyncFileToHotaiFtp($fileContent)
    {
        $this->sftpClient->open(
            [
                'host'     => $this->commonHelper->getHotaiPointConfig(CommonHelper::HOTAI_POINT_CONFIG_PATH_FTP_HOST),
                'username' => $this->commonHelper->getHotaiPointConfig(CommonHelper::HOTAI_POINT_CONFIG_PATH_FTP_USERNAME),
                'password' => $this->commonHelper->getHotaiPointConfig(CommonHelper::HOTAI_POINT_CONFIG_PATH_FTP_PASSWORD),
                'timeout'  => 90,
            ]
        );

        $syncFolderPath = $this->commonHelper->getHotaiPointConfig(CommonHelper::HOTAI_POINT_CONFIG_PATH_FTP_SYNC_FOLDER_PATH);

        $this->commonHelper->writeLogIfEnabled(
            "Sync folder path from config: " . $syncFolderPath,
            self::LOG_FOLDER_NAME,
            self::DEBUG_LOG_OPTION
        );

        if (empty($syncFolderPath)) {
            $this->commonHelper->writeLogIfEnabled(
                "Sync folder path from config is empty, use default sync folder path: " . self::DEFAULT_HOTAI_FTP_SYNC_FOLDER_PATH,
                self::LOG_FOLDER_NAME,
                self::DEBUG_LOG_OPTION
            );

            $syncFolderPath = self::DEFAULT_HOTAI_FTP_SYNC_FOLDER_PATH;
        }

        // 統一處理前後的 /，先 trim 然後再加回去
        $syncFolderPath = trim($syncFolderPath, '/');
        $syncFolderPath = '/' . $syncFolderPath . '/';

        $this->commonHelper->writeLogIfEnabled(
            "Sync folder path after normalization: " . $syncFolderPath,
            self::LOG_FOLDER_NAME,
            self::DEBUG_LOG_OPTION
        );

        return $this->sftpClient->write(
            $syncFolderPath . $this->getSyncFileName(),
            $fileContent
        );
    }

    /**
     * @param \Branch8\HotaiPoint\Model\ResourceModel\HotaiPointApiRecord\Collection $recordCollection
     * @return void
     */
    public function writeSuccessLog($recordCollection)
    {
        $recordIds = [];

        /** @var \Branch8\HotaiPoint\Model\HotaiPointApiRecord $record */
        foreach ($recordCollection->getItems() as $record) {
            $recordIds[] = $record->getId();
        }

        $this->commonHelper->writeLogIfEnabled(json_encode([
            "Title"                => "DT sync cron success.",
            "Sync file name"       => $this->getSyncFileName(),
            "Handled record IDs"   => \implode(",", $recordIds),
            "Handled record count" => count($recordIds),
        ]), self::LOG_FOLDER_NAME, self::DEBUG_LOG_OPTION);
    }

    public function setTargetTransDate(string $transDate)
    {
        $this->commonHelper->writeLogIfEnabled(
            "Try to set targetTransDate with input: " . $transDate,
            self::LOG_FOLDER_NAME,
            self::DEBUG_LOG_OPTION
        );

        $format  = 'Y-m-d';
        $dateObj = \DateTime::createFromFormat($format, $transDate);

        if ($dateObj === false || $dateObj->format($format) !== $transDate) {
            throw new \InvalidArgumentException("Invalid date value or date format. Expected format: {$format}");
        }

        $dateObj->setTimezone(new \DateTimeZone("Asia/Taipei"));

        $this->targetTransDateObj = $dateObj;
    }

    protected function initTargetTransDateObj()
    {
        if (!empty($this->targetTransDateObj)) {
            return;
        }

        /** @var \DateTime $targetTransDateObj */
        $targetTransDateObj = $this->hotaiCoreCommonHelper->getTaiwanDateTimeObject();
        $targetTransDateObj->modify('-1 day');

        $this->targetTransDateObj = $targetTransDateObj;
    }

    public function setForceExecute(bool $forceExecute)
    {
        $this->commonHelper->writeLogIfEnabled(
            "Set setForceExecute: " . var_export($forceExecute, true),
            self::LOG_FOLDER_NAME,
            self::DEBUG_LOG_OPTION
        );

        $this->forceExecute = $forceExecute;
    }

    public function setIgnoreSyncStatus(bool $ignoreSyncStatus)
    {
        $this->commonHelper->writeLogIfEnabled(
            "Set ignoreSyncStatus: " . var_export($ignoreSyncStatus, true),
            self::LOG_FOLDER_NAME,
            self::DEBUG_LOG_OPTION
        );

        $this->ignoreSyncStatus = $ignoreSyncStatus;
    }

    protected function checkExecuteCondition(): bool
    {
        $this->commonHelper->writeLogIfEnabled(
            "Check execute condition.",
            self::LOG_FOLDER_NAME,
            self::DEBUG_LOG_OPTION
        );

        if ($this->forceExecute) {
            $this->commonHelper->writeLogIfEnabled(
                "Find forceExecute is true.",
                self::LOG_FOLDER_NAME,
                self::DEBUG_LOG_OPTION
            );
            return true;
        }

        $ftpEnable = $this->commonHelper->getHotaiPointConfig(
            CommonHelper::HOTAI_POINT_CONFIG_PATH_FTP_ENABLE
        );

        if ($ftpEnable == 1) {
            $this->commonHelper->writeLogIfEnabled(
                "Find HOTAI_POINT_CONFIG_PATH_FTP_ENABLE enable.",
                self::LOG_FOLDER_NAME,
                self::DEBUG_LOG_OPTION
            );
            return true;
        }

        $this->commonHelper->writeLogIfEnabled(
            "Find HOTAI_POINT_CONFIG_PATH_FTP_ENABLE disable.",
            self::LOG_FOLDER_NAME,
            self::DEBUG_LOG_OPTION
        );

        return false;
    }
}
