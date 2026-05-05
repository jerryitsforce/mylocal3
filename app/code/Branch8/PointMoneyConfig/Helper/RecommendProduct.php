<?php

namespace Branch8\PointMoneyConfig\Helper;

use Branch8\PointMoneyConfig\Helper\Common as CommonHelper;
use Branch8\PointMoneyConfig\Model\Product\PointMoneyConfigType as PointMoneyConfigType;
use Branch8\PointMoneyConfig\Model\Product\PointMoneyConfigFreeRatioRedeemLimitType as PointMoneyLimitType;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Catalog\Model\ResourceModel\Product\CollectionFactory as ProductCollectionFactory;
use Magento\Catalog\Model\Product\Attribute\Source\Status as ProductStatus;
use Magento\Catalog\Model\ResourceModel\Product\Collection as ProductCollection;
use Magento\CatalogInventory\Helper\Stock;
use Magento\Catalog\Model\Product;

class RecommendProduct
{
    /** @var CommonHelper */
    protected $commonHelper;

    /** @var ProductRepositoryInterface */
    protected $productRepository;

    /** @var ProductCollectionFactory */
    protected $productCollectionFactory;

    /** @var Stock */
    protected $stock;

    protected $pointRatio;

    public function __construct(
        CommonHelper $commonHelper,
        ProductRepositoryInterface $productRepository,
        ProductCollectionFactory $productCollectionFactory,
        Stock $stock
    ) {
        $this->commonHelper             = $commonHelper;
        $this->productRepository        = $productRepository;
        $this->productCollectionFactory = $productCollectionFactory;
        $this->stock                    = $stock;
    }

    /**
     * 以純點的方式獲取推薦商品
     *
     * @param int $point
     * @return \Magento\Catalog\Model\ResourceModel\Product\Collection
     */
    public function getRecommendProductCollectionByOnlyPoint(int $point): ProductCollection
    {
        $removeProductKeys = [];
        $collection        = $this->getEnableAndInStockProductCollection();

        $productList = $collection->getItems();
        /** @var Product $product */
        foreach ($productList as $product) {
            switch ($product->getPointMoneyConfigType()) {
                // 不推薦
                case PointMoneyConfigType::TYPE_ONLY_MONEY:
                    $removeProductKeys[] = $product->getId();
                    break;

                // 傳入點數 >= 商品價錢轉換的點數
                case PointMoneyConfigType::TYPE_ONLY_POINT:
                    $productPointValue = $product->getPrice() / $this->getPointRatio();

                    if ($point < $productPointValue) {
                        $removeProductKeys[] = $product->getId();
                        break;
                    }

                    break;

                // (百分點數設定 >= 100) && (傳入點數 >= 百分比轉換的點數)
                // (固定點數設定 == 產品價錢) && (傳入點數 >= 固定點數)
                case PointMoneyConfigType::TYPE_FREE_RATIO:
                    if (!$this->recommendProductFreeRatioCheckerForOnlyPoint($product, $point)) {
                        $removeProductKeys[] = $product->getId();
                        break;
                    }

                    break;

                // 這一定會有錢的部分(固定點設定必須小於商品價錢), 所以不推薦
                case PointMoneyConfigType::TYPE_FIXED_POINT_AND_MONEY:
                    $removeProductKeys[] = $product->getId();
                    break;

                default:
                    $removeProductKeys[] = $product->getId();
                    break;
            }
        }

        foreach ($removeProductKeys as $removeKey) {
            $collection->removeItemByKey($removeKey);
        }

        return $collection;
    }

    /**
     * 為純點獲取推薦商品檢查自由點金設定
     *
     * @param \Magento\Catalog\Model\Product $product
     * @param int $point
     * @return bool
     */
    protected function recommendProductFreeRatioCheckerForOnlyPoint(Product $product, int $point): bool
    {
        $pointRatio = $this->getPointRatio();
        $limitType  = $this->commonHelper->getFreeRatioUpperRedeemLimitTypeByProductId($product->getId());
        $limitValue = $this->commonHelper->getFreeRatioUpperRedeemLimitValueByProductId($product->getId()) ?? 0;

        if ($limitType == PointMoneyLimitType::TYPE_PERCENTAGE) {
            $limitPoint = $this->transPriceToPointByPercentageSetting($product, $limitValue);

            return ($limitValue >= 100) && ($point >= $limitPoint);
        }

        if ($limitType == PointMoneyLimitType::TYPE_POINT) {
            $limitPoint                    = $limitValue;
            $productPriceWithoutPointValue = $product->getPrice() - ($limitPoint * $pointRatio);

            return ($productPriceWithoutPointValue <= 0) && ($point >= $limitPoint);
        }

        return false;
    }

    /**
     * 以點數及價錢上下限的方式獲取推薦商品
     *
     * @param int $point
     * @param int $lowerPrice
     * @param int $upperPrice
     * @throws \Exception
     * @return \Magento\Catalog\Model\ResourceModel\Product\Collection
     */
    public function getRecommendProductCollectionByPointAndPriceLimit(int $point, int $lowerPrice, int $upperPrice): ProductCollection
    {
        if ($lowerPrice > $upperPrice) {
            throw new \Exception(
                __(
                    "Upper price limit should be grater than lower price limit, " .
                    "upper price limit given: {$upperPrice}, " .
                    "lower price limit given: {$lowerPrice}."
                )
            );
        }

        $removeProductKeys = [];
        $collection        = $this->getEnableAndInStockProductCollection();

        $productList = $collection->getItems();
        /** @var Product $product */
        foreach ($productList as $product) {
            switch ($product->getPointMoneyConfigType()) {
                // 不推薦
                case PointMoneyConfigType::TYPE_ONLY_MONEY:
                    $removeProductKeys[] = $product->getId();
                    break;

                // 傳入點數 >= 商品價錢轉換的點數
                case PointMoneyConfigType::TYPE_ONLY_POINT:
                    $productPointValue = $product->getPrice() / $this->getPointRatio();

                    if ($point < $productPointValue) {
                        $removeProductKeys[] = $product->getId();
                    }

                    break;

                // 傳入點數 >= 商品設定下限點數
                // 傳入下限 <= (商品價錢-下限點數) && (商品價錢-上限點數) <= 傳入上限
                case PointMoneyConfigType::TYPE_FREE_RATIO:
                    if (!$this->lowerLimitChecker($product, $point, $lowerPrice)) {
                        $removeProductKeys[] = $product->getId();
                        break;
                    }

                    if (!$this->upperLimitChecker($product, $point, $upperPrice)) {
                        $removeProductKeys[] = $product->getId();
                        break;
                    }

                    break;

                // 傳入點數 >= 商品設定固定點數
                // 傳入下限 <= (商品價錢-固定點數) && (商品價錢-固定點數) <= 傳入上限
                case PointMoneyConfigType::TYPE_FIXED_POINT_AND_MONEY:
                    $productPoint = $this->commonHelper->getProductPointByProductId($product->getId());
                    $productPriceWithoutPointValue = $product->getPrice() - ($productPoint * $this->getPointRatio());

                    if ($point < $productPoint) {
                        $removeProductKeys[] = $product->getId();
                        break;
                    }

                    if ($productPriceWithoutPointValue < $lowerPrice) {
                        $removeProductKeys[] = $product->getId();
                        break;
                    }

                    if ($productPriceWithoutPointValue > $upperPrice) {
                        $removeProductKeys[] = $product->getId();
                        break;
                    }

                    break;

                default:
                    $removeProductKeys[] = $product->getId();
                    break;
            }
        }

        foreach ($removeProductKeys as $removeKey) {
            $collection->removeItemByKey($removeKey);
        }

        return $collection;
    }

    /**
     * 獲取"啟用"且"有庫存"的product collection
     *
     * @return \Magento\Catalog\Model\ResourceModel\Product\Collection
     */
    protected function getEnableAndInStockProductCollection(): ProductCollection
    {
        $collection = $this->productCollectionFactory->create();
        $collection->addAttributeToSelect('*');
        $collection->addAttributeToFilter('status', ['eq' => ProductStatus::STATUS_ENABLED]);
        $this->stock->addInStockFilterToCollection($collection);
        $collection->load();

        return $collection;
    }

    /**
     * 為純點+價錢上下限獲取推薦商品function檢驗自由點金下限
     *
     * @param \Magento\Catalog\Model\Product $product
     * @param int $point
     * @param int $lowerPrice
     * @return bool
     */
    protected function lowerLimitChecker(Product $product, int $point, int $lowerPrice): bool
    {
        $pointRatio = $this->getPointRatio();
        $limitType  = $this->commonHelper->getFreeRatioLowerRedeemLimitTypeByProductId($product->getId());
        $limitValue = $this->commonHelper->getFreeRatioLowerRedeemLimitValueByProductId($product->getId()) ?? 0;

        // 百分比:
        // 1. 百分比下限轉換成固定點數值看看傳入點數夠不夠(下限empty則跳過這個判斷)
        // 2. 下限判斷: (百分比下限點數轉換成等量價值, 下限empty則=0)加上(產品減去下限點數的價值)有沒有在(傳入的點數+價錢上下限價值)之間
        if ($limitType == PointMoneyLimitType::TYPE_PERCENTAGE) {
            $limitPoint = $this->transPriceToPointByPercentageSetting($product, $limitValue);

            if (!empty($limitValue) && $point < $limitPoint) {
                return false;
            }

            $productPriceWithoutPointValue = $product->getPrice() - ($limitPoint * $pointRatio);

            return $productPriceWithoutPointValue >= $lowerPrice;
        }

        // 點數:
        // 1. 看看傳入點數夠不夠產品點數下限(下限empty則跳過這個判斷)
        // 2. 下限判斷: (下限點數轉換成等量價值, 下限empty則=0)加上(產品減去下限點數的價值)有沒有在(傳入的點數+價錢上下限價值)之間
        if ($limitType == PointMoneyLimitType::TYPE_POINT) {
            $limitPoint = $limitValue;

            if (!empty($limitValue) && $point < $limitPoint) {
                return false;
            }

            $productPriceWithoutPointValue = $product->getPrice() - ($limitPoint * $pointRatio);

            return $productPriceWithoutPointValue >= $lowerPrice;
        }

        return false;
    }

    /**
     * 為純點+價錢上下限獲取推薦商品function檢驗自由點金上限
     *
     * @param \Magento\Catalog\Model\Product $product
     * @param int $point
     * @param int $upperPrice
     * @return bool
     */
    protected function upperLimitChecker(Product $product, int $point, int $upperPrice): bool
    {
        $pointRatio = $this->getPointRatio();
        $limitType  = $this->commonHelper->getFreeRatioUpperRedeemLimitTypeByProductId($product->getId());
        $limitValue = $this->commonHelper->getFreeRatioUpperRedeemLimitValueByProductId($product->getId()) ?? 0;

        // 百分比:
        // 上限判斷: (百分比上限點數轉換成等量價值, 上限empty則=產品總價)加上(產品減去上限點數的價值)有沒有在(傳入的點數+價錢上下限價值)之間
        if ($limitType == PointMoneyLimitType::TYPE_PERCENTAGE) {
            $productPriceWithoutPointValue = ($product->getPrice() / 100) * (100 - $limitValue);

            return $productPriceWithoutPointValue <= $upperPrice;
        }

        // 點數:
        // 上限判斷: (上限點數轉換成等量價值, 上限empty則=產品總價)加上(產品減去上限點數的價值)有沒有在(傳入的點數+價錢上下限價值)之間
        if ($limitType == PointMoneyLimitType::TYPE_POINT) {
            $limitPoint = $limitValue;

            $productPriceWithoutPointValue = $product->getPrice() - ($limitPoint * $pointRatio);

            return $productPriceWithoutPointValue <= $upperPrice;
        }

        return false;
    }

    /**
     * 將自由點金百分比設定轉換成實際點數
     *
     * @param \Magento\Catalog\Model\Product $product
     * @param int $limitValue
     * @return float
     */
    protected function transPriceToPointByPercentageSetting(Product $product, int $limitValue)
    {
        $pointRatio = $this->getPointRatio();

        return ($product->getPrice() / 100) * $limitValue / $pointRatio;
    }

    /**
     * 取得點數兌換金錢比率設定
     *
     * @return int
     */
    protected function getPointRatio(): float
    {
        if (!is_null($this->pointRatio)) {
            return $this->pointRatio;
        }

        $this->pointRatio = $this->commonHelper->getRatio();

        return $this->pointRatio;
    }

    /**
     * Calculate value for point_money_config_product_point attribute
     * @param float $price
     * @param int $config_type
     * @param int $free_ratio_lower_redeem_limit_type
     * @param int $free_ratio_lower_redeem_limit_value
     * @return int | float | null
     */
    public function getPointMoneyConfigProductPoint(
        $price,
        $config_type, 
        $free_ratio_lower_redeem_limit_type,
        $free_ratio_lower_redeem_limit_value
    ){
        $pointRatio = $this->getPointRatio();
        // No point for product only money - hide product when filter by point
        if($config_type == PointMoneyConfigType::TYPE_ONLY_MONEY){
            return -1;
        }
        // Set point base on price/point ratio
        if($config_type == PointMoneyConfigType::TYPE_ONLY_POINT){
            return $price / $pointRatio;
        }
        // Set point = 0 - filter condition free ratio without limit type for always show "free ratio without limit" type
        if($config_type == PointMoneyConfigType::TYPE_FREE_RATIO_WITHOUT_LIMIT){
            return 0;
        }
        // set point to filter condition free ratio type and lower limit < current point for "free ratio" type
        if($config_type == PointMoneyConfigType::TYPE_FREE_RATIO){
            if ($free_ratio_lower_redeem_limit_type == PointMoneyLimitType::TYPE_PERCENTAGE) {
                return ($price / 100) * $free_ratio_lower_redeem_limit_value / $pointRatio;
            }

            if ($free_ratio_lower_redeem_limit_type == PointMoneyLimitType::TYPE_POINT) {
                return $free_ratio_lower_redeem_limit_value / $pointRatio;
            }
        }

        return -1;
    }
}
