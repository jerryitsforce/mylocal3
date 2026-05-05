<?php

namespace Branch8\Marketplace\Model\Actions;

use Magento\Framework\App\ResourceConnection;

class GetSellerInformation
{
    private $cache = [];

    private ResourceConnection $resource;

    /**
     * @param ResourceConnection $resource
     */
    public function __construct(
        ResourceConnection $resource,
    )
    {
        $this->resource = $resource;
    }

    /**
     * @param int $sellerId
     * @return array|mixed
     */
    public function get(int $sellerId)
    {
        if (isset($this->cache[$sellerId])) {
            return $this->cache[$sellerId];
        }
        $connection = $this->resource->getConnection();
        $select = $connection->select();
        $columns = [
            'seller_code' => 'seller_code',
            'seller_id' => 'seller_id',
        ];
        $select->from(
            'marketplace_userdata',
            $columns
        )->where('seller_id = ?', $sellerId);
        $row = $connection->fetchRow($select);
        $this->cache[$sellerId] = $row ?: [];
        return $this->cache[$sellerId];
    }
}
