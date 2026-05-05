<?php
namespace Branch8\Sales\Helper\Order;

use \Magento\Framework\App\ResourceConnection;

class Item
{
    /** @var \Magento\Framework\App\ResourceConnection $resourceConnection */
    protected $resourceConnection;

    public function __construct(
        ResourceConnection $resourceConnection

    ) {
        $this->resourceConnection = $resourceConnection;
    }

    /**
     * getChangableItemIds
     *
     * @return array
     */
    public function getChangableItemIds($filter)
    {

        $from   = $filter['from'];
        $to     = $filter['to'];
        $status = $filter['status'];

        $connection = $this->resourceConnection->getConnection();
        $itemId     = [0];
        $items      = $connection->fetchAll("
            SELECT items.item_id
            FROM sales_order_status_history as history
            Left join sales_order_item as order_item on history.item_id = order_item.item_id
            WHERE history.created_at between '{$from}' and '{$to}'
                and history.status = '{$status}'
                and order_item.flow_status = '{$status}'");

        foreach ($items as $item) {
            $itemId[] = $item['item_id'];
        }

        return $itemId;
    }

    /**
     * getRmaChangableItemIds
     *
     * @return array
     */
    public function getRmaChangableItemIds($filter)
    {

        $from   = $filter['from'];
        $to     = $filter['to'];
        $status = $filter['status'];

        $connection = $this->resourceConnection->getConnection();
        $itemId     = [0];
        $items      = $connection->fetchAll("
            SELECT items.item_id
            FROM marketplace_rma_status_history as history
            LEFT JOIN marketplace_rma_items as items ON history.parent_id = items.rma_id
            Left join sales_order_item as order_item on items.item_id = order_item.item_id
            WHERE history.created_at between '{$from}' and '{$to}'
                and history.status = '{$status}'
                and order_item.flow_status = '{$status}'");

        foreach ($items as $item) {
            $itemId[] = $item['item_id'];
        }

        return $itemId;
    }

    /**
     * isRmaProcessing
     *
     * @param  int| string $itemId
     * @return bool
     */
    public function isRmaProcessing($itemId)
    {
        $connection = $this->resourceConnection->getConnection();
        $select     = $connection->select()
            ->from('sales_order_item', ['rma_status'])
            ->where('item_id = ?', $itemId);
        $rmaStatus = $connection->fetchOne($select);

        if ($rmaStatus == \Branch8\Rma\Model\Rma\Status::RMA_PROCESSING) {
            return true;
        }

        return false;

    }

}
