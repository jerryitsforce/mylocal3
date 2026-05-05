<?php
declare(strict_types=1);

namespace Branch8\MarketPlaceParentOrderFrontendUi\Block\ParentOrder;

use Branch8\HotaiCore\Model\Order\Status as HotaiStatus;
use Branch8\PointMoneyConfig\Helper\Common as PointMoneyConfigCommon;
use Branch8\Rma\Model\Rma\Status as RmaStatus;
use Branch8\Shipping\Model\ShippingMethod;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Catalog\Helper\ImageFactory;
use Magento\Catalog\Model\Product\OptionFactory;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\ObjectManager;
use Magento\Framework\Registry;
use Magento\Framework\View\Element\AbstractBlock;
use Magento\Framework\View\Element\Template\Context;
use Magento\Sales\Block\Items\AbstractItems;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\ResourceModel\Order\Item\Collection;
use Magento\Sales\Model\ResourceModel\Order\Item\CollectionFactory;
use Magento\Store\Model\ScopeInterface as StoreScopeInterface;
use Magento\Store\Model\Store;
use Magento\Theme\Block\Html\Pager;
use \Magento\Sales\Model\OrderRepository;
use Magento\Catalog\Helper\Image;
use Magento\Rma\Helper\Data as RmaHelper;
use Webkul\Marketplace\ViewModel\Profile;
use Magento\Framework\Stdlib\StringUtils;
use Branch8\HotaiCore\Helper\VirtualProduct as VirtualProductHelper;
use Branch8\HotaiCore\Model\Product\VirtualProductType;
use Branch8\Edenred\Model\EdenredTicketRecordRepository;
use Magento\Customer\Model\Session\Proxy as CustomerSession;

class SubOrderItems extends Items
{
    protected $_order = null;

    /**
     * @var Image
     */
    protected $imageHelper;

    /**
     * @var \Branch8\Sales\Block\ParentOrder\Items
     */
    protected $itemBlock;

    /**
     * @var \Webkul\MarketplacePreorder\Helper\Data
     */
    protected $preorderHelper;

    /**
     * @var ScopeConfigInterface
     */
    protected $scopeConfig;

    /**
     * @var \Ecpay\General\Controller\Api\Invoice
     */
    protected $invoiceAPi;

    /**
     * @var RmaHelper
     */
    protected RmaHelper $rmaHelper;

    /**
     * @var \Branch8\Sales\Helper\Config
     */
    protected $config;

    protected $preorderItemCollectionFactory;

    protected $rmaDetail;
    protected $virtualProductHelper;
    protected $edenredTicketRecordRepository;

    /**
     * @param Context $context
     * @param Registry $registry
     * @param OrderRepository $_orderRepository
     * @param Profile $profile
     * @param ProductRepositoryInterface $productRepository
     * @param ImageFactory $imageFactory
     * @param \Webkul\Marketplace\Helper\Data $marketPlaceDataHelper
     * @param \Branch8\SplitCart\Helper\Data $splitCartHelper
     * @param \Magento\Store\Model\StoreManagerInterface $storemanager
     * @param OptionFactory $productOptionFactory
     * @param StringUtils $string
     * @param Image $imageHelper
     * @param \Branch8\Sales\Block\ParentOrder\Items $itemBlock
     * @param \Webkul\MarketplacePreorder\Helper\Data $preorderHelper
     * @param RmaHelper $rmaHelper
     * @param ScopeConfigInterface $scopeConfig
     * @param \Ecpay\General\Controller\Api\Invoice $invoiceAPi
     * @param \Branch8\Sales\Helper\Config $config
     * @param CollectionFactory|null $itemCollectionFactory
     */
    public function __construct(
        Context                                                                         $context,
        Registry                                                                        $registry,
        OrderRepository                                                                 $_orderRepository,
        \Webkul\Marketplace\ViewModel\Profile                                           $profile,
        ProductRepositoryInterface                                                      $productRepository,
        ImageFactory                                                                    $imageFactory,
        \Webkul\Marketplace\Helper\Data                                                 $marketPlaceDataHelper,
        \Branch8\SplitCart\Helper\Data                                                  $splitCartHelper,
        \Magento\Store\Model\StoreManagerInterface                                      $storemanager,
        OptionFactory                                                                   $productOptionFactory,
        StringUtils                                                                     $string,
        Image                                                                           $imageHelper,
        \Branch8\Sales\Block\ParentOrder\Items                                          $itemBlock,
        \Webkul\MarketplacePreorder\Helper\Data                                         $preorderHelper,
        RmaHelper                                                                       $rmaHelper,
        ScopeConfigInterface                                                            $scopeConfig,
        \Ecpay\General\Controller\Api\Invoice                                           $invoiceAPi,
        \Branch8\Sales\Helper\Config                                                    $config,
        \Webkul\MarketplacePreorder\Model\ResourceModel\PreorderItems\CollectionFactory $preorderItemCollectionFactory,
        \Webkul\MpRmaSystem\Model\DetailsFactory                                        $rmaDetail,
        VirtualProductHelper          $virtualProductHelper,
        EdenredTicketRecordRepository $edenredTicketRecordRepository,
        CustomerSession $customerSession,
        CollectionFactory             $itemCollectionFactory = null

    )
    {
        parent::__construct(
            $context,
            $registry,
            $itemCollectionFactory,
            $_orderRepository,
            $profile,
            $productRepository,
            $imageFactory,
            $marketPlaceDataHelper,
            $splitCartHelper,
            $storemanager,
            $productOptionFactory,
            $string
        );
        $this->imageHelper = $imageHelper;
        $this->itemBlock = $itemBlock;
        $this->preorderHelper = $preorderHelper;
        $this->rmaHelper = $rmaHelper;
        $this->scopeConfig = $scopeConfig;
        $this->invoiceAPi = $invoiceAPi;
        $this->config = $config;
        $this->preorderItemCollectionFactory = $preorderItemCollectionFactory;
        $this->rmaDetail = $rmaDetail;
        $this->virtualProductHelper = $virtualProductHelper;
        $this->edenredTicketRecordRepository = $edenredTicketRecordRepository;
        $this->_customerSession = $customerSession;
    }


    /**
     * Init pager block and item collection with page size and current page number
     *
     * @return $this
     * @since 100.1.7
     */
    protected function _prepareLayout()
    {
        $this->itemsPerPage = $this->_scopeConfig->getValue('sales/orders/items_per_page');


        /** @var Pager $pagerBlock */
        $pagerBlock = $this->getChildBlock('sales_order_item_pager');
        if ($pagerBlock) {
            $this->preparePager($pagerBlock);
        }
    }

    public function getInvoicePrintUrl($order)
    {
        return ''; //ticket 656-bugfe05order-management-invoice-funtion-link
        try {
            $response = $this->invoiceAPi->getInvoiceUrl($order->getId(), $order->getProtectCode(), false);

            return $response['data'];
        } catch (\Exception $e) {
            return '';
        }

    }

    /**
     * @param \Magento\Sales\Model\Order $order
     * @return string
     */
    public function getPrintUrl($order)
    {
        if (!$this->_customerSession->isLoggedIn()) {
            return $this->getUrl('sales/parentOrder/GuestPrint', ['id' => $order->getId()]);
        }
        return $this->getUrl('sales/parentOrder/printPdf', ['id' => $order->getId()]);
    }


    /**
     * Get Item Block
     */
    public function getItemsBlock()
    {
        return $this->itemBlock;
    }

    public function isPassedSameDay($order)
    {
        $orderDate = $order->getCreatedAt();
        $orderDate = $this->_localeDate->date($orderDate)->format('Y-m-d');
        $currentDate = $this->_localeDate->date()->format('Y-m-d');
        return $orderDate == $currentDate;
    }

    public function getCurrentTime()
    {
        return $this->_localeDate->date()->format('Y-m-d H:i:s');
    }

    /**
     * canCreateRma
     *
     * @param string $status
     * @return bool
     */
    public function canCreateRmaRequest($item)
    {
        $product=$item->getProduct();
        if (!$product) {
            return false;
        }
        $rmaStatus = $item->getRmaStatus();
        if ($rmaStatus != RmaStatus::RETURN_OR_EXCHANGE_AVALIABLE
            || !$this->rmaHelper->canReturnProduct($item->getProduct(), $item->getStoreId())
            // || $item->getData('is_virtual')
        ) {
            return false;
        }
        $status = $item->getFlowStatus();

        $avaliableRmaStatus = [
            HotaiStatus::STATUS_PENDING,
            HotaiStatus::STATUS_PENDING_PAYMENT,
            HotaiStatus::STATUS_PROCESSING,
            HotaiStatus::STATUS_PENDING,
            HotaiStatus::STATUS_SHIPPING,
            HotaiStatus::STATUS_ARRIVED,
            HotaiStatus::STATUS_PROCESSING_REPLACE_ARRIVED,
            HotaiStatus::STATUS_PICKED,
            HotaiStatus::STATUS_TALLYING,
        ];

        return in_array($status, $avaliableRmaStatus);
    }

    public function getItemSubTotal($item)
    {
        $discount = $item->getData('discount_amount')?? 0;
        $pointUsed = $item->getData('row_total_point_used')?? 0;
        $subTotal = $item->getData('row_total_incl_tax') - $discount - $pointUsed;
        return $subTotal > 0 ? $subTotal : 0;
    }

    /**
     * canCancelRmaRequest
     *
     * @param string $status
     * @return bool
     */
    public function canCancelRmaRequest($status)
    {

        $avaliableRmaStatus = [
            HotaiStatus::STATUS_APPLYING_REPLACE,
            HotaiStatus::STATUS_APPLYING_RETURN,
        ];

        return in_array($status, $avaliableRmaStatus);

    }

    /**
     * Get Preorder Helper
     */
    public function getPreorderHelper()
    {
        return $this->preorderHelper;
    }

    /**
     * Get Message by Flow Status
     */
    public function getFlowStatusNotification($item)
    {
        switch ($item->getFlowStatus()) {
            case HotaiStatus::STATUS_ARRIVED:
            case HotaiStatus::STATUS_PROCESSING_REPLACE_ARRIVED:
                return __('If you have not received the goods, please contact the seller.');
            case HotaiStatus::STATUS_RETURNED:
                return __("The actual refund date is subject to the issuing bank's processing time.");
            // Return status
            case HotaiStatus::STATUS_APPLYING_RETURN:
            case HotaiStatus::STATUS_APPLYING_RETURN_SHIPPING:
            case HotaiStatus::STATUS_APPLYING_RETURN_CANCEL:
            case RmaStatus::RETURN_APPLY_PROCESSING:
            case RmaStatus::RETURN_SHIPPING:
            // Exchange status
            case HotaiStatus::STATUS_APPLYING_REPLACE:
            case HotaiStatus::STATUS_REPLACE_SHIP_TO_SUPPLIER:
            case RmaStatus::REPLACE_APPLY_PROCESSING:
            case RmaStatus::REPLACE_SHIPPING_TO_SUPPLIER:
                return __('If you have received the product, please package it properly and wait for the delivery personnel to collect it. If you have not yet received the product, please refuse the delivery when it arrives. Thank you!');
            default:
                return '';
        }
    }

    /**
     * Get Order Item error message
     *
     * @param $item
     * @return \Magento\Framework\Phrase|string
     */
    public function getFlowStatusError($item)
    {
        switch ($item->getFlowStatus()) {
            case HotaiStatus::STATUS_APPLYING_REPLACE_REJECT:
            case HotaiStatus::STATUS_APPLYING_REPLACE_REVIEW_REJECT:
                return __('Exchange failed');
            case HotaiStatus::STATUS_APPLYING_RETURN_REJECT:
            case HotaiStatus::STATUS_APPLYING_RETURN_REVIEW_REJECT:
                return __('Return failed');
            case HotaiStatus::STATUS_FAILED_DELIVERY:
                return __("This item has been canceled");
            default:
                return '';
        }
    }

    public function getFlowStatusErrorReason($item)
    {
        switch ($item->getFlowStatus()) {
            case HotaiStatus::STATUS_APPLYING_REPLACE_REJECT:
            case HotaiStatus::STATUS_APPLYING_REPLACE_REVIEW_REJECT:
                return __('商品已被使用或損壞');
            case HotaiStatus::STATUS_APPLYING_RETURN_REJECT:
            case HotaiStatus::STATUS_APPLYING_RETURN_REVIEW_REJECT:
                return __('商品已被使用或損壞');
            case HotaiStatus::STATUS_FAILED_DELIVERY:
                return __("Delivery failed");
            default:
                return '';
        }
    }

    /**
     * @param $item
     * @return bool
     */
    public function isItemLongTimeShipped($item)
    {
        $product = $item->getProduct();
        $isItemLongTimeShiped = false;
        if ($product) {
            $isItemLongTimeShiped = (bool)$item->getProduct()->getData('long_time_ship');
        }
        return $isItemLongTimeShiped;
    }

    /**
     * Get Long Time Message
     */
    public function getLongTimeMessage()
    {
        //This product has a longer lead time (more than 5 days), please be patient
        return __('This product has a longer lead time (more than 5 days), please be patient');
    }


    /**
     * Get Product Image URL
     * @param $product
     * @return string
     */
    public function getProductImageUrl($product)
    {
        if ($product === null) {
            return '';
        }
        return $this->imageHelper
            ->init($product, 'product_page_image_small')
            ->setImageFile($product->getImage())
            ->keepAspectRatio(true)
            ->resize(400, 400)
            ->getUrl();
    }

    /**
     * Sets order.
     *
     * @param Order $order
     * @return $this
     */
    public function setOrder($order)
    {
        $this->_order = $order;
        return $this;
    }

    /**
     * Sets items.
     *
     * @param $items
     * @return $this
     */
    public function setItemsCollection($items)
    {
        $this->itemCollection = $items;
        if (!$items) {
            $this->itemCollection = $this->createItemsCollection();
        }
        return $this;
    }

    /**
     * Retrieve current order model instance
     *
     * @return Order
     */
    public function getOrder()
    {
        return $this->_order;
    }


    /**
     * Retrieve current order model instance
     *
     * @return Collection
     */
    public function getItemsCollection()
    {
        return $this->itemCollection;
    }

    /**
     * Get visible items for current page.
     *
     * To be called from templates(after _prepareLayout()).
     *
     * @return \Magento\Framework\DataObject[]
     * @since 100.1.7
     */
    public function getItems()
    {
        $groupedItems = [];
        /**
         * @var $item \Magento\Sales\Model\Order\Item
         */
        foreach ($this->itemCollection->getItems() as $item) {
            /*  if ($item->getProduct() == null){
                  continue;
              }*/
            $sellerId = (int)$item->getData('seller_id');
            if (!isset($groupedItems[$sellerId . '-' . $item->getData('order_id')])) {
                $groupedItems[$sellerId . '-' . $item->getData('order_id')] = [];
            }
            $groupedItems[$sellerId . '-' . $item->getData('order_id')][] = $item;
        }

        return $groupedItems;
    }

    public function formatDateToTimeZone($date)
    {
        return $this->_localeDate->date(new \DateTime($date))->format('Y-m-d H:i:s');
    }

    /**
     * @param $subOrder
     * @return bool
     */
    public function isSubOrderCancelled($subOrder)
    {
        return $subOrder->getStatus() == 'canceled';
    }

    /**
     * Check if the item is only product point
     * @param $order
     * @param $item
     * @return bool
     */
    public function isOnlyProductPoint($order, $item)
    {
        $product = $item->getProduct();
        if ($product) {
            return !in_array($order->getShippingMethod(), [ShippingMethod::METHOD_HOME, ShippingMethod::METHOD_CONVENIENCE_STORE]) &&
                in_array($item->getProduct()->getPointMoneyConfigType(), PointMoneyConfigCommon::TYPES_ONLY_PRODUCT_POINT);
        }
        return false;
    }

    /**
     * Get Cancel Reason Message
     * @param $subOrder
     * @return string
     */
    public function getCancelReasonMessage($subOrder)
    {
        if ($subOrder->getShippingMethod() == \Branch8\Shipping\Model\ShippingMethod::METHOD_CONVENIENCE_STORE && $subOrder->getFlowStatus() != HotaiStatus::STATUS_PICKED) {
            return __('Not picked up');
        }
        if ($subOrder->getFlowStatus() == HotaiStatus::STATUS_FAILED_DELIVERY) {
            return __('配送失敗');
        }
        return '';
        // return __('Delivery failed');
    }

    /**
     * Get order item notifications
     *
     * @param $item
     * @param $subOrder
     * @return array
     */
    public function getItemNotifications($item, $subOrder)
    {
        $messages = [];
        if ($this->preorderHelper->isPreorderOrderedItem($subOrder->getEntityId())) {
            $preOrderItemMessage = $this->getPreorderMessage($item->getId());
            if ($preOrderItemMessage != null) {
                $messages[] = $preOrderItemMessage;
            }
        }

        if ($this->isItemLongTimeShipped($item)) {
            $messages[] = $this->getLongTimeMessage();
        }

        if (!empty($this->getFlowStatusNotification($item))) {
            $messages[] = $this->getFlowStatusNotification($item);
        }

        return $messages;
    }

    protected function getPreorderMessage($itemId)
    {
        $items = $this->preorderItemCollectionFactory->create()
            ->addFieldToFilter('item_id', $itemId)
            ->addFieldToSelect('preorder_message');
        foreach ($items as $_item) {
            return $_item['preorder_message'];
        }
        return null;
    }

    /**
     * Get order item logistic info
     * @param $subOrder
     * @param $orderItemId
     * @return array
     */
    public function getLogisticInfo($subOrder, $orderItemId)
    {
        $logisticsCompanies = $this->config->getLogisticsCompanies();
        $shipments = $subOrder->getShipmentsCollection();

        $shipmentFormatted = [];

        foreach ($shipments as $shipment) {
            $tracks = $shipment->getAllTracks();
            if (empty($tracks)) {
                continue;
            }

            $trackingInfo = [];
            foreach ($tracks as $track) {
                $trackingInfo[] = [
                    'logistics_company' => $track->getTitle(),
                    'logistics_company_url' => $logisticsCompanies[$track->getTitle()] ?? '',
                    'tracking_number' => $track->getTrackNumber()
                ];
            }

            $shipmentItems = $shipment->getItems();
            foreach ($shipmentItems as $item) {
                $itemId = $item->getOrderItemId();
                if (!isset($shipmentFormatted[$itemId])) {
                    $shipmentFormatted[$itemId] = $trackingInfo;
                } else {
                    $shipmentFormatted[$itemId] = array_merge($shipmentFormatted[$itemId], $trackingInfo);
                }
            }
        }

        return $shipmentFormatted[$orderItemId] ?? [];
    }


    /**
     * Get order item error message
     *
     * @param $item
     * @param $subOrder
     * @param $parentOrder
     * @return array
     */
    public function getItemErrorMessage($item, $subOrder, $parentOrder)
    {
        if ($this->isSubOrderCancelled($subOrder)) {
            $reason = $this->getCancelReasonMessage($subOrder);
            if($reason != '') {
                return [
                    'error_msg' => __('This item has been canceled'),
                    // 'reason' => $reason,
                    'reason' => '',
                ];
            }
            return [
                'error_msg' => __('訂單已取消'),
                'reason' => __(''),
                // 'reason' => __('操作錯誤'),
            ];
        }

        if (!empty($this->getFlowStatusError($item))) {
            $data = [
                'error_msg' => $this->getFlowStatusError($item),
            ];

            if (!empty($this->getFlowStatusErrorReason($item))) {
                $data['reason'] = $this->getFlowStatusErrorReason($item);
            }

            return $data;
        }

        return [];
    }

    /**
     * getRmaOptionsArray
     *
     * @param mixed $item
     * @return array
     */
    public function getRmaOptionsArray($item)
    {
        $rmaOptions = $item->getRmaOptions();
        if (is_null($rmaOptions)) {
            return [];
        }

        return json_decode($rmaOptions, true);
    }

    /**
     * getRmaId
     *
     * @param mixed $item
     * @return string|int
     */
    public function getRmaId($item)
    {
        $options = $this->getRmaOptionsArray($item);
        if (empty($options)) {
            return 0;
        }

        return $options['rma_id'];

    }

    public function getRmaDataForCancel($item)
    {
        $rmaId = $this->getRmaId($item);
        $rmaItem = $this->rmaDetail->create()->load($rmaId);
        return $rmaItem;
    }

    /**
     * getCancelRmaRequestBtnTitle
     *
     * @param string $status
     * @return string
     */
    public function getCancelRmaRequestBtnTitle($status)
    {
//        $title = 'Cancel Return Request';
//        if ($status == HotaiStatus::STATUS_APPLYING_REPLACE) {
//            $title = 'Cancel Replace Request';
//        }
        $title = __('我不退了');
        return $title;

    }

    public function canShowChatSeller($status)
    {
        /**
         * Show for all statuses, QA confirmed
         */
//        $notShowChatStatus = [
//            \Branch8\HotaiCore\Model\Order\Status::STATUS_PENDING_PAYMENT,
//            \Branch8\HotaiCore\Model\Order\Status::STATUS_PROCESSING,
//            \Branch8\HotaiCore\Model\Order\Status::STATUS_COMPLETE
//        ];
//        if(in_array($status, $notShowChatStatus)){
//            return false;
//        }
        return true;
    }

    public function isEdenredTicket($item): bool
    {
        $virtualProductType = $this->virtualProductHelper->getProductTicketTypeByOrderItemId((int) $item->getId());

        return $virtualProductType == VirtualProductType::TYPE_EDENRED_TICKET;
    }

    public function getTicketStatusByOrderItemId($item): array
    {
        $virtualProductType = $this->virtualProductHelper->getTicketStatusByOrderItemId((int) $item->getId());

        return $virtualProductType;
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
        // return $this->getUrl('member/ticket/detail/', ['id' => $id]);
    }


    public function getEdenredClientOrderNumber($item): string
    {
        $clientOrderNumber = "";

        $records = $this->edenredTicketRecordRepository->getRecordsByOrderItemId((int) $item->getId());

        if ($records->getSize()) {
            /** @var \Branch8\Edenred\Model\EdenredTicketRecord $record */
            $record = $records->getFirstItem();
            $clientOrderNumber = $record->getEdenredClientOrderNumber();
        }

        return $clientOrderNumber;
    }

    public function isGiftOrder($order)
    {
        return (bool) $order->getData('is_gift_order');
    }
}
