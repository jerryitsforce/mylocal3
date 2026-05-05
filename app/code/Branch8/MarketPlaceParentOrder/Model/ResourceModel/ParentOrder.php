<?php
declare(strict_types=1);

namespace Branch8\MarketPlaceParentOrder\Model\ResourceModel;
class ParentOrder extends \Magento\Framework\Model\ResourceModel\Db\AbstractDb
{
    /**
     * @return void
     */
    protected function _construct()
    {
        $this->_init('marketplace_mpsplitorder', 'index_id');
    }

    /**
     * @param int $parentId
     * @param array $subOrderIds
     * @return bool
     */
    public function saveSubOrderRelation(
        int   $parentId,
        array $subOrderIds = []
    )
    {
        if (empty($subOrderIds)) {
            return false;
        }
        $connection = $this->getConnection();
        $table = $connection->getTableName('sales_parent_order_children');
        $insert = [];
        foreach ($subOrderIds as $orderId) {
            $insert[$parentId . '-' . $orderId] = [
                'parent_id' => $parentId,
                'children_id' => $orderId
            ];
        }
        if ($insert) {
            $connection->insertOnDuplicate($table, $insert, ['parent_id', 'children_id']);
        }
        return true;
    }

    /**
     * @param int $parentId
     * @return array
     */
    public function getSubOrders(int $parentId)
    {
        $connection = $this->getConnection();
        $table = $connection->getTableName('sales_parent_order_children');
        $select = $connection->select()->from(
            $table, ['children_id'])
            ->where('parent_id = ?', $parentId);
        $subOrders = $connection->fetchAll($select);
        return $subOrders ? $subOrders : [];
    }

    /**
     * @param int $childrenId
     * @return array
     */
    public function getParentOrder(int $childrenId)
    {
        $connection = $this->getConnection();
        $table = $connection->getTableName('sales_parent_order_children');
        $select = $connection->select()->from(
            $table, ['parent_id'])
            ->where('children_id = ?', $childrenId);
        $parentOrder = $connection->fetchOne($select);
        return $parentOrder ? $parentOrder : [];
    }

}
