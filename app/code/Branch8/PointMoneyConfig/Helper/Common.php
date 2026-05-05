<?php

namespace Branch8\PointMoneyConfig\Helper;

use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;

class Common
{
    const RATIO_CONFIG_PATH = "point_money_config/general/ratio";

    const ATTRIBUTE_CODE_TYPE                                = "point_money_config_type";
    const ATTRIBUTE_CODE_PRODUCT_POINT                       = "point_money_config_product_point";
    const ATTRIBUTE_CODE_FREE_RATIO_UPPER_REDEEM_LIMIT_TYPE  = "point_money_config_free_ratio_upper_redeem_limit_type";
    const ATTRIBUTE_CODE_FREE_RATIO_UPPER_REDEEM_LIMIT_VALUE = "point_money_config_free_ratio_upper_redeem_limit_value";
    const ATTRIBUTE_CODE_FREE_RATIO_LOWER_REDEEM_LIMIT_TYPE  = "point_money_config_free_ratio_lower_redeem_limit_type";
    const ATTRIBUTE_CODE_FREE_RATIO_LOWER_REDEEM_LIMIT_VALUE = "point_money_config_free_ratio_lower_redeem_limit_value";

    const DEFAULT_RATIO = 1;

    const TYPES_ONLY_PRODUCT_MONEY = [1];
    const TYPES_ONLY_PRODUCT_POINT = [2];

    const TYPES_REQUIRE_PRODUCT_POINT = [4];

    const TYPE_RATIO_NO_LIMIT = 5;

    const TYPE_RATIO_LIMIT = 3;

    const TYPES_REQUIRE_RATIO_RULE = [3, 4];

    const TYPES_ALLOW_FREE_RATIO_REDEEM_SETTING = [3, 5];

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

    public function getTypesRequireProductPoint()
    {
        return self::TYPES_REQUIRE_PRODUCT_POINT;
    }

    public function getRatio(): float
    {
        $ratio = $this->scopeConfig->getValue(self::RATIO_CONFIG_PATH);

        return empty($ratio) ? self::DEFAULT_RATIO : (float) $ratio;
    }

    public function getTypeByProductId($productId)
    {
        /** @var \Magento\Catalog\Model\Product $product */
        $product = $this->productRepository->getById($productId);

        return $product->getData(self::ATTRIBUTE_CODE_TYPE);
    }

    public function getProductPointByProductId($productId)
    {
        /** @var \Magento\Catalog\Model\Product $product */
        $product = $this->productRepository->getById($productId);

        return $product->getData(self::ATTRIBUTE_CODE_PRODUCT_POINT);
    }

    public function getFreeRatioUpperRedeemLimitTypeByProductId($productId)
    {
        /** @var \Magento\Catalog\Model\Product $product */
        $product = $this->productRepository->getById($productId);
        $type    = $this->getTypeByProductId($productId);

        if (!in_array($type, self::TYPES_ALLOW_FREE_RATIO_REDEEM_SETTING)) {
            return null;
        }

        return $product->getData(self::ATTRIBUTE_CODE_FREE_RATIO_UPPER_REDEEM_LIMIT_TYPE);
    }

    public function getFreeRatioUpperRedeemLimitValueByProductId($productId)
    {
        /** @var \Magento\Catalog\Model\Product $product */
        $product = $this->productRepository->getById($productId);
        $type    = $this->getTypeByProductId($productId);

        if (!in_array($type, self::TYPES_ALLOW_FREE_RATIO_REDEEM_SETTING)) {
            return null;
        }

        return $product->getData(self::ATTRIBUTE_CODE_FREE_RATIO_UPPER_REDEEM_LIMIT_VALUE);
    }

    public function getFreeRatioLowerRedeemLimitTypeByProductId($productId)
    {
        /** @var \Magento\Catalog\Model\Product $product */
        $product = $this->productRepository->getById($productId);
        $type    = $this->getTypeByProductId($productId);

        if (!in_array($type, self::TYPES_ALLOW_FREE_RATIO_REDEEM_SETTING)) {
            return null;
        }

        return $product->getData(self::ATTRIBUTE_CODE_FREE_RATIO_LOWER_REDEEM_LIMIT_TYPE);
    }

    public function getFreeRatioLowerRedeemLimitValueByProductId($productId)
    {
        /** @var \Magento\Catalog\Model\Product $product */
        $product = $this->productRepository->getById($productId);
        $type    = $this->getTypeByProductId($productId);

        if (!in_array($type, self::TYPES_ALLOW_FREE_RATIO_REDEEM_SETTING)) {
            return null;
        }

        return $product->getData(self::ATTRIBUTE_CODE_FREE_RATIO_LOWER_REDEEM_LIMIT_VALUE);
    }
}
