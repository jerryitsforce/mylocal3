<?php

namespace Branch8\Rma\Model\Actions;

use Branch8\HotaiCore\Model\Order\Status as OrderStatus;
use Branch8\HotaiCore\Model\Order\State as OrderState;
use Branch8\MarketPlaceParentOrder\Api\ParentOrderRepositoryInterface;
use Branch8\MarketPlaceParentOrder\Model\ParentOrder\Email\ParentOrderCancel\Sender;
use Branch8\MarketPlaceParentOrder\Model\ResourceModel\ParentOrder;
use Branch8\Rma\Helper\Status as RmaStatusHelper;
use Branch8\Rma\Model\Rma\Status as RmaStatus;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\Exception\LocalizedException;
use Magento\Sales\Model\OrderRepository;
use Webkul\MpRmaSystem\Api\Data\DetailsInterface;
use \Branch8\Sales\Helper\Order\UpdateOrderStatus;
use Branch8\Sales\Helper\Order\ProcessReturnQtyOnCreditMemo;
use \Webkul\MpRmaSystem\Model\ResourceModel\Items\CollectionFactory;
use Magento\Framework\Event\ManagerInterface as EventManager;
use \Magento\Sales\Model\OrderFactory;
use Branch8\Refund\Helper\CreateCreditMemo;
use Branch8\Refund\Helper\CancelTicket;
use Exception;

class ApproveFinance
{
    /**
     * Log option value for this action class.
     */
    private const LOG_OPTION = 'ApproveFinance';

    private $resourceConnection;

    /**
     * @var OrderRepository
     */
    private OrderRepository $orderRepository;

    /**
     * @var GetSaleItemsByRma
     */
    private BuildUpdateSaleItems $buildUpdateSalesItem;

    private \Branch8\Sales\Helper\Order\UpdateOrderStatus $updateOrderStatus;

    private \Branch8\Sales\Helper\Order\ProcessReturnQtyOnCreditMemo $processReturnQtyOnCreditMemo;

    private CollectionFactory $collectionFactory;

    /** @var \Magento\Sales\Api\OrderItemRepositoryInterface $itemCollectionFactory */
    protected $rmaStatusHelper;

    /** @var EventManager */
    private $eventManager;

    /** @var \Magento\Sales\Model\OrderFactory $orderFactory */
    protected $orderFactory;

    /** @var \Branch8\Refund\Helper\CreateCreditMemo $createCreditMemo */
    protected $createCreditMemo;

    protected CancelTicket $cancelTicket;

    private $sender;

    /**
     * @var ParentOrderRepositoryInterface
     */
    protected $parentOrderRepository;

    private ParentOrder $parentOrder;

    protected $creditMemoRepository;


    /**
     * @param ResourceConnection $resourceConnection
     * @param OrderRepository $orderRepository
     * @param BuildUpdateSaleItems $getSaleItemsByRma
     * @param UpdateOrderStatus $updateOrderStatus
     * @param CollectionFactory $collectionFactory
     */
    public function __construct(
        ResourceConnection $resourceConnection,
        OrderRepository $orderRepository,
        BuildUpdateSaleItems $getSaleItemsByRma,
        UpdateOrderStatus $updateOrderStatus,
        ProcessReturnQtyOnCreditMemo $processReturnQtyOnCreditMemo,
        CollectionFactory $collectionFactory,
        RmaStatusHelper $rmaStatusHelper,
        EventManager $eventManager,
        OrderFactory $orderFactory,
        CreateCreditMemo $createCreditMemo,
        CancelTicket $cancelTicket,
        Sender $sender,
        ParentOrderRepositoryInterface $parentOrderRepository,
        ParentOrder $parentOrder,
        \Magento\Sales\Model\Order\CreditmemoRepository $creditMemoRepository
    ) {
        $this->updateOrderStatus = $updateOrderStatus;
        $this->processReturnQtyOnCreditMemo = $processReturnQtyOnCreditMemo;
        $this->buildUpdateSalesItem = $getSaleItemsByRma;
        $this->orderRepository = $orderRepository;
        $this->resourceConnection = $resourceConnection;
        $this->collectionFactory = $collectionFactory;
        $this->rmaStatusHelper = $rmaStatusHelper;
        $this->eventManager = $eventManager;
        $this->orderFactory = $orderFactory;
        $this->createCreditMemo = $createCreditMemo;
        $this->cancelTicket = $cancelTicket;
        $this->sender = $sender;
        $this->parentOrderRepository = $parentOrderRepository;
        $this->parentOrder = $parentOrder;
        $this->creditMemoRepository = $creditMemoRepository;
    }

    /**
     * @param DetailsInterface $rmaDetail
     * @return bool
     * @throws LocalizedException
     */
    public function execute(DetailsInterface $rmaDetail)
    {
        /*****************************************************************************
         * (1) update marketplace_rma_details.status => Branch8\Rma\Model\Rma\Status::RETURN_FINANCIAL_REVIEW_AGREE
         * (2) update sales_creditmemo.financial_review_status => Branch8\Sales\Model\CreditMemo\FinancialReviewStatus::FINANCIAL_REVIEW_SUCCESS
         ******************************************************************************/

        if($rmaDetail->getStatus() == RmaStatus::RETURN_FINANCIAL_REVIEW_AGREE) {
            throw new Exception('Financial already agree.');
        }

        $memoID =  $rmaDetail->getMemoId();

        if ($rmaDetail->getResolutionType() == \Branch8\Rma\Helper\Data::RESOLUTION_CANCEL) {
            $order = $this->orderRepository->get($rmaDetail->getOrderId());
            $this->cancelTicket->execute($order);

            $memoID = $this->createCreditMemo->afterCancellationApprove($order, $rmaDetail);
        }

        $connection = $this->resourceConnection->getConnection();
        $status = true;
        /**
         * @var $item \Webkul\MpRmaSystem\Model\Items
         */
        $items = $this->collectionFactory->create()->addFieldToFilter('rma_id', $rmaDetail->getId());
        $orderId = $rmaDetail->getOrderId();
        foreach ($items as $item) {
            $orderItems[] = $item->getItemId();
        }
        $orderItems = array_unique($orderItems);
        try {
            /**
             *(1) update marketplace_rma_details.status => Branch8\Rma\Model\Rma::RETURN_FINANCIAL_REVIEW_AGREE
             */
            $connection->beginTransaction();
            $marketPlaceRmaDetail = $connection->getTableName('marketplace_rma_details');
            $bind = ['status' => RmaStatus::RETURN_FINANCIAL_REVIEW_AGREE, 'memo_id' => $memoID];
            $where = ['id = ?' => $rmaDetail->getId()];
            $connection->update($marketPlaceRmaDetail, $bind, $where);
            /**
             * (2) update sales_creditmemo.financial_review_status => Branch8\Sales\Model\CreditMemo::FINANCIAL_REVIEW_SUCCESS
             */
            if ($memoID) {
                $bind = [
                    'financial_review_status' => \Branch8\Sales\Model\CreditMemo\FinancialReviewStatus::FINANCIAL_REVIEW_SUCCESS,
                    'finanical_approve_at' => date('Y-m-d H:i:s')
                ];
                $where = ['entity_id = ?' => $memoID];
                $salesCreditNemo = $connection->getTableName('sales_creditmemo');
                $connection->update($salesCreditNemo, $bind, $where);
            }
            if ($orderItems && $orderId) {
                foreach ($orderItems as $orderItem) {
                    $this->updateOrderStatus->updateItemStatusById(
                        $orderItem,
                        OrderStatus::STATUS_PROCESSING_REFUND,
                        $orderId,
                        false,
                        null,
                        true
                    );
                }
            }
            $connection->commit();

            $this->rmaStatusHelper->createStatusRecord(
                OrderStatus::STATUS_PROCESSING_REFUND,
                $rmaDetail
            );

            if ($rmaDetail->getResolutionType() == \Branch8\Rma\Helper\Data::RESOLUTION_CANCEL) {
                $this->updateCancellationOrderStatus($rmaDetail);
            } else {
                if ($rmaDetail->getReturnToStock() == true) {
                    $this->processReturnQtyOnCreditMemo->processReturnQtyOnCreditMemo(
                        $orderId,
                        $memoID
                    );
                    $creditMemo = $this->creditMemoRepository->get($memoID);
                    $this->eventManager->dispatch(
                        'sales_order_creditmemo_rerund_financial_review',
                        ['creditmemo' => $creditMemo, 'rmaDetail' => $rmaDetail]
                    );
                }
            }

            if ($memoID) {
                $this->eventManager->dispatch(
                    'creditmemo_financial_review_done',
                    ['memoId' => $memoID]
                );
            }
        } catch (\Exception $exception) {
            $connection->rollBack();
            \Magento\Framework\App\ObjectManager::getInstance()
                ->get(\Branch8\Rma\Helper\Log::class)
                ->exception($exception, self::LOG_OPTION, __METHOD__, ['rma_id' => $rmaDetail->getId()]);
            $status = false;
        }
        return $status;
    }

    public function updateCancellationOrderStatus($rmaDetail)
    {
        try {

            $connection = $this->resourceConnection->getConnection();
            $connection->beginTransaction();

            $salesOrder = $connection->getTableName('sales_order');
            $bind = [
                'state' => OrderState::STATE_CANCELED,
                'status' => OrderStatus::STATUS_CANCELED
            ];
            $where = ['entity_id = ?' => $rmaDetail->getOrderId()];
            $connection->update($salesOrder, $bind, $where);
            $connection->commit();
            $parentOrderId = $this->parentOrder->getParentOrder($rmaDetail->getOrderId());
            if(!empty($parentOrderId)) {
                $parentOrder = $this->parentOrderRepository->get($parentOrderId);
                $this->sendCancelEmail($parentOrder);
            }
        } catch (Exception $e) {
            $connection->rollBack();
            \Magento\Framework\App\ObjectManager::getInstance()
                ->get(\Branch8\Rma\Helper\Log::class)
                ->exception($e, self::LOG_OPTION, __METHOD__, ['rma_id' => $rmaDetail->getId()]);
            throw $e;
        }
    }

    public function sendCancelEmail($parentOrder)
    {
        try {
            $this->sender->send($parentOrder);
        } catch (\Exception $exception) {
            \Magento\Framework\App\ObjectManager::getInstance()
                ->get(\Branch8\Rma\Helper\Log::class)
                ->exception($exception, self::LOG_OPTION, __METHOD__);
        }
    }
}
