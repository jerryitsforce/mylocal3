<?php
namespace Branch8\GiftToFriend\Block\History;

use Branch8\Customer\Helper\AddressRenderer;
use Branch8\HotaiCore\Model\Order\Status;
use Branch8\MarketPlaceParentOrder\Model\ParentOrder;
use Branch8\MarketPlaceParentOrderFrontendUi\Model\Config;
use Branch8\MarketPlaceParentOrder\Api\Data\ParentOrderInterface;
use Branch8\MarketPlaceParentOrder\Api\ParentOrderManagementInterface;
use Branch8\MarketPlaceParentOrder\Model\ResourceModel\ParentOrder\CollectionFactory;
use Branch8\Shipping\Model\ShippingMethod;
use Magento\Catalog\Block\Product\Widget\Html\Pager;
use Magento\Customer\Model\Context;
use Magento\Framework\App\Area;
use Magento\Framework\App\ObjectManager;
use Magento\Framework\App\ResourceConnection;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\ResourceModel\Order\CollectionFactory as OrderCollectionFactory;
use Magento\Sales\Model\ResourceModel\Order\Item\CollectionFactory as OrderItemCollectionFactory;
use Magento\Store\Model\ScopeInterface as StoreScopeInterface;
use Branch8\Customer\Helper\Info;
use Magento\Customer\Model\Session\Proxy as CustomerSession;

class Listing extends \Magento\Framework\View\Element\Template {
    const FILTER_STATUSES = [
        "all" => "Order Status All",
        "pending" => "Order Status Pending",
        "processing" => "Order Status Processing",
        "complete" => "Order Status Completed",
        "closed" => "Order Status Closed",
        "canceled" => "Order Status Canceled",
    ];

    private const SALES_CANCELLATION_REASONS = 'sales/cancellation/reasons';

    /**
     * @var string
     */
    protected $_template = 'Branch8_MarketPlaceParentOrderFrontendUi::parent_order/history.phtml';

    const PAGE_VAR_NAME = 'event-page';
    const DEFAULT_PAGE_SIZE = 10;

    /**
     * @var \Magento\Sales\Model\ResourceModel\Order\CollectionFactory
     */
    protected $parentOrderCollectionFactory;

    /**
     * @var \Magento\Customer\Model\Session
     */
    protected $_customerSession;

    /**
     * @var \Magento\Sales\Model\Order\Config
     */
    protected $_orderConfig;

    /**
     * @var \Branch8\MarketPlaceParentOrder\Model\ResourceModel\ParentOrder\Collection
     */
    protected $orders;

    /**
     * @var CollectionFactoryInterface
     */
    private $orderCollectionFactory;

    private $parentOrderManagement;
    /**
     * @var \Magento\Framework\App\Http\Context
     */
    protected $httpContext;

    private $config;

    /**
     * @var
     */
    protected $pager;

    protected $totalPage = 0;

    protected $orderItemCollectionFactory;

    protected $hotaiShippingHelper;

    protected $subOrderCollectionFactory;

    protected $resourceConnection;

    /** @var AddressRenderer  */
    protected $addressRenderer;

    /** @var \Magento\Sales\Api\OrderAddressRepositoryInterface  */
    private $orderAddressRepository;

    private \Magento\Payment\Helper\Data $paymentHelper;

    private $paymentMethodInstances = [];

    /**
     * @var \Magento\Sales\Api\InvoiceRepositoryInterface
     */
    protected $_invoiceRepository;

    /**
     * @var Info
     */
    protected $infoHelper;

    /**
     * @var \Branch8\GiftToFriend\Helper\Data
     */
    protected $giftHelper;
    /**
     * @var \Branch8\HotaiAuth\Service\HotaiAuthService
     */
    protected $hotaiAuthService;

    /**
     * @param \Magento\Framework\View\Element\Template\Context $context
     * @param CollectionFactory $orderCollectionFactory
     * @param \Magento\Customer\Model\Session $customerSession
     * @param \Magento\Sales\Model\Order\Config $orderConfig
     * @param ParentOrderManagementInterface $parentOrderManagement
     * @param Config $config
     * @param \Magento\Framework\App\Http\Context $httpContext
     * @param OrderItemCollectionFactory $orderItemCollectionFactory
     * @param \Branch8\HotaiShipping\Helper\Data $hotaiShippingHelper
     * @param OrderCollectionFactory $subOrderCollectionFactory
     * @param ResourceConnection $resourceConnection
     * @param AddressRenderer $addressRenderer
     * @param \Magento\Sales\Api\OrderAddressRepositoryInterface $orderAddressRepository
     * @param \Magento\Payment\Helper\Data $paymentHelper
     * @param Info $infoHelper
     * @param \Branch8\GiftToFriend\Helper\Data $giftHelper
     * @param \Magento\Sales\Api\InvoiceRepositoryInterface $invoiceRepository
     * @param \Branch8\HotaiAuth\Service\HotaiAuthService $hotaiAuthService
     * @param array $data
     */
    public function __construct(
        \Magento\Framework\View\Element\Template\Context $context,
        CollectionFactory $orderCollectionFactory,
        CustomerSession $customerSession,
        \Magento\Sales\Model\Order\Config $orderConfig,
        ParentOrderManagementInterface $parentOrderManagement,
        Config $config,
        \Magento\Framework\App\Http\Context $httpContext,
        OrderItemCollectionFactory $orderItemCollectionFactory,
        \Branch8\HotaiShipping\Helper\Data $hotaiShippingHelper,
        OrderCollectionFactory $subOrderCollectionFactory,
        ResourceConnection $resourceConnection,
        AddressRenderer $addressRenderer,
        \Magento\Sales\Api\OrderAddressRepositoryInterface $orderAddressRepository,
        \Magento\Payment\Helper\Data $paymentHelper,
        Info $infoHelper,
        \Branch8\GiftToFriend\Helper\Data $giftHelper,
        \Magento\Sales\Api\InvoiceRepositoryInterface $invoiceRepository,
        \Branch8\HotaiAuth\Service\HotaiAuthService $hotaiAuthService,
        array $data = []
    ) {
        $this->config = $config;
        $this->parentOrderCollectionFactory = $orderCollectionFactory;
        $this->_customerSession = $customerSession;
        $this->_orderConfig = $orderConfig;
        $this->parentOrderManagement = $parentOrderManagement;
        $this->httpContext = $httpContext;
        $this->orderItemCollectionFactory = $orderItemCollectionFactory;
        $this->hotaiShippingHelper = $hotaiShippingHelper;
        $this->subOrderCollectionFactory = $subOrderCollectionFactory;
        $this->resourceConnection = $resourceConnection;
        $this->addressRenderer = $addressRenderer;
        $this->orderAddressRepository = $orderAddressRepository;
        $this->paymentHelper = $paymentHelper;
        $this->infoHelper = $infoHelper;
        $this->giftHelper = $giftHelper;
        $this->_invoiceRepository = $invoiceRepository;
        $this->hotaiAuthService = $hotaiAuthService;
        parent::__construct($context, $data);
    }

    /**
     * @inheritDoc
     */
    protected function _construct()
    {
        parent::_construct();
        $this->pageConfig->getTitle()->set(__('檢視送禮進度'));
    }

    /**
     * @return CollectionFactory|CollectionFactoryInterface|mixed
     */
    private function getOrderCollectionFactory()
    {
        if ($this->orderCollectionFactory === null) {
            $this->orderCollectionFactory = ObjectManager::getInstance()->get(
                \Branch8\MarketPlaceParentOrder\Model\ResourceModel\ParentOrder\CollectionFactory::class
            );
        }
        return $this->orderCollectionFactory;
    }

    public function getShippingMethod($order)
    {
        return $this->hotaiShippingHelper->getCarrierTitleByCarrierMethoCode($order->getShippingMethod());
    }

    /**
     * @param ParentOrder $order
     * @return mixed
     */
    public function renderShippingInfo($order)
    {
        $address = $order->getShippingAddress();
        $addressString = '';
        if ($order->getShippingMethod() == ShippingMethod::METHOD_CONVENIENCE_STORE) {
            $addressString = $this->infoHelper->getConvenienceAdress($address);
        } else {
            $addressString = $this->infoHelper->getHomeDeliveryAdress($address);
        }

        return $order->getShippingDescription() . ($addressString ? " ($addressString)" : '');
    }

    /**
     * @param ParentOrder $order
     * @return mixed
     */
    public function renderBillingInfo($order, $type = '')
    {
        $address = $order->getBillingAddress();
        if (!$address) {
            return '';
        }

        if($type === 'name') {
            return $this->infoHelper->getOAuthName('', $address->getFirstName());
        } else if($type === 'phone') {
            return $this->infoHelper->getOAuthPhone($address->getTelephone());
        } else if($type === 'street') {
            return $this->infoHelper->getHomeDeliveryAdress($address);
        } else {
           return $address;
        }
    }


    public function getSearchQuery()
    {
        return $this->getRequest()->getParam('search');
    }

    /**
     * Get filter status
     * @return mixed
     */
    public function getFilterStatus()
    {
        return $this->getRequest()->getParam('status');
    }

    /**
     * Get filter from date
     * @return mixed
     */
    public function getFromDate()
    {
        return $this->getRequest()->getParam('from_date');
    }

    /**
     * get filter to date
     * @return mixed
     */
    public function getToDate()
    {
        return $this->getRequest()->getParam('to_date');
    }

    /**
     * @return \Magento\Framework\Phrase
     */
    public function getEmptySearchMessage()
    {
        return __('未找到符合"%1"的訂單', $this->getSearchQuery());
    }

    /**
     * Is order auto cancelled
     * @param $order
     * @return bool
     */
    public function isOrderCancelled($order)
    {
        return $order->getStatus() === Order::STATE_CANCELED;
    }

    public function isOrderAutoCancelled($order)
    {
        return $order->getStatus() === Order::STATE_CANCELED && (bool) $order->getData('is_auto_cancelled');
    }

    public function isOrderManualCancelled($order)
    {
        return $order->getStatus() === Order::STATE_CANCELED && !(bool) $order->getData('is_auto_cancelled');
    }

    /**
     * Is ticket order
     * @param $order
     * @return bool
     */
    public function isTicketOrder($order)
    {
        $items = $order->getAllItems();
        $isVirtual = true;
        foreach ($items as $item) {
            if (!$item->getData('is_virtual')) {
                $isVirtual = false;
                return $isVirtual;
            }
        }
        return $isVirtual;
    }

    public function isGiftOrder($order)
    {
        return (bool) $order->getData('is_gift_order');
    }

    /**
     * Is Gift Order - Confirmed
     */
    public function isGiftOrderConfirmed($order)
    {
        return (bool) $order->getData('is_gift_order') && $order->getData('is_gift_confirmed') && $order->getStatus() !== Order::STATE_CANCEL;
    }

    public function getCustomerServiceUrl() {
       return $this->getUrl('helpdesk/ticket');
    }

    public function getFAQUrl() {
        $faqCategoryId = $this->giftHelper->getFAQCatConfig();
        if($faqCategoryId && $faqCategoryId !== '') {
            return $this->getUrl('faq',['_query' => ['tabid' => $faqCategoryId]]);
        }
        return $this->getUrl('faq');
    }

    /**
     * @param ParentOrderInterface $order
     * @return void
     */
    public function getContactServiceUrl($order)
    {
        if(!$order) {
            return $this->getUrl('gift-order/giftbox/service');
        }
        return $this->getUrl('gift-order/giftbox/service', ['id' => $order->getId()]);
    }

    /**
     * @param $createdAt
     * @return string
     */
    public function getOrderDate($createdAt)
    {
        try {
            return $this->_localeDate->date($createdAt)->format('Y/m/d H:i');
        } catch (\Exception $exception) {
            return '';
        }
    }

    public function getStoreNameForPickupMethod($order)
    {
        if ($order->getShippingMethod() !== ShippingMethod::METHOD_CONVENIENCE_STORE) {
            return '';
        }

        return $order->getStoreInfoBySubOrder();
    }

    /**
     * Get my ticket url
     * @return string
     */
    public function getMyTicketUrl($id = 3)
    {
        if($this->_customerSession->isLoggedIn()){
            return $this->getUrl('member/ticket/detail/', ['id' => $id]); //mockdata for ticket detail
        }else{
            return $this->getUrl('gift-order/giftBox/ticket', ['id' => $id]);
        }
    }

    /**
     * Check if the invoice creation through ECPAY invoice fails
     */

    public function hasEcpayInvoiceFail($order)
    {
         $hasInvoices = $order->hasInvoices();
         $hasPaidFail = false;

         if($hasInvoices) {
             $invoices = $order->getInvoiceCollection();

             foreach ($invoices as $invoice) {
                 if ($invoice->getState() != \Magento\Sales\Model\Order\Invoice::STATE_PAID) {
                     $hasPaidFail = true;
                     break;
                 }
             }
         }

         return $hasPaidFail;
    }

    
    /**
     * Check if the invoice creation through ECPAY invoice successully
     */
     public function hasNoInvoiceNumber($order)
     {
         $subOrders = $order->getSubOrders();
         $hasNoInvoiceIssues = false;
 
            foreach ($subOrders ?? [] as $subOrder) {
                if(!$subOrder->getData('ecpay_invoice_number')) {
                    // Get order items of the sub-order
                    $orderItems = $subOrder->getItems();
                    foreach ($orderItems as $item) {
                        $discount = $item->getData('discount_amount')?? 0;
                        $pointUsed = $item->getData('row_total_point_used')?? 0;
                        $subTotal = $item->getData('row_total_incl_tax') - $discount - $pointUsed;
                        if($subTotal <= 0 && $pointUsed > 0) {
                            $hasNoInvoiceIssues = false;
                        } else {
                            $hasNoInvoiceIssues = true;
                            break;
                        } 
                    }
                } 
            }

         return $hasNoInvoiceIssues;
     }

    /**
     * Check if there are only-points products in the order that can only be paid with points, the order cannot be canceled.
     */

    public function hasOnlyPointsProducts($order)
    {
        // $writer = new \Zend_Log_Writer_Stream(BP . '/var/log/suborders.log');
        // $logger = new \Zend_Log();
        // $logger->addWriter($writer);
        $subOrders = $order->getSubOrders();
        $hasOnlyPoints = false;

        // $logger->info('start-----------------');
        // $logger->info(print_r(get_class($subOrders), true));
        // $logger->info(print_r(json_encode($subOrders->getData()), true));

        foreach ($subOrders ?? [] as $subOrder) {
            // Get order items of the sub-order
            $orderItems = $subOrder->getItems();
            // $logger->info('start items-----------------');
            foreach ($orderItems as $item) {
                // $logger->info(print_r($item->getData(), true));

                $discount = $item->getData('discount_amount')?? 0;
                $pointUsed = $item->getData('row_total_point_used')?? 0;
                $subTotal = $item->getData('row_total_incl_tax') - $discount - $pointUsed;
                $isVirtual = $item->getData('is_virtual');
                // $logger->info(print_r($item->getData('row_total_point_used'), true));
                // $logger->info(print_r($subTotal, true));
                if($subTotal <= 0 && $pointUsed > 0 && $isVirtual) {
                    $hasOnlyPoints = true;
                    break;
                }
            }
        }

        // $shippingMethod = $order->getShippingMethod();
        // $method = \Branch8\Sales\Helper\FrontOrderFlow::PROGRESS_TYPE_TICKET;
        // $grandTotal = $this->getTotalByCode($order, 'grand_total');
        // $pointUsedTotal = $this->getTotalByCode($order, 'point_used_total');

        // if($shippingMethod == \Branch8\Shipping\Model\ShippingMethod::METHOD_HOME) {
        //     $method = \Branch8\Sales\Helper\FrontOrderFlow::PROGRESS_TYPE_DELIVERY;
        // }

        // if($shippingMethod == \Branch8\Shipping\Model\ShippingMethod::METHOD_CONVENIENCE_STORE) {
        //     $method = \Branch8\Sales\Helper\FrontOrderFlow::PROGRESS_TYPE_CONVIENCE_STORE;
        // }

        // if($method === \Branch8\Sales\Helper\FrontOrderFlow::PROGRESS_TYPE_TICKET && $grandTotal == 0 && $pointUsedTotal > 0) {
        //     return true;
        // }

        // return false;
        return $hasOnlyPoints;
    }

    /**
     * Can show cancel button
     * @param $order
     * @return bool
     */
    public function canShowCancelBtn($order)
    {
        $createdAt = new \DateTime($order->getCreatedAt());
        $currentDate = new \DateTime();
        $currentDate->setTime(23, 59, 59);

        if($this->hasOnlyPointsProducts($order)) {
            return false;
        }

        if($this->hasEcpayInvoiceFail($order)) {
            return false;
        }

        if($this->hasNoInvoiceNumber($order)) {
            return false;
        }

        if ($createdAt->format('Y-m-d') === $currentDate->format('Y-m-d') && $this->parentOrderManagement->canCancel($order)
            && in_array(
                $order->getStatus(),
                [
                    Status::STATUS_PROCESSING,
                    Status::STATUS_GIFT_INFO_PENDING,
                    Status::STATUS_GIFT_INFO_COMPLETE,
                ]
            )
        ) {
            return true;
        }

        if ($order->getData('is_gift_order') && $order->getStatus() == Status::STATUS_PENDING_PAYMENT) {
            return true;
        }

        return false;
    }

    /**
     * Returns order cancellation reasons.
     *
     * @return array
     */
    public function getCancellationReasons(): array
    {
        $reasons = $this->_scopeConfig->getValue(
            self::SALES_CANCELLATION_REASONS,
            StoreScopeInterface::SCOPE_STORE
        );
        return array_map(function ($reason) {
            return $reason['description'];
        }, is_array($reasons) ? $reasons : json_decode($reasons, true));
    }

    /**
     * @param $status
     * @return string
     */
    public function mappingStatus($status)
    {
        return isset(self::FILTER_STATUSES[$status]) ? self::FILTER_STATUSES[$status] : '';
    }

    public function getCustomerName()
    {
        if ($this->_customerSession->isLoggedIn()) {
            return $this->infoHelper->getOAuthName($this->_customerSession->getCustomer()->getNickname(), $this->_customerSession->getCustomer()->getFirstname()?? '');
        }
        return '';
    }

    /**
     * @return \Branch8\MarketPlaceParentOrder\Model\ResourceModel\ParentOrder\Collection|false
     */
    public function getOrders()
    {
        $collection = $this->getOrderCollectionFactory()->create()
            ->joinDetail(
                [
                    '*',
                ]
                )->addFieldToFilter(
            'detail.status',
            ['in' => $this->_orderConfig->getVisibleOnFrontStatuses()]
        );
        $guestSession = $this->_customerSession->getGuestGiftBoxSession();

        if($this->_customerSession->isLoggedIn()){
            $customerId = $this->_customerSession->getCustomerId();
            $customerMemberSeq = $this->_customerSession->getCustomer()
                ->getDataModel()->getCustomAttribute('member_seq')->getValue();
            $collection->getSelect()
            // ->joinLeft(['addr' => 'sales_parent_order_address'], 'detail.parent_id = addr.parent_order_id', [])
            ->where('detail.is_gift_order = 1')
            ->where('detail.recipient_member_seq = "'.$customerMemberSeq.'"');
        }else if($guestSession){
            $telephone = $guestSession['phone_number'];
            $memberSeq = $this->hotaiAuthService->getHotaiOneIdByPhoneNumber($telephone);
            if(!$memberSeq){
                $collection->getSelect()
                    ->where('detail.recipient_telephone = '.$telephone)
                    ->where('detail.recipient_member_seq is null')
                    ->where('detail.is_gift_order = 1');
            }else{
                $collection->getSelect()
                    ->where('detail.recipient_member_seq = "'.$memberSeq.'"')
                    ->where('detail.is_gift_order = 1');
            }
            
        }else{
            $this->orders = [];
            return $this->orders;
        }

        $collection->getSelect()->where('detail.is_gift_confirmed = 1');

        $collection->addFieldToFilter(['detail.is_removed_gift_box','detail.is_removed_gift_box'], [['null' => true], ['neq' => 1]]);
        $collection->setPageSize($this->getPageSize());
        $collection->setCurPage($this->getCurrentPage());
        $collection->getSelect()->group('main_table.index_id');

        $this->orders = $collection->setOrder('detail.created_at', 'desc');
        // echo $collection->getSelect();die;
        // var_dump($this->orders->getSelect()->__toString()); 
        // var_dump($collection->getData());
        // die;

        return $this->orders;
    }

    public function getParentId($childIds)
    {
        $connection = $this->resourceConnection->getConnection();
        $tableName = $connection->getTableName('sales_parent_order_children');

        $select = $connection->select()
            ->from($tableName, ['parent_id'])
            ->where('children_id IN (?)', $childIds);

        return $connection->fetchCol($select);
    }

    /**
     * @return int
     */
    public function getCurrentPage()
    {
        return (int) $this->getRequest()->getParam(self::PAGE_VAR_NAME, 1);
    }

    /**
     * Get order status
     * @return mixed
     */
    public function getStatuses()
    {
        return $this->getRequest()->getParam('status');
    }

    /**
     * @inheritDoc
     */
    protected function _prepareLayout()
    {
        parent::_prepareLayout();
//        if ($this->getOrders()) {
//            $pager = $this->getLayout()->createBlock(
//                \Magento\Theme\Block\Html\Pager::class,
//                'sales.parent.order.history.pager'
//            )->setCollection(
//                $this->getOrders()
//            );
//            $this->setChild('pager', $pager);
//            $this->getOrders()->load();
//        }
        return $this;
    }

    /**
     * @return string|void
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function getPagerHtml()
    {
        $pagerBlockName = 'sales.parent.order.history.pager';
        if (!$this->pager) {
            $this->pager = $this->getLayout()->createBlock(
                Pager::class,
                $pagerBlockName
            )->setTemplate('Branch8_GiftToFriend::giftbox/page/pager.phtml');

            $this->pager->setUseContainer(true)
                ->setShowAmounts(true)
                ->setShowPerPage(false)
                ->setPageVarName(self::PAGE_VAR_NAME)
                ->setLimit($this->getPageSize())
                ->setTotalLimit($this->totalPage)
                ->setCollection($this->getOrders());
        }
        if ($this->pager instanceof \Magento\Framework\View\Element\AbstractBlock) {
            return $this->pager->toHtml();
        }
    }

    /**
     * @return array|mixed|null
     */
    public function getPageSize()
    {
        if (!$this->hasData('page_size')) {
            $this->setData('page_size', self::DEFAULT_PAGE_SIZE);
        }
        return $this->getData('page_size');
    }

    /**
     * Get order view URL
     *
     * @param object $order
     * @return string
     */
    public function getViewUrl($order)
    {
        return $this->getUrl('sales/parentOrder/view', ['id' => $order->getId()]);
    }

    /**
     * Get order track URL
     *
     * @param object $order
     * @return string
     * @deprecated 102.0.3 Action does not exist
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function getTrackUrl($order)
    {
        //phpcs:ignore Magento2.Functions.DiscouragedFunction
        trigger_error('Method is deprecated', E_USER_DEPRECATED);
        return '';
    }

    /**
     * Get reorder URL
     *
     * @param object $order
     * @return string
     */
    public function getReorderUrl($order)
    {
        return $this->getUrl('sales/parentOrder/reorder', ['id' => $order->getId()]);
    }

    /**
     * Get customer account URL
     *
     * @return string
     */
    public function getBackUrl()
    {
        return $this->getUrl('customer/account/');
    }

    /**
     * Get message for no orders.
     *
     * @return \Magento\Framework\Phrase
     * @since 102.1.0
     */
    public function getEmptyOrdersMessage()
    {
        return __('You have placed no orders.');
    }

    /**
     * @param ParentOrderInterface $parentOrder
     * @return true
     */
    public function canReorder(ParentOrderInterface $parentOrder)
    {
        if ($this->config->allowReorderOOSProduct()) {
            return true;
        }
        return $this->parentOrderManagement->canReorder($parentOrder);
    }

    public function getEcpayInvoiceCarruerNumber($order)
    {
        return $order->getEcpayInvoiceCarruerNum();
    }

    /**
     * @param ParentOrderInterface $parentOrder
     * @return false|string
     */
    public function getReorderPostData(ParentOrderInterface $parentOrder)
    {
        return json_encode(
            [
                'action' => $this->getUrl('sales/parentOrder/reorder'),
                'data' => ['id' => $parentOrder->getIndexId()],
            ]
        );
    }

    /**
     * @param ParentOrderInterface $parentOrder
     * @param $code
     * @return float|int
     */
    public function getTotalByCode(ParentOrderInterface $parentOrder, $code)
    {
        try {
            $totals = $this->parentOrderManagement->getTotals($parentOrder);
            foreach ($totals as $total) {
                if ($total->getCode() === $code) {
                    return (float) $total->getValue();
                }
            }
            return 0;
        } catch (\Exception $exception) {
            return 0;
        }
    }


    public function getPaymentMethodTitle(ParentOrderInterface $parentOrder)
    {
        $paymentMethod = $parentOrder->getDetail()->getPaymentMethod();
        $paymentMethodInstance = $this->getPaymentMethodInstance($paymentMethod);
        $title = $paymentMethodInstance?->getTitle();

        return $title ? " ($title)" : '';
    }


    public function getPaymentMethodInstance($paymentMethod)
    {
        if (!isset($this->paymentMethodInstances[$paymentMethod])) {
            try {
                $this->paymentMethodInstances[$paymentMethod] = $this->paymentHelper->getMethodInstance($paymentMethod);
            } catch (\Exception $exception) {
                $this->paymentMethodInstances[$paymentMethod] = null;
            }
        }
        return $this->paymentMethodInstances[$paymentMethod];
    }

    /**
     * @param ParentOrderInterface $parentOrder
     * @return int
     */
    public function getPointDiscount(ParentOrderInterface $parentOrder)
    {
        try {
            $total = 0;
            /**
             * @var $order \Magento\Sales\Model\Order
             */
            foreach ($parentOrder->getSubOrders() as $order) {
                $total += $order->getData('point_discount_total');
            }
            return $total;
        } catch (\Exception $exception) {
            return 0;
        }
    }

    public function getDiscountAmount(ParentOrderInterface $parentOrder)
    {
        try {
            $total = 0;
            /**
             * @var $order \Magento\Sales\Model\Order
             */
            foreach ($parentOrder->getSubOrders() as $order) {
                $total += $order->getData('discount_amount');
            }
            return $total;
        } catch (\Exception $exception) {
            return 0;
        }
    }

    public function getNoResultFilterMessage()
    {
        $fromDate = $this->getRequest()->getParam('from_date');
        $toDate = $this->getRequest()->getParam('to_date');
        $statuses = $this->getStatuses();
        //找不到 "2023/05 - 2024/05"且 "處理中" 的訂單
        // let format date to 2023/05 - 2024/05
        $fromDate = $fromDate ? date('Y/m', strtotime($fromDate)) : '';
        $toDate = $toDate ? date('Y/m', strtotime($toDate)) : '';

        $message = '';
        if ($fromDate && $toDate) {
            $message .= sprintf('找不到 "%s - %s"', $fromDate, $toDate);
        }
        if ($statuses) {
            $message .= sprintf('且 "%s" 的訂單', __($this->mappingStatus($statuses[0])));
        }
        return $message;
    }

//    public function getNoResultFilterMessage()
//    {
//        $fromDate = $this->getRequest()->getParam('from_date');
//        $toDate = $this->getRequest()->getParam('to_date');
//        $statuses = $this->getStatuses();
//
//        $message = '';
//        if ($fromDate && $toDate) {
//            $message .= __('No orders found from "%1" to "%2"', $fromDate, $toDate);
//        }
//        if ($statuses) {
//            $message .= __(' and with status "%1"', $this->mappingStatus($statuses[0]));
//        }
//        return $message;
//    }

    /**
     * @param ParentOrderInterface $parentOrder
     * @return void
     */
    public function getPrintUrl($order)
    {
        if (!$this->httpContext->getValue(Context::CONTEXT_AUTH)) {
            return $this->getUrl('sales/parentOrder/GuestPrint', ['id' => $order->getId()]);
        }
        return $this->getUrl('sales/parentOrder/printPdf', ['id' => $order->getId()]);
    }

    /**
     * canRepayment
     * If order within 24 hours
     * @param  mixed $_order
     * @return void | bool
     */
    public function canRepayment($_order)
    {
        $createdAt = $this->_localeDate->date($_order->getCreatedAt())->format('Y-m-d');
        $currentDate = $this->_localeDate->date()->format('Y-m-d');
        $repaymentStatus = [Status::STATUS_PENDING_PAYMENT];

        // Check if the order was created within the last 24 hours and if its status is in the repayment statuses
        if (strtotime($currentDate) > strtotime($createdAt) || !in_array($_order->getStatus(), $repaymentStatus)) {
            return false;
        }

        return true;
    }

    /**
     * getRepaymentMessage
     *
     * @param  mixed $_order
     * @return void | string
     */
    public function getRepaymentMessage($_order)
    {
        $createdAt = $this->_localeDate->date($_order->getCreatedAt())->format('Y/m/d');
        // let add +24h to created_at
        $createdAt = $createdAt . ' 23:59';
        return __("請於{$createdAt} 前完成付款，逾期將自動取消訂單");
    }

    /**
     * getRePaymentUrl
     *
     * @param  mixed $order
     * @return void | string
     */
    public function getRePaymentUrl($order)
    {
        return '/repayment/checkout/processor/order/' . $order->getIndexId();
    }

    /**
     * @return bool
     */
    public function showStatus()
    {
        return $this->config->showStatus();
    }

    /**
     * get Ecpay invoice carruer num
     * @return bool
     */
    public function getEcpayInvoiceCarruerNum($order)
    {
        return $order->getEcpayInvoiceCarruerNum();
    }

    /**
     * get Ecpay invoice carruer type
     * @return bool
     */
    public function getEcpayInvoiceCarruerType($order)
    {
        return $order->getEcpayInvoiceCarruerType();
    }

    /**
     * @param ParentOrderInterface | ParentOrder $order
     * @return string
     */
    public function getEcpayInvoiceAdditionalData($order)
    {
        $invoiceCarrierType = $order->getDetail()->getEcpayInvoiceCarruerType();

        switch ($invoiceCarrierType) {
            case '0':
                $identifier = $order->getDetail()->getEcpayInvoiceCustomerIdentifier();
                return $identifier ? ($order->getDetail()->getEcpayInvoiceCustomerCompany() . '/' . $identifier) : '';
            case '3':
                return $order->getDetail()->getEcpayInvoiceCarruerNum();
            default:
                return '';
        }


    }

    /**
     * get Ecpay invoice customer identifier
     * @return bool
     */
    public function getEcpayInvoiceCustomerIdentifier($order)
    {
        return $order->getEcpayInvoiceCustomerIdentifier();
    }

    /**
     * get Ecpay invoice customer company
     * @return bool
     */
    public function getEcpayInvoiceCustomerCompany($order)
    {
        return $order->getEcpayInvoiceCustomerCompany();
    }

    /**
     * isNeeddToDisplayInvoiceNote
     *
     * @param  mixed $order
     * @return bool
     */
    public function isNeedToDisplayInvoiceNote($order)
    {
//        https://trello.com/c/nsQrjp2O/1002-uat-bug-checkout-and-order-summary-issues
//        Client want to hide this, maybe it change in the future
        return false;
        $displayInvoiceCreditNoteStatus = [Status::STATUS_CANCELED];
        $displayStatus = in_array($order->getStatus(), $displayInvoiceCreditNoteStatus);

        $customerIdentifer = $order->getData('ecpay_invoice_customer_identifier');
        $isCompany = !is_null($customerIdentifer);
        return $displayStatus && $isCompany;
    }

    /**
     * getInvoiceCreditNoteUrl
     *
     * @return string
     */
    public function getInvoiceCreditNoteUrl()
    {
        return $this->getBaseUrl() . 'files/和泰聯網折讓單(空白).pdf';
    }

    /**
     * @param \Branch8\MarketPlaceParentOrder\Model\ParentOrder $parentOrder
     * @return string
     */
    public function getParentOrderStatusDisplay($parentOrder)
    {
        $parentOrderStatus = $parentOrder->getDetail()->getStatus();

        if ($parentOrderStatus !== Status::STATUS_COMPLETE) {
            return $parentOrderStatus;
        }

        $subOrders = $parentOrder->getSubOrders();
        $isRmaProcessing = false;
        foreach ($subOrders ?? [] as $subOrder) {
            if ($subOrder->getRmaStatus() !== null && $subOrder->getRmaStatus() !== Status::STATUS_RMA_COMPLETED) {
                $isRmaProcessing = true;
                break;
            }
        }

        if ($isRmaProcessing) {
            return Status::STATUS_PROCESSING;
        }

        return $parentOrderStatus;
    }

    public function getParentOrderStatus($parentOrder)
    {
        $parentOrderStatus = $parentOrder->getDetail()->getStatus();
        $parentRmaStatus = $parentOrder->getDetail()->getRmaStatus();

        if($parentOrderStatus === Status::STATUS_COMPLETE && (!$parentRmaStatus || $parentRmaStatus === Status::STATUS_RMA_COMPLETED)) {
            return Status::STATUS_COMPLETE;
        }

        $isFinancialReview = $this->hasFinancialReview($parentOrder);

        if(!$isFinancialReview && $parentOrderStatus === Status::STATUS_CANCELED) {
            return Status::STATUS_CANCELED;
        }

        return Status::STATUS_PROCESSING;
    }

    public function hasFinancialReview($parentOrder)
    {
        $subOrders = $parentOrder->getSubOrders();
        $isFinancialReview = false;

        foreach ($subOrders ?? [] as $subOrder) {
            $orderItems = $subOrder->getItems();
            foreach ($orderItems as $item) {
                if ($item->getFlowStatus() !== null && $item->getFlowStatus() === Status::STATUS_FINANCIAL_REVIEW) {
                    $isFinancialReview = true;
                    break;
                }
            }
        }

        return $isFinancialReview;
    }

    public function getParentOderStatusLabel($parentOrderStatus)
    {
        if($parentOrderStatus === Status::STATUS_COMPLETE) {
            return __('已完成');
        }
        if ($parentOrderStatus === Status::STATUS_CANCELED) {
            return __('已取消');
        }
        return __('處理中');
    }

    public function getSpecificStatusFrontendLabel($status)
    {
        return $this->statusLabel->getStatusFrontendLabel(
            $this->getStatus(),
            Area::AREA_FRONTEND,
            (int) $this->getStoreId()
        );
    }
}
