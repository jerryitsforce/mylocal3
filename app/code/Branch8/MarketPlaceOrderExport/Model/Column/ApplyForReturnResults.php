<?php

namespace Branch8\MarketPlaceOrderExport\Model\Column;

use Branch8\MarketPlaceOrderExport\Model\ColumnInterface;
use Branch8\MarketPlaceOrderExport\Model\Services\GetAllRmaStatusItem;
use Branch8\MarketPlaceOrderExport\Model\Services\GetOrderRmaDetail;
use Branch8\MarketPlaceOrderExport\Model\Services\GetRmaOrderItemInformation;
use Branch8\Rma\Model\Rma\Source\RmaStatus;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\Stdlib\DateTime\Timezone;
use Magento\Sales\Model\OrderRepository;
use Magento\Store\Model\StoreManagerInterface;
use Branch8\HotaiCore\Model\Order\Status;

class ApplyForReturnResults implements ColumnInterface
{
    private $cached = [];

    private GetOrderRmaDetail $getOrderRmaDetail;
    private Timezone $timeZone;
    private StoreManagerInterface $storeManager;
    /**
     * @var GetRmaOrderItemInformation
     */
    private GetRmaOrderItemInformation $getRmaOrderItemInformation;
    private GetAllRmaStatusItem $getAllRmaStatusItem;
    private \Magento\Sales\Model\Order\ItemFactory $orderItemFactory;
    private OrderRepository $orderRepository;
    private ResourceConnection $resourceConnection;

    /**
     * @param GetOrderRmaDetail $getOrderRmaDetail
     * @param StoreManagerInterface $storeManager
     * @param Timezone $timezone
     * @param GetRmaOrderItemInformation $getRmaOrderItemInformation
     * @param GetAllRmaStatusItem $getAllRmaStatusItem
     */
    public function __construct(
        GetOrderRmaDetail                      $getOrderRmaDetail,
        StoreManagerInterface                  $storeManager,
        Timezone                               $timezone,
        GetRmaOrderItemInformation             $getRmaOrderItemInformation,
        GetAllRmaStatusItem                    $getAllRmaStatusItem,
        OrderRepository                        $orderRepository,
        ResourceConnection                     $resourceConnection,
        \Magento\Sales\Model\Order\ItemFactory $itemFactory
    )
    {
        $this->storeManager = $storeManager;
        $this->timeZone = $timezone;
        $this->getOrderRmaDetail = $getOrderRmaDetail;
        $this->getRmaOrderItemInformation = $getRmaOrderItemInformation;
        $this->getAllRmaStatusItem = $getAllRmaStatusItem;
        $this->orderItemFactory = $itemFactory;
        $this->orderRepository = $orderRepository;
        $this->resourceConnection = $resourceConnection;
    }

    public function getHeader()
    {
        return __('Apply For Return Results');
    }

    /**
     * @param array $row
     * @return int|\Magento\Framework\Phrase|string
     */
    public function processColumnData(array $row = [])
    {
        $record = $this->getRmaOrderItemInformation->get($row['order_item_id'], Status::STATUS_APPLYING_RETURN);
        if (!$record) {
            return '';
        }
        /*
           (1)同意申請且派車回收 > 派車回收 applying_return_shipping
           (2) 不同意申請 > 不同意申請 applying_return_reject
           (3)未出貨取消 > 未出貨取消 processing_return_cancel_before_shipping
           (4) 退款不退貨 >  退款不退貨 processing_not_return_but_refund
        */

        $status = $this->getDisAgreeStatus($row['order_item_id']);
        if ($status) {
            return __('Disagree')->render();
        }
        return __('Agree')->render();
    }

    private function getDisAgreeStatus($itemId)
    {
        //$select = $this->resourceConnection->getConnection()->select();
        $connection = $this->resourceConnection->getConnection();
        $statuses = \Branch8\HotaiCore\Model\Order\Status::REVERSE_FLOW_DECLINE;
        $select = $connection->select()
            ->from(
                'marketplace_rma_items',
                ['rma_status' => 'marketplace_rma_status_history.status']
            )->join('marketplace_rma_details',
                'marketplace_rma_items.rma_id = marketplace_rma_details.id',
                [
                    'rma_reason' => 'rma_reason',
                    'rma_delivery_time' => 'rma_delivery_time',
                ]
            );
        $select->join(
            'marketplace_rma_status_history',
            'marketplace_rma_status_history.parent_id=marketplace_rma_details.id',
            ['created_at' => 'created_at']);
        $select->where('marketplace_rma_status_history.status IN (?)', $statuses);
        $select->where('marketplace_rma_items.item_id IN (?)', [$itemId]);
        $select->order('marketplace_rma_status_history.created_at DESC');
        $select->limit(1);
        return $connection->fetchOne($select);
    }
}
