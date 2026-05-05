<?php

namespace Branch8\Yoxi\Helper;

use Branch8\HotaiCore\Helper\Common as HotaiCoreCommonHelper;
use Branch8\HotaiCore\Model\Product\VirtualProductType;
use Branch8\HotaiCore\Helper\DebugLog as HotaiCoreDebugLog;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;

class Common
{
    const MAIN_MODULE_LOG_FOLDER = '/Yoxi/';

    const YOXI_CONFIG_PATH_API_DOMAIN      = "yoxi/api/api_domain";
    const YOXI_CONFIG_PATH_CID             = "yoxi/api/cid";
    const YOXI_CONFIG_PATH_APP_VERSION     = "yoxi/api/app_version";
    const YOXI_CONFIG_PATH_ENCRYPT_VERSION = "yoxi/api/encrypt_version";
    const YOXI_CONFIG_PATH_TOKEN           = "yoxi/api/token";
    const YOXI_CONFIG_PATH_AES_KEY         = "yoxi/api/aes_key";
    const YOXI_CONFIG_PATH_AES_IV          = "yoxi/api/aes_iv";

    const ATTRIBUTE_CODE_YOXI_VALUE_PER_TICKET = "yoxi_value_per_ticket";
    const ATTRIBUTE_CODE_YOXI_TICKET_COUNT     = "yoxi_ticket_count";

    /** @var ScopeConfigInterface */
    protected $scopeConfig;

    /** @var ProductRepositoryInterface */
    protected $productRepository;

    /** @var HotaiCoreCommonHelper */
    protected $hotaiCoreCommonHelper;

    public function __construct(
        ScopeConfigInterface $scopeConfig,
        ProductRepositoryInterface $productRepository,
        HotaiCoreCommonHelper $hotaiCoreCommonHelper
    ) {
        $this->scopeConfig       = $scopeConfig;
        $this->productRepository = $productRepository;
        $this->hotaiCoreCommonHelper = $hotaiCoreCommonHelper;
    }

    /**
     * 取得後臺設定
     *
     * @param string $configPath
     * @return string|null
     */
    public function getConfig(string $configPath): ?string
    {
        return $this->scopeConfig->getValue($configPath);
    }

    /**
     * 將新增的備註資料和現有的hotai_point資料庫備註欄位內容(json字串)結合
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

    /**
     * 確認商品是否為YOXI票券
     *
     * @param integer $productId
     * @return boolean
     */
    public function IsYoxiTicketProduct(int $productId): bool
    {
        try {
            $product = $this->productRepository->getById($productId);
        } catch (\Magento\Framework\Exception\NoSuchEntityException $e) {
            return false;
        }

        $virtualProductType = $product->getCustomAttribute(VirtualProductType::ATTRIBUTE_CODE);

        if (empty($virtualProductType)) {
            return false;
        }

        return $virtualProductType->getValue() == VirtualProductType::TYPE_YOXI_TICKET;
    }

    /**
     * 依照後臺設定代號取得對應的當前設定
     * @param string $configCode
     * @return string|null
     */
    public function getApiConfigByConfigCode(string $configCode): ?string
    {
        return $this->scopeConfig->getValue($configCode);
    }

    public function writeLogIfEnabled(
        string|array $message,
        string $logOptionValue,
        string $folderName,
        string $fileName = ""
    ): void {
        if (!HotaiCoreDebugLog::isEnable('Branch8_Yoxi', $logOptionValue)) {
            return;
        }

        if (is_array($message)) {
            $message = json_encode($message, JSON_UNESCAPED_UNICODE);
        }

        $this->hotaiCoreCommonHelper->writeLog($message, $folderName, $fileName);
    }
}
