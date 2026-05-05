<?php
declare(strict_types=1);

namespace Branch8\MarketPlaceParentOrder\Model\ResourceModel;

use Magento\Framework\App\ObjectManager;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\DB\Adapter\AdapterInterface;
use Magento\Sales\Model\Grid\LastUpdateTimeCache;
use Magento\Sales\Model\ResourceModel\Grid;
use Magento\Sales\Model\ResourceModel\Provider\NotSyncedDataProviderInterface;
use Magento\Sales\Model\ResourceModel\Provider\Query\IdListBuilder;

/**
 * Retrieves ID's of not synced by `updated_at` column entities.
 * The result should contain list of entities ID's from the main table which have `updated_at` column greater
 * than in the grid table.
 */
class UpdatedIdListProvider implements NotSyncedDataProviderInterface
{
    /**
     * @var ResourceConnection
     */
    private $resourceConnection;

    /**
     * @var AdapterInterface
     */
    private $connection;

    /**
     * @var IdListBuilder
     */
    private $idListQueryBuilder;

    /**
     * NotSyncedDataProvider constructor.
     * @param ResourceConnection $resourceConnection
     * @param IdListBuilder|null $idListQueryBuilder
     */
    public function __construct(
        ResourceConnection $resourceConnection,
        ?IdListBuilder     $idListQueryBuilder = null
    )
    {
        $this->resourceConnection = $resourceConnection;
        $this->idListQueryBuilder = $idListQueryBuilder ?? ObjectManager::getInstance()->get(IdListBuilder::class);
    }

    /**
     * @inheritdoc
     */
    public function getIds($mainTableName, $gridTableName)
    {
        $mainTableName = $this->resourceConnection->getTableName($mainTableName);
        $gridTableName = $this->resourceConnection->getTableName($gridTableName);
        $select = $this->getConnection()->select()
            ->from(['main_table' => $mainTableName], ['main_table.entity_id'])
            ->joinLeft(
                ['grid_table' => $this->resourceConnection->getTableName($gridTableName)],
                'main_table.parent_id = grid_table.entity_id',
                []
            );

        $select->where('grid_table.entity_id IS NULL or grid_table.status != main_table.status');
        $select->limit(ParentOrderGrid::BATCH_SIZE);
        return $this->getConnection()->fetchAll($select, [], \Zend_Db::FETCH_COLUMN);
    }

    /**
     * Returns connection.
     *
     * @return AdapterInterface
     */
    private function getConnection()
    {
        if (!$this->connection) {
            $this->connection = $this->resourceConnection->getConnection('sales');
        }

        return $this->connection;
    }
}
