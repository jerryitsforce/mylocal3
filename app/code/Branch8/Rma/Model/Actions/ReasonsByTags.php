<?php

namespace Branch8\Rma\Model\Actions;

use Magento\Framework\App\ResourceConnection;

class ReasonsByTags
{
    const SELLER_TAG = 'seller';
    const BUYER_TAG = 'buyer';

    const FRONTEND_TAG='Frontend';

    const BACKEND_TAG='Backend';
    const RMA_DECLINE_REASON = 'RMA Declination Reason';

    private ResourceConnection $resourceConnection;

    /**
     * @param ResourceConnection $resourceConnection
     */
    public function __construct(ResourceConnection $resourceConnection)
    {
        $this->resourceConnection = $resourceConnection;
    }

    /**
     * @param $tag
     * @param $columns
     * @param $onlyIsActive
     * @return array
     */
    public function find($tag, $columns = [], $onlyIsActive = true, $asArrayKey = false)
    {
        $connection = $this->resourceConnection->getConnection();
        $table = $connection->getTableName('marketplace_rma_reasons');
        $select = $connection->select()
            ->from($table, $columns ?: '*')
            ->where(new \Zend_Db_Expr('FIND_IN_SET("' . $tag . '", tags)'));
        if ($onlyIsActive) {
            $select->where('status', 1);
        }
        $rows = $connection->fetchAll($select);
        if (!count($rows)) {
            return [];
        }
        $return = [];
        if ($asArrayKey) {
            foreach ($rows as $item) {
                $return[$item['id']] = $item['reason'];
            }
        } else {
            $return = $rows;
        }
        return $return;
    }
}
