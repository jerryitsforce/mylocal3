<?php

namespace Branch8\Rma\Model\Actions;

use Branch8\HotaiCore\Model\Order\Status as OrderStatus;
use Branch8\Rma\Helper\Data as RmaHelper;
use Branch8\Rma\Model\Rma\Status as RmaStatus;
use Branch8\Sales\Helper\Order\UpdateOrderStatus;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\Exception\LocalizedException;
use Magento\Sales\Model\OrderRepository;
use Webkul\MpRmaSystem\Api\Data\DetailsInterface;

class RejectFinance
{
    /**
     * Log option value for this action class.
     */
    private const LOG_OPTION = 'RejectFinance';

    private $resourceConnection;
    /**
     * @var OrderRepository
     */
    private OrderRepository $orderRepository;
    /**
     * @var GetSaleItemsByRma
     */
    private BuildUpdateSaleItems $buildUpdateSalesItem;

    protected RmaHelper $rmaHelper;

    /** @var \Branch8\Sales\Helper\Order\UpdateOrderStatus $updateOrderStatus */
    protected $updateOrderStatus;

    protected $orderFactory;

    /**
     * @param ResourceConnection $resourceConnection
     * @param OrderRepository $orderRepository
     * @param BuildUpdateSaleItems $getSaleItemsByRma
     */
    public function __construct(
        ResourceConnection $resourceConnection,
        OrderRepository $orderRepository,
        BuildUpdateSaleItems $getSaleItemsByRma,
        RmaHelper $rmaHelper,
        UpdateOrderStatus $updateOrderStatus,
        \Magento\Sales\Model\OrderFactory $orderFactory
    ) {
        $this->buildUpdateSalesItem = $getSaleItemsByRma;
        $this->orderRepository = $orderRepository;
        $this->resourceConnection = $resourceConnection;
        $this->rmaHelper = $rmaHelper;
        $this->updateOrderStatus = $updateOrderStatus;
        $this->orderFactory = $orderFactory;
    }

    /**
     * @param DetailsInterface $rmaDetail
     * @return bool
     * @throws LocalizedException
     */
    public function execute(DetailsInterface $rmaDetail)
    {
        /*****************************************************************************
         * (1) update marketplace_rma_details.status => Branch8\Rma\Model\Rma\Status::RETURN_FINANCIAL_REVIEW_DECLINE
         * (2) update sales_creditmemo.financial_review_status => Branch8\Sales\Model\CreditMemo\FinancialReviewStatus::FINANCIAL_REVIEW_FAIL
         ******************************************************************************/
        $connection = $this->resourceConnection->getConnection();
        $status = true;
        $memoID = (int) $rmaDetail->getMemoId();
        try {
            /**
             *(1) update marketplace_rma_details.status => Branch8\Rma\Model\Rma::RETURN_FINANCIAL_REVIEW_DECLINE
             */
            $connection->beginTransaction();
            $marketPlaceRmaDetail = $connection->getTableName('marketplace_rma_details');
            $bind = ['status' => RmaStatus::RETURN_FINANCIAL_REVIEW_DECLINE];
            $where = ['id = ?' => $rmaDetail->getId()];
            $connection->update($marketPlaceRmaDetail, $bind, $where);
            /**
             * (2) update sales_creditmemo.financial_review_status => Branch8\Sales\Model\CreditMemo::FINANCIAL_REVIEW_FAIL
             */
            if ($memoID) {
                $bind = ['financial_review_status' => \Branch8\Sales\Model\CreditMemo\FinancialReviewStatus::FINANCIAL_REVIEW_FAIL];
                $where = ['entity_id = ?' => $memoID];
                $salesCreditNemo = $connection->getTableName('sales_creditmemo');
                $connection->update($salesCreditNemo, $bind, $where);
            }
            $connection->commit();

            if ($rmaDetail->getResolutionType() == $this->rmaHelper::RESOLUTION_CANCEL) {
                $this->updateCancellationOrderStatus($rmaDetail);
            } else {
                $this->updateRejectOrderStatus($rmaDetail);
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

    protected function updateCancellationOrderStatus($rmaDetail)
    {
        $rmaId = $rmaDetail->getId();
        $rmaActions = $this->rmaHelper->getRmaActions();
        $items = $rmaActions->getRmaItemCollection($rmaId);

        $order = $this->orderFactory->create()->load($rmaDetail->getOrderId());
        $order->setState(OrderStatus::STATUS_PROCESSING);
        $order->setStatus(OrderStatus::STATUS_PROCESSING);
        $order->addCommentToStatusHistory(
            __(
                'Financial Approve Reject. Update order status to processing. Rma id: %1.', 
                $rmaId
            )
        );
        
        $order->save();

        foreach ($items as $singleItem) {
            $this->updateOrderStatus->updateItemStatusById(
                $singleItem->getItemId(),
                OrderStatus::STATUS_PROCESSING,
                $singleItem->getOrderId()
            );

            $singleItem->setFlowStatus(OrderStatus::STATUS_PROCESSING);
            $singleItem->setRmaStatus(\Branch8\Rma\Model\Rma\Status::RETURN_OR_EXCHANGE_NOT_AVALIABLE);
            $singleItem->save();
        }

    }

    protected function updateRejectOrderStatus($rmaDetail) {
        $rmaId = $rmaDetail->getId();
        $rmaActions = $this->rmaHelper->getRmaActions();
        $items = $rmaActions->getRmaItemCollection($rmaId);

        $order = $this->orderFactory->create()->load($rmaDetail->getOrderId());
        $order->addCommentToStatusHistory(
            __(
                'Financial Approve Reject. Update order status to financial review reject. Rma id: %1.', 
                $rmaId
            )
        );
        
        $order->save();

        foreach ($items as $singleItem) {
            $this->updateOrderStatus->updateItemStatusById(
                $singleItem->getItemId(),
                OrderStatus::STATUS_FINANCIAL_REVIEW_REJECT,
                $singleItem->getOrderId()
            );

            $singleItem->setFlowStatus(OrderStatus::STATUS_FINANCIAL_REVIEW_REJECT);
            $singleItem->save();
        }
    }
}
