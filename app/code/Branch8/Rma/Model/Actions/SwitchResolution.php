<?php
declare(strict_types=1);

namespace Branch8\Rma\Model\Actions;

use Branch8\Rma\Model\Rma\Resolution;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\Exception\NoSuchEntityException;
use Psr\Log\LoggerInterface;
use Webkul\MpRmaSystem\Api\Data\DetailsInterface;
use Branch8\Rma\Model\Rma\Status;
use Branch8\Rma\Helper\Data;
use Branch8\HotaiCore\Model\Order\Status as OrderStatus;

/**
 *
 */
class SwitchResolution
{
    /**
     * Log option value for this action class.
     */
    private const LOG_OPTION = 'SwitchResolution';

    private ResourceConnection $resourceConnection;
    private BuildUpdateSaleItems $buildUpdateSalesItems;
    private \Magento\Sales\Model\OrderRepository $orderRepository;
    private LoggerInterface $logger;
    private \Branch8\Rma\Helper\RmaActions $rmaActions;
    private \Branch8\Sales\Helper\Order\UpdateOrderStatus $updateOrderStatus;

    /**
     * @param ResourceConnection $resourceConnection
     * @param BuildUpdateSaleItems $buildUpdateSaleItems
     * @param \Magento\Sales\Model\OrderRepository $orderRepository
     * @param \Branch8\Rma\Helper\RmaActions $rmaActions
     * @param LoggerInterface $logger
     * @param \Branch8\Sales\Helper\Order\UpdateOrderStatus $updateOrderStatus
     */
    public function __construct(
        ResourceConnection                            $resourceConnection,
        BuildUpdateSaleItems                          $buildUpdateSaleItems,
        \Magento\Sales\Model\OrderRepository          $orderRepository,
        \Branch8\Rma\Helper\RmaActions                $rmaActions,
        LoggerInterface                               $logger,
        \Branch8\Sales\Helper\Order\UpdateOrderStatus $updateOrderStatus
    )
    {
        $this->orderRepository = $orderRepository;
        $this->buildUpdateSalesItems = $buildUpdateSaleItems;
        $this->resourceConnection = $resourceConnection;
        $this->logger = $logger;
        $this->rmaActions = $rmaActions;
        $this->updateOrderStatus = $updateOrderStatus;
    }

    /**
     * @param DetailsInterface $rmaDetail
     * @param $resolutionType
     * @return bool
     * @throws \Exception
     */
    public function execute(DetailsInterface $rmaDetail, $resolutionType)
    {
        switch ($resolutionType) {
            case Resolution::RESOLUTION_TYPE_EXCHANGE:
                return $this->exchange($rmaDetail);
                break;
            case Resolution::RESOLUTION_TYPE_RETURN_REFUND:
                return $this->return($rmaDetail);
                break;
        }
        return false;
    }

    /**
     * @param DetailsInterface $rmaDetail
     * @return true
     * @throws NoSuchEntityException
     * @throws \Magento\Framework\Exception\InputException
     */
    private function return(DetailsInterface $rmaDetail)
    {

        /*****************************************
         * 換貨變退貨 exchange switch to return&refund
         * (1) update marketplace_rma_details.status
         * > $rmaStatus :: RETURN_APPLY_PROCESSING
         * (2) update marketplace_rma_details.resolution_type
         * > $rmaHelper :: RESOLUTION_REFUND
         * (3) update sales_order_item.flow_status = $orderStatus::STATUS_APPLYING_RETURN,
         * and log record history to  sales_order_status_history, marketplace_rma_status_history
         *****************************************/
        $salesItemData = $this->buildUpdateSalesItems->execute(
            $rmaDetail,
            OrderStatus::STATUS_APPLYING_RETURN
        );
        $orderId = $rmaDetail->getOrderId();
        $connection = $this->resourceConnection->getConnection();
        $order = $this->orderRepository->get($orderId);
        $orderItemIds = array_keys($salesItemData);
        try {
            $connection->beginTransaction();
            // (1) update marketplace_rma_details.status
            // (2) update marketplace_rma_details.resolution_type
            $rmaDetailTable = $connection->getTableName('marketplace_rma_details');
            $where = ['id = ? ' => $rmaDetail->getId()];
            $bind = ['status' => Status::RETURN_APPLY_PROCESSING, 'resolution_type' => Data::RESOLUTION_REFUND];
            $connection->update($rmaDetailTable, $bind, $where);
            // (3) update sales_order_item.flow_status =  $orderStatus::RESOLUTION_REFUND,
            $saleItemsTable = $connection->getTableName('sales_order_item');
            $connection->insertOnDuplicate($saleItemsTable,
                $salesItemData, ['flow_status']
            );
            //(4) log record history to sales_order_status_history, marketplace_rma_status_history
            $this->rmaActions->saveActionRecord(Status::RETURN_APPLY_PROCESSING, $rmaDetail->getId());
            $comment = __('Change RMA Resolution to %1', OrderStatus::STATUS_APPLYING_RETURN);
            $order->addCommentToStatusHistory($comment);
            $order->save();
            foreach ($order->getItems() as $item) {
                if (in_array($item->getId(), $orderItemIds)) {
                    $this->updateOrderStatus->updateItemStatusById(
                        $item->getId(),
                        OrderStatus::STATUS_APPLYING_RETURN,
                        $order->getEntityId()
                    );
                }
            }

            $connection->commit();
        } catch (\Exception $e) {
            $connection->rollBack();
            \Magento\Framework\App\ObjectManager::getInstance()
                ->get(\Branch8\Rma\Helper\Log::class)
                ->exception($e, self::LOG_OPTION, __METHOD__, ['rma_id' => $rmaDetail->getId()]);
            throw $e;
        }
        return true;
    }

    /**
     * @param DetailsInterface $rmaDetail
     * @return bool
     * @throws \Exception
     */
    private function exchange(DetailsInterface $rmaDetail)
    {

        /*****************************************
         * (1) update marketplace_rma_details.status
         * > $rmaStatus :: REPLACE_APPLY_PROCESSING
         * (2) update marketplace_rma_details.resolution_type
         * > $rmaHelper :: RESOLUTION_REPLACE
         * (3) update sales_order_item.flow_status =  $orderStatus::STATUS_APPLYING_REPLACE,
         * (4) log record history to sales_order_status_history, marketplace_rma_status_history
         *
         *****************************************/

        /**
         * @var $order \Magento\Sales\Model\Order
         */
        $salesItemData = $this->buildUpdateSalesItems->execute(
            $rmaDetail,
            OrderStatus::STATUS_APPLYING_REPLACE
        );
        $orderId = $rmaDetail->getOrderId();
        $connection = $this->resourceConnection->getConnection();
        $order = $this->orderRepository->get($orderId);
        $orderItemIds = array_keys($salesItemData);
        try {
            $connection->beginTransaction();
            // (1) update marketplace_rma_details.status
            // (2) update marketplace_rma_details.resolution_type
            $rmaDetailTable = $connection->getTableName('marketplace_rma_details');
            $where = ['id = ? ' => $rmaDetail->getId()];
            $bind = ['status' => Status::REPLACE_APPLY_PROCESSING, 'resolution_type' => Data::RESOLUTION_REPLACE];
            $connection->update($rmaDetailTable, $bind, $where);
            // (3) update sales_order_item.flow_status =  $orderStatus::STATUS_APPLYING_REPLACE,
            $saleItemsTable = $connection->getTableName('sales_order_item');
            $connection->insertOnDuplicate($saleItemsTable,
                $salesItemData, ['flow_status']
            );
            //(4) log record history to sales_order_status_history, marketplace_rma_status_history
            $this->rmaActions->saveActionRecord(Status::REPLACE_APPLY_PROCESSING, $rmaDetail->getId());
            $comment = __('Change RMA Resolution to %1', OrderStatus::STATUS_APPLYING_REPLACE);
            $order->addCommentToStatusHistory($comment);
            $order->save();
            foreach ($order->getItems() as $item) {
                if (in_array($item->getId(), $orderItemIds)) {
                    $this->updateOrderStatus->updateItemStatusById(
                        $item->getId(),
                        OrderStatus::STATUS_APPLYING_REPLACE,
                        $order->getEntityId()
                    );
                }
            }
            $connection->commit();
        } catch (\Exception $e) {
            $connection->rollBack();
            \Magento\Framework\App\ObjectManager::getInstance()
                ->get(\Branch8\Rma\Helper\Log::class)
                ->exception($e, self::LOG_OPTION, __METHOD__, ['rma_id' => $rmaDetail->getId()]);
            throw $e;
        }
        return true;
    }
}
