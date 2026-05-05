<?php

namespace Branch8\HotaiCore\Helper;

use Branch8\HotaiCore\Model\Product\VirtualProductType;
use Branch8\HotaiCore\Helper\DebugLog as HotaiCoreDebugLog;
use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Contracts\Support\Jsonable;
use Magento\Catalog\Model\ProductFactory;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Exception\FileSystemException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Filesystem\DirectoryList;
use Magento\Framework\Filesystem\Io\File;
use Magento\Framework\Math\Random;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Sales\Model\Order\Status\HistoryFactory;
use Magento\Sales\Api\OrderStatusHistoryRepositoryInterface;
use Magento\Sales\Model\Order;
use Zend_Log;
use Zend_Log_Exception;
use Zend_Log_Writer_Stream;

class Common
{
    const HOTAI_CORE_CONFIG_PATH_BU_NO  = "hotai_core/general/bu_no";
    const HOTAI_CORE_CONFIG_PATH_RS_NO  = "hotai_core/general/rs_no";
    const HOTAI_CORE_CONFIG_PATH_POS_NO = "hotai_core/general/pos_no";
    const HOTAI_CORE_CONFIG_PATH_CURL_HEADER_USER_AGENT = "hotai_core/general/curl_header_user_agent";

    const RANDOM_CHARACTERS_POOL  = "ABCEFGHJKLNPQRSTUVWXYZ0123456789";
    const RANDOM_CHARACTERS_COUNT = 4;

    /** @var ScopeConfigInterface */
    protected $scopeConfig;

    /** @var DirectoryList */
    protected DirectoryList $directoryList;

    /** @var File */
    protected File $file;

    /** @var StoreManagerInterface */
    protected StoreManagerInterface $storeManager;

    /** @var ProductFactory */
    protected ProductFactory $productFactory;

    /** @var Random */
    protected $random;

    /** @var HistoryFactory */
    protected HistoryFactory $historyFactory;

    /** @var OrderStatusHistoryRepositoryInterface */
    protected OrderStatusHistoryRepositoryInterface $orderStatusHistoryRepository;

    public function __construct(
        ScopeConfigInterface $scopeConfig,
        DirectoryList $directoryList,
        File $file,
        StoreManagerInterface $storeManager,
        ProductFactory $productFactory,
        Random $random,
        HistoryFactory $historyFactory,
        OrderStatusHistoryRepositoryInterface $orderStatusHistoryRepository
    ) {
        $this->scopeConfig                  = $scopeConfig;
        $this->directoryList                = $directoryList;
        $this->file                         = $file;
        $this->storeManager                 = $storeManager;
        $this->productFactory               = $productFactory;
        $this->random                       = $random;
        $this->historyFactory               = $historyFactory;
        $this->orderStatusHistoryRepository = $orderStatusHistoryRepository;
    }

    /**
     * 取得和泰核心設定
     *
     * @param string $configPath
     * @return string
     */
    public function getHotaiCoreConfig(string $configPath): string
    {
        return $this->scopeConfig->getValue($configPath);
    }

    /**
     * @param  string|array  $message
     * @param  string  $fileName
     * @param  string  $folderName
     * @return void
     * @throws FileSystemException
     * @throws Zend_Log_Exception
     */
    public function writeLog(string|array $message, string $folderName, string $fileName = ""): void
    {
        $folderPath = $this->directoryList->getPath('log');

        if (!empty($folderName)) {
            $folderPath .= '/' . $folderName;
            $folderPath = str_replace('//', '/', $folderPath);
        }

        if (!file_exists($folderPath)) {
            $this->file->mkdir($folderPath);
        }

        if (empty($fileName)) {
            $fileName = trim(date("Y_m_d") . ".log", '/');
        } else {
            $fileName .= ".log";
            $fileName = trim($fileName, '/');
        }

        $fullFilePath = "{$folderPath}/{$fileName}";
        $fullFilePath = str_replace('//', '/', $fullFilePath);

        $message = $this->formatMessage($message);

        $writer = new Zend_Log_Writer_Stream($fullFilePath);
        $logger = new Zend_Log();
        $logger->addWriter($writer);
        $logger->info($message);
    }

    public function writeLogIfEnabled(
        string|array $message,
        string $folderName,
        string $logOptionValue,
        string $fileName = ""
    ): void {
        if (!HotaiCoreDebugLog::isEnable('Branch8_HotaiCore', $logOptionValue)) {
            return;
        }

        $this->writeLog($message, $folderName, $fileName);
    }

    public function removeLog(string $folderName, string $fileName = ""): void
    {
        $folderPath = $this->directoryList->getPath('log');

        if (!empty($folderName)) {
            $folderPath .= '/' . $folderName;
            $folderPath = str_replace('//', '/', $folderPath);
        }

        if (empty($fileName)) {
            $fileName = trim(date("Y_m_d") . ".log", '/');
        } else {
            $fileName .= ".log";
            $fileName = trim($fileName, '/');
        }

        $fullFilePath = "{$folderPath}/{$fileName}";
        $fullFilePath = str_replace('//', '/', $fullFilePath);

        if (file_exists($fullFilePath)) {
            $this->file->rm($fullFilePath);
        }
    }

    /**
     * Format the parameters for the logger.
     *
     * @param  array|string|Arrayable|Jsonable  $message
     * @return string
     */
    protected function formatMessage(array|string|Jsonable|Arrayable $message): string
    {
        if (is_array($message)) {
            return var_export($message, true);
        }

        if ($message instanceof Jsonable) {
            return $message->toJson();
        }

        if ($message instanceof Arrayable) {
            return var_export($message->toArray(), true);
        }

        return (string) $message;
    }

    /**
     * 將新增的備註資料和現有的資料庫備註欄位內容(json字串)結合
     *
     * @param string $currentMemoString
     * @param array $insertMemoArray
     * @return string
     */
    public function prepareMemoStringForUpdate(string|null $currentMemoString, array $insertMemoArray): string
    {
        if (empty($currentMemoString)) {
            return json_encode([$insertMemoArray]);
        }

        $memoArray = json_decode($currentMemoString, true);

        if (empty($memoArray)) {
            return json_encode([$insertMemoArray]);
        }

        array_push($memoArray, $insertMemoArray);

        return json_encode($memoArray);
    }

    public function isTicketProduct(int $productId): bool
    {
        $virtualProductType = $this->getVirtualProductType($productId);

        if (!$virtualProductType) {
            return false;
        }

        return in_array($virtualProductType, VirtualProductType::TYPES_TICKET);
    }

    public function isBatchImportTicketProduct(int $productId): bool
    {
        $virtualProductType = $this->getVirtualProductType($productId);

        if (!$virtualProductType) {
            return false;
        }

        return in_array($virtualProductType, VirtualProductType::TYPES_BATCH_IMPORT_TICKET);
    }

    /**
     * 確認商品是否為票券類型
     *
     * @param int $productId
     * @return false|mixed
     */
    public function getVirtualProductType(int $productId): mixed
    {
        $resourceProduct = $this->productFactory->create()->getResource();
        try {
            $virtualProductType = $resourceProduct->getAttributeRawValue(
                $productId,
                VirtualProductType::ATTRIBUTE_CODE,
                $this->storeManager->getStore()->getId()
            );
        } catch (NoSuchEntityException $e) {
            return false;
        }

        if ($virtualProductType === null) {
            return false;
        }

        return $virtualProductType;
    }

    /**
     * @param int $productId
     * @return string
     */
    public function getTicketActionUrl(int $productId): string
    {
        $virtualProductType = $this->getVirtualProductType($productId);

        return match ((int) $virtualProductType) {
            VirtualProductType::TYPE_YOXI_TICKET               => "yoxi/Import/ReceiveGridForm",
            VirtualProductType::TYPE_FAMILY_BONUS_PIN_TICKET   => "family_bonus_pin/Import/ReceiveGridForm",
            VirtualProductType::TYPE_GENERAL_NOTIFY_TICKET     => "GeneralNotifyTicket/Import/ReceiveGridForm",
            VirtualProductType::TYPE_GENERAL_NON_NOTIFY_TICKET => "GeneralNonNotifyTicket/Import/ReceiveGridForm",
            default                                            => "",
        };
    }

    public function getRandomString(
        int $randomCharacterCount = self::RANDOM_CHARACTERS_COUNT,
        string $randomCharacterPool = self::RANDOM_CHARACTERS_POOL
    ) {
        return $this->random->getRandomString($randomCharacterCount, $randomCharacterPool);
    }

    public function getTaiwanDateTimeObject($datetime = null, $timezone = "Asia/Taipei"): \DateTimeInterface
    {
        $taiwanDateObj = null;

        if ($datetime) {
            $taiwanDateObj = \DateTime::createFromFormat('Y-m-d H:i:s', $datetime);
            $taiwanDateObj->setTimezone(new \DateTimeZone($timezone));

            return $taiwanDateObj;
        }

        $dateTimeString = sprintf('%.6f', microtime(true));
        $taiwanDateObj  = \DateTime::createFromFormat(
            'U.u',
            $dateTimeString
        );

        $taiwanDateObj->setTimezone(new \DateTimeZone($timezone));

        return $taiwanDateObj;
    }

    public function addSalesOrderHistoryComment(Order $order, string $comment, string $callerClass = ""): void
    {
        if (!empty($callerClass)) {
            $comment = "[{$callerClass}] {$comment}";
        }

        /** @var \Magento\Sales\Api\Data\OrderStatusHistoryInterface $history */
        $history = $this->historyFactory->create();
        $history
            ->setParentId($order->getEntityId())
            ->setComment($comment)
            ->setEntityName("order")
            ->setStatus($order->getStatus());

        $this->orderStatusHistoryRepository->save($history);
    }
}
