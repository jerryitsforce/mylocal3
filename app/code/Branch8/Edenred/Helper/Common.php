<?php

namespace Branch8\Edenred\Helper;

use Branch8\HotaiCore\Model\Product\VirtualProductType;
use Branch8\HotaiCore\Helper\Common as HotaiCoreCommonHelper;
use Branch8\HotaiCore\Helper\DebugLog as HotaiCoreDebugLog;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Branch8\Edenred\Model\Config\Source\ApiMode;

class Common
{
    const ATTRIBUTE_CODE_EDENRED_ORDER_NUMBER  = "edenred_order_number";
    const ATTRIBUTE_CODE_EDENRED_PRODUCT_CODE  = "edenred_product_code";
    const ATTRIBUTE_CODE_EDENRED_MERCHANT_CODE = "edenred_merchant_code";

    const CONFIG_PREFIX_API                         = "edenred/api/dev/";
    const CONFIG_PREFIX_API_PROD                    = "edenred/api/production/";
    const CONFIG_PATH_API_MODE                      = "edenred/api/general/api_mode";
    const CONFIG_PATH_CLIENT_ORDER_NUMBER_PREFIX    = "edenred/api/general/client_order_number_prefix";
    const CONFIG_PATH_QTY_VALIDATOR_ENABLE          = "edenred/others/qty_validator_when_checkout_enable";

    const CONFIG_CODE_API_DOMAIN                    = "api_domain";
    const CONFIG_CODE_API_VERSION                   = "api_version";
    const CONFIG_CODE_SSL_KEY                       = "ssl_key";
    const CONFIG_CODE_SSL_IV                        = "ssl_iv";
    const CONFIG_CODE_CONSUMER_CODE                 = "consumer_code";
    const CONFIG_CODE_DYNAMIC_VOUCHER_GUID_PAGE_URL = "dynamic_voucher_guid_page_url";
    const CONFIG_CODE_NOTIFICATION_ALLOWED_IPS      = "notification_allowed_ips";

    const ENCRYPT_METHOD = "des-ede3-cbc";

    /** @var ProductRepositoryInterface */
    protected $productRepository;

    /** @var ScopeConfigInterface */
    protected $scopeConfig;

    /** @var HotaiCoreCommonHelper */
    protected $hotaiCoreCommonHelper;

    protected $apiMode = null;

    public function __construct(
        ProductRepositoryInterface $productRepository,
        ScopeConfigInterface $scopeConfig,
        HotaiCoreCommonHelper $hotaiCoreCommonHelper
    ) {
        $this->productRepository    = $productRepository;
        $this->scopeConfig          = $scopeConfig;
        $this->hotaiCoreCommonHelper = $hotaiCoreCommonHelper;
    }

    /**
     * 確認商品是否為宜睿票券
     *
     * @param integer $productId
     * @return boolean
     */
    public function IsEdenredTicketProduct(int|string $productId): bool
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

        return $virtualProductType->getValue() == VirtualProductType::TYPE_EDENRED_TICKET;
    }

    /**
     * 取得當前的API模式設定
     *
     * @return string
     */
    public function getApiMode(): string
    {
        if (!empty($this->apiMode)) {
            return $this->apiMode;
        }

        $this->apiMode = $this->scopeConfig->getValue(self::CONFIG_PATH_API_MODE) ?? ApiMode::API_MODE_DEFAULT;

        return $this->apiMode;
    }

    /**
     * 依照後臺設定代號取得對應的當前設定
     *
     * @param string $configCode
     * @return string|null
     */
    public function getApiConfigByConfigCode(string $configCode): ?string
    {
        switch ($this->getApiMode()) {
            case ApiMode::API_MODE_DEV:
                $configPath = self::CONFIG_PREFIX_API . $configCode;
                return $this->scopeConfig->getValue($configPath);

            case ApiMode::API_MODE_PRODUCTION:
                $configPath = self::CONFIG_PREFIX_API_PROD . $configCode;
                return $this->scopeConfig->getValue($configPath);

            default:
                throw new \Exception("Something went wrong while getting Edenred api mode: " . $this->apiMode);
        }
    }

    /**
     * 宜睿票券字串加密
     *
     * @param string $requestDataArray
     * @return string
     */
    public function encryptString(string $targetString): string
    {
        $key = $this->getApiConfigByConfigCode(self::CONFIG_CODE_SSL_KEY);
        $iv  = $this->getApiConfigByConfigCode(self::CONFIG_CODE_SSL_IV);

        $binKey = pack("H*", $key);
        $binIv  = pack("H*", $iv);

        $encryptString = openssl_encrypt($targetString, self::ENCRYPT_METHOD, $binKey, OPENSSL_RAW_DATA, $binIv);

        return \base64_encode($encryptString);
    }

    /**
     * 宜睿票券字串解密
     *
     * @param string $targetString
     * @return string|null
     */
    public function decryptString(string $targetString): ?string
    {
        $key = $this->getApiConfigByConfigCode(self::CONFIG_CODE_SSL_KEY);
        $iv  = $this->getApiConfigByConfigCode(self::CONFIG_CODE_SSL_IV);

        $binKey = pack("H*", $key);
        $binIv  = pack("H*", $iv);

        return openssl_decrypt(\base64_decode($targetString), self::ENCRYPT_METHOD, $binKey, OPENSSL_RAW_DATA, $binIv);
    }

    public function writeLogIfEnabled(
        string|array $message,
        string $folderName,
        string $logOptionValue,
        string $fileName = ""
    ): void {
        if (!HotaiCoreDebugLog::isEnable('Branch8_Edenred', $logOptionValue)) {
            return;
        }

        $this->hotaiCoreCommonHelper->writeLog($message, $folderName, $fileName);
    }
}
