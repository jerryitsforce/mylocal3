<?php

namespace Branch8\PointMoneyCollect\Helper;

use Branch8\PointMoneyConfig\Helper\Common;
use Branch8\PointMoneyConfig\Helper\Common as PointMoneyConfigCommon;
use Branch8\PointMoneyConfig\Model\Product\PointMoneyConfigFreeRatioRedeemLimitType;
use Magento\Bundle\Model\Product\Type as BundleType;
use Magento\Checkout\Model\Session as CheckoutSession;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\Pricing\PriceCurrencyInterface;
use Magento\Quote\Api\CartItemRepositoryInterface as QuoteItemRepository;
use Magento\Quote\Api\CartRepositoryInterface;
use Magento\Quote\Model\Quote;
use Magento\Quote\Model\Quote\Item;
use Magento\Quote\Model\QuoteRepository;

class Data extends \Magento\Framework\App\Helper\AbstractHelper{

    const RATECONF = 'sales/point_setting/rate';

    /**
     * @var PriceCurrencyInterface
     */
    protected $priceCurrency;

    /**
     * @var int
     */
    private  $allocatedPoint = 0;

    /**
     * @var float
     */
    private  $pointConvertRate = 0;

    /**
     * @var float
     */
    private  $freeRatioItemsTotal = 0;

    /**
     * @var array
     */
    protected $requireProductPointItems = [];

    /**
     * @var array
     */
    protected $freeRatioItems = [];

    /**
     * @var \Magento\Store\Model\StoreManagerInterface
     */
    protected $_storeManager;
    /**
     * @var CheckoutSession
     */
    protected $checkoutSession;
    /**
     * @var QuoteRepository
     */
    protected $quoteRepository;
    /**
     * @var QuoteItemRepository
     */
    protected $quoteItemRepository;
    /**
     * @var \Magento\Quote\Model\ResourceModel\Quote\Item\CollectionFactory
     */
    protected $itemCollectionFactory;

    protected $pointHelper;

    /**
     * @param \Magento\Framework\App\Helper\Context $context
     * @param PriceCurrencyInterface $priceCurrency
     * @param \Magento\Store\Model\StoreManagerInterface $storeManager
     * @param CheckoutSession $checkoutSession
     * @param QuoteRepository $quoteRepository
     * @param QuoteItemRepository $quoteItemRepository
     * @param \Magento\Quote\Model\ResourceModel\Quote\Item\CollectionFactory $itemCollectionFactory
     * @param \Branch8\HotaiPoint\Helper\Data $pointHelper
     */
    public function __construct(
        \Magento\Framework\App\Helper\Context $context,
        PriceCurrencyInterface $priceCurrency,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        CheckoutSession $checkoutSession,
        QuoteRepository $quoteRepository,
        QuoteItemRepository $quoteItemRepository,
        \Magento\Quote\Model\ResourceModel\Quote\Item\CollectionFactory $itemCollectionFactory,
        \Branch8\HotaiPoint\Helper\Data $pointHelper
    ){
        parent::__construct($context);
        $this->priceCurrency = $priceCurrency;
        $this->_storeManager = $storeManager;
        $this->pointConvertRate = $this->getPointConvertRate();
        $this->checkoutSession = $checkoutSession;
        $this->quoteRepository = $quoteRepository;
        $this->quoteItemRepository = $quoteItemRepository;
        $this->itemCollectionFactory = $itemCollectionFactory;
        $this->pointHelper = $pointHelper;
    }

    public function isValidPointApply($quote){
        $totalPointApplied = $quote->getPointUsedTotal();

        if($this->getHotaiPointByCustomerId($quote->getCustomerId()) < $totalPointApplied){
            return false;
        }

        $pointLimit = $this->getPointLimit();

        if($totalPointApplied > $pointLimit['max_point']){
            return false;
        }
        if($totalPointApplied < $pointLimit['min_point']){
            return false;
        }

        return true;
//        $allItems = $quote->getAllItems();
//        $errorProducts = [];
//        foreach ($allItems as $item) {
//            //will apply for bundle items
//            if(!$item->getAvailableToCheckout() || $item->getProductType() == \Magento\Bundle\Model\Product\Type::TYPE_CODE){
//                continue;
//            }
//            if($item->getParentItemId()){
//                $parentItem = $this->itemCollectionFactory->create()
//                    ->addFieldToFilter('item_id', $item->getParentItemId())
//                    ->getFirstItem();
//                //for configurable product, check parent config
//                if($parentItem->getProductType() == \Magento\ConfigurableProduct\Model\Product\Type\Configurable::TYPE_CODE){
//                    continue;
//                }
//            }
//            list($lowerLimit, $upperLimit) = $this->getSimpleItemLimit($item->getProduct(), $item);
//            $itemQty = $item->getQty();
//            $lowerLimit = $lowerLimit * $itemQty;
//            $upperLimit = $upperLimit * $itemQty;
//            $pointApplied = $item->getRowTotalPointUsed();
//
//            if($pointApplied > $upperLimit || $pointApplied < $lowerLimit){
//                $errorProducts[] = $item->getName();
//            }
//
//        }
//        if(count($errorProducts) > 0){
//            return false;
//        }
//        return true;
    }

    public function applyPoint($point, $quote){
        // Check if the point is within the customer's available points
        $customerPoints = $this->getHotaiPointByCustomerId($quote->getCustomerId());
        if ($point > $customerPoints) {
            return [
                'success' => false,
                'msg' => __('You do not have enough points.')
            ];
        }
        //get min-max point for this quote
        $limitPointData = $this->getPointLimit();

        if($point < $limitPointData['min_point']){
            return [
                'success' => false,
                'msg' => sprintf(__('該訂單點數折抵為%s%s%s'), $limitPointData['min_point'],'-', number_format($limitPointData['max_point'], 0, '.', ','))
            ];
        }
        if($point > $limitPointData['max_point']){
            return [
                'success' => false,
                'msg' => sprintf(__('Exceeded allowed points, maximum point for this order is %s'), number_format($limitPointData['max_point'], 0, '.', ','))
            ];
        }

        //add point to order item
        $this->collect($quote, $point);
        //add to quote
        $quote->setData('point_used_total', $point);
        $quote->setData('point_discount_total', floor($point*$this->pointConvertRate));
        $quote->setTotalsCollectedFlag(false);
        $quote->collectTotals();
        $this->quoteRepository->save($quote);
        return [
            'success' => true
        ];
    }

    /**
     * @throws \Exception
     */
    public function collect(Quote $quote, $totalPoint)
    {
        $this->freeRatioItems = [];
        $this->requireProductPointItems = [];
        // TODO: distribute the point discount and point used to each item based on the item price ratio and product attributes
        /**
         * @var Item $item
         */
        foreach ($quote->getAllItems() as $key => $item) {
            // Get the product attributes for point money config
            $product = $item->getProduct();
            if (!$item->getAvailableToCheckout() ||
                !$product ||
                !$product->getPointMoneyConfigType() ||
                in_array($product->getPointMoneyConfigType(), PointMoneyConfigCommon::TYPES_ONLY_PRODUCT_MONEY) ||
                $product->getTypeId() == \Magento\Bundle\Model\Product\Type::TYPE_CODE /*apply to childs*/
            ) {
                continue;
            }
            if($item->getParentItemId()){
                $parentItem = $this->itemCollectionFactory->create()
                    ->addFieldToFilter('item_id', $item->getParentItemId())
                    ->getFirstItem();
                //for parent is configurable product, ignore child
                if($parentItem->getProductType() == \Magento\ConfigurableProduct\Model\Product\Type\Configurable::TYPE_CODE){
                    continue;
                }
            }

            //分類商品
            if (in_array($product->getPointMoneyConfigType(), PointMoneyConfigCommon::TYPES_ONLY_PRODUCT_POINT)) {
                $this->requireProductPointItems[] = $item;
            } elseif (in_array($product->getPointMoneyConfigType(), PointMoneyConfigCommon::TYPES_ALLOW_FREE_RATIO_REDEEM_SETTING)) {
                //用在計算比例
                $this->freeRatioItemsTotal += ($item->getRowTotalWithDiscount() - $item->getDiscountAmount());
                $this->freeRatioItems[] = $item;
            }
        }
        /**
         * 先處理 固定點 or 固定點加固定金額商品 (TYPES_REQUIRE_PRODUCT_POINT)
         *
         * @var Item $requireProductPointItem
         */
        foreach ($this->requireProductPointItems as $requireProductPointItem) {
            $this->distributeFixedPointToItem($requireProductPointItem);
        }

        //calculate percent of free ratio
        $remainPoint = $totalPoint - $this->allocatedPoint;

        $itemPointCalc = [];
        $itemDeltaLowerUper = [];
        $totalDelta = 0;
        foreach($this->freeRatioItems as $_item) {
            $product = $_item->getProduct();
            $itemPrice = ($_item->getRowTotalInclTax() - $_item->getDiscountAmount())/ $_item->getQty();

            if ($product->getPointMoneyConfigType() ==  PointMoneyConfigCommon::TYPE_RATIO_LIMIT) {

                if($product->getPointMoneyConfigFreeRatioUpperRedeemLimitValue() == 0){
                    $upperPointLimit = $itemPrice;
                }else{
                    if ($product->getPointMoneyConfigFreeRatioUpperRedeemLimitType() == PointMoneyConfigFreeRatioRedeemLimitType::TYPE_PERCENTAGE) {
                        $upperPointLimit = floor($itemPrice * ($product->getPointMoneyConfigFreeRatioUpperRedeemLimitValue() / 100) / $this->pointConvertRate);
                    } else {
                        $upperPointLimit = $product->getPointMoneyConfigFreeRatioUpperRedeemLimitValue();
                    }
                }

                if($product->getPointMoneyConfigFreeRatioLowerRedeemLimitValue() == 0){
                    $lowerPointLimit = 0;
                }else{
                    // 一個item所需點數下限與type
                    if ($product->getPointMoneyConfigFreeRatioLowerRedeemLimitType() == PointMoneyConfigFreeRatioRedeemLimitType::TYPE_PERCENTAGE) {
                        $lowerPointLimit = floor($itemPrice * ($product->getPointMoneyConfigFreeRatioLowerRedeemLimitValue() / 100) / $this->pointConvertRate);
                    } else {
                        $lowerPointLimit = $product->getPointMoneyConfigFreeRatioLowerRedeemLimitValue();
                    }
                }

            }
            if ($product->getPointMoneyConfigType() == PointMoneyConfigCommon::TYPE_RATIO_NO_LIMIT) {
                $lowerPointLimit = 0;
                $upperPointLimit = $itemPrice;
            }
            if($upperPointLimit > $itemPrice){
                $upperPointLimit = $itemPrice;
            }
            $lowerPointLimit = $lowerPointLimit * $_item->getQty();
            $upperPointLimit = $upperPointLimit * $_item->getQty();

            //delta item
            $deltaUperLower = $upperPointLimit - $lowerPointLimit;
//            $totalDelta += $deltaUperLower;
            $itemDeltaLowerUper[$_item->getId()] = $deltaUperLower;
            //point for every item, init is lower
            $itemPointCalc[$_item->getId()] = $lowerPointLimit;

            $remainPoint -= $lowerPointLimit;
        }
        $itemPointCalc = $this->calculatePointForFreeRatio($itemPointCalc, $remainPoint, $itemDeltaLowerUper);
        /**
         * Algorithm: fill from top to bottom, after taking the lower, fill freely until all points are gone
         */
//        foreach($itemPointCalc as $_itemId => &$_itemPointCalc){
//            $itemDelta = min($itemDeltaLowerUper[$_itemId], $remainPoint);
//            $_itemPointCalc += $itemDelta;
//            $remainPoint -= $itemDelta;
//            if($remainPoint <= 0){
//                break;
//            }
//        }

        /**
         * 再處理 自由點加金分配商品 (TYPES_ALLOW_FREE_RATIO_REDEEM_SETTING)
         *
         * @var Item $freeRatioItem
         */
        foreach ($this->freeRatioItems as $freeRatioItem) {
            $this->distributeFreeRatioPointToItem($freeRatioItem, $itemPointCalc[$freeRatioItem->getId()]);
        }

        return $this;
    }

    /**
     * @param Item $item
     * @return void
     * @throws \Magento\Framework\Exception\CouldNotSaveException
     * @throws \Magento\Framework\Exception\InputException
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    protected function distributeFixedPointToItem(Item $item)
    {
        $product = $item->getProduct();
        $itemPointNeed =  0;
        $itemPrice = ($item->getRowTotalInclTax() - $item->getDiscountAmount())/ $item->getQty();

        if (in_array($product->getPointMoneyConfigType(), PointMoneyConfigCommon::TYPES_ONLY_PRODUCT_POINT)) {
            $itemPointNeed = $itemPrice / $this->pointConvertRate;//convert to point
        }
        $itemPointNeed = $itemPointNeed * $item->getQty();

        $this->allocatedPoint += $itemPointNeed;
        $item->setData('row_total_point_used', $itemPointNeed);
        $item->setData('row_total_point_discount', floor($itemPointNeed*$this->pointConvertRate));
//        $this->quoteItemRepository->save($item);
    }

    /**
     * $pointCalculated = array[item => point]
     * $itemLimit = array[item id => max limit]
     * Return data = $point + new value calculated
     * @param $point
     * @param $itemLimit
     * @return void
     */
    public function calculatePointForFreeRatio($pointCalculated, $pointApply, $itemLimit){
        if($pointApply < count($itemLimit) || $pointApply == 0){
            /**
             * point < number item
             * add 1p for revery item, if p = 0, stop
             */
            foreach($itemLimit as $kLimit => $_itemLimit){
                if($pointApply == 0){
                    break;
                }
                $pointCalculated[$kLimit] ++;
                $pointApply --;

            }
            return $pointCalculated;
        }
        $average = floor($pointApply/count($itemLimit));
        $minValue = min($itemLimit);
        $pointToAlowcate = min($average, $minValue);
        foreach($itemLimit as $kAddLimit => &$_addLimit){
            $pointCalculated[$kAddLimit] += $pointToAlowcate;
            $pointApply -= $pointToAlowcate;
            /**
             * in new round, if new added value of item = calc value, remove them
             * if item value > calc item, minus calc value
             */
            if($_addLimit <= $pointToAlowcate){
                unset($itemLimit[$kAddLimit]);
            }
            $_addLimit -= $pointToAlowcate;
        }

        return $this->calculatePointForFreeRatio($pointCalculated, $pointApply, $itemLimit);
    }

    /**
     * @param Item $item
     * @return void
     * @throws \Magento\Framework\Exception\CouldNotSaveException
     * @throws \Magento\Framework\Exception\InputException
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    protected function distributeFreeRatioPointToItem(Item $item, $itemPointUsed)
    {
        $item->setData('row_total_point_used', $itemPointUsed);
        $item->setData('row_total_point_discount', floor($itemPointUsed*$this->pointConvertRate));
//        $this->quoteItemRepository->save($item);
    }

    public function getPointUsedTotal()
    {
        $quote = $this->checkoutSession->getQuote();
        $pointData = [
            'used_point' => $quote->getData('point_used_total')
        ];

        return $pointData;
    }
    public function getPointLimit()
    {
        $this->freeRatioItems = [];
        $this->requireProductPointItems = [];
        $quote = $this->checkoutSession->getQuote();

        $itemsTotalPointNeeds = 0;
        $ItemsUpperPointLimit = 0;
        $ItemsLowerPointLimit = 0;
        /**
         * 分類商品
         * @var  $key
         * @var  $item
         */
        foreach ($quote->getAllItems() as $key => $item) {
            // Get the product attributes for point money config
            $product = $item->getProduct();

            if (
                !$item->getAvailableToCheckout() ||
                !$product ||
                !$product->getPointMoneyConfigType() ||
                $product->getTypeId() == \Magento\Bundle\Model\Product\Type::TYPE_CODE ||
                in_array($product->getPointMoneyConfigType(), PointMoneyConfigCommon::TYPES_ONLY_PRODUCT_MONEY)
            ) {
                continue;
            }

            if($item->getParentItemId()){
                $parentItem = $this->itemCollectionFactory->create()
                    ->addFieldToFilter('item_id', $item->getParentItemId())
                    ->getFirstItem();
                //for parent is configurable product, ignore child because we will get from parent product
                if($parentItem->getProductType() == \Magento\ConfigurableProduct\Model\Product\Type\Configurable::TYPE_CODE){
                    continue;
                }
            }

            //分類商品
            if (in_array($product->getPointMoneyConfigType(), PointMoneyConfigCommon::TYPES_ONLY_PRODUCT_POINT)) {

                $this->requireProductPointItems[] = $item;

            } elseif (in_array($product->getPointMoneyConfigType(), PointMoneyConfigCommon::TYPES_ALLOW_FREE_RATIO_REDEEM_SETTING)) {
                $this->freeRatioItems[] = $item;
            }
        }

        /**
         * @var \Magento\Quote\Model\Quote\Item $item
         */
        foreach ($this->requireProductPointItems as $item) {
            $product = $item->getProduct();
            $itemPrice = ($item->getRowTotalInclTax() - $item->getDiscountAmount())/ $item->getQty();;
            if (in_array($product->getPointMoneyConfigType(), PointMoneyConfigCommon::TYPES_ONLY_PRODUCT_POINT)) {
                $itemPointUsed = $itemPrice / $this->getPointConvertRate();
            }

            //這些是下限
            $itemsTotalPointNeeds += $itemPointUsed * $item->getQty();
        }
        $lowerPointLimit = 0;
        $ItemsUpperPointLimit = 0;
        /**
         * @var \Magento\Quote\Model\Quote\Item $item
         */
        foreach ($this->freeRatioItems as $item) {
            $product = $item->getProduct();
            $itemPrice = ($item->getRowTotalInclTax() - $item->getDiscountAmount())/ $item->getQty();

            if($product->getPointMoneyConfigType() == PointMoneyConfigCommon::TYPE_RATIO_NO_LIMIT){
                $lowerPointLimit = 0;
                $upperPointLimit = $itemPrice;
            }
            if($product->getPointMoneyConfigType() == PointMoneyConfigCommon::TYPE_RATIO_LIMIT){

                if($product->getPointMoneyConfigFreeRatioLowerRedeemLimitValue() == 0){//unlimit for lower
                    $lowerPointLimit = 0;
                }else{
                    // 一個item所需點數下限與type
                    if ($product->getPointMoneyConfigFreeRatioLowerRedeemLimitType() == PointMoneyConfigFreeRatioRedeemLimitType::TYPE_PERCENTAGE) {
                        $lowerPointLimit = floor($itemPrice * ($product->getPointMoneyConfigFreeRatioLowerRedeemLimitValue() / 100) / $this->pointConvertRate);
                    } else {
                        $lowerPointLimit = $product->getPointMoneyConfigFreeRatioLowerRedeemLimitValue();
                    }
                }

                if($product->getPointMoneyConfigFreeRatioUpperRedeemLimitValue() == 0){
                    $upperPointLimit = $itemPrice;
                }else{
                    // 一個item所需點數上限與type
                    if ($product->getPointMoneyConfigFreeRatioUpperRedeemLimitType() == PointMoneyConfigFreeRatioRedeemLimitType::TYPE_PERCENTAGE) {
                        $upperPointLimit = floor($itemPrice * ($product->getPointMoneyConfigFreeRatioUpperRedeemLimitValue() / 100) / $this->pointConvertRate);
                    } else {
                        $upperPointLimit = $product->getPointMoneyConfigFreeRatioUpperRedeemLimitValue();
                    }
                }
                if($upperPointLimit > $itemPrice){
                    $upperPointLimit = $itemPrice;
                }

            }

            $ItemsUpperPointLimit += $upperPointLimit * $item->getQty();
            $ItemsLowerPointLimit += $lowerPointLimit * $item->getQty();
        }

        $pointLimits = [
            'max_point' => $ItemsUpperPointLimit + $itemsTotalPointNeeds,
            'min_point' => $ItemsLowerPointLimit + $itemsTotalPointNeeds,
            'used_point' => $quote->getData('point_used_total')
        ];

        return $pointLimits;
    }

    public function getPointConvertRate()
    {
        // TODO: return the point rate for converting points to money
        // You can set the point rate as 1 for now
        $pointRate = 1;
        $rateConfig = $this->scopeConfig->getValue(
            \Branch8\PointMoneyConfig\Helper\Common::RATIO_CONFIG_PATH,
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE,
            $this->_storeManager->getStore()
        );
        if($rateConfig){
            $pointRate = $rateConfig;
        }
        return $pointRate;
    }

    public function getCustomerPoints(){
        $customerPoint = $this->pointHelper->getHotaiPoint();
        return $customerPoint;
    }

    public function getHotaiPointByCustomerId($customerId){
        $customerPoint = $this->pointHelper->getHotaiPointByCustomerId($customerId);
        return $customerPoint;
    }

    public function getLimitByQuoteItem($item){

        if(is_numeric($item)){
            $quoteItem = $this->itemCollectionFactory->create()
                ->addFieldToFilter('item_id', $item)
                ->getFirstItem();
        }else{
            $quoteItem = $item;
        }
        $lowerLimit = 0;
        $upperLimit = 0;
        $product = $quoteItem->getProduct();
        $productType = $product->getTypeId();
        if($productType == BundleType::TYPE_CODE){
            //get all child items that are added
            $bundleChildItemss = $this->itemCollectionFactory->create()
                ->addFieldToFilter('parent_item_id', $quoteItem->getId());
            foreach($bundleChildItemss as $_bundleChildItemss){
                $bundleChildProduct = $_bundleChildItemss->getProduct();
                $bundleChildLimit = $this->getSimpleItemLimit($bundleChildProduct, $_bundleChildItemss);
                $lowerLimit += $bundleChildLimit[0];
                $upperLimit += $bundleChildLimit[1];
            }

        }else{
            //all other types like simple
            list($lowerLimit, $upperLimit) = $this->getSimpleItemLimit($product, $quoteItem);
        }

        return [
            'lower_limit' => $lowerLimit,
            'upper_limit' => $upperLimit
        ];
    }

    protected function getSimpleItemLimit($product, $item){
        $lowerLimit = 0;
        $upperLimit = 0;
        $pointType = $product->getPointMoneyConfigType();
        $itemPrice = ($item->getRowTotalInclTax() - $item->getDiscountAmount())/ $item->getQty();
        if (in_array($pointType, PointMoneyConfigCommon::TYPES_ONLY_PRODUCT_POINT)) {
            $lowerLimit = $itemPrice;
            $upperLimit = $itemPrice;
        }else if($pointType == PointMoneyConfigCommon::TYPE_RATIO_NO_LIMIT){
            $lowerLimit = 0;
            $upperLimit = $itemPrice;
        }else if($pointType == PointMoneyConfigCommon::TYPE_RATIO_LIMIT){
            if($product->getPointMoneyConfigFreeRatioLowerRedeemLimitValue() == 0){//unlimit for lower
                $lowerLimit = 0;
            }else{
                // 一個item所需點數下限與type
                if ($product->getPointMoneyConfigFreeRatioLowerRedeemLimitType() == PointMoneyConfigFreeRatioRedeemLimitType::TYPE_PERCENTAGE) {
                    $lowerLimit = floor($itemPrice * ($product->getPointMoneyConfigFreeRatioLowerRedeemLimitValue() / 100) / $this->pointConvertRate);
                } else {
                    $lowerLimit = $product->getPointMoneyConfigFreeRatioLowerRedeemLimitValue();
                }
            }

            if($product->getPointMoneyConfigFreeRatioUpperRedeemLimitValue() == 0){
                $upperLimit = $itemPrice;
            }else{
                // 一個item所需點數上限與type
                if ($product->getPointMoneyConfigFreeRatioUpperRedeemLimitType() == PointMoneyConfigFreeRatioRedeemLimitType::TYPE_PERCENTAGE) {
                    $upperLimit = floor($itemPrice * ($product->getPointMoneyConfigFreeRatioUpperRedeemLimitValue() / 100) / $this->pointConvertRate);
                } else {
                    $upperLimit = $product->getPointMoneyConfigFreeRatioUpperRedeemLimitValue();
                }
            }
            if($upperLimit > $itemPrice){
                $upperLimit = $itemPrice;
            }
        }

        return [
            $lowerLimit,
            $upperLimit
        ];
    }
}