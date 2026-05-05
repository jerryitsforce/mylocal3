<?php

namespace Branch8\Yoxi\Cron;

use Branch8\Yoxi\Helper\Common as CommonHelper;
use Branch8\Yoxi\Helper\Email as EmailHelper;
use Branch8\Yoxi\Model\ResourceModel\YoxiBatchSetting\Collection as YoxiBatchSettingCollection;
use Branch8\Yoxi\Model\ResourceModel\YoxiBatchSetting\CollectionFactory as YoxiBatchSettingCollectionFactory;
use Branch8\Yoxi\Model\ResourceModel\YoxiTicketRecord\Collection as YoxiTicketRecordCollection;
use Branch8\Yoxi\Model\ResourceModel\YoxiTicketRecord\CollectionFactory as YoxiTicketRecordCollectionFactory;
use Branch8\Yoxi\Model\YoxiBatchSetting as YoxiBatchSettingModel;
use Branch8\Yoxi\Model\YoxiTicketRecord as YoxiTicketRecordModel;
use Branch8\Yoxi\Model\Config\Source\LogOption;

class CheckSafetyQuantity
{
    const LOG_FOLDER_NAME = 'Yoxi/Cron/CheckSafetyQuantity';
    const LOG_OPTION_VALUE = LogOption::LOG_OPTION_VALUE_CHECK_SAFETY_QUANTITY;

    /** @var YoxiTicketRecordCollectionFactory */
    protected $yoxiTicketRecordCollectionFactory;

    /** @var YoxiBatchSettingCollectionFactory */
    protected $yoxiBatchSettingCollectionFactory;

    /** @var YoxiBatchSettingCollection */
    protected $yoxiBatchSettingCollection;

    /** @var CommonHelper */
    protected $commonHelper;

    /** @var EmailHelper */
    protected $emailHelper;

    public function __construct(
        YoxiTicketRecordCollectionFactory $yoxiTicketRecordCollectionFactory,
        YoxiBatchSettingCollectionFactory $yoxiBatchSettingCollectionFactory,
        CommonHelper $commonHelper,
        EmailHelper $emailHelper
    ) {
        $this->yoxiTicketRecordCollectionFactory = $yoxiTicketRecordCollectionFactory;
        $this->yoxiBatchSettingCollectionFactory = $yoxiBatchSettingCollectionFactory;
        $this->commonHelper                      = $commonHelper;
        $this->emailHelper                       = $emailHelper;
    }

    public function execute()
    {
        try {
            $this->writeLog("Cron start.");

            $batchSettingCollection = $this->getYoxiBatchSettingsWithinSaleTime();
            $ticketRecordCollection = $this->getYoxiTicketRecordsWithBatchSetting($batchSettingCollection);
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
     * 查詢當前在販售時間內的YoxiBatchSetting
     *
     * @return YoxiBatchSettingCollection
     */
    protected function getYoxiBatchSettingsWithinSaleTime(): YoxiBatchSettingCollection
    {
        $currentDatetime = date("Y-m-d H:i:s");

        $collection = $this->yoxiBatchSettingCollectionFactory->create()
            ->addFieldToFilter(
                YoxiBatchSettingModel::SALE_START_TIME,
                ['lteq' => $currentDatetime]
            )->addFieldToFilter(
            YoxiBatchSettingModel::SALE_END_TIME,
            ['gteq' => $currentDatetime]
        );

        $collection->load();

        return $collection;
    }

    /**
     * 根據YoxiBatchSetting查詢當前可販售的YoxiTicketRecord
     *
     * @param YoxiBatchSettingCollection $batchSettingCollection
     * @return YoxiTicketRecordCollection
     */
    protected function getYoxiTicketRecordsWithBatchSetting(YoxiBatchSettingCollection $batchSettingCollection): YoxiTicketRecordCollection
    {
        $targetBatchSettingIds = [];

        /** @var YoxiBatchSettingModel $batchSetting */
        foreach ($batchSettingCollection->getItems() as $batchSetting) {
            $targetBatchSettingIds[] = $batchSetting->getId();
        }

        $ticketRecordCollection = $this->yoxiTicketRecordCollectionFactory->create();

        $ticketRecordCollection->addFieldToFilter(
            YoxiTicketRecordModel::BATCH_SETTING_ID,
            ['in' => $targetBatchSettingIds]
        )->addFieldToFilter(
            YoxiTicketRecordModel::STATUS,
            YoxiTicketRecordModel::STATUS_IMPORTED
        );

        $ticketRecordCollection->getSelect()
            ->columns([new \Zend_Db_Expr('COUNT(`' . YoxiTicketRecordModel::RECORD_ID . '`) as available_quantity')])
            ->group(YoxiTicketRecordModel::BATCH_SETTING_ID);

        $ticketRecordCollection->load();

        return $ticketRecordCollection;
    }

    /**
     * 根據getYoxiTicketRecordsWithBatchSetting的查詢檢驗安全庫存與當前可用庫存的數量
     * 並將結果整理成array回傳
     *
     * @param YoxiBatchSettingCollection $batchSettingCollection
     * @param YoxiTicketRecordCollection $ticketRecordCollection
     * @return array
     */
    protected function getSaftyQuantityExamineResult(YoxiBatchSettingCollection $batchSettingCollection, YoxiTicketRecordCollection $ticketRecordCollection): array
    {
        $availableQuantityArray = [];
        $checkArray             = [];

        /** @var YoxiTicketRecordModel $ticketRecord */
        foreach ($ticketRecordCollection->getItems() as $ticketRecord) {
            $availableQuantityArray[$ticketRecord->getYoxiBatchSettingId()] = $ticketRecord["available_quantity"];
        }

        /** @var YoxiBatchSettingModel $batchSetting */
        foreach ($batchSettingCollection->getItems() as $batchSetting) {
            $checkArray[] = [
                "yoxi_batch_code"    => $batchSetting->getYoxiBatchCode(),
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
            $tableContent .= "<td>" . $batchSettingData["yoxi_batch_code"] . "</td>";
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
            self::LOG_OPTION_VALUE,
            self::LOG_FOLDER_NAME
        );
    }
}
