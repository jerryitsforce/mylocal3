<?php

namespace Branch8\Rma\Model\Actions;

use Magento\Framework\App\ResourceConnection;
use Webkul\MpRmaSystem\Api\Data\DetailsInterface;

class BuildUpdateSaleItems
{
    private $resourceConnection;

    /**
     * @param ResourceConnection $resourceConnection
     */
    public function __construct(
        ResourceConnection $resourceConnection
    )
    {
        $this->resourceConnection = $resourceConnection;
    }

    /**
     * @param DetailsInterface $detail
     * @param $flowStatus
     * @return array
     */
    public function execute(DetailsInterface $detail, $flowStatus)
    {
        $data = [];
        $connection = $this->resourceConnection->getConnection();
        $select = $connection->select();
        $select->from(
            'marketplace_rma_items', ['item_id']
        )->where('rma_id = ? ', $detail->getId());
        foreach ($connection->fetchAll($select) as $item) {
            $item = [
                'item_id' => $item['item_id'],
            ];
            if ($flowStatus) {
                $item['flow_status'] = $flowStatus;
            }
            $data[$item['item_id']] = $item;
        };
        return ($data);
    }
}
