<?php

namespace HotaiConnected\Qware\Helper;

use Branch8\HotaiCore\Model\Product\VirtualProductType;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;
use HotaiConnected\Qware\Model\Config\Source\ApiMode;

class Common
{
    const ATTRIBUTE_CODE_QWARE_GUID                 = "qware_guid";
    const ATTRIBUTE_CODE_QWARE_SALE_START           = "qware_sale_start_date";
    const ATTRIBUTE_CODE_QWARE_SALE_END             = "qware_sale_end_date";

    const CONFIG_PREFIX_API                         = "qware/api/dev/";
    const CONFIG_PREFIX_API_PROD                    = "qware/api/production/";
    const CONFIG_PATH_API_MODE                      = "qware/api/general/api_mode";
    const CONFIG_CODE_API_DOMAIN                    = "api_domain";
    const CONFIG_CODE_API_VERSION                   = "api_version";
    const CONFIG_CODE_APP_ID                        = "app_id";
    const CONFIG_CODE_APP_KEY                       = "app_key";
    const CONFIG_CODE_CONSUMER_CODE                 = "consumer_code";
    const CONFIG_CODE_SECURITY_CODE                 = "security_code";
    const CONFIG_CODE_NOTIFICATION_ALLOWED_IPS      = "notification_allowed_ips";

    const ENCRYPT_METHOD = "des-ede3-cbc";

    /** @var ProductRepositoryInterface */
    protected $productRepository;

    /** @var ScopeConfigInterface */
    protected $scopeConfig;

    protected $apiMode = null;

    public function __construct(
        ProductRepositoryInterface $productRepository,
        ScopeConfigInterface $scopeConfig
    ) {
        $this->productRepository = $productRepository;
        $this->scopeConfig       = $scopeConfig;
    }

    /**
     * 確認商品是否為安源票券
     *
     * @param integer $productId
     * @return boolean
     */
    public function IsQwareTicketProduct(int $productId): bool
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

        return $virtualProductType->getValue() == VirtualProductType::TYPE_QWARE_TICKET;
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
                throw new \Exception("Something went wrong while getting Qware api mode: " . $this->apiMode);
        }
    }
}
