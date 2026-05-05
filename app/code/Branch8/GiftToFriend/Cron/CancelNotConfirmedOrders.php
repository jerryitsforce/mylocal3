<?php

namespace Branch8\GiftToFriend\Cron;

use Branch8\HotaiCore\Helper\Common as HotaiCoreCommonHelper;
use Magento\Sales\Model\ResourceModel\Order\Collection as OrderCollection;
use Magento\Sales\Model\ResourceModel\Order\CollectionFactory as OrderCollectionFactory;
use Magento\Sales\Api\OrderRepositoryInterface as OrderRepository;
use Branch8\HotaiCore\Model\Order\State as OrderState;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Event\ManagerInterface as EventManager;
use Branch8\MarketPlaceParentOrder\Model\ResourceModel\ParentOrder as ParentOrderResourceModel;
use Magento\Framework\App\ResourceConnection;

class CancelNotConfirmedOrders
{
    const LOG_FOLDER_NAME = 'GiftToFriend/Cron/CancelNotConfirmedOrders';

    const DEFAULT_NOT_CONFIRMED_EXPIRE_DAYS = 14;

    const EXCLUDE_ORDER_STATES = [
        OrderState::STATE_CANCEL_PENDING,
        OrderState::STATE_CANCELED,
        OrderState::STATE_PENDING_COMPLETE,
        OrderState::STATE_COMPLETE,
        OrderState::STATE_CLOSED
    ];

    /** @var HotaiCoreCommonHelper */
    protected $hotaiCoreCommonHelper;

    /** @var OrderCollectionFactory */
    protected $orderCollectionFactory;

    /** @var OrderRepository */
    protected $orderRepository;

    /** @var ScopeConfigInterface */
    protected $scopeConfig;

    /** @var EventManager */
    protected $eventManager;

    /** @var ParentOrderResourceModel */
    protected $parentOrderResourceModel;

    /** @var ResourceConnection */
    protected $resourceConnection;

    protected $targetOrderIds = null;

    public function __construct(
        HotaiCoreCommonHelper $hotaiCoreCommonHelper,
        OrderCollectionFactory $orderCollectionFactory,
        OrderRepository $orderRepository,
        ScopeConfigInterface $scopeConfig,
        EventManager $eventManager,
        ParentOrderResourceModel $parentOrderResourceModel,
        ResourceConnection $resourceConnection
    ) {
        $this->hotaiCoreCommonHelper    = $hotaiCoreCommonHelper;
        $this->orderCollectionFactory   = $orderCollectionFactory;
        $this->orderRepository          = $orderRepository;
        $this->scopeConfig              = $scopeConfig;
        $this->eventManager             = $eventManager;
        $this->parentOrderResourceModel = $parentOrderResourceModel;
        $this->resourceConnection       = $resourceConnection;
    }

    public function execute()
    {
        $this->writeLog("CancelNotConfirmedOrders cron start.",
            self::LOG_FOLDER_NAME);

        $orderCollection = $this->getTargetOrderCollection();

        if (!empty($this->targetOrderIds)) {
            $this->checkOrderCollection($orderCollection);
        }

        $orderIdsArray = $orderCollection->getAllIds();

        $this->writeLog(
            "Target order IDs: " . implode(', ', $orderIdsArray),
            self::LOG_FOLDER_NAME,
        );

        foreach ($orderCollection as $order) {
            try {
                $this->writeLog(
                    "Ready to cancel order ID {$order->getId()}",
                    self::LOG_FOLDER_NAME,
                );

                $fullDataOrder = $this->orderRepository->get($order->getId());

                $this->eventManager->dispatch(
                    'sales_order_cancel_paied_order',
                    ['order' => $fullDataOrder]
                );

                $this->updateParentOrderDetail($order->getId());

                $order->addStatusHistoryComment("CancelNotConfirmedOrders handle done.")->save();

                $this->writeLog(
                    "Handled order ID {$order->getId()} cancellation success.",
                    self::LOG_FOLDER_NAME,
                );
            } catch (\Throwable $th) {
                $this->writeLog(
                    "Exception when canceling order ID {$order->getId()}: " . $th->getMessage(),
                    self::LOG_FOLDER_NAME,
                );
            }
        }

        $this->writeLog(
            "CancelNotConfirmedOrders cron end.",
            self::LOG_FOLDER_NAME,
        );
    }

    protected function getTargetOrderCollection(): OrderCollection
    {
        $orderCollection = $this->orderCollectionFactory->create();

        if (!empty($this->targetOrderIds)) {
            $targetOrderIdArray = explode(',', $this->targetOrderIds);

            $orderCollection->addFieldToFilter(
                'entity_id',
                ['in' => $targetOrderIdArray]
            );

            return $orderCollection;
        }

        $orderCollection
            ->addFieldToSelect([
                'entity_id',
                'state',
                'ecpay_invoice_tag',
                'is_gift_order',
                'is_gift_confirmed',
                'created_at'
            ])->addFieldToFilter(
                'ecpay_invoice_tag',
                1
            )->addFieldToFilter(
                'is_gift_order',
                1
            )->addFieldToFilter(
                'is_gift_confirmed',
                ['neq' => 1]
            )->addFieldToFilter(
                'state',
                ['nin' => self::EXCLUDE_ORDER_STATES]
            )->addFieldToFilter(
                'gift_expired_at',
                ['lt' => date('Y-m-d H:i:s')]
            );

        return $orderCollection;
    }

    protected function checkOrderCollection(OrderCollection $orderCollection): void
    {
        $error = [];

        foreach ($orderCollection as $order) {
            $orderError = [];

            if (in_array($order->getState(), self::EXCLUDE_ORDER_STATES)) {
                $orderError[] = "Order ID {$order->getId()} state is {$order->getState()} not correct.";
            }

            if ($order->getEcpayInvoiceTag() != 1) {
                $orderError[] = "Order ID {$order->getId()} does not have ecpay invoice tag.";
            }

            if (!$order->getIsGiftOrder()) {
                $orderError[] = "Order ID {$order->getId()} is not a gift order.";
            }

            if ($order->getIsGiftConfirmed()) {
                $orderError[] = "Order ID {$order->getId()} is already confirmed.";
            }

            if (empty($order->getData('gift_expired_at')) || strtotime($order->getData('gift_expired_at')) > time()) {
                $orderError[] = "Order ID {$order->getId()} gift_expired_at exception.";
            }

            if (count($orderError) > 0) {
                $error[] = $orderError;
            }
        }

        if (count($error) > 0) {
            throw new \Exception(
                "Order collection check failed: " . json_encode($error)
            );
        }
    }

    protected function updateParentOrderDetail(int $subOrderId): void
    {
        $parentOrderId = (int) $this->parentOrderResourceModel->getParentOrder($subOrderId);

        if (empty($parentOrderId)) {
            throw new \Exception("Can't find parent order ID by sub order ID {$subOrderId}");
        }

        $connection = $this->resourceConnection->getConnection();
        $tableName  = $this->resourceConnection->getTableName('sales_parent_order_detail');

        $data = [
            'is_auto_cancelled' => 1
        ];

        $connection->update(
            $tableName,
            $data,
            ['parent_id = ?' => $parentOrderId]
        );
    }

    public function setTargetOrderIds(string $targetOrderIds): void
    {
        $this->targetOrderIds = $targetOrderIds;
    }

    protected function writeLog($message, $path, $fileName = ""){
        if(\Branch8\HotaiCore\Helper\DebugLog::isEnable('Branch8_GiftToFriend', 'cancel_gift_order_not_confirmed')){
            $this->hotaiCoreCommonHelper->writeLog(
                $message,
                $path,
                $fileName
            );
        }
    }
}
