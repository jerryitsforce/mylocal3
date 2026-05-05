<?php

namespace Branch8\MarketPlaceOrderExport\Model\Column;

use Branch8\MarketPlaceOrderExport\Model\ColumnInterface;
use Magento\Framework\App\ResourceConnection;

class SalesRepresentatives implements ColumnInterface
{
    private ResourceConnection $resourceConnection;

    /**
     * @param ResourceConnection $resourceConnection
     */
    public function __construct(ResourceConnection $resourceConnection)
    {
        $this->resourceConnection = $resourceConnection;
    }

    public function getHeader()
    {
        return __('Sales Representatives');
    }

    /**
     * @param array $row
     * @return int|mixed|string
     */
    public function processColumnData(array $row = [])
    {

        if (!empty($row['seller_id'])) {
            return $this->getUserRole($row['seller_id']);
        }
        return '';
    }

    private function getUserRole($sellerId)
    {
        $select = $this->resourceConnection->getConnection()->select();
        $select->from('marketplace_userdata')
            ->join('authorization_role', 'marketplace_userdata.salesperson=authorization_role.role_id')
            ->where('marketplace_userdata.seller_id = ?', $sellerId)
            ->reset(\Magento\Framework\DB\Select::COLUMNS)
            ->columns(['role_name' => 'authorization_role.role_name'])->limit(1);
        $rows = $this->resourceConnection->getConnection()->fetchRow($select);
        return $rows ? $rows['role_name'] : '';
    }
}
