<?php

namespace Branch8\FamilyBonusPin\Cron;

use Branch8\FamilyBonusPin\Helper\Common as CommonHelper;
use Branch8\FamilyBonusPin\Model\Config\Source\LogOption;
use Branch8\FamilyBonusPin\Helper\Email as EmailHelper;
use Branch8\FamilyBonusPin\Model\ResourceModel\FamilyBonusPinBatchSetting\Collection as BatchSettingCollection;
use Branch8\FamilyBonusPin\Model\ResourceModel\FamilyBonusPinBatchSetting\CollectionFactory as BatchSettingCollectionFactory;
use Branch8\FamilyBonusPin\Model\ResourceModel\FamilyBonusPinTicketRecord\Collection as TicketRecordCollection;
use Branch8\FamilyBonusPin\Model\ResourceModel\FamilyBonusPinTicketRecord\CollectionFactory as TicketRecordCollectionFactory;
use Branch8\FamilyBonusPin\Model\FamilyBonusPinBatchSetting as BatchSettingModel;
use Branch8\FamilyBonusPin\Model\FamilyBonusPinTicketRecord as TicketRecordModel;

class CheckSafetyQuantity
{
    const LOG_FOLDER_NAME = 'FamilyBonusPin/Cron/CheckSafetyQuantity';

    private const DEBUG_LOG_OPTION = LogOption::LOG_CRON_CHECK_SAFETY_QUANTITY;

    /** @var TicketRecordCollectionFactory */
    protected $ticketRecordCollectionFactory;

    /** @var BatchSettingCollectionFactory */
    protected $batchSettingCollectionFactory;

    /** @var CommonHelper */
    protected $commonHelper;

    /** @var EmailHelper */
    protected $emailHelper;

    public function __construct(
        TicketRecordCollectionFactory $ticketRecordCollectionFactory,
        BatchSettingCollectionFactory $batchSettingCollectionFactory,
        CommonHelper $commonHelper,
        EmailHelper $emailHelper
    ) {
        $this->ticketRecordCollectionFactory = $ticketRecordCollectionFactory;
        $this->batchSettingCollectionFactory = $batchSettingCollectionFactory;
        $this->commonHelper                  = $commonHelper;
        $this->emailHelper                   = $emailHelper;
    }

    public function execute()
    {
        try {
            $this->writeLog("FamilyBonusPin CheckSafetyQuantity Cron start.");

            $batchSettingCollection = $this->getYoxiBatchSettingsWithinSaleTime();
            $ticketRecordCollection = $this->getTicketRecordsWithBatchSetting($batchSettingCollection);
            $checkArray             = $this->getSaftyQuantityExamineResult($batchSettingCollection, $ticketRecordCollection);

            $this->writeLog("Batch settings without enough quantity needs to send notification email: " . json_encode($checkArray));

            if (count($checkArray) != 0) {
                $this->writeLog("Ready to send email.");

                $this->sendNotificationEmail($checkArray);

                $this->writeLog("Send email done.");
            }

            $this->writeLog("Cron end.");
        } catch (\Exception $e) {
            $message = $e->getMessage();
            $this->writeLog("Something went wrong, exception message: {$message}.");
        }
    }

    /**
     * 查詢當前在販售時間內的BatchSetting
     *
     * @return BatchSettingCollection
     */
    protected function getYoxiBatchSettingsWithinSaleTime(): BatchSettingCollection
    {
        $currentDatetime = date("Y-m-d H:i:s");

        $collection = $this->batchSettingCollectionFactory->create()
            ->addFieldToFilter(
                BatchSettingModel::SALE_START_TIME,
                ['lteq' => $currentDatetime]
            )->addFieldToFilter(
                BatchSettingModel::SALE_END_TIME,
                ['gteq' => $currentDatetime]
            );

        $collection->load();

        return $collection;
    }

    /**
     * 根據BatchSetting查詢當前可販售的TicketRecord
     *
     * @param BatchSettingCollection $batchSettingCollection
     * @return TicketRecordCollection
     */
    protected function getTicketRecordsWithBatchSetting(BatchSettingCollection $batchSettingCollection): TicketRecordCollection
    {
        $targetBatchSettingIds = [];

        /** @var BatchSettingModel $batchSetting */
        foreach ($batchSettingCollection->getItems() as $batchSetting) {
            $targetBatchSettingIds[] = $batchSetting->getId();
        }

        $ticketRecordCollection = $this->ticketRecordCollectionFactory->create();

        $ticketRecordCollection
            ->addFieldToFilter(
                TicketRecordModel::BATCH_SETTING_ID,
                ['in' => $targetBatchSettingIds]
            )->addFieldToFilter(
                TicketRecordModel::STATUS,
                TicketRecordModel::STATUS_IMPORTED
            );

        $ticketRecordCollection
            ->getSelect()
            ->columns([new \Zend_Db_Expr('COUNT(`' . TicketRecordModel::RECORD_ID . '`) as available_quantity')])
            ->group(TicketRecordModel::BATCH_SETTING_ID);

        $ticketRecordCollection->load();

        return $ticketRecordCollection;
    }

    /**
     * 根據getTicketRecordsWithBatchSetting的查詢檢驗安全庫存與當前可用庫存的數量
     * 並將結果整理成array回傳
     *
     * @param BatchSettingCollection $batchSettingCollection
     * @param TicketRecordCollection $ticketRecordCollection
     * @return array
     */
    protected function getSaftyQuantityExamineResult(BatchSettingCollection $batchSettingCollection, TicketRecordCollection $ticketRecordCollection): array
    {
        $availableQuantityArray = [];
        $checkArray             = [];

        /** @var TicketRecordModel $ticketRecord */
        foreach ($ticketRecordCollection->getItems() as $ticketRecord) {
            $availableQuantityArray[$ticketRecord->getBatchSettingId()] = $ticketRecord["available_quantity"];
        }

        /** @var BatchSettingModel $batchSetting */
        foreach ($batchSettingCollection->getItems() as $batchSetting) {
            $checkArray[] = [
                "batch_code"         => $batchSetting->getBatchCode(),
                "safety_stock"       => $batchSetting->getSafetyStock(),
                "available_quantity" => $availableQuantityArray[$batchSetting->getId()],
                "sale_start_time"    => $batchSetting->getSaleStartTime(),
                "sale_end_time"      => $batchSetting->getSaleEndTime(),
            ];
        }

        $this->writeLog("All batch setting quantity status within sale time: " . json_encode($checkArray));

        foreach ($checkArray as $key => $value) {
            if ($value["available_quantity"] < $value["safety_stock"]) {
                continue;
            }

            unset($checkArray[$key]);
        }

        return $checkArray;
    }

    /**
     * 寄通知信
     *
     * @param array $checkArray
     * @return void
     */
    protected function sendNotificationEmail(array $checkArray): void
    {
        \date_default_timezone_set("Asia/Taipei");

        $receiverAddressesArray = $this->emailHelper->getNotificationEmailReceiverAddresses();

        foreach ($receiverAddressesArray as $receiverAddress) {
            if (empty($receiverAddress)) {
                continue;
            }

            $this->emailHelper->sendSafetyQuantityNotificationEmail(
                [
                    "titleTimeString" => date("Y-m-d H:i:s"),
                    "tableContent"    => $this->prepareTableContentForErrorNotificationEmail($checkArray),
                ],
                [
                    "name"  => "Administrator",
                    "email" => $receiverAddress,
                ]
            );
        }
    }

    /**
     * 根據傳入的checkArray整理出信件中的表格內容
     *
     * @param array $checkArray
     * @return string
     */
    protected function prepareTableContentForErrorNotificationEmail(array $checkArray): string
    {
        $tableContent = "<thead><tr><td>貨號</td><td>安全庫存數量</td><td>可販售庫存數量</td><td>開始販售時間</td><td>結束販售時間</td></tr></thead>";
        $tableContent .= "<tbody>";

        /** @var \Magento\Sales\Api\Data\OrderInterface $order */
        /** @var \Magento\Sales\Api\Data\OrderInterface $currentOrder */
        foreach ($checkArray as $batchSettingData) {
            $tableContent .= "<tr>";
            $tableContent .= "<td>" . $batchSettingData["batch_code"] . "</td>";
            $tableContent .= "<td>" . $batchSettingData["safety_stock"] . "</td>";
            $tableContent .= "<td>" . $batchSettingData["available_quantity"] . "</td>";
            $tableContent .= "<td>" . $batchSettingData["sale_start_time"] . "</td>";
            $tableContent .= "<td>" . $batchSettingData["sale_end_time"] . "</td>";
            $tableContent .= "</tr>";
        }

        $tableContent .= "</tbody>";

        return $tableContent;
    }

    /**
     * 寫入log
     *
     * @param string $message
     * @return void
     */
    protected function writeLog($message)
    {
        $this->commonHelper->writeLogIfEnabled(
            $message,
            self::LOG_FOLDER_NAME,
            self::DEBUG_LOG_OPTION
        );
    }
}
