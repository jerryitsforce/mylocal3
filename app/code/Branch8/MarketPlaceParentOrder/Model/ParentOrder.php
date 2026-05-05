<?php
declare(strict_types=1);

namespace Branch8\MarketPlaceParentOrder\Model;

use Branch8\MarketPlaceParentOrder\Api\Data\ParentOrderDetailInterface;
use Branch8\MarketPlaceParentOrder\Api\Data\ParentOrderInterface;
use Branch8\MarketPlaceParentOrder\Model\ResourceModel\History\Collection;
use Branch8\MarketPlaceParentOrder\Model\ResourceModel\ParentOrderAddress\CollectionFactory as ParentOrderAddressCollectionFactory;
use Magento\Directory\Model\Currency;
use Magento\Directory\Model\CurrencyFactory;
use Magento\Framework\Api\AttributeValueFactory;
use Magento\Framework\Api\ExtensionAttributesFactory;
use Magento\Framework\DataObject;
use Magento\Framework\Model\AbstractExtensibleModel;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\ResourceModel\Order\Shipment\Track;


class ParentOrder extends AbstractExtensibleModel implements ParentOrderInterface
{
    /**
     * @var \Magento\Sales\Model\ResourceModel\Order\Shipment\Track\CollectionFactory
     */
    protected $_trackCollectionFactory;
    /**
     * @var null
     */
    private $detail = null;
    /**
     * @var ParentOrderDetailFactory
     */
    private $detailFactory;
    /**
     * @var null
     */
    private $addreses = null;
    /**
     * @var array
     */
    private $shippingAddress = null;
    /**
     * @var array
     */
    private $billingAddress = null;
    /**
     * @var ParentOrderAddressCollectionFactory
     */
    private $paretOrderAddressCollectionFactory;

    private $subOrders = null;

    private $payments = null;

    private $orderCollectionFactory;

    private $historyCollectionFactory;

    private $_orderConfig;

    private $items = null;

    private $_orderCurrency = null;
    /**
     * @var null
     */
    private $_currencyFactory = null;
    /**
     * @var null
     */
    private $invoices = null;
    /**
     * @var null
     */
    private $shipments = null;
    /**
     * @var null
     */
    private $creditNemos = null;
    /**
     * @var
     */
    private $_tracks;
    /**
     * @var TrackFactory
     */
    private $historyFactory;

    /**
     * @var \Magento\Framework\Filter\Input\PurifierInterface
     */
    private $purifier;

    /**
     * @param \Magento\Framework\Model\Context $context
     * @param \Magento\Framework\Registry $registry
     * @param ExtensionAttributesFactory $extensionFactory
     * @param AttributeValueFactory $customAttributeFactory
     * @param ParentOrderDetailFactory $detailFactory
     * @param ParentOrderAddressCollectionFactory $parentOrderAddressCollectionFactory
     * @param \Magento\Sales\Model\ResourceModel\Order\Shipment\Track\CollectionFactory $trackCollectionFactory
     * @param ResourceModel\History\CollectionFactory $historyCollectionFactory
     * @param \Magento\Sales\Model\ResourceModel\Order\CollectionFactory $orderCollectionFactory
     * @param Order\Config $_orderConfig
     * @param CurrencyFactory $currencyFactory
     * @param \Magento\Framework\Model\ResourceModel\AbstractResource|null $resource
     * @param \Magento\Framework\Data\Collection\AbstractDb|null $resourceCollection
     * @param array $data
     */
    public function __construct(
        \Magento\Framework\Model\Context                                              $context,
        \Magento\Framework\Registry                                                   $registry,
        ExtensionAttributesFactory                                                    $extensionFactory,
        AttributeValueFactory                                                         $customAttributeFactory,
        ParentOrderDetailFactory                                                      $detailFactory,
        ParentOrderAddressCollectionFactory                                           $parentOrderAddressCollectionFactory,
        \Magento\Sales\Model\ResourceModel\Order\Shipment\Track\CollectionFactory     $trackCollectionFactory,
        \Branch8\MarketPlaceParentOrder\Model\ResourceModel\History\CollectionFactory $historyCollectionFactory,
        \Branch8\MarketPlaceParentOrder\Model\HistoryFactory                          $historyFactory,
        \Magento\Sales\Model\ResourceModel\Order\CollectionFactory                    $orderCollectionFactory,
        \Magento\Sales\Model\Order\Config                                             $_orderConfig,
        \Magento\Directory\Model\CurrencyFactory                                      $currencyFactory,
        \Magento\Framework\Filter\Input\PurifierInterface                             $purifier,
        \Magento\Framework\Model\ResourceModel\AbstractResource                       $resource = null,
        \Magento\Framework\Data\Collection\AbstractDb                                 $resourceCollection = null,
        array                                                                         $data = []
    )
    {
        parent::__construct(
            $context,
            $registry,
            $extensionFactory,
            $customAttributeFactory,
            $resource,
            $resourceCollection,
            $data
        );
        $this->detailFactory = $detailFactory;
        $this->paretOrderAddressCollectionFactory = $parentOrderAddressCollectionFactory;
        $this->orderCollectionFactory = $orderCollectionFactory;
        $this->historyCollectionFactory = $historyCollectionFactory;
        $this->_trackCollectionFactory = $trackCollectionFactory;
        $this->_orderConfig = $_orderConfig;
        $this->_currencyFactory = $currencyFactory;
        $this->historyFactory = $historyFactory;
        $this->purifier = $purifier;
    }

    /**
     * @return void
     */
    protected function _construct()
    {
        $this->_init(
            \Branch8\MarketPlaceParentOrder\Model\ResourceModel\ParentOrder::class
        );
    }

    /**
     * @return int|mixed|null
     */
    public function getEntityId()
    {
        return $this->getIndexId();
    }

    /**
     * GetIndexId
     * @return int|mixed|null
     */
    public function getIndexId()
    {
        return $this->getData(self::INDEX_ID);
    }

    /**
     * @param int $value
     * @return $this|\Branch8\MarketPlaceParentOrder\Api\Data\ParentOrderDetailInterface
     */
    public function setIndexId(int $value)
    {
        $this->setData(self::INDEX_ID, $value);
        return $this;
    }

    /**
     * @return mixed|string|null
     */
    public function getOrderIds()
    {
        return $this->getData(self::ORDER_IDS);
    }

    /**
     * @param string $value
     * @return $this|\Branch8\MarketPlaceParentOrder\Api\Data\ParentOrderDetailInterface
     */
    public function setOrderIds(string $value)
    {
        $this->setData(self::ORDER_IDS, $value);
        return $this;
    }

    /**
     * @return int|mixed|null
     */
    public function getPaymentStatus()
    {
        return $this->getData(self::INDEX_ID);
    }

    /**
     * @param int $value
     * @return $this|\Branch8\MarketPlaceParentOrder\Api\Data\ParentOrderDetailInterface
     */
    public function setPaymentStatus(int $value)
    {
        $this->setData(self::PAYMENT_STATUS, $value);
        return $this;
    }

    /**
     * @return \Branch8\MarketPlaceParentOrder\Api\Data\ParentOrderExtensionInterface |null
     */
    public function getExtensionAttributes()
    {
        return $this->_getExtensionAttributes();
    }

    /**
     * @param \Branch8\MarketPlaceParentOrder\Api\Data\ParentOrderExtensionInterface $extensionAttributes
     * @return ParentOrder
     */
    public function setExtensionAttributes(\Branch8\MarketPlaceParentOrder\Api\Data\ParentOrderExtensionInterface $extensionAttributes)
    {
        return $this->_setExtensionAttributes($extensionAttributes);
    }

    /**
     * @return int|mixed|null
     */
    public function getLastOrderId()
    {
        return $this->getData(self::LAST_ORDER_ID);
    }

    /**
     * @param int $value
     * @return $this|ParentOrderDetailInterface
     */
    public function setLastOrderId(int $value)
    {
        $this->setData(self::LAST_ORDER_ID, $value);
        return $this;
    }

    /**
     * @return $this
     */
    public function saveAddresses()
    {
        if (!$this->getAddresses()) {
            return $this;
        }
        foreach ((array)$this->getAddresses() as $address) {
            $address->setParentOrderId($this->getIndexId());
            $address->save();
        }
        return $this;
    }

    /**
     * @return ParentOrderDetailInterface|null
     */
    public function getDetail()
    {
        if ($this->detail === null) {
            $this->detail = $this->detailFactory->create()->load(
                $this->getEntityId(),
                'parent_id'
            );
        }
        return $this->detail;
    }

    /**
     * @param ParentOrderDetailInterface $orderDetail
     * @return $this
     */
    public function setDetail(ParentOrderDetailInterface $orderDetail)
    {
        $this->detail = $orderDetail;
        return $this;
    }

    /**
     * @return null
     */
    public function getAddresses()
    {
        if ($this->addreses === null) {
            $this->addreses = $this->paretOrderAddressCollectionFactory->create()
                ->addFieldToFilter(
                    'parent_order_id',
                    $this->getEntityId()
                )->load();
        }
        return $this->addreses;
    }

    /**
     * @param $type
     * @return array|null
     */
    private function getAddressByType($type)
    {
        if ($allAddress = $this->getAddresses()) {
            foreach ($allAddress as $item) {
                if ($item->getAddressType() === $type) {
                    return $item;
                }
            }
        }
        return null;
    }

    /**
     * @return DataObject
     */
    public function getShippingAddress()
    {
        if ($this->shippingAddress === null) {
            $this->shippingAddress = $this->getAddressByType('shipping');
        }
        return $this->shippingAddress;
    }

    /**
     * @return array
     */
    public function getBillingAddress()
    {
        if ($this->billingAddress === null) {
            $this->billingAddress = $this->getAddressByType('billing');
        }
        return $this->billingAddress;
    }

    /**
     * @return array|\Magento\Sales\Model\ResourceModel\Order\Collection|null
     */
    public function getSubOrders()
    {
        if ($this->subOrders === null) {
            $this->subOrders = [];
            try {
                $suborders = $this->getResource()->getSubOrders((int)$this->getEntityId());
            } catch (\Exception $e) {
                $suborders = [];
            }
            if ($suborders) {
                $this->subOrders = $this->orderCollectionFactory->create()
                    ->addFieldToFilter('entity_id', ['in' => $suborders]);
            }
        }
        return $this->subOrders;
    }

    /**
     * @return array
     */
    public function getSuborderIds()
    {
        if ($this->hasData('subOrderIds')) {
            return $this->getData('subOrderIds');
        }
        $subIds = [];
        foreach ($this->getSubOrders() as $order) {
            $subIds[] = $order->getId();
        }
        $this->setData('subOrderIds', $subIds);
        return $this->getData('subOrderIds');
    }

    /**
     * @return array
     */
    public function getAllPayments()
    {
        if ($this->payments === null) {
            $this->payments = [];
            /**
             * @var $order Order
             */
            foreach ($this->getSubOrders() as $order) {
                $this->payments[] = $order->getPayment();
            }
        }
        return $this->payments;
    }

    /**
     * @return mixed|null
     */
    public function getHistory()
    {
        if ($this->getData('history') !== null) {
            return $this->getData('history');
        }
        /**
         * @var $item \Branch8\MarketPlaceParentOrder\Model\History
         */
        $history = $this->historyCollectionFactory->create()
            ->addFieldToFilter('parent_id', $this->getIndexId())
            ->setOrder('created_at', 'desc')
            ->setOrder('entity_id', 'desc');
        foreach ($history as $item) {
            $item->setParentOrder($this);
        }
        $this->setData('history', $history);
        return $history;
    }

    /**
     * Retrieve order configuration model
     *
     * @return \Magento\Sales\Model\Order\Config
     */
    public function getConfig()
    {
        return $this->_orderConfig;
    }

    /**
     * @return \Magento\Store\Model\Store
     */
    public function getStore()
    {
        return $this->getDetail()->getStore();
    }

    /**
     * @return mixed|null
     */
    public function isVirtual()
    {
        if (!$this->hasData('is_virtual')) {
            $isVirtual = true;
            /**
             * @var $order \Magento\Sales\Model\Order
             */
            foreach ($this->getSubOrders() as $order) {
                if (!$order->getIsVirtual()) {
                    $isVirtual = false;
                    break;
                }
            }
            $this->setData('is_virtual', $isVirtual);
        }
        return $this->getData('is_virtual');
    }

    /**
     * @param $suborders
     * @return $this|ParentOrderDetailInterface
     */
    public function setSubOrders($suborders = [])
    {
        $this->subOrders = $suborders;
        return $this;
    }

    /**
     * @return array
     */
    public function getAllItems($includeFlagshipProcessOrder = true)
    {
        if ($this->items === null) {
            $this->items = [];
            if ($this->getSubOrders()) {
                /**
                 * @var $subOrder Order
                 */
                foreach ($this->getSubOrders() as $subOrder) {
                    if(!$includeFlagshipProcessOrder && $subOrder->getData('is_flagship_store_process_order')){
                        continue;
                    }
                    foreach ($subOrder->getAllItems() as $item) {
                        $this->items[] = $item;
                    }
                }
            }

        }
        return $this->items;
    }

    public function getStoreInfoBySubOrder()
    {
        if ($this->getSubOrders()) {
            /**
             * @var $subOrder Order
             */
            foreach ($this->getSubOrders() as $subOrder) {
                $shippingAddress = $subOrder->getShippingAddress();
                if (!$shippingAddress || empty($shippingAddress->getData('store_address_info'))) {
                    continue;
                }
                $storeAddressInfo = json_decode($shippingAddress->getData('store_address_info') ?? '',true);
                return isset($storeAddressInfo['storename']) ? __('超商取貨(711%1)',$storeAddressInfo['storename']) : '';
            }
        }
        return '';
    }

    /**
     * Format price precision
     *
     * @param float $price
     * @param int $precision
     * @param bool $addBrackets
     * @return string
     */
    public function formatPricePrecision($price, $precision, $addBrackets = false)
    {
        return $this->purifier->purify($this->getOrderCurrency()->formatPrecision($price, $precision, [], true, $addBrackets));
    }

    /**
     * @param $price
     * @param $addBrackets
     * @return string
     */
    public function formatPrice($price, $addBrackets = false)
    {
        return $this->purifier->purify($this->formatPricePrecision($price, 2, $addBrackets));
    }

    /**
     * Get currency model instance. Will be used currency with which order placed
     *
     * @return Currency
     */
    public function getOrderCurrency()
    {
        if ($this->_orderCurrency === null) {
            $this->_orderCurrency = $this->_currencyFactory->create();
            $this->_orderCurrency->load($this->getDetail()->getOrderCurrencyCode());
        }
        return $this->_orderCurrency;
    }

    /**
     * Retrieve order unhold availability
     *
     * @return bool
     */
    public function canUnhold()
    {
        return $this->getDetail() === Order::STATE_HOLDED;
    }

    /**
     * /**
     * Retrieve order edit availability
     *
     * @return bool
     */
    public function canEdit()
    {
        if ($this->canUnhold()) {
            return false;
        }
        $state = $this->getDetail()->getState();
        if ($this->isCanceled() ||
            $this->isPaymentReview() ||
            $state === Order::STATE_COMPLETE ||
            $state === Order::STATE_CLOSED
        ) {
            return false;
        }
        return true;
    }

    /**
     * @return bool
     */
    public function isCanceled()
    {
        return $this->getDetail()->getState() === Order::STATE_CANCELED;
    }

    /**
     * @return bool
     */
    public function isPaymentReview()
    {
        return $this->getDetail()->getState() === Order::STATE_PAYMENT_REVIEW;
    }

    /**
     * @return array
     */
    public function getVisibleStatusHistory()
    {
        $history = [];
        /**
         * @var $status History
         */
        foreach ($this->getHistory() as $status) {
            if (!$status->isDeleted() && $status->getComment() && $status->getIsVisibleOnFront()) {
                $history[] = $status;
            }
        }
        return $history;
    }

    /**
     * @return array|Track\Collection
     */
    public function getTracksCollection()
    {
        if (empty($this->_tracks)) {
            /**
             * @var $collection Track\Collection
             */
            $collection = $this->_trackCollectionFactory->create();
            $subOrderIds = [];
            foreach ($this->getSubOrders() as $subOrder) {
                $subOrderIds[] = $subOrder->getId();
            }
            if (!count($subOrderIds)) {
                $this->_tracks = [];
            } else {
                $collection->addFieldToFilter('order_id',
                    ['in' => $subOrderIds]
                );
                $this->_tracks = $collection->load();
            }
        }
        return $this->_tracks;
    }

    /**
     * @return bool
     */
    public function hasInvoices()
    {
        return count($this->getInvoiceCollection()) > 0;
    }

    /**
     * @return array|null
     */
    public function getInvoiceCollection()
    {
        if ($this->invoices === null) {
            $this->invoices = [];
            /**
             * @var $order Order
             */
            foreach ($this->getSubOrders() as $order) {
                foreach ($order->getInvoiceCollection() as $invoice) {
                    $this->invoices[] = $invoice;
                }
            }
        }
        return $this->invoices;
    }

    /**
     * Check order shipments availability
     *
     * @return bool
     */
    public function hasShipments()
    {
        return count($this->getShipmentCollection()) > 0;
    }

    /**
     * @return array|null
     */
    public function getShipmentCollection()
    {
        if ($this->shipments === null) {
            $this->shipments = [];
            /**
             * @var $order Order
             */
            foreach ($this->getSubOrders() as $order) {
                foreach ($order->getShipmentsCollection() as $shipment) {
                    $this->shipments[] = $shipment;
                }
            }
        }
        return $this->shipments;
    }

    /**
     * @return array|null
     */
    public function getCreditmemosCollection()
    {
        if ($this->creditNemos === null) {
            $this->creditNemos = [];
            /**
             * @var $order Order
             */
            foreach ($this->getSubOrders() as $order) {
                foreach ($order->getCreditmemosCollection() as $nemo) {
                    $this->creditNemos[] = $nemo;
                }
            }
        }
        return $this->creditNemos;
    }

    /**
     * Check order creditmemos availability
     *
     * @return bool
     */
    public function hasCreditmemos()
    {
        return count($this->getCreditmemosCollection()) > 0;
    }

    /**
     * @return mixed|string|null
     */
    public function getRealOrderId()
    {
        $id = $this->getData('real_order_id');
        if ($id === null) {
            $id = $this->getDetail()->getIncrementId();
        }
        return $id;
    }

    /**
     * @param $comment
     * @param $status
     * @param $isVisibleOnFront
     * @return $this
     * @throws \Exception
     */
    public function addComment($comment = '', $status = null, $isVisibleOnFront = false)
    {
        /**
         * @var $history History
         */
        $history = $this->historyFactory->create();
        $history->setParentOrder($this)
            ->setComment($comment)
            ->setStatus($status)
            ->setParentId($this->getEntityId())
            ->setIsVisibleOnFront($isVisibleOnFront)->save();
        return $this;
    }
}
