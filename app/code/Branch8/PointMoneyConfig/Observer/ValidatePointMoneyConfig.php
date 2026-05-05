<?php

namespace Branch8\PointMoneyConfig\Observer;

use Branch8\PointMoneyConfig\Helper\Common as Helper;
use Branch8\PointMoneyConfig\Helper\RecommendProduct as RecommendProduct;
use Branch8\PointMoneyConfig\Model\Product\PointMoneyConfigFreeRatioRedeemLimitType;
use Branch8\PointMoneyConfig\Model\Product\PointMoneyConfigType as PointMoneyTypeModel;
use Magento\Catalog\Model\Product;
use Magento\Framework\Event\ObserverInterface;

class ValidatePointMoneyConfig implements ObserverInterface
{
    /** @var Helper */
    protected $helper;

    /** @var RecommendProduct */
    protected $recommendProduct;

    /** @var PointMoneyTypeModel */
    protected $pointMoneyTypeModel;

    protected $price;
    protected $type;
    protected $productPoint;
    protected $upperRedeemType;
    protected $upperRedeemValue;
    protected $lowerRedeemType;
    protected $lowerRedeemValue;

    public function __construct(
        Helper $helper,
        RecommendProduct $recommendProduct,
        PointMoneyTypeModel $pointMoneyTypeModel
    ) {
        $this->helper              = $helper;
        $this->recommendProduct    = $recommendProduct;
        $this->pointMoneyTypeModel = $pointMoneyTypeModel;
    }

    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        $product                = $observer->getProduct();
        $this->price            = $this->getProductPrice($product);
        $this->type             = $product->getData($this->helper::ATTRIBUTE_CODE_TYPE);
        $this->productPoint     = $product->getData($this->helper::ATTRIBUTE_CODE_PRODUCT_POINT);
        $this->upperRedeemType  = $product->getData($this->helper::ATTRIBUTE_CODE_FREE_RATIO_UPPER_REDEEM_LIMIT_TYPE);
        $this->upperRedeemValue = $product->getData($this->helper::ATTRIBUTE_CODE_FREE_RATIO_UPPER_REDEEM_LIMIT_VALUE);
        $this->lowerRedeemType  = $product->getData($this->helper::ATTRIBUTE_CODE_FREE_RATIO_LOWER_REDEEM_LIMIT_TYPE);
        $this->lowerRedeemValue = $product->getData($this->helper::ATTRIBUTE_CODE_FREE_RATIO_LOWER_REDEEM_LIMIT_VALUE);

        if (empty($this->type)) {
            return;
        }

        $this->validateType($this->type);

        $point_money_config_product_point = $this->recommendProduct->getPointMoneyConfigProductPoint(
            $this->getProductPrice($product),
            $product->getData($this->helper::ATTRIBUTE_CODE_TYPE),
            $product->getData($this->helper::ATTRIBUTE_CODE_FREE_RATIO_LOWER_REDEEM_LIMIT_TYPE),
            (int) $product->getData($this->helper::ATTRIBUTE_CODE_FREE_RATIO_LOWER_REDEEM_LIMIT_VALUE)
        );
        $product->setData($this->helper::ATTRIBUTE_CODE_PRODUCT_POINT, $point_money_config_product_point);

        if ($this->type == PointMoneyTypeModel::TYPE_ONLY_MONEY || $this->type == PointMoneyTypeModel::TYPE_ONLY_POINT) {
            return;
        }

        if ($this->type == PointMoneyTypeModel::TYPE_FIXED_POINT_AND_MONEY) {
            $this->validateProductPoint($this->type, $this->productPoint);
            $this->validateRatioRule($this->price, $this->type, $this->productPoint);
            return;
        }

        if (!in_array($this->type, $this->helper::TYPES_ALLOW_FREE_RATIO_REDEEM_SETTING)) {
            $product->setData($this->helper::ATTRIBUTE_CODE_FREE_RATIO_UPPER_REDEEM_LIMIT_VALUE, null);
            $product->setData($this->helper::ATTRIBUTE_CODE_FREE_RATIO_LOWER_REDEEM_LIMIT_VALUE, null);
            return;
        }

        if ($this->type == PointMoneyTypeModel::TYPE_FREE_RATIO_WITHOUT_LIMIT) {
            $product->setData($this->helper::ATTRIBUTE_CODE_FREE_RATIO_UPPER_REDEEM_LIMIT_VALUE, null);
            $product->setData($this->helper::ATTRIBUTE_CODE_FREE_RATIO_LOWER_REDEEM_LIMIT_VALUE, null);
            return;
        }

        // Fix issue empty value on seller dashboard
        if(empty($this->upperRedeemValue)){
            $this->upperRedeemValue = 0;
        }
        if(empty($this->lowerRedeemValue)){
            $this->lowerRedeemValue = 0;
        }

        $this->validateRedeemType($this->upperRedeemType);
        $this->validateRedeemValue($this->upperRedeemValue);

        $this->validateRedeemType($this->lowerRedeemType);
        $this->validateRedeemValue($this->lowerRedeemValue);

        if ($this->upperRedeemType == PointMoneyConfigFreeRatioRedeemLimitType::TYPE_PERCENTAGE) {
            $this->validatePercentageValue((float) $this->upperRedeemValue);
        }

        if ($this->upperRedeemType == PointMoneyConfigFreeRatioRedeemLimitType::TYPE_POINT) {
            $this->validateRatioRule($this->price, $this->type, $this->upperRedeemValue);
        }

        if ($this->lowerRedeemType == PointMoneyConfigFreeRatioRedeemLimitType::TYPE_POINT) {
            $this->validateRatioRule($this->price, $this->type, $this->lowerRedeemValue);
        }

        if ($this->upperRedeemType == $this->lowerRedeemType) {
            $this->validateForFreeRatioUpperValueShouldBeGreaterThanLowerValue();
        }
    }

    /**
     * 獲取產品價格
     *
     * @param Product $product
     * @return float|null
     */
    protected function getProductPrice(Product $product): ?float
    {
        if ($product->getId() && $product->getTypeId() == \Magento\ConfigurableProduct\Model\Product\Type\Configurable::TYPE_CODE) {
            return $product->getFinalPrice();
        }

        if($product->getFinalPrice()){
            return $product->getFinalPrice();
        }
        
        return $product->getPrice();
    }

    /**
     * 檢查點金設定類型
     *
     * @param string $type
     * @return void
     */
    protected function validateType(string $type): void
    {
        if (!is_numeric($type)) {
            throw new \Exception(__("Type must be a valid number.(Current input: %1)", $type));
        }

        $allTypes = \Branch8\PointMoneyConfig\Model\Product\PointMoneyConfigType::getOptionArray();

        if (!isset($allTypes[$type])) {
            throw new \Exception(__("Type value is not one of current options.(Current input: %1)", $type));
        }
    }

    /**
     * 檢查固定點金的點數設定
     *
     * @param string $type
     * @param string|null $productPoint
     * @return void
     */
    protected function validateProductPoint(string $type, ?string $productPoint): void
    {
        if (!in_array($type, $this->helper::TYPES_REQUIRE_PRODUCT_POINT)) {
            return;
        }

        if (!is_numeric($productPoint)) {
            throw new \Exception(__("Product point must be a valid number.(Current input: %1)", $productPoint));
        }

        $productPointNumber = (double) $productPoint;

        if ($productPointNumber < 0) {
            throw new \Exception(__("Product point must be greater than 0.(Current input: %1)", $productPoint));
        }
    }

    /**
     * 檢查百分比是否合法
     *
     * @param float $value
     * @throws \Exception
     * @return void
     */
    protected function validatePercentageValue(float $value)
    {
        if (!(0 <= $value && $value <= 100)) {
            throw new \Exception(__("Percentage value should be between 0 and 100."));
        }
    }

    /**
     * 檢查固定點金的點數轉換後價值不可超過產品本身售價
     *
     * @param mixed $productPrice
     * @param string $type
     * @param mixed $productPoint
     * @throws \Exception
     * @return void
     */
    protected function validateRatioRule(?float $productPrice, string $type, ?string $productPoint): void
    {
        if (!in_array($type, $this->helper::TYPES_REQUIRE_RATIO_RULE)) {
            return;
        }

        if (is_null($productPoint)) {
            return;
        }

        $ratio = $this->helper->getRatio();

        $moneyValue = (double) $productPoint * $ratio;

        if ((double) $productPrice < (double) $moneyValue) {
            throw new \Exception(
                __(
                    "For Current point-money type: %1, the money value of product point can not exceed product price.(Current price: %2, ratio: %3, product point: %4, money value: %5)",
                    $this->pointMoneyTypeModel->getOptionText($type),
                    $productPrice,
                    $ratio,
                    $productPoint,
                    $moneyValue,
                )
            );
        }
    }

    /**
     * 檢查自由點金的類型設定
     *
     * @param string|null $redeemType
     * @return void
     */
    protected function validateRedeemType(?string $redeemType): void
    {
        if (!is_numeric($redeemType)) {
            throw new \Exception(__("Redeem Type must be a valid number.(Current input: %1)", $redeemType));
        }

        $allRedeemTypes = PointMoneyConfigFreeRatioRedeemLimitType::getOptionArray();

        if (!isset($allRedeemTypes[$redeemType])) {
            throw new \Exception(__("Redeem Type value is not one of current options.(Current input: %1)", $redeemType));
        }
    }

    /**
     * 檢查自由點金的數值設定
     *
     * @param string $redeemValue
     * @return boolean
     */
    protected function validateRedeemValue(?string $redeemValue): void
    {
        if (!is_numeric($redeemValue)) {
            throw new \Exception(__("Redeem value must be a valid number.(Current input: %1)", $redeemValue));
        }

        $redeemValueNumber = (double) $redeemValue;

        if ($redeemValueNumber < 0) {
            throw new \Exception(__("Redeem value must be greater than 0.(Current input: %1)", $redeemValue));
        }
    }

    /**
     * 當自由點金上下限類型相同時, 檢查上限值必須要大於下限值
     *
     * @return void
     */
    protected function validateForFreeRatioUpperValueShouldBeGreaterThanLowerValue(): void
    {
        if (is_null($this->upperRedeemValue)) {
            return;
        }

        if (is_null($this->lowerRedeemValue)) {
            return;
        }

        //now upper can be 0, it mean that unlimit
//        if ((int) $this->upperRedeemValue < (int) $this->lowerRedeemValue) {
//            throw new \Exception(__("Upper redeem value should be greater than lower redeem value."));
//        }
    }
}
