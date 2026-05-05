<?php

namespace HotaiConnected\OpenHub\Helper;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Store\Model\ScopeInterface;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Branch8\HotaiCore\Model\Product\VirtualProductType;

class Common
{
    const ATTRIBUTE_SET_NAME = "openhub_ticket";
    const ATTRIBUTE_CODE_OPENHUB_PRODUCT_ID = "openhub_product_id";
    
    // API 配置路徑
    const CONFIG_PREFIX = 'openhub/api/';
    
    // General 配置
    const CONFIG_CODE_API_MODE = 'general/api_mode';
    const CONFIG_CODE_ENABLED  = 'general/enabled';
    
    // Dev 環境配置
    const CONFIG_CODE_DEV_API_DOMAIN = 'dev/api_domain';
    const CONFIG_CODE_DEV_API_KEY    = 'dev/x_api_key';
    
    // Production 環境配置
    const CONFIG_CODE_PROD_API_DOMAIN = 'production/api_domain';
    const CONFIG_CODE_PROD_API_KEY    = 'production/x_api_key';
    
    // API 模式
    const API_MODE_DEV        = 'dev';
    const API_MODE_PRODUCTION = 'production';

    /** @var ScopeConfigInterface */
    protected $scopeConfig;

    /** @var ProductRepositoryInterface */
    protected $productRepository;


    public function __construct(
        ScopeConfigInterface $scopeConfig,
        ProductRepositoryInterface $productRepository
    ) {
        $this->scopeConfig       = $scopeConfig;
        $this->productRepository = $productRepository;
    }

    /**
     * 檢查是否為 OpenHub 票券商品
     *
     * @param int $productId
     * @return bool
     */
    public function IsOpenHubTicketProduct(int $productId): bool
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

        return $virtualProductType->getValue() == VirtualProductType::TYPE_OPENHUB_TICKET;
    }

    /**
     * 檢查模組是否已啟用
     *
     * @return bool
     */
    public function isEnabled(): bool
    {
        return $this->scopeConfig->isSetFlag(
            self::CONFIG_PREFIX . self::CONFIG_CODE_ENABLED,
            ScopeInterface::SCOPE_STORE
        );
    }

    /**
     * 取得 API 模式 (dev/production)
     *
     * @return string
     */
    public function getApiMode(): string
    {
        return $this->scopeConfig->getValue(
            self::CONFIG_PREFIX . self::CONFIG_CODE_API_MODE,
            ScopeInterface::SCOPE_STORE
        ) ?: self::API_MODE_DEV;
    }

    /**
     * 根據配置代碼取得 API 設定值
     *
     * @param string $configCode
     * @return string
     */
    public function getApiConfigByConfigCode(string $configCode): string
    {
        $apiMode = $this->getApiMode();
        
        // 根據 API 模式選擇對應的配置
        $configPath = self::CONFIG_PREFIX . $apiMode . '/' . $this->mapConfigCode($configCode);
        
        return $this->scopeConfig->getValue(
            $configPath,
            ScopeInterface::SCOPE_STORE
        ) ?: '';
    }

    /**
     * 映射配置代碼到實際的配置路徑
     *
     * @param string $configCode
     * @return string
     */
    private function mapConfigCode(string $configCode): string
    {
        $mapping = [
            'api_domain' => 'api_domain',
            'api_key'    => 'x_api_key'
        ];

        return $mapping[$configCode] ?? $configCode;
    }

    /**
     * 取得 API 網域
     *
     * @return string
     */
    public function getApiDomain(): string
    {
        return $this->getApiConfigByConfigCode('api_domain');
    }

    /**
     * 取得 API 金鑰
     *
     * @return string
     */
    public function getApiKey(): string
    {
        return $this->getApiConfigByConfigCode('api_key');
    }

    /**
     * 檢查 API 憑證是否有效
     *
     * @return bool
     */
    public function hasValidApiCredentials(): bool
    {
        return !empty($this->getApiDomain()) 
            && !empty($this->getApiKey());
    }
}