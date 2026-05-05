<?php
declare(strict_types=1);

namespace Branch8\WebkulMpsplitorder\Model\Services;

use Branch8\HotaiOrderNumber\Model\Actions\HotaiGenerateIncrementIdForOrders;
use Branch8\WebkulMpsplitorder\Model\SubQuoteShippingMethodResolver;
use Branch8\WebkulMpsplitorder\Model\TransferMasterQuoteDataToSubQuoteDataService;
use Branch8\WebkulMpsplitorder\Model\TransferMasterQuoteItemDataToSubQuoteItemDataService;
use Magento\Catalog\Model\ProductFactory;
use Magento\Customer\Api\Data\GroupInterface;
use Magento\Framework\App\Area;
use Magento\Framework\App\ObjectManager;
use Magento\Framework\App\State;
use Magento\Framework\Exception\LocalizedException;
use Magento\Quote\Model\Quote;
use Magento\Quote\Model\ResourceModel\Quote\Item as QuoteItemResource;
use Magento\Quote\Model\QuoteRepository;
use Magento\Setup\Exception;
use Magento\Store\Model\StoreManagerInterface;
use Psr\Log\LoggerInterface;
use Magento\Quote\Model\ResourceModel\Quote\Address as AddressResource;
use Branch8\GiftToFriend\Model\Config\Source\AddressType;
use Branch8\HotaiCore\Helper\Common as HotaiCoreCommonHelper;

class SplitMasterQuoteIntoSubQuote
{
    const SUB_NORMAL = 1;

    const SUB_SELLER_IN_FLAGSHIP_STORE = 2;

    const MAIN_SELLER_IN_FLAGSHIP_STORE = 3;

    const LOG_FOLDER_NAME = 'WebkulMpsplitorder/Services/SplitMasterQuoteIntoSubQuote';

    private $state;
    private \Magento\Quote\Model\QuoteFactory $quoteFactory;
    private \Webkul\Marketplace\Helper\Data $marketPlaceDataHelper;
    private TransferMasterQuoteItemDataToSubQuoteItemDataService $transferMasterQuoteItemDataToSubQuoteItemDataService;
    private \Magento\ConfigurableProduct\Model\Product\Type\Configurable $configurable;
    private StoreManagerInterface $storeManager;
    private Quote $masterQuote;
    private ProductFactory $productFactory;
    private LoggerInterface $logger;
    private Quote\AddressFactory $addressFactory;
    private SubQuoteShippingMethodResolver $shippingMethodResolver;
    private QuoteRepository $quoteRepository;

    private $addressResource;
    private TransferMasterQuoteDataToSubQuoteDataService $transferMasterQuoteDataToSubQuoteDataService;
    private QuoteItemResource $quoteItemResource;
    /**
     * @var \Branch8\SplitCart\Helper\Data
     */
    protected $splitCartHelperData;
    /**
     * @var \Branch8\HotaiShipping\Helper\Data
     */
    protected $hotaiShippingHelperData;
    /**
     * @var \Magento\Shipping\Model\Config
     */
    protected $shippingConfig;
    /**
     * @var \Magento\Shipping\Model\Carrier\AbstractCarrierInterface[]
     */
    private $activeShippingMethods;
    /**
     * @var QuoteItemResource\CollectionFactory
     */
    protected $quoteItemCollectionFactory;
    /**
     * @var
     */
    protected $pointMoneyCollectHelperData;
    /**
     * @var HotaiCoreCommonHelper
     */
    protected $hotaiCoreCommonHelper;
    private \Webkul\Mpsplitorder\Model\Mpsplitorder $parentOrder;

    private $hotaiGenerateIncrementIdForOrders;

    private $shippingDiscountSplitTotal = 0;
    /**
     * @var \Branch8\FlagshipStore\Helper\Sales
     */
    protected $flagshipSalesHelper;

    protected $sellerIds = [];
    /**
     * @var \Magento\Catalog\Model\ResourceModel\Product\CollectionFactory
     */
    protected $productCollectionFactory;
    /**
     * @var \Magento\Customer\Model\Session
     */
    protected $customerSession;
    /**
     * @var \Magento\Customer\Model\ResourceModel\CustomerRepository
     */
    protected $customerRepository;

    /**
     * @param \Magento\Quote\Model\QuoteFactory $quoteFactory
     * @param \Webkul\Marketplace\Helper\Data $mpDataHelper
     * @param \Magento\ConfigurableProduct\Model\Product\Type\Configurable $configurable
     * @param StoreManagerInterface $storeManager
     * @param QuoteRepository $quoteRepository
     * @param TransferMasterQuoteItemDataToSubQuoteItemDataService $transferMasterQuoteItemDataToSubQuoteItemDataService
     * @param ProductFactory $productFactory
     * @param Quote\AddressFactory $addressFactory
     * @param SubQuoteShippingMethodResolver $shippingMethodResolver
     * @param AddressResource $addressResource
     * @param QuoteItemResource $quoteItemResource
     * @param TransferMasterQuoteDataToSubQuoteDataService $transferMasterQuoteDataToSubQuoteDataService
     * @param LoggerInterface $logger
     * @param \Branch8\SplitCart\Helper\Data $splitCartHelperData
     * @param \Branch8\HotaiShipping\Helper\Data $hotaiShippingHelperData
     * @param \Magento\Shipping\Model\Config $shipconfig
     * @param QuoteItemResource\CollectionFactory $quoteItemCollectionFactory
     * @param HotaiGenerateIncrementIdForOrders $hotaiGenerateIncrementIdForOrders
     * @param \Branch8\PointMoneyCollect\Helper\Data $pointMoneyCollectHelperData
     * @param HotaiCoreCommonHelper $hotaiCoreCommonHelper
     */
    public function __construct(
        \Magento\Quote\Model\QuoteFactory                            $quoteFactory,
        \Webkul\Marketplace\Helper\Data                              $mpDataHelper,
        \Magento\ConfigurableProduct\Model\Product\Type\Configurable $configurable,
        StoreManagerInterface                                        $storeManager,
        QuoteRepository                                              $quoteRepository,
        TransferMasterQuoteItemDataToSubQuoteItemDataService         $transferMasterQuoteItemDataToSubQuoteItemDataService,
        ProductFactory                                               $productFactory,
        \Magento\Quote\Model\Quote\AddressFactory                    $addressFactory,
        SubQuoteShippingMethodResolver                               $shippingMethodResolver,
        AddressResource                                              $addressResource,
        QuoteItemResource                                            $quoteItemResource,
        TransferMasterQuoteDataToSubQuoteDataService                 $transferMasterQuoteDataToSubQuoteDataService,
        LoggerInterface                                              $logger,
        \Branch8\SplitCart\Helper\Data                               $splitCartHelperData,
        \Branch8\HotaiShipping\Helper\Data                           $hotaiShippingHelperData,
        \Magento\Shipping\Model\Config                               $shipconfig,
        \Magento\Quote\Model\ResourceModel\Quote\Item\CollectionFactory $quoteItemCollectionFactory,
        HotaiGenerateIncrementIdForOrders $hotaiGenerateIncrementIdForOrders,
        \Branch8\PointMoneyCollect\Helper\Data $pointMoneyCollectHelperData,
        \Branch8\FlagshipStore\Helper\Sales $flagshipSalesHelper,
        \Magento\Catalog\Model\ResourceModel\Product\CollectionFactory $productCollectionFactory,
        \Magento\Customer\Model\Session $customerSession,
        \Magento\Customer\Model\ResourceModel\CustomerRepository $customerRepository,
        HotaiCoreCommonHelper $hotaiCoreCommonHelper
    )
    {
        $this->addressFactory = $addressFactory;
        $this->logger = $logger;
        $this->productFactory = $productFactory;
        $this->storeManager = $storeManager;
        $this->configurable = $configurable;
        $this->quoteFactory = $quoteFactory;
        $this->addressResource = $addressResource;
        $this->marketPlaceDataHelper = $mpDataHelper;
        $this->transferMasterQuoteItemDataToSubQuoteItemDataService = $transferMasterQuoteItemDataToSubQuoteItemDataService;
        $this->shippingMethodResolver = $shippingMethodResolver;
        $this->quoteRepository = $quoteRepository;
        $this->quoteItemResource = $quoteItemResource;
        $this->transferMasterQuoteDataToSubQuoteDataService = $transferMasterQuoteDataToSubQuoteDataService;
        $this->splitCartHelperData = $splitCartHelperData;
        $this->hotaiShippingHelperData = $hotaiShippingHelperData;
        $this->shippingConfig = $shipconfig;
        $this->activeShippingMethods = $this->shippingConfig->getActiveCarriers();
        $this->quoteItemCollectionFactory = $quoteItemCollectionFactory;
        $this->pointMoneyCollectHelperData = $pointMoneyCollectHelperData;
        $this->hotaiGenerateIncrementIdForOrders = $hotaiGenerateIncrementIdForOrders;
        $this->flagshipSalesHelper = $flagshipSalesHelper;
        $this->productCollectionFactory = $productCollectionFactory;
        $this->customerSession = $customerSession;
        $this->customerRepository = $customerRepository;
        $this->hotaiCoreCommonHelper = $hotaiCoreCommonHelper;
    }

    /**
     * @param \Webkul\Mpsplitorder\Model\Mpsplitorder $parentOrder
     * @return SplitMasterQuoteIntoSubQuote
     */
    public function setParentOrder(\Webkul\Mpsplitorder\Model\Mpsplitorder $parentOrder)
    {
        $this->parentOrder = $parentOrder;
        return $this;
    }

    /**
     * @param Quote $masterQuote
     * @return SplitMasterQuoteIntoSubQuote
     */
    public function setMasterQuote(Quote $masterQuote)
    {
        $this->masterQuote = $masterQuote;
        return $this;
    }

    /**
     * @return Quote
     */
    public function getMasterQuote()
    {
        if (!$this->masterQuote->getCustomerEmail()) {
            $quoteDb = $this->quoteRepository->get($this->masterQuote->getId());
            $this->masterQuote->setCustomerEmail($quoteDb->getCustomerEmail());
        }

        return $this->masterQuote;
    }

    /**
     * @return array Quote
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function generate()
    {
        $this->shippingDiscountSplitTotal = 0;
        $masterQuote = $this->getMasterQuote();
        list($allItemInformation, $totalItemNeedToShip, $flagshipStoreSubCarts) = $this->groupProductFollowSeller($this->getMasterQuote());
        $shippingAddress = $masterQuote->getShippingAddress();
        $billingAddress = $masterQuote->getBillingAddress();
        /**
         * Update address for virtual cart
         */
        if(
            $masterQuote->isVirtual() &&
            (
                !$masterQuote->getData('is_gift_order') ||
                ($masterQuote->getData('is_gift_order') && $masterQuote->getData('gift_address_type') == AddressType::RECIPIENT_INPUT_ADDRESS)
            )
        ){
            $customerId = $masterQuote->getCustomerId();
            $customer = $this->customerRepository->getById($customerId);
            $customerFirstname = $customer->getFirstname();
            $telephone = $customer->getCustomAttribute('phone_number')->getValue();
            $billingAddress->setFirstname($customerFirstname);
            $billingAddress->setLastname('Hotai');
            $billingAddress->setTelephone($telephone);
        }
        $shippingAmountPerItemExcludeTax = 0;
        $sequence = 1;
        if ($shippingAddress) {
            $shippingAmountIncludeTax = (float)$shippingAddress->getShippingInclTax();
            $shippingAmountPerItemExcludeTax = $shippingAmountIncludeTax ? $shippingAmountIncludeTax / $totalItemNeedToShip : 0;
        }
        $subQuotes = [];
        $store = $masterQuote->getStore() ?: $this->storeManager->getStore();
        $couponCode = $masterQuote->getCouponCode();
        $ruleIds = $masterQuote->getAppliedRuleIds();
        $selectedShippingCarrier = null;
        foreach($this->activeShippingMethods as $_method){
            if(strpos((string)$this->getMasterQuote()->getShippingAddress()->getShippingMethod(), $_method->getCarrierCode()) === 0){
                $selectedShippingCarrier = $_method->getCarrierCode();
            }
        }
        $allQuotes = [];
        try {
            $flagshipStoreShippingOrder = [];
            foreach ($allItemInformation as $seller => $items) {
                $flagshipStoreSeller = $this->flagshipSalesHelper->getFlagshipStoreCart($seller);
                foreach ($items as $orderType => $__items) {
                    $subType = self::SUB_NORMAL;
                    if(!empty($flagshipStoreSeller)) {
                        $subType = self::SUB_SELLER_IN_FLAGSHIP_STORE;
                        if ($flagshipStoreSeller['main_seller'] == $seller) {
                            $subType = self::MAIN_SELLER_IN_FLAGSHIP_STORE;
                        }
                    }
                    $subQuote = $this->quoteFactory->create()->save();

                    $allQuotes[] = $subQuote;
                    $subQuote->setData('is_gift_order', $masterQuote->getData('is_gift_order'));
                    $subQuote->setData('gift_address_type', $masterQuote->getData('gift_address_type'));
                    $subQuote->setOrderType($orderType);
                    $subQuote->setTotalsCollectedFlag(true);
                    $subQuote->setData('is_sub_quote', true);
                    $subQuote = $this->addProduct($subQuote, $__items, $shippingAmountPerItemExcludeTax);
                    $subQuote = $this->addAddress($subQuote, $billingAddress, $shippingAddress);
                    $subQuote->setData('parent_id', $masterQuote->getId())
                        ->setStore($store)
                        ->setStoreId($masterQuote->getStoreId());
                    $subQuote = $this->addPayment($subQuote);
                    $this->copyGeneralInformatiofromMasterQuote($subQuote);
                    $this->quoteRepository->save($subQuote);
                    //                $subQuote = $this->addShippingRate($subQuote, (float)$subQuote->getReservedShippingAmount());
                    /**
                     * Calculate shipping fee for every sub order
                     */
                    if (!empty($couponCode)) {
                        $subQuote->setReserverCouponCode($couponCode);
                    }
                    if($ruleIds){
                        $subQuote->setReserverRuleIds($ruleIds);
                    }

                    $subCartShippingFeeData = $this->hotaiShippingHelperData->getSubCartFreeShippingData($orderType, [$selectedShippingCarrier], $subQuote->getItemsCollection(false));

                    if($subType == self::SUB_SELLER_IN_FLAGSHIP_STORE) {
                        $subCartShippingFee = 0;
                    }else if($subType == self::MAIN_SELLER_IN_FLAGSHIP_STORE){
                        $flagshipSubId = \Branch8\FlagshipStore\Helper\Sales::CART_FLAGSHIP_STORE_PREFIX.$flagshipStoreSeller['entity_id'];
                        $cartByFlagshipStore = $flagshipStoreSubCarts[$flagshipSubId][$orderType];
                        $subCartFlagShipStoreShippingFeeData = $this->hotaiShippingHelperData->getSubCartFreeShippingData($orderType, [$selectedShippingCarrier], $cartByFlagshipStore);
                        $subCartShippingFee = $subCartFlagShipStoreShippingFeeData['carries_shipping_fee'][$selectedShippingCarrier]['shipping_fee'];

                    }else{
                        $subCartShippingFee = $subCartShippingFeeData['carries_shipping_fee'][$selectedShippingCarrier]['shipping_fee'];
                    }
                    $siteFreeShippingThreshold = $subCartShippingFeeData['carries_shipping_fee'][$selectedShippingCarrier]['threshold'];
                    $orderFreeShippingThreshold = $subCartShippingFeeData['carries_shipping_fee'][$selectedShippingCarrier]['threshold'];
                    $siteShippingFee = $subCartShippingFeeData['carries_shipping_fee'][$selectedShippingCarrier]['origin_shipping_fee'];

                    $subQuote = $this->addShippingRate($subQuote, (float)$subCartShippingFee);
//
                    $subQuote->setTotalsCollectedFlag(false);
                    // we will ignore collect sale rul since it already calculate by CustomDiscount
                    /***********************
                     * reset discount
                     * discount already calculated by custom discount
                     * no need collect sale rule at this time
                     *****************/
                    $this->resetDiscount($subQuote);
                    $subQuote->setIgnoreCollectSaleRules(true);
                    $subQuote->collectTotals();
                    $subQuote = $this->resolveTax($subQuote);
                    //shipping fee threshold(in phase 1, 2 fields above are the same)
                    $subQuote->setSiteFreeShippingThreshold($siteFreeShippingThreshold);
                    $subQuote->setOrderFreeShippingThreshold($orderFreeShippingThreshold);
                    //order preservation status
                    $distributionThermosphere = $this->splitCartHelperData->getPreservationMapping($orderType);
                    $subQuote->setDistributionThermosphere($distributionThermosphere);
                    //shipping fee with large item before threshold
                    $subQuote->setSiteShippingFee($siteShippingFee);
                    $reservedOrderId = $this->hotaiGenerateIncrementIdForOrders->generateForSubOrder($this->parentOrder, $sequence);
                    $subQuote->setData('hotai_reserved_order_id', $reservedOrderId);
                    $subQuote->setData('reserved_order_id', $reservedOrderId);
                    if(!$subQuote->getIsVirtual()) {
                        $subQuote = $this->setShippingDiscount($subQuote, $shippingAddress);
                        $subQuoteShippingDiscount = $subQuote->getShippingAddress()->getShippingDiscountAmount();
                        $subQuoteBaseShippingDiscount = $subQuote->getShippingAddress()->getBaseShippingDiscountAmount();

                        $subQuote->setBaseShippingDiscountAmount($subQuoteBaseShippingDiscount);
                        $subQuote->setShippingDiscountAmount($subQuoteShippingDiscount);

                        $subQuote->setCustomDiscount($subQuote->getCustomDiscount() - $subQuoteShippingDiscount);
                        $subQuote->setBaseCustomDiscount($subQuote->getBaseCustomDiscount() - $subQuoteBaseShippingDiscount);
                    }
                    $sequence++;
                    $this->quoteRepository->save($subQuote);

                    $subQuotes[] = $subQuote;

                    /**
                     * Check is sub order of main seller of flagship store or not
                     */
                    if(!empty($flagshipStoreSeller)){
                        if($flagshipStoreSeller['main_seller'] == $seller){
                            $flagshipStoreShippingOrder[$flagshipStoreSeller['entity_id']][$orderType][] = '';//main seller in this current order type
                        }else {
                            $flagshipStoreShippingOrder[$flagshipStoreSeller['entity_id']][$orderType][] = $reservedOrderId;
                        }
                        /**
                         * Check is main seller or normal seller
                         */

                    }
                }
            }
            /**
             * Create Proccess order(for main seller) of Flagship store if main seller has not in this cart + order type
             */
            foreach($flagshipStoreShippingOrder as $_flagshipStoreId => $_flagshipStoreTypes){
                foreach($_flagshipStoreTypes as $flOrderType =>  $processForOrderIds){
                    /**
                     * $processForOrderIds is array, if has item value = '', main seller covered, no need to create virtual process order
                     */
                    if(in_array('', $processForOrderIds)){
                        continue;
                    }
                    $itemsOfProccessOrder = $flagshipStoreSubCarts[\Branch8\FlagshipStore\Helper\Sales::CART_FLAGSHIP_STORE_PREFIX . $_flagshipStoreId][$flOrderType];
                    $processQuote = $this->createProcessOrderForFlagshipStore($_flagshipStoreId, $flOrderType, $selectedShippingCarrier, $masterQuote, $itemsOfProccessOrder, $sequence);
                    if($processQuote == null){
                        continue;
                    }
                    $processQuote->setProcessForOrderId(implode(',', $processForOrderIds));
                    $subQuotes['flagship_shipping_quote_' . $_flagshipStoreId . '_' . $flOrderType] = $processQuote;
                    $sequence++;
                }
            }
        }catch (\Exception $exception){
            //disable all sub-quotes if error
            foreach ($allQuotes as $_subQuote){
                $_subQuote->setIsActive(false);
                $_subQuote->save();
            }
            throw new \Exception(__($exception->getMessage())->render());
        }

        return $subQuotes;
    }

    public function createProcessOrderForFlagshipStore($flagshipStoreId, $orderType, $selectedShippingCarrier, $masterQuote, $items, $sequenceOrderId){
        /**
         * Get main seller
         */
        $conn = $this->addressResource->getConnection();
        $mainSellerQuery = $conn->select()
            ->from(['fls' => 'flagship_store'], ['main_seller'])
            ->where('entity_id = ?', $flagshipStoreId);
        $mainSellerId = $conn->fetchOne($mainSellerQuery);

        /**
         * Get Fake product of main seller
         */
        $product = $this->productCollectionFactory->create()
            ->addAttributeToSelect('*')
            ->addAttributeToFilter('flagship_store_process_seller_id', $mainSellerId)
            ->getFirstItem();
        /**
         * Calculate shipping fee
         */
        $shippingFeeData = $this->hotaiShippingHelperData
            ->getSubCartFreeShippingData($orderType, [$selectedShippingCarrier], $items);
        $shippingFee = $shippingFeeData['carries_shipping_fee'][$selectedShippingCarrier]['shipping_fee'];
        $price = (float)$shippingFee; //set your price here
        if($price == 0){
            return null;
        }
        /**
         * Create quote
         */
        $subQuote = $this->quoteFactory->create()->save();

        $siteFreeShippingThreshold = $shippingFeeData['carries_shipping_fee'][$selectedShippingCarrier]['threshold'];
        $orderFreeShippingThreshold = $shippingFeeData['carries_shipping_fee'][$selectedShippingCarrier]['threshold'];
        $siteShippingFee = $shippingFeeData['carries_shipping_fee'][$selectedShippingCarrier]['origin_shipping_fee'];
        $subQuote->setSiteFreeShippingThreshold($siteFreeShippingThreshold);
        $subQuote->setOrderFreeShippingThreshold($orderFreeShippingThreshold);
        $subQuote->setSiteShippingFee($siteShippingFee);

        $customer = $this->customerSession->getCustomer()->getDataModel();
        $subQuote->assignCustomer($customer); //Assign quote to customer
        $store = $masterQuote->getStore() ?: $this->storeManager->getStore();
        $subQuote->setData('parent_id', $masterQuote->getId())
            ->setStore($store)
            ->setStoreId($masterQuote->getStoreId());

        $subQuote->setOrderType(\Branch8\SplitCart\Helper\Data::TYPE_VIRTUAL);
        $subQuote->setData('is_sub_quote', true);
        $subQuote->setIsVirtual(1);
        $subQuote->setForceIsVirtual(1);
        $subQuote->setCurrency($masterQuote->getCurrency());

        $product->setSpecialPrice($price);
        $product->setPrice($price);
        $item = $subQuote->addProduct($product, 1);
        $item->setCustomPrice($price)
            ->setOriginalCustomPrice($price)
            ->setNoDiscount(1)
            ->getProduct()->setIsSuperMode(true);

        $shippingAddress = $masterQuote->getShippingAddress();
        $billingAddress = $masterQuote->getBillingAddress();
        $subQuote = $this->addAddress($subQuote, $billingAddress, $shippingAddress);

        $item->calcRowTotal();
        $subQuote->setTotalsCollectedFlag(false);
        $subQuote->collectTotals();
        $this->quoteRepository->save($subQuote);

        $subQuote = $this->resolveTax($subQuote);

        $reservedOrderId = $this->hotaiGenerateIncrementIdForOrders->generateForSubOrder($this->parentOrder, $sequenceOrderId);
        $subQuote->setData('hotai_reserved_order_id', $reservedOrderId);
        $subQuote->setData('reserved_order_id', $reservedOrderId);

        $subQuote = $this->addPayment($subQuote);
        $subQuote = $this->copyGeneralInformatiofromMasterQuote($subQuote);

        $this->quoteRepository->save($subQuote);

        return $subQuote;
    }

    /**
     * @param Quote $subquote
     * @return $this
     */
    private function resetDiscount(Quote $subquote)
    {
        foreach ($subquote->getAllAddresses() as $address) {
            $address->setDiscountAmount(0);
            $address->setBaseDiscountAmount(0);
            $address->setBaseSubtotalWithDiscount(0);
            $address->setSubtotalWithDiscount(0);
            $address->getResource()->save($address);
        }
        return $this;
    }
    /**
     * @param Quote $subQuote
     * @return Quote
     */
    public function copyGeneralInformatiofromMasterQuote(Quote $subQuote)
    {
        $masterQuote = $this->getMasterQuote();
        return $this->transferMasterQuoteDataToSubQuoteDataService->transfer(
            $masterQuote,
            $subQuote
        );
    }

    /**
     * @param Quote $subQuote
     * @return Quote
     * @throws \Exception
     */
    public function addPayment(Quote $subQuote)
    {
        if (!$this->masterQuote->getPayment()) {
            return;
        }
        $masterQuotePayment = $this->getMasterQuote()->getPayment();
        $paymentMethod = $this->getMasterQuote()->getPayment()->getMethod();
        $additional = $masterQuotePayment->getAdditionalInformation();
        if (array_key_exists('cardType', $additional)) {
            $cardType = $additional['cardType'];
        } else {
            $cardType = '';
        }
        if (array_key_exists('paymentType', $additional)) {
            $paymentType = $additional['paymentType'];
        } else {
            $paymentType = '';
        }
        $importData['method'] = $paymentMethod;
        if (!empty($cardType)) {
            $importData['cardType'] = $cardType;
        }
        if (!empty($paymentType)) {
            $importData['paymentType'] = $paymentType;
        }
        $subQuotePayment = $subQuote->getPayment();
        $subQuotePayment->setQuote($subQuote);
        $subQuote->getPayment()->importData($importData);
        $subQuote->setPaymentMethod($paymentMethod);
        $subQuote->setInventoryProcessed(false);
        return $subQuote;
    }

    /**
     * @param Quote $subQuote
     * @param float $shippingAmount
     * @return Quote
     * @throws \Magento\Framework\Exception\AlreadyExistsException
     */
    public function addShippingRate(Quote $subQuote, float $shippingAmount)
    {
        $masterQuote = $this->getMasterQuote();
        if (!$masterQuote->getShippingAddress()) {
            return $subQuote;
        }
        if ($subQuote->getIsVirtual()) {
            return $subQuote;
        }
        $shippingMethod = $masterQuote->getShippingAddress()->getShippingMethod();
        $shippingAddress = $subQuote->getShippingAddress();
        $shippingAddress->setCollectShippingRates(true)
            ->collectShippingRates()
            ->setShippingMethod($shippingMethod);
        // ->setBaseShippingAmount($shippingAmount)
        // ->setShippingAmount($shippingAmount);
        $shippingAddress->collectShippingRates()->save();
        $rates = $shippingAddress->collectShippingRates()
            ->getGroupedAllShippingRates();
        foreach ($rates as $carrier) {
            foreach ($carrier as $rate) {
                $rate->setPrice($shippingAmount);
                $rate->save();
            }
        }
        $shippingAddress->setCollectShippingRates(false);
        $this->addressResource->save($shippingAddress);
        return $subQuote;
    }

    /**
     * @param Quote $subQuote
     * @param Quote\Address $masterBillingAddress
     * @param Quote\Address|null $masterShippingAddress
     * @return Quote
     */
    public function addAddress(
        Quote                              $subQuote,
        \Magento\Quote\Model\Quote\Address $masterBillingAddress,
        \Magento\Quote\Model\Quote\Address $masterShippingAddress = null
    )
    {
        if (!$subQuote->getForceIsVirtual() && $masterShippingAddress) {
            $subQuote->setShippingAddress($this->createAddress($masterShippingAddress));
            $subQuote->setIsVirtual(0);
        }
        if ($masterBillingAddress) {
            $subQuote->setBillingAddress(
                $this->createAddress($masterBillingAddress)
            );
        }
        return $subQuote;
    }

    /**
     * @param Quote\Address $masterAddress
     * @return Quote\Address
     */
    private function createAddress(\Magento\Quote\Model\Quote\Address $masterAddress)
    {
        return $this->addressFactory->create([
                'data' => [
                    'customer_id' => $masterAddress->getCustomerId(),
                    'address_type' => $masterAddress->getAddressType(),
                    'firstname' => $masterAddress->getFirstname(),
                    'lastname' => $masterAddress->getLastname(),
                    'email' => $masterAddress->getEmail(),
                    'street' => $masterAddress->getStreet(),
                    'city' => $masterAddress->getCity(),
                    'country_id' => $masterAddress->getCountryId(),
                    'region_id' => $masterAddress->getRegionId(),
                    'region' => $masterAddress->getRegion(),
                    'postcode' => $masterAddress->getPostcode(),
                    'telephone' => $masterAddress->getTelephone(),
                    'same_as_billing' => $masterAddress->getSameAsBilling(),
                    'company' => $masterAddress->getCompany(),
                    'fax' => $masterAddress->getFax(),
                    'customer_address_id' => $masterAddress->getCustomerAddressId()
                ]
            ]
        );
    }

    /**
     * @param Quote $subQuote
     * @param $items
     * @param $shippingAmountPerItemExcludeTax
     * @return Quote
     */
    public function addProduct(Quote $subQuote, $items, $shippingAmountPerItemExcludeTax)
    {
        $isVirtual = 1;
        $shippingAmount = 0;
        $baseDiscount = 0;
        $itemTaxAmount = 0;
        $baseItemTaxAmount = 0;
        $discount = 0;
        $rowTotalPointUsed = 0;
        $rowTotalPointDiscount = 0;
        $isCustomerCheckout = $this->getMasterQuote()->getCheckoutMethod() === Quote::CHECKOUT_METHOD_LOGIN_IN;
        $customer = $this->getMasterQuote()->getCustomer();
        $customerModel = $customer;
        $baseDiscounts = [];
        $discounts = [];
        $discountPercents = [];
        if ($customer instanceof \Magento\Customer\Model\Customer) {
            $customerModel = $customer->getDataModel();
        }
        $subQuote->setCurrency($this->getMasterQuote()->getCurrency());
        $customerEmail = $this->getMasterQuote()->getCustomerEmail();
        /**
         * Loop all seller items and add product base on seller
         */
        foreach ($items as $requestInformation) {
            try {
                $discount += floatval($requestInformation['discount']);
                $baseDiscount += floatval($requestInformation['base_discount']);
                $itemTaxAmount += floatval($requestInformation['tax_amount']);
                $baseItemTaxAmount += floatval($requestInformation['base_tax_amount']);
                //point discount
                $rowTotalPointUsed += floatval($requestInformation['row_total_point_used']);
                $rowTotalPointDiscount += floatval($requestInformation['row_total_point_discount']);
                $oldExtraField = $requestInformation['extra_field_data'] ?? [];
                if (!empty($requestInformation['shippable'])) {
                    $shippingAmount += $requestInformation['shippable'] * $shippingAmountPerItemExcludeTax;
                    $isVirtual = 0;
                }
                $product = $this->productFactory->create()->load($requestInformation['product']);
                if (isset($requestInformation['request']['options'])) {
                    foreach ($requestInformation['request']['options'] as $tempKey => $tempOptions) {
                        if (isset($tempOptions['date_internal'])) {
                            $requestInformation['request']['options'][$tempKey] = $tempOptions['date_internal'];
                        }
                    }
                }
                $item = $subQuote->addProduct($product, new \Magento\Framework\DataObject($requestInformation['request']), 'full');
                if ($isCustomerCheckout && $customer->getId()) {
                    $subQuote->setCustomerId($customer->getId())
                        ->setCustomerGroupId($customer->getGroupId())
                        ->setCustomer($customerModel);
                } else {
                    $subQuote->setCustomerId(0)
                        ->setCustomerIsGuest(true)
                        ->setCustomerGroupId(GroupInterface::NOT_LOGGED_IN_ID);
                }

                $subQuote->setCustomerEmail($customerEmail);

                if ($item instanceof \Magento\Quote\Model\Quote\Item) {
                    /**
                     * Copy old custom field to new item
                     */
                    foreach ($oldExtraField as $key => $field) {
                        $item->setData($key, $field);
                    };
                    $item->setSpecialPrice($product->getSpecialPrice());
                    $item->setData('discount_percent', $requestInformation['discount_percent']);
                    $item->setData('base_discount_amount', $requestInformation['base_discount']);
                    $item->setData('discount_amount', $requestInformation['discount']);
                    $item->setData('price', $requestInformation['price']);
                    $item->setData('tax_amount', $requestInformation['tax_amount']);
                    $item->setData('base_tax_amount', $requestInformation['base_tax_amount']);
                    $item->setData('row_total_point_used', $requestInformation['row_total_point_used']);
                    $item->setData('row_total_point_discount', $requestInformation['row_total_point_discount']);
                    $item->setData('row_total_incl_tax', $requestInformation['row_total_incl_tax']);
                    /* deal with tigren split checkout plugin
                     vendor/tigrensolutions/module-split-cart/Plugin/Magento/Quote/Model/Quote/Address.php
                    */
                    $item->setAvailableToCheckout(1);
                    $item->setForceAvailableToCheckout(1);
                    $this->quoteItemResource->save($item);
                    foreach ($subQuote->getItemsCollection() as $_item) {
                        $_item->setAvailableToCheckout(1);
                    }
                    $baseDiscounts[$item->getId()] = $requestInformation['base_discount'];
                    $discounts[$item->getId()] = $requestInformation['discount'];
                    $discountPercents[$item->getId()] = $requestInformation['discount_percent'];
                } else {
                    $this->writeLogInternal(
                        __('Can not add product %1 into subquote : %2', $product->getSku(), $item)->__toString(),
                        self::LOG_FOLDER_NAME
                    );
                    $this->writeLogInternal(
                        __('Request Information:%1', print_r($requestInformation, true))->__toString(),
                        self::LOG_FOLDER_NAME
                    );
                }

            } catch (\Exception $exception) {
                $this->writeLogInternal(
                    __('Error when add item into subquote:%1', print_r($exception->getMessage(), true))->__toString(),
                    self::LOG_FOLDER_NAME
                );
                $this->writeLogInternal(
                    __('%1', print_r($exception->getTraceAsString(), true))->__toString(),
                    self::LOG_FOLDER_NAME
                );
            }
        }
        // will use for new sub order
        $subQuote->setCustomDiscount(-$discount);
        $subQuote->setBaseCustomDiscount(-$baseDiscount);
        $subQuote->setReservedShippingAmount($shippingAmount);
        $subQuote->setReservedItemTax($itemTaxAmount);
        $subQuote->setReservedBaseItemTaxAmount($baseItemTaxAmount);
        $subQuote->setPointUsedTotal($rowTotalPointUsed);
        $subQuote->setPointDiscountTotal($rowTotalPointDiscount);

        $subQuote->setIsVirtual($isVirtual);
        $this->resolveTax($subQuote);
        /// Conflict with Tigren Split cart
        $subQuote->setForceIsVirtual($isVirtual);
        /// save into quote to use update for item in place order flow
        $subQuote->setReserverItemDiscounts($discounts);
        $subQuote->setReserverItemBaseDiscounts($baseDiscounts);
        $subQuote->setReserverItemDiscountPercent($discountPercents);

        return $subQuote;
    }

    /**
     * @param Quote $subQuote
     * @return Quote
     */
    private function resolveTax(Quote $subQuote)
    {
        $taxAmount = 0;
        $baseTaxAmount = 0;
        $addresses = $subQuote->getAllAddresses();
        foreach ($addresses as $address) {
            $taxAmount += $address->getTaxAmount();
            $baseTaxAmount += $address->getBaseTaxAmount();
        }

        $subQuote->setReservedTaxAmount($taxAmount);
        $subQuote->setReservedBaseTaxAmount($baseTaxAmount);
        return $subQuote;
    }

    /**
     * @return mixed
     */
    private function getState()
    {
        if ($this->state === null) {
            $this->state = ObjectManager::getInstance()->get(State::class);
        }
        return $this->state;
    }

    /**
     * @param Quote $masterQuote
     * @return array
     */
    public function groupProductFollowSeller(Quote $masterQuote)
    {
        $itemsFollowSellers = [];
        $itemsFollowFflagShipStores = [];
        $totalItemNeedShip = 0;
        $allItems = $masterQuote->getAllItems();
        $items = [];

        $isAdmin = $this->getState()->getAreaCode() === Area::AREA_ADMINHTML;

        foreach ($allItems as &$_item) {
            if(!$_item->getAvailableToCheckout() && !$isAdmin){
                continue;
            }
            $items[$_item->getId()] = $_item;
        }
        /**
         * @var $item \Magento\Quote\Model\Quote\Item
         */
        foreach ($allItems as $item) {
            if (!$item->getAvailableToCheckout() && !$isAdmin) {
                continue;
            }
            $infoBuyRequest = [];
            $shipQuantity = 0;
            $sellerId = 0;
            $rowTotal = $item->getRowTotal();

            if ($item->getParentItem()) {
                continue;
            }

            $type = $this->splitCartHelperData->getSplitType($items, $item);

            if (!in_array($item->getProductType(), ["virtual", "downloadable"])) {
                $shipQuantity = $item->getQty();
                $totalItemNeedShip += $item->getQty();
            }
            foreach ($item->getOptions() as $option) {
                if ($option->getCode() == "info_buyRequest") {
                    $value = json_decode($option->getValue(), true);
                    $value['qty'] = $item->getQty();
                    $infoBuyRequest[] = $value;
                }
            }
            if (!isset($infoBuyRequest[0]['product'])) {
                $product = $this->configurable->getParentIdsByChild($item->getProductId());
                if (isset($product[0])) {
                    $proId = $product[0];
                } else {
                    $proId = $item->getProductId();
                }
            } else {
                $proId = $infoBuyRequest[0]['product'];
            }
            $sellerId = (int)$this->marketPlaceDataHelper->getSellerIdByProductId($proId);
            // todo: 改成$finalArray[$id][$type][], $type為要拆分訂單需要判斷的商品類型eg. 溫層, 虛擬商品
            // $type等於商品類型eg. 商品溫層(冷凍, 冷藏), 虛擬商品, 特殊, 其他則歸類為一般商品
            $typeCount[$type] = $type;
            $infoBuyRequest[0]['type'] = $type;
            $newItemData = [
                'product' => $item->getProductId(),
                'request' => $infoBuyRequest[0],
                'qty' => $item->getQty(),
                'item_id' => $item->getId(),
                'tax_amount' => $item->getTaxAmount(),
                'base_tax_amount' => $item->getBaseTaxAmount(),
                'discount' => $item->getDiscountAmount(),
                'base_discount' => $item->getBaseDiscountAmount(),
                'discount_percent' => $item->getDiscountPercent(),
                'shippable' => $shipQuantity,
                'price' => $item->getPrice(),
                'error' => $item->getErrorInfos(),
                'row_total_point_used' => $item->getRowTotalPointUsed(),
                'row_total_point_discount' => $item->getRowTotalPointDiscount(),
                'row_total_incl_tax' => $item->getRowTotalInclTax()
            ];
            $extraFieldData = $this->transferMasterQuoteItemDataToSubQuoteItemDataService->transfer($item, []);
            $newItemData['extra_field_data'] = $extraFieldData;
            $itemsFollowSellers[$sellerId][$type][] = $newItemData;

            /**
             * Add new array split cart for flagship store
             */
            if(!isset($this->sellerIds[$sellerId])){
                $flagShipStoreId = $this->flagshipSalesHelper->getFlagshipStoreFromSellerId($sellerId);
                if($flagShipStoreId) {
                    $this->sellerIds[$sellerId] = $flagShipStoreId;
                }
            }else{
                $flagShipStoreId = $this->sellerIds[$sellerId];
            }
            if($flagShipStoreId){
                $sellerOrFlagshipId = \Branch8\FlagshipStore\Helper\Sales::CART_FLAGSHIP_STORE_PREFIX.$flagShipStoreId;
                $itemsFollowFflagShipStores[$sellerOrFlagshipId][$type][] = $item;
            }
            /*--------------------End flagship store------------------------*/

        }
        return [
            $itemsFollowSellers,
            $totalItemNeedShip,
            $itemsFollowFflagShipStores
        ];
    }

    /**
     * @param \Magento\Quote\Model\Quote $subQuote
     * @param \Magento\Quote\Model\Quote\Address $masterAddress
     * @return void
     */
    protected function setShippingDiscount($subQuote, $masterAddress){
        $totalShippingDiscount = $masterAddress->getShippingDiscountAmount();
        $totalShippingAmount = $masterAddress->getShippingAmount();
        if($totalShippingAmount == 0){
            $subQuote->setShippingDiscountAmount(0);
            $subQuote->setBaseShippingDiscountAmount(0);
            return $subQuote;
        }

        $subQuoteShippingAddress = $subQuote->getShippingAddress();
        $subQuoteShippingAmount = $subQuoteShippingAddress->getShippingAmount();
        $percent = (int)($subQuoteShippingAmount/$totalShippingAmount*100);

        $subQuoteShippingDiscountAmount = $totalShippingDiscount*$percent/100;
        /**
         * first physical order
         */
        if($this->shippingDiscountSplitTotal == 0){
            $subQuoteShippingDiscountAmount = ceil($subQuoteShippingDiscountAmount);
        }else{
            $subQuoteShippingDiscountAmount = floor($subQuoteShippingDiscountAmount);
        }

        $subQuoteShippingAddress->setShippingDiscountAmount($subQuoteShippingDiscountAmount);
        $subQuoteShippingAddress->setBaseShippingDiscountAmount($subQuoteShippingDiscountAmount);
        $subQuote->setShippingAddress($subQuoteShippingAddress);

        return $subQuote;
    }

    protected function writeLogInternal(string|array $message, string $folderName, string $fileName = ""){
        if (\Branch8\HotaiCore\Helper\DebugLog::isEnable('Branch8_WebkulMpsplitorder', 'splitMasterQuoteIntoSubQuote')) {
            $this->hotaiCoreCommonHelper->writeLog($message, $folderName, $fileName);
        }
    }
}
