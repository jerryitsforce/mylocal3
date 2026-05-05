<?php
declare (strict_types = 1);

namespace Branch8\Sales\Block\ParentOrder;

use Branch8\HotaiCore\Model\Order\Status as HotaiStatus;
use Branch8\Rma\Helper\Config\StatusLabel as RmaStatusLabel;
use Branch8\Rma\Model\MarketplaceRmaStatusHistoryFactory;
use Branch8\Sales\Helper\FrontOrderFlow;
use Magento\Framework\App\ObjectManager;
use Magento\Framework\Registry;
use Magento\Framework\View\Element\AbstractBlock;
use Magento\Framework\View\Element\Template\Context;
use Magento\Sales\Block\Items\AbstractItems;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\ResourceModel\Order\Item\Collection;
use Magento\Sales\Model\ResourceModel\Order\Item\CollectionFactory;
use Magento\Theme\Block\Html\Pager;
use \Magento\Sales\Model\OrderRepository;
use \Magento\Sales\Model\ResourceModel\Order\Status\History\CollectionFactory as HistoryCollection;

class Items extends AbstractItems
{
    /**
     * @var Registry
     */
    protected $_coreRegistry = null;

    /**
     * @var int
     */
    private $itemsPerPage;

    /**
     * @var CollectionFactory
     */
    private $itemCollectionFactory;

    /**
     * @var Collection|null
     */
    private $itemCollection;

    /** @var \Magento\Sales\Model\OrderRepository */
    protected $_orderRepository;

    /** @var \Magento\Sales\Model\ResourceModel\Order\Status\History\CollectionFactory $historyCollection */
    protected $historyCollection;

    /** @var \Branch8\Rma\Helper\Config\StatusLabel $rmaStatusLabel */
    protected $rmaStatusLabel;

    /** @var \Branch8\Rma\Model\MarketplaceRmaStatusHistoryFactory $marketplaceRmaStatusHistory */
    protected $marketplaceRmaStatusHistory;

    /** @var \Branch8\Sales\Helper\FrontOrderFlow $frontOrderFlow */
    protected $frontOrderFlow;

    /**
     * @param Context $context
     * @param Registry $registry
     * @param array $data
     * @param CollectionFactory|null $itemCollectionFactory
     * @param OrderRepository $_orderRepository
     */
    public function __construct(
        Context $context,
        Registry $registry,
        CollectionFactory $itemCollectionFactory = null,
        OrderRepository $_orderRepository,
        HistoryCollection $historyCollection,
        RmaStatusLabel $rmaStatusLabel,
        FrontOrderFlow $frontOrderFlow,
        MarketplaceRmaStatusHistoryFactory $marketplaceRmaStatusHistory
    ) {
        $this->_coreRegistry = $registry;
        $this->itemCollectionFactory = $itemCollectionFactory ?: ObjectManager::getInstance()
            ->get(CollectionFactory::class);
        $this->_orderRepository = $_orderRepository;
        $this->historyCollection = $historyCollection;
        $this->rmaStatusLabel = $rmaStatusLabel;
        $this->frontOrderFlow = $frontOrderFlow;
        $this->marketplaceRmaStatusHistory = $marketplaceRmaStatusHistory;
        parent::__construct($context, []);
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
        $this->itemCollection = $this->createItemsCollection();

        /** @var Pager $pagerBlock */
        $pagerBlock = $this->getChildBlock('sales_order_item_pager');
        if ($pagerBlock) {
            $this->preparePager($pagerBlock);
        }

        return parent::_prepareLayout();
    }

    /**
     * Determine if the pager should be displayed for order items list.
     *
     * To be called from templates(after _prepareLayout()).
     *
     * @return bool
     * @since 100.1.7
     */
    public function isPagerDisplayed()
    {
        $pagerBlock = $this->getChildBlock('sales_order_item_pager');
        return $pagerBlock && ($this->itemCollection->getSize() > $this->itemsPerPage);
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
        return $this->itemCollection->getItems();
    }

    /**
     * Get pager HTML according to our requirements.
     *
     * To be called from templates(after _prepareLayout()).
     *
     * @return string HTML output
     * @since 100.1.7
     */
    public function getPagerHtml()
    {
        /** @var Pager $pagerBlock */
        $pagerBlock = $this->getChildBlock('sales_order_item_pager');
        return $pagerBlock ? $pagerBlock->toHtml() : '';
    }

    /**
     * Retrieve current order model instance
     *
     * @return Order
     */
    public function getOrder()
    {
        return $this->_coreRegistry->registry('current_parent_order');
    }

    /**
     * Prepare pager block
     *
     * @param AbstractBlock $pagerBlock
     */
    private function preparePager(AbstractBlock $pagerBlock): void
    {
        $collectionToPager = $this->itemCollection;
        $collectionToPager->addFieldToFilter('parent_item_id', ['null' => true]);
        $pagerBlock->setLimit($this->itemsPerPage);
        $pagerBlock->setAvailableLimit([$this->itemsPerPage]);
        $pagerBlock->setCollection($collectionToPager);
        $pagerBlock->setShowAmounts($this->isPagerDisplayed());
    }

    /**
     * Create items collection
     *
     * @return Collection
     */
    private function createItemsCollection(): Collection
    {
        $collection = $this->itemCollectionFactory->create();
        $collection->addFieldToFilter(
            'order_id', ['in' => $this->getOrder()->getSuborderIds()])
        ;
        return $collection;
    }

    /**
     * getSubOrderById
     *
     * @param  int $orderId
     * @return
     */
    public function getSubOrderById($orderId)
    {
        return $this->_orderRepository->get($orderId);
    }

    /**
     * getProgressType
     *
     * @param  mixed $order
     * @return int
     */
    public function getProgressType($order)
    {
        $shippingMethod = $order->getShippingMethod();

        switch ($shippingMethod) {
            case \Branch8\Shipping\Model\ShippingMethod::METHOD_HOME:
                return $this->frontOrderFlow::PROGRESS_TYPE_DELIVERY;
            case \Branch8\Shipping\Model\ShippingMethod::METHOD_CONVENIENCE_STORE:
                return $this->frontOrderFlow::PROGRESS_TYPE_CONVIENCE_STORE;
            default:
                return $this->frontOrderFlow::PROGRESS_TYPE_TICKET;
        }

    }

    /**
     * getProgressBar
     *
     * @param  mixed $subOrder
     * @param  mixed $item
     * @return array
     */
    public function getProgressBar($subOrder, $item)
    {
        $type = $this->getProgressType($subOrder);
        $flow = $this->getProgressFlow($type, $item);

        $statusCollection = $this->getItemStatusCollection($subOrder->getId(), $item->getId());
        $rmaCollection = $this->getRmaStatusCollection($this->getRmaId($item));
        $statusTimeArray = $this->getOrderItemStatusHistoryTime($statusCollection, $rmaCollection);

        // $writer = new \Zend_Log_Writer_Stream(BP . '/var/log/status.log');
        // $logger = new \Zend_Log();
        // $logger->addWriter($writer);
        // $logger->info('start-----------------');
        // $logger->info($type);
        // $logger->info(print_r($flow, true));
        // $logger->info('statusTimeArray');
        // $logger->info(print_r($statusCollection->getData(), true));
        // $logger->info(print_r($rmaCollection->getData(), true));
        // $logger->info(print_r($statusTimeArray, true));
        
        foreach ($flow as $key => $value) {
            $orderStatus = $value['Status'];
            if ($type == $this->frontOrderFlow::PROGRESS_TYPE_TICKET) {
                if (isset($statusTimeArray[$orderStatus])) {
                    $flow[$key]['Time'] = $statusTimeArray[$orderStatus];
                }
                $orderStatus = $orderStatus == HotaiStatus::STATUS_PROCESSING ? HotaiStatus::STATUS_PENDING : $orderStatus;
                $flow[$key]['Label'] = $orderStatus == HotaiStatus::STATUS_ARRIVED ? HotaiStatus::STATUS_TICKET_ARRIVED : $value['Label'];
            }
            if (isset($statusTimeArray[$orderStatus]) && !isset($flow[$key]['Time'])) {
                $flow[$key]['Time'] = $statusTimeArray[$orderStatus];
            }
        }

        // add null value at the middle position for styling the process bar
        $totalSteps = count($flow);
        $updatedFlow = [];
        if ($totalSteps === 2) {
            $updatedFlow[] = $flow[0];
            for ($i = 0; $i < 3; $i++) {
                $updatedFlow[] = [
                    'Status' => null,
                ];
            }
            $updatedFlow[] = $flow[1];

        } elseif ($totalSteps === 3) {
            $updatedFlow[] = $flow[0];
            $updatedFlow[] = [
                'Status' => null,
            ];
            $updatedFlow[] = $flow[1];
            $updatedFlow[] = [
                'Status' => null,
            ];
            $updatedFlow[] = $flow[2];

        } elseif ($totalSteps === 4) {
            $updatedFlow[] = $flow[0];
            $updatedFlow[] = $flow[1];
            $updatedFlow[] = [
                'Status' => null,
            ];
            $updatedFlow[] = $flow[2];
            $updatedFlow[] = $flow[3];
        } else {
            $updatedFlow = $flow;
        }

        return $updatedFlow;

    }

    /**
     * getOrderItemStatusHistoryTime 取得 order item 的狀態時間
     *
     * @param  $statusCollection
     * @param  $rmaCollection
     * @return array
     */
    public function getOrderItemStatusHistoryTime($statusCollection, $rmaCollection)
    {
        $statusHistoryArray = [];

        // Formal Flow正流程
        foreach ($statusCollection->getItems() as $data) {
            $status = $data->getItemStatus();
            $rmaLabel = $this->rmaStatusLabel->getCustomerRmaStatusTitle($data->getItemStatus());
            if ($rmaLabel) {
                continue;
            }

            // Get the First Record
            if (isset($statusHistoryArray[$status])) {
                continue;
            }

            $statusHistoryArray[$status] = $data->getCreatedAt();
        }

        // Reverse Flow 逆流程
        foreach ($rmaCollection->getItems() as $data) {
            $rmaLabel = $this->rmaStatusLabel->getCustomerRmaStatusTitle($data->getStatus());
            // Get the First Record
            if (isset($statusHistoryArray[$rmaLabel])) {
                continue;
            }
            $statusHistoryArray[$rmaLabel] = $data->getCreatedAt();
        }

        return $statusHistoryArray;
    }

    /**
     * getItemStatusCollection
     *
     * @param  int $orderId
     * @param  int $itemId
     * @return \Magento\Sales\Model\ResourceModel\Order\Status\History\Collection $statusCollection
     */
    public function getItemStatusCollection($orderId, $itemId)
    {
        $statusCollection = $this->historyCollection->create();
        $statusCollection
            ->addFieldToFilter('parent_id', $orderId)
            ->addFieldToFilter('item_id', $itemId);
        return $statusCollection;
    }

    /**
     * getRmaStatusCollection
     *
     * @param  int $rmaId
     * @return \Branch8\Rma\Model\MarketplaceRmaStatusHistory $statusCollection
     */
    public function getRmaStatusCollection($rmaId)
    {
        $statusCollection = $this->marketplaceRmaStatusHistory->create()->getCollection();
        $statusCollection
            ->addFieldToFilter('parent_id', $rmaId);
        return $statusCollection;
    }

    /**
     * getStatusDotPhase
     *
     * @param  mixed $item
     * @return string
     */
    public function getStatusDotPhase($item)
    {
        if (!$this->isRma($item)) {
            if($item->getFlowStatus() == HotaiStatus::STATUS_COMPLETE) {
                $statusFlow = $this->getProgressBar($item->getOrder(), $item);
                $lastStatus = end($statusFlow);
                return $lastStatus['Status'];
            }

            return $this->getOrderItemStatus($item->getFlowStatus());
        }

        return $this->rmaStatusLabel->getCustomerRmaStatusTitle($item->getFlowStatus());
    }

    /**
     * canManuallyUpdateStatus
     *
     * @param  string $status
     * @return bool
     */
    public function canManuallyUpdateStatus($status, $itemType)
    {
        $statusCheck = in_array($status, [HotaiStatus::STATUS_SHIPPING, HotaiStatus::STATUS_ARRIVED]);
        $itemTypeCheck = !in_array($itemType, [$this->frontOrderFlow::PROGRESS_TYPE_TICKET]);
        return ($statusCheck && $itemTypeCheck);
    }

    /**
     * getProgressFlow
     *
     * @param  string $type
     * @param  mixed $item
     * @return array
     */
    public function getProgressFlow($type, $item)
    {
        if (!$this->isRma($item)) {
            return $this->frontOrderFlow->getFormalFlow($type, $item->getFlowStatus()) ?? [];
        }
        $frontlLabel = $this->rmaStatusLabel->getCustomerRmaStatusTitle($item->getFlowStatus());
        return $this->frontOrderFlow->getRmaFlow($type, $frontlLabel);
    }

    /**
     * getOrderItemStatus
     *
     * @param  string $status
     * @return string
     */
    public function getOrderItemStatus($status)
    {
        switch ($status) {
            case HotaiStatus::STATUS_PENDING:
            case HotaiStatus::STATUS_PENDING_PAYMENT:
                return HotaiStatus::STATUS_PENDING;
            default:
                return $status;
        }
    }

    /**
     * isRma
     *
     * @param  mixed $item
     * @return bool
     */
    public function isRma($item)
    {
        return $item->getRmaStatus() == \Branch8\Rma\Model\Rma\Status::RMA_PROCESSING;
    }

    /**
     * canCreateRma
     *
     * @param  string $status
     * @return bool
     */
    public function canCreateRmaRequest($item)
    {
        $rmaStatus = $item->getRmaStatus();
        if ($rmaStatus != \Branch8\Rma\Model\Rma\Status::RETURN_OR_EXCHANGE_AVALIABLE) {
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
            HotaiStatus::STATUS_PICKED,
            HotaiStatus::STATUS_TALLYING,
        ];

        return in_array($status, $avaliableRmaStatus);

    }

    /**
     * canCancelRmaRequest
     *
     * @param  string $status
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
     * getRmaOptionsArray
     *
     * @param  mixed $item
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
     * @param  mixed $item
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
}
