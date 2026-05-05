<?php
declare(strict_types=1);

namespace Branch8\WebkulMpsplitorder\Model;

use Branch8\HotaiCore\Helper\Common as HotaiCoreCommonHelper;
use Branch8\HotaiOrderNumber\Model\Actions\HotaiGenerateIncrementIdForOrders;
use Branch8\MarketPlaceParentOrder\Model\Action\LinkParentOrderWithChild;
use Branch8\WebkulMpsplitorder\Model\Actions\GetCouponInformation;
use Branch8\WebkulMpsplitorder\Model\Actions\ProcessParentOrderFailed;
use Branch8\WebkulMpsplitorder\Exception\SplitOrderException;
use Branch8\WebkulMpsplitorder\Model\ErrorCodeMapping;
use Branch8\WebkulMpsplitorder\Model\Services\SplitMasterQuoteIntoSubQuote;
use Magento\Authorization\Model\UserContextInterface;
use Magento\Checkout\Model\Session as CheckoutSession;
use Magento\Customer\Api\AccountManagementInterface;
use Magento\Customer\Api\AddressRepositoryInterface;
use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Customer\Model\CustomerFactory;
use Magento\Customer\Model\Session as CustomerSession;
use Magento\Framework\Api\DataObjectHelper;
use Magento\Framework\App\Area;
use Magento\Framework\App\ObjectManager;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\App\State;
use Magento\Framework\Event\ManagerInterface as EventManager;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\HTTP\PhpEnvironment\RemoteAddress;
use Magento\Framework\Lock\LockManagerInterface;
use Magento\Framework\Registry;
use Magento\Quote\Api\CartRepositoryInterface;
use Magento\Quote\Model\CustomerManagement;
use Magento\Quote\Model\Quote;
use Magento\Quote\Model\Quote as QuoteEntity;
use Magento\Quote\Model\Quote\Address\ToOrder as ToOrderConverter;
use Magento\Quote\Model\Quote\Address\ToOrderAddress as ToOrderAddressConverter;
use Magento\Quote\Model\Quote\AddressFactory;
use Magento\Quote\Model\Quote\Item\ToOrderItem as ToOrderItemConverter;
use Magento\Quote\Model\Quote\Payment\ToOrderPayment as ToOrderPaymentConverter;
use Magento\Quote\Model\QuoteFactory;
use Magento\Quote\Model\QuoteIdMaskFactory;
use Magento\Quote\Model\SubmitQuoteValidator;
use Magento\Sales\Api\Data\OrderInterfaceFactory as OrderFactory;
use Magento\Sales\Api\OrderManagementInterface as OrderManagement;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\OrderRepository;
use Magento\Store\Model\StoreManagerInterface;
use Psr\Log\LoggerInterface;
use Magento\Quote\Model\ResourceModel\Quote as QuoteResource;
use \Magento\Payment\Model\Method\Free;
use Branch8\WebkulMpsplitorder\Helper\CacheLock as SplitOrderCacheLock;
use Branch8\HotaiPoint\Helper\Common as HotaiPointCommonHelper;

class QuoteManagementPlugin extends \Magento\Quote\Model\QuoteManagement
{
    const LOG_FOLDER_NAME = 'WebkulMpsplitorder/QuoteManagementPlugin';

    /**
     * @var SplitMasterQuoteIntoSubQuote
     */
    private $splitMasterQuoteIntoSubQuote;
    /**
     * @var \Webkul\Mpsplitorder\Helper\Data
     */
    private \Webkul\Mpsplitorder\Helper\Data $mpSplitOrderHelper;

    private HotaiCoreCommonHelper $hotaiCoreCommonHelper;

    private $logger;

    private $errorMapping;
    private OrderRepository $orderRepository;

    private $coupon;

    private $couponUsage;

    private $mpSplitOrderFactory;
    private QuoteResource $quoteResource;

    private $state;

    private Registry $registry;
    private SubmitQuoteValidator $submitQuoteValidator;

    /**
     * @var \Magento\SalesRule\Api\RuleRepositoryInterface
     */
    protected $ruleRepositoryInterface;
    private HotaiGenerateIncrementIdForOrders $hotaiGenerateIncrementIdForOrders;
    private mixed $mpsplitOrderResource;
    private LinkParentOrderWithChild $linkParentOrderWithChild;
    private \Magento\Framework\DB\Adapter\AdapterInterface $connnection;

    private ProcessParentOrderFailed $processParentOrderFailed;

    protected SplitOrderCacheLock $splitOrderCacheLock;

    private $getCouponInformation = null;

    private HotaiPointCommonHelper $hotaiPointCommonHelper;

    protected $masterQuoteGrandTotalBefore = NULL;

    /**
     * @param EventManager $eventManager
     * @param SubmitQuoteValidator $submitQuoteValidator
     * @param OrderFactory $orderFactory
     * @param OrderManagement $orderManagement
     * @param CustomerManagement $customerManagement
     * @param ToOrderConverter $quoteAddressToOrder
     * @param ToOrderAddressConverter $quoteAddressToOrderAddress
     * @param ToOrderItemConverter $quoteItemToOrderItem
     * @param ToOrderPaymentConverter $quotePaymentToOrderPayment
     * @param UserContextInterface $userContext
     * @param CartRepositoryInterface $quoteRepository
     * @param CustomerRepositoryInterface $customerRepository
     * @param CustomerFactory $customerModelFactory
     * @param AddressFactory $quoteAddressFactory
     * @param DataObjectHelper $dataObjectHelper
     * @param StoreManagerInterface $storeManager
     * @param CheckoutSession $checkoutSession
     * @param CustomerSession $customerSession
     * @param AccountManagementInterface $accountManagement
     * @param QuoteFactory $quoteFactory
     * @param \Webkul\Mpsplitorder\Helper\Data $mpSplitOrderHelper
     * @param SplitMasterQuoteIntoSubQuote $splitMasterQuoteIntoSubQuote
     * @param \Branch8\WebkulMpsplitorder\Model\ErrorCodeMapping $errorCodeMapping
     * @param QuoteResource $quoteResource
     * @param OrderRepository $orderRepository
     * @param LoggerInterface $logger
     * @param \Magento\SalesRule\Model\Coupon $coupon
     * @param \Magento\SalesRule\Model\ResourceModel\Coupon\Usage $couponUsage
     * @param \Webkul\Mpsplitorder\Model\ResourceModel\Mpsplitorder $mpsplitorderResource
     * @param \Webkul\Mpsplitorder\Model\MpsplitorderFactory $mpSplitOrderFactory
     * @param Registry $registry
     * @param \Magento\SalesRule\Api\RuleRepositoryInterface $ruleRepositoryInterface
     * @param HotaiGenerateIncrementIdForOrders $hotaiGenerateIncrementIdForOrders
     * @param LinkParentOrderWithChild $linkParentOrderWithChild
     * @param \Magento\Framework\App\ResourceConnection $resource
     * @param ProcessParentOrderFailed $processParentOrderFailed
     * @param QuoteIdMaskFactory|null $quoteIdMaskFactory
     * @param AddressRepositoryInterface|null $addressRepository
     * @param RequestInterface|null $request
     * @param RemoteAddress|null $remoteAddress
     * @param LockManagerInterface|null $lockManager
     * @param SplitOrderCacheLock $splitOrderCacheLock
     */
    public function __construct(
        EventManager                                                            $eventManager,
        SubmitQuoteValidator                                                    $submitQuoteValidator,
        OrderFactory                                                            $orderFactory,
        OrderManagement                                                         $orderManagement,
        CustomerManagement                                                      $customerManagement,
        ToOrderConverter                                                        $quoteAddressToOrder,
        ToOrderAddressConverter                                                 $quoteAddressToOrderAddress,
        ToOrderItemConverter                                                    $quoteItemToOrderItem,
        ToOrderPaymentConverter                                                 $quotePaymentToOrderPayment,
        UserContextInterface                                                    $userContext,
        CartRepositoryInterface                                                 $quoteRepository,
        CustomerRepositoryInterface                                             $customerRepository,
        CustomerFactory                                                         $customerModelFactory,
        AddressFactory                                                          $quoteAddressFactory,
        DataObjectHelper                                                        $dataObjectHelper,
        StoreManagerInterface                                                   $storeManager,
        CheckoutSession                                                         $checkoutSession,
        CustomerSession                                                         $customerSession,
        AccountManagementInterface                                              $accountManagement,
        QuoteFactory                                                            $quoteFactory,
        \Webkul\Mpsplitorder\Helper\Data                                        $mpSplitOrderHelper,
        \Branch8\WebkulMpsplitorder\Model\Services\SplitMasterQuoteIntoSubQuote $splitMasterQuoteIntoSubQuote,
        ErrorCodeMapping                                                        $errorCodeMapping,
        QuoteResource                                                           $quoteResource,
        OrderRepository                                                         $orderRepository,
        LoggerInterface                                                         $logger,
        \Magento\SalesRule\Model\Coupon                                         $coupon,
        \Magento\SalesRule\Model\ResourceModel\Coupon\Usage                     $couponUsage,
        \Webkul\Mpsplitorder\Model\ResourceModel\Mpsplitorder                   $mpsplitorderResource,
        \Webkul\Mpsplitorder\Model\MpsplitorderFactory                          $mpSplitOrderFactory,
        Registry                                                                $registry,
        \Magento\SalesRule\Api\RuleRepositoryInterface $ruleRepositoryInterface,
        HotaiGenerateIncrementIdForOrders $hotaiGenerateIncrementIdForOrders,
        LinkParentOrderWithChild                  $linkParentOrderWithChild,
        \Magento\Framework\App\ResourceConnection $resource,
        ProcessParentOrderFailed $processParentOrderFailed,
        HotaiCoreCommonHelper $hotaiCoreCommonHelper,
        HotaiPointCommonHelper $hotaiPointCommonHelper,
        QuoteIdMaskFactory                                                      $quoteIdMaskFactory = null,
        AddressRepositoryInterface                                              $addressRepository = null,
        RequestInterface                                                        $request = null,
        RemoteAddress                                                           $remoteAddress = null,
        LockManagerInterface                                                    $lockManager = null,
        SplitOrderCacheLock $splitOrderCacheLock
    )
    {
        parent::__construct(
            $eventManager,
            $submitQuoteValidator,
            $orderFactory,
            $orderManagement,
            $customerManagement,
            $quoteAddressToOrder,
            $quoteAddressToOrderAddress,
            $quoteItemToOrderItem,
            $quotePaymentToOrderPayment,
            $userContext,
            $quoteRepository,
            $customerRepository,
            $customerModelFactory,
            $quoteAddressFactory,
            $dataObjectHelper,
            $storeManager,
            $checkoutSession,
            $customerSession,
            $accountManagement,
            $quoteFactory,
            $quoteIdMaskFactory,
            $addressRepository,
            $request,
            $remoteAddress,
            $lockManager
        );
        $this->splitMasterQuoteIntoSubQuote = $splitMasterQuoteIntoSubQuote;
        $this->mpSplitOrderHelper = $mpSplitOrderHelper;
        $this->errorMapping = $errorCodeMapping;
        $this->quoteResource = $quoteResource;
        $this->orderRepository = $orderRepository;
        $this->logger = $logger;
        $this->coupon = $coupon;
        $this->couponUsage = $couponUsage;
        $this->registry = $registry;
        $this->mpSplitOrderFactory = $mpSplitOrderFactory;
        $this->mpsplitOrderResource = $mpsplitorderResource;
        $this->submitQuoteValidator = $submitQuoteValidator;
        $this->ruleRepositoryInterface = $ruleRepositoryInterface;
        $this->hotaiGenerateIncrementIdForOrders = $hotaiGenerateIncrementIdForOrders;
        $this->linkParentOrderWithChild = $linkParentOrderWithChild;
        $this->connnection = $resource->getConnection();
        $this->processParentOrderFailed = $processParentOrderFailed;
        $this->hotaiCoreCommonHelper = $hotaiCoreCommonHelper;
        $this->hotaiPointCommonHelper = $hotaiPointCommonHelper;
        $this->splitOrderCacheLock = $splitOrderCacheLock;
    }

    /**
     * @return GetCouponInformation|mixed
     */
    private function getCouponInformation()
    {
        if ($this->getCouponInformation === null) {
            $this->getCouponInformation = ObjectManager::getInstance()->get(GetCouponInformation::class);
        }
        return $this->getCouponInformation;
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
     * @return \Webkul\Mpsplitorder\Model\Mpsplitorder
     * @throws \Exception
     */
    private function createParentOrder($masterQuoteID)
    {
        /**
         * @var $splitOrder \Webkul\Mpsplitorder\Model\Mpsplitorder
         */
        $splitOrder = $this->mpSplitOrderFactory->create();
        $splitOrder->setPaymentStatus(0);
        $splitOrder->save();
        $splitOrder->setData('hotai_reserved_order_id',
            $this->hotaiGenerateIncrementIdForOrders->generateForParentOrder($splitOrder)
        )->setData('master_quote_id',$masterQuoteID)->save();
        return $splitOrder;
    }

    /**
     * @param QuoteEntity $quote
     * @param $orderData
     * @return \Magento\Sales\Api\Data\OrderInterface
     * @throws SplitOrderException
     * @throws \Magento\Framework\Exception\AlreadyExistsException
     * @throws \Magento\Framework\Exception\CouldNotSaveException
     * @throws \Magento\Framework\Exception\InputException
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function submitMasterQuote(QuoteEntity $quote, $orderData = [])
    {
        $lastOrderId = $this->__submitMasterQuote($quote);
        return $this->orderRepository->get($lastOrderId);
    }

    /**
     * @param Order $order
     * @param QuoteEntity $subQuote
     * @return Order
     */
    private function updateDiscountForOrderItems(Order $order, Quote $subQuote)
    {
        $baseDiscounts = (array)$subQuote->getReserverItemBaseDiscounts();
        $discounts = (array)$subQuote->getReserverItemDiscounts();
        $discountPercents = (array)$subQuote->getReserverItemDiscountPercent();

        // Batch reload variation properties from DB in a single query
        // to prevent cached OrderRepository save() from reverting CheckoutSubmitAllAfter updates
        $variationDataMap = [];
        $allItems = $order->getAllItems();
        $itemIds = array_map(fn($item) => $item->getId(), $allItems);
        if (!empty($itemIds)) {
            try {
                $tableName = $this->connnection->getTableName('sales_order_item');
                $select = $this->connnection->select()
                    ->from($tableName, ['item_id', 'spec_title', 'special_price', 'variation_price', 'option_sku'])
                    ->where('item_id IN (?)', $itemIds);
                $rows = $this->connnection->fetchAll($select);
                foreach ($rows as $row) {
                    $variationDataMap[(int)$row['item_id']] = $row;
                }
            } catch (\Exception $e) {
                $this->logger->critical('Failed to batch-reload variation data: ' . $e->getMessage());
            }
        }

        foreach ($allItems as $item) {
            if (isset($discounts[$item->getQuoteItemId()]) && $discounts[$item->getQuoteItemId()]) {
                $discount = $discounts[$item->getQuoteItemId()];
                $baseDiscount = $baseDiscounts[$item->getQuoteItemId()];
                $discountPercent = $discountPercents[$item->getQuoteItemId()];
                $item->setDiscountAmount($discount);
                $item->setBaseDiscountAmount($baseDiscount);
                $item->setDiscountPercent($discountPercent);
            }

            // Apply reloaded variation data from batch query
            $dbItemData = $variationDataMap[(int)$item->getId()] ?? null;
            if ($dbItemData) {
                if ($dbItemData['spec_title'] !== null && $dbItemData['spec_title'] !== '') {
                    $item->setData('spec_title', $dbItemData['spec_title']);
                }
                if ($dbItemData['special_price'] !== null) {
                    $item->setData('special_price', $dbItemData['special_price']);
                }
                if ($dbItemData['variation_price'] !== null) {
                    $item->setData('variation_price', $dbItemData['variation_price']);
                }
                if ($dbItemData['option_sku'] !== null && $dbItemData['option_sku'] !== '') {
                    $item->setData('option_sku', $dbItemData['option_sku']);
                }
            }
        }
        return $order;
    }

    /**
     * @param QuoteEntity $masterQuote
     * @return int|mixed|string
     * @throws SplitOrderException
     * @throws \Magento\Framework\Exception\AlreadyExistsException
     * @throws \Magento\Framework\Exception\CouldNotSaveException
     * @throws \Magento\Framework\Exception\InputException
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    private function __submitMasterQuote(QuoteEntity $masterQuote)
    {
        $masterQuote->setIsMaster(true);
        $subOrderIds = [];
        $isAdmin = $this->getState()->getAreaCode() === Area::AREA_ADMINHTML;
        $this->submitQuoteValidator->validateQuote($masterQuote);
        if ($masterQuote->getErrors()) {
            $masterQuoteErrors = $masterQuote->getErrors();
            $messageTextArr = [];
            foreach($masterQuoteErrors as $_error){
                $messageTextArr[] = $_error->getText();
            }

            $this->writeLogInternal(json_encode([
                "Title" => "__submitMasterQuote: Error when validating master quote",
                "MasterQuote ID" => $masterQuote->getId(),
                "masterQuote Customer ID" => $masterQuote->getCustomerId(),
                "Error Messages" => $messageTextArr,
            ]), self::LOG_FOLDER_NAME);
            $quoteItemErrors = join(',', $messageTextArr);
            $message = $this->errorMapping->getErrorMessage(ErrorCodeMapping::ERROR_CODE_SPE02_01, $quoteItemErrors);
            // throw new LocalizedException(__('Something went wrong with your cart:%1', join(',', $messageTextArr)));
            throw new SplitOrderException(__($message));
        }
        $parentOrder = $this->createParentOrder($masterQuote->getId());
        try {
            $this->splitOrderCacheLock->splitOrderProcedureLock($masterQuote->getId());

            /**
             * @var array $subQuotes
             */
            if (!$parentOrder->getData('hotai_reserved_order_id')) {
                // throw new LocalizedException(__('Empty Hotai Reserer Order Id'));
                $message = $this->errorMapping->getErrorMessage(ErrorCodeMapping::ERROR_CODE_SPE02_02);
                throw new SplitOrderException(__($message));
            }

            $this->eventManager->dispatch(
                'branch8_split_order_check_old_system',
                [
                    'parent_order' => $parentOrder,
                    'quote' => $masterQuote
                ]
            );

            $customer = $masterQuote->getCustomer();
            $this->splitMasterQuoteIntoSubQuote->setParentOrder($parentOrder)->setMasterQuote($masterQuote);
            $subQuotes = $this->splitMasterQuoteIntoSubQuote->generate();
            $lastOrderId = $lastRealOrderId = '';

            $desireNumberSubOrderCreated = count($subQuotes);

            $sequence = 1;
            $parentOrderGrandTotal = 0;
            $subOrders = [];
            foreach ($subQuotes as $key => $subQuote) {
                try {
                    $subQuote->setIsActive(1);
                    $subQuote->setData('is_sub_quote', 1);
                    if ($this->registry->registry('ignore_calculate_discount')) {
                        $this->registry->unregister('ignore_calculate_discount');
                    }
                    $this->registry->register('ignore_calculate_discount', true);

                    if(strpos((string)$key, 'flagship_shipping_quote_') !== 0) {
                        $orderId = parent::placeOrder($subQuote->getId());
                    }else{
                        $orderShippingFlagship = parent::submit($subQuote);
                        $orderId = $orderShippingFlagship->getId();
                        $this->eventManager->dispatch('checkout_submit_flagship_shipping_order_all_after', ['order' => $orderShippingFlagship, 'quote' => $subQuote]);
                    }

                    $subOrderIds[] = $orderId;
                    if (empty($orderId)) {
                        $this->registry->unregister('ignore_calculate_discount');
                        // throw new \Exception(__('sub order submit error')->render());
                        $message = $this->errorMapping->getErrorMessage(ErrorCodeMapping::ERROR_CODE_SPE02_03);
                        throw new SplitOrderException(__($message));
                    }
                    $this->registry->unregister('ignore_calculate_discount');
                    $order = $this->orderRepository->get($orderId);

                    $order->setOrderType($subQuote->getOrderType());

                    $order->setShippingDiscountAmount($subQuote->getBaseShippingDiscountAmount());
                    $order->setBaseShippingDiscountAmount($subQuote->getShippingDiscountAmount());

                    $order->setTaxAmount((float)$subQuote->getReservedTaxAmount());
                    $order->setBaseTaxAmount((float)$subQuote->getReservedBaseTaxAmount());
                    // custom discount
                    $customDiscount = abs((float)$subQuote->getCustomDiscount());
                    $baseCustomDiscount = abs((float)$subQuote->getBaseCustomDiscount());
                    $grandTotal = $order->getGrandTotal();
                    $baseGrandTotal = $order->getBaseGrandTotal();

                    /*
                    Update custom discount follow webkul logic
                     @see vendor/webkul/marketplace-split-order/src/app/code/Webkul/Mpsplitorder/Observer/SalesOrderSaveAfterObserver.php
                    */
                    $order->setCustomDiscount(
                        $customDiscount
                    );
                    $order->setBaseCustomDiscount(
                        $baseCustomDiscount
                    );
                    if ($customDiscount > 0 && ($grandTotal - $customDiscount) >= 0) {
                        $order->setGrandTotal((float)$grandTotal - $customDiscount);
                        $order->setDiscountAmount(-$customDiscount);
                    }
                    if ($baseCustomDiscount > 0 && ($baseGrandTotal - $baseCustomDiscount) >= 0) {
                        $order->setBaseGrandTotal((float)$baseGrandTotal - $baseCustomDiscount);
                        $order->setBaseDiscountAmount(-$baseCustomDiscount);
                    }
                    $couponCode = $subQuote->getReserverCouponCode();
                    if($couponCode) {
                        $couponInformation = $this->getCouponInformation()->get($couponCode);
                        $order->setCouponCode($couponInformation['coupon_code']);
                        $order->setCouponRuleName($couponInformation['coupon_rule_name']);
                        $order->setCouponPriceRule($couponInformation['coupon_price_rule']);
                        $order->setCouponRuleId($couponInformation['coupon_rule_id']);
                    }

                    if($subQuote->getReserverRuleIds()){
                        $ruleNames = [];
                        $ruleIds = explode(',', $subQuote->getReserverRuleIds());
                        foreach ($ruleIds as $ruleId) {
                            $rule = $this->ruleRepositoryInterface->getById($ruleId);
                            $ruleNames[] = $rule->getName();
                        }
                        $order->setAppliedRuleNames(implode(',', $ruleNames));
                    }
                    if (!$order->getDiscountDescription()) {
                        $order->setDiscountDescription($order->getCouponCode());
                    }
                    $order = $this->updateDiscountForOrderItems($order, $subQuote);
                    $order->setData('hotai_child_order_number', $order->getIncrementId());
                    $order->setBaseShippingDiscountAmount($subQuote->getBaseShippingDiscountAmount());
                    $order->setShippingDiscountAmount($subQuote->getShippingDiscountAmount());


                    $this->orderRepository->save($order);

                    $parentOrderGrandTotal = $parentOrderGrandTotal + $order->getGrandTotal();
                    $this->eventManager->dispatch(
                        'branch8_split_order_item_success',
                        [
                            'order' => $order,
                            'quote' => $masterQuote,
                            'subQuote' => $subQuote
                        ]
                    );

                    $this->eventManager->dispatch(
                        'branch8_set_order_status',
                        [
                            'order' => $order,
                            'quote' => $masterQuote
                        ]
                    );

                    $lastOrderId = $orderId;
                    $lastRealOrderId = $order->getIncrementId();

                    $subOrders[] = $order;
                } catch (\Exception $exception) {
                    $this->writeLogInternal(json_encode([
                        "Title" => "__submitMasterQuote: Error when splitting master quote while looping through sub quotes",
                        "Exception Message" => $exception->getMessage(),
                        "MasterQuote ID" => $masterQuote->getId(),
                        "MasterQuote Customer ID" => $masterQuote->getCustomerId(),
                        "SubQuote ID" => $subQuote->getId(),
                        "Exception Trace" => $exception->getTraceAsString(),
                    ]), self::LOG_FOLDER_NAME);

                    // cancel current sub orders
                    //$this->cancelSubOrders($subOrderIds);
                    $this->deactiveSubQuotes($subQuotes);
                    $masterQuote->setIsActive(true);
                    $this->quoteResource->save($masterQuote);
                    if (!$isAdmin) {
                        $this->checkoutSession->replaceQuote($masterQuote);
                    }
                    $this->eventManager->dispatch(
                        'b8_splitorder_fail',
                        [
                            'parent_order' => $parentOrder,
                            'sub_order_ids' => $subOrderIds
                        ]
                    );
                    if (empty($subOrderIds)) {
                        $parentOrder->delete();
                        $this->writeLogInternal(json_encode([
                            "Title" => "__submitMasterQuote: Empty sub order, delete parent order",
                            "MasterQuote ID" => $masterQuote->getId(),
                            "MasterQuote Customer ID" => $masterQuote->getCustomerId(),
                            "Parent Order ID" => $parentOrder->getId() ?? "N/A",
                            "Parent Order Index ID" => $parentOrder->getData('index_id') ?? "N/A",
                            "Parent Order Hotai Reserved Order ID" => $parentOrder->getData('hotai_reserved_order_id') ?? "N/A",
                        ]), self::LOG_FOLDER_NAME);
                    }
                    $message = $this->errorMapping->getErrorMessage(ErrorCodeMapping::ERROR_CODE_SPE02_04, $exception->getMessage(), true);
                    throw new SplitOrderException(__($message));
                }
            }
            $enoughSubOrder = $desireNumberSubOrderCreated === count($subOrderIds);
            // fail
            if (!$enoughSubOrder) {
                $this->writeLogInternal(json_encode([
                    "Title" => "__submitMasterQuote: Not enough sub order",
                    "MasterQuote ID" => $masterQuote->getId(),
                    "MasterQuote Customer ID" => $masterQuote->getCustomerId(),
                    "Desired Number of Sub Orders" => $desireNumberSubOrderCreated,
                    "Actual Number of Sub Orders" => count($subOrderIds),
                    "Actual Sub Order IDs" => join(',', $subOrderIds),
                    "Parent Order ID" => $parentOrder->getId() ?? "N/A",
                ]), self::LOG_FOLDER_NAME);

                // cancel current sub orders
                $this->cancelSubOrders($subOrderIds);
                $this->deactiveSubQuotes($subQuotes);
                $masterQuote->setIsActive(true);
                $this->quoteResource->save($masterQuote);
                if (!$isAdmin) {
                    $this->checkoutSession->replaceQuote($masterQuote);
                }

                $this->eventManager->dispatch(
                    'b8_splitorder_fail',
                    [
                        'parent_order' => $parentOrder,
                        'sub_order_ids' => $subOrderIds
                    ]
                );
                if(empty($subOrderIds)) {
                    $parentOrder->delete();
                }

                $message = $this->errorMapping->getErrorMessage(ErrorCodeMapping::ERROR_CODE_SPE01);
                throw new SplitOrderException(__($message));
            }

            /** Re-validate sub-order <-> payment method value */
            $paymentMethod = '';
            $parentOrderTotal = 0;
            foreach($subOrders as $_subOrder){
                if(!$paymentMethod){
                    $orderPayment = $order->getPayment();
                    $paymentMethod = $orderPayment->getMethod();
                }
                $grandTotal = $_subOrder->getGrandTotal();
                $parentOrderTotal += $grandTotal;
                if($paymentMethod == Free::PAYMENT_METHOD_FREE_CODE && $grandTotal != 0){
                    // throw new LocalizedException(__('An error occurred while creating the order. Please refresh the page and try again.'));
                    $message = $this->errorMapping->getErrorMessage(ErrorCodeMapping::ERROR_CODE_SPE02_05);
                    throw new SplitOrderException(__($message));
                }

                $pointDiscountTotal = $order->getPointDiscountTotal();
                /** Product price changed OR new catalog rule applied, it make the applied point not match order total */
                // if($pointDiscountTotal > $grandTotal){
                //     throw new LocalizedException(__('An error occurred while creating the order. Please refresh the page and try again.'));
                // }

            }
            if($paymentMethod != Free::PAYMENT_METHOD_FREE_CODE && $parentOrderTotal <= 0){
                // throw new LocalizedException(__('An error occurred while creating the order. Please refresh the page and try again.'));
                $message = $this->errorMapping->getErrorMessage(ErrorCodeMapping::ERROR_CODE_SPE02_06);
                throw new SplitOrderException(__($message));
            }
            /**
             * Validate Grand Total change after create sub orders
             * Make sure what customer see is what customer pay
            */
            if($parentOrderTotal != $this->getMasterQuoteGrandTotal($masterQuote->getId())){
                // throw new LocalizedException(__('An error occurred while creating the order, and some amounts have changed.'));
                $message = $this->errorMapping->getErrorMessage(ErrorCodeMapping::ERROR_CODE_SPE02_07);
                throw new SplitOrderException(__($message));
            }

            if ($customer && $masterQuote->getCouponCode()) {
                $this->updateCouponUsages($masterQuote->getCouponCode(), $customer->getId());
            }
            $this->eventManager->dispatch(
                'branch8_split_master_success',
                [
                    'quote' => $masterQuote
                ]
            );
            // $masterQuote->setIsActive(false);
            // $this->quoteResource->save($masterQuote);
            $this->checkoutSession->setQuoteId(null);
            $parentOrder->setOrderIds(implode(',', $subOrderIds));
            $parentOrder->setLastOrderId($lastOrderId);
            $parentOrder->setPaymentStatus(0);
            $parentOrder->setData('master_quote_id', $masterQuote->getId());
            $parentOrder->setData('grand_total', $parentOrderGrandTotal);
            $parentOrder->save();
            $this->mpsplitOrderResource->save($parentOrder);
            if ($this->registry->registry('new_parent_order_id')) {
                $this->registry->unregister('new_parent_order_id');
            }
            $this->registry->register(
                'new_parent_order_id',
                $parentOrder->getId()
            );
            $parentOrder->isObjectNew(true);
            try{
                $this->connnection->beginTransaction();
                $this->linkParentOrderWithChild->execute($parentOrder, $masterQuote, $subOrderIds);
                $this->connnection->commit();
            }catch (\Exception $exception){
                $this->writeLogInternal(
                    json_encode([
                        "Title" => "__submitMasterQuote: Exception when linking parent order with child",
                        "Exception Message" => $exception->getMessage(),
                        "MasterQuote ID" => $masterQuote->getId(),
                        "MasterQuote Customer ID" => $masterQuote->getCustomerId(),
                        "Sub Order IDs" => join(',', $subOrderIds),
                        "Parent Order ID" => $parentOrder->getId() ?? "N/A",
                        "Exception Trace" => $exception->getTraceAsString(),
                    ]),
                    self::LOG_FOLDER_NAME
                );
                $this->connnection->rollBack();
                // throw $exception;
                $message = $this->errorMapping->getErrorMessage(ErrorCodeMapping::ERROR_CODE_SPE02_08);
                throw new SplitOrderException(__($message));
            }

            $this->eventManager->dispatch(
                'marketplace_mpsplitorder_submit_after',
                [
                    'data_object' => $parentOrder,
                    'quote' => $masterQuote,
                    'sub_order_ids' => $subOrderIds
                ]
            );

            if ($subOrderIds) {
                foreach ($subOrderIds as $subOrderId) {
                    try {
                        $subOrder = $this->orderRepository->get($subOrderId);
                        if ($subOrder->getEntityId()) {
                            $this->eventManager->dispatch(
                                'update_is_paid_for_point_only_order',
                                [
                                    'parent_order' => $parentOrder,
                                    'order' => $subOrder
                                ]
                            );
                        }
                    } catch (NoSuchEntityException $e) {
                        $this->writeLogInternal(
                            json_encode([
                                "Title" => "__submitMasterQuote: NoSuchEntityException when updating is paid for point only order",
                                "Sub Order ID" => $subOrderId,
                                "MasterQuote ID" => $masterQuote->getId(),
                                "MasterQuote Customer ID" => $masterQuote->getCustomerId(),
                                "Exception Message" => $e->getMessage(),
                                "Exception Trace" => $e->getTraceAsString(),
                            ]),
                            self::LOG_FOLDER_NAME
                        );
                    } catch (\Throwable $th) {
                        $this->writeLogInternal(
                            json_encode([
                                "Title" => "__submitMasterQuote: Throwable when updating is paid for point only order.",
                                "Sub Order ID" => $subOrderId,
                                "MasterQuote ID" => $masterQuote->getId(),
                                "MasterQuote Customer ID" => $masterQuote->getCustomerId(),
                                "Exception Message" => $th->getMessage(),
                                "Exception Trace" => $th->getTraceAsString(),
                            ]),
                            self::LOG_FOLDER_NAME
                        );

                        $this->hotaiPointCommonHelper->setPointCommitRetryDataBySalesOrderId($subOrderId);
                    }
                }
            }

            if (!$isAdmin) {
                $this->cacheSessionForSuccessPage(
                    $subOrderIds,
                    $parentOrder->getId(),
                    $lastOrderId,
                    $lastRealOrderId
                );
            }

            return $lastOrderId;
        } catch (\Exception $exception) {
            $masterQuote->setIsActive(1);
            $this->quoteResource->save($masterQuote);
            $this->writeLogInternal(
                json_encode([
                    "Title" => "__submitMasterQuote: Error when splitOrder",
                    "Exception Message" => $exception->getMessage(),
                    "MasterQuote ID" => $masterQuote->getId(),
                    "MasterQuote Customer ID" => $masterQuote->getCustomerId(),
                    "Exception Trace" => $exception->getTraceAsString(),
                ]),
                self::LOG_FOLDER_NAME
            );

            // cancel current sub orders
            //$this->cancelSubOrders($subOrderIds);
            $this->processParentOrderFailed->process($parentOrder, $masterQuote,$subOrderIds);
            if(isset($subQuotes) && is_array($subQuotes)) {
                $this->deactiveSubQuotes($subQuotes);
            }
            $masterQuote->setIsActive(true);
            $this->quoteResource->save($masterQuote);
            if (!$isAdmin) {
                $this->checkoutSession->replaceQuote($masterQuote);
            }

            if(empty($subOrderIds)) {
                $parentOrder->delete();
            }

            $this->eventManager->dispatch(
                'b8_splitorder_fail',
                [
                    'parent_order' => $parentOrder,
                    'sub_order_ids' => $subOrderIds
                ]
            );
            $message = $exception->getMessage();
            if(substr($message, 0, 3) != 'SPE'){
                $message = $this->errorMapping->getErrorMessage(ErrorCodeMapping::ERROR_CODE_SPE02_09);
                throw new SplitOrderException(__($message));
            }else{
                throw $exception;
            }

        } finally {
            $this->splitOrderCacheLock->splitOrderProcedureUnlock($masterQuote->getId());
        }
    }

    /**
     * @param \Magento\Quote\Model\QuoteManagement $subject
     * @param callable $proceed
     * @param $cartId
     * @return void
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function aroundPlaceOrder(\Magento\Quote\Model\QuoteManagement $subject, callable $proceed, $cartId)
    {
        $this->getMasterQuoteGrandTotal($cartId);
        /**
         * @var $masterQuote Quote
         */
        $masterQuote = $this->quoteRepository->getActive($cartId);
        $this->eventManager->dispatch(
            'before_handle_master_quote',
            ['quote' => $masterQuote]
        );
        if ($this->mpSplitOrderHelper->getIsActive() == 0) {
            return parent::placeOrder($masterQuote->getId());
        }
        $this->eventManager->dispatch(
            'before_submit_master_quote',
            ['quote' => $masterQuote]
        );
        $order = $this->__submitMasterQuote($masterQuote);
        return $order;
    }

    /**
     * @param array $subQuotes
     * @return void
     */
    private function deactiveSubQuotes(array $subQuotes)
    {
        /**
         * @var $subQuote Quote
         */
        foreach ($subQuotes as $subQuote) {
            try {
                $subQuote->setIsActive(false);
                $this->quoteResource->save($subQuote);
            } catch (\Exception $exception) {
                if (\Branch8\HotaiCore\Helper\DebugLog::isEnable('Branch8_WebkulMpsplitorder', 'deactive_cancel_subquote')) {
                    $this->logger->error(__('Error when deactive sub quote %1 :%2', $subQuote->getId(), $exception->getMessage()));
                }
            }
        }
    }

    /**
     * @param array $subOrderIds
     * @return void
     */
    private function cancelSubOrders(array $subOrderIds = [])
    {
        foreach ($subOrderIds as $orderId) {
            try {
                $this->orderManagement->cancel($orderId);
                if (\Branch8\HotaiCore\Helper\DebugLog::isEnable('Branch8_WebkulMpsplitorder', 'deactive_cancel_subquote')) {
                    $this->logger->info('Error on place order, cancel sub order:' . $orderId);
                }
            } catch (\Exception $exception) {
                if (\Branch8\HotaiCore\Helper\DebugLog::isEnable('Branch8_WebkulMpsplitorder', 'deactive_cancel_subquote')) {
                    $this->logger->error(__('Error when cancel sub order %1 :%2', $orderId, $exception->getMessage()));
                    $this->logger->info($exception->getTraceAsString());
                }
            }
        }
    }

    /**
     * @param $orderIds
     * @param $parentOrderId
     * @param $lastOrderId
     * @param $lastRealOrderId
     * @return void
     */
    private function cacheSessionForSuccessPage(
        $orderIds,
        $parentOrderId,
        $lastOrderId,
        $lastRealOrderId
    )
    {
        $this->checkoutSession->setData('parentOrderId', $parentOrderId);
        $this->checkoutSession->setData('subOrderIds', implode(',', $orderIds));
        /// default magento
        $this->checkoutSession->setLastOrderId($lastOrderId);
        $this->checkoutSession->setLastRealOrderId($lastRealOrderId);
    }

    /**
     * @param $couponCode
     * @param $customerId
     * @return void
     * @throws \Exception
     */
    public function updateCouponUsages($couponCode, $customerId)
    {
        $increment = true;
        $this->coupon->load($couponCode, 'code');
        if ($this->coupon->getId()) {
            if ($increment || $this->coupon->getTimesUsed() > 0) {
                $this->coupon->setTimesUsed($this->coupon->getTimesUsed() + ($increment ? 1 : -1));
                $this->coupon->save();
            }
            if ($customerId) {
                $this->couponUsage->updateCustomerCouponTimesUsed($customerId, $this->coupon->getId(), $increment);
            }
        }
    }

    /**
     * @param $cartId
     * @return string
     */
    protected function getMasterQuoteGrandTotal($cartId){
        if ($this->masterQuoteGrandTotalBefore === null) {
            $sql = 'select grand_total from quote where entity_id=' . $cartId;
            $masterQuoteGrandTotal = $this->connnection->fetchOne($sql);
            $this->masterQuoteGrandTotalBefore = $masterQuoteGrandTotal;
        }
        return $this->masterQuoteGrandTotalBefore;
    }

    protected function writeLogInternal(string|array $message, string $folderName, string $fileName = ""){
        if (\Branch8\HotaiCore\Helper\DebugLog::isEnable('Branch8_WebkulMpsplitorder', 'quoteManagementPlugin')) {
            $this->hotaiCoreCommonHelper->writeLog($message, $folderName, $fileName);
        }
    }
}
