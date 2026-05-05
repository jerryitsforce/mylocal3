<?php
declare(strict_types=1);

namespace Branch8\MarketPlaceParentOrder\Model\ResourceModel;

use Magento\Framework\App\ObjectManager;
use Magento\Framework\Model\ResourceModel\Db\Context;
use Magento\Sales\Model\Grid\LastUpdateTimeCache;
use Magento\Sales\Model\ResourceModel\Provider\NotSyncedDataProviderInterface;

class ParentOrderGrid extends \Magento\Sales\Model\ResourceModel\Grid
{
    /**
     * @var string
     */
    protected $gridTableName;

    /**
     * @var string
     */
    protected $mainTableName;

    /**
     * @var string
     */
    protected $orderIdField;

    /**
     * @var array
     */
    protected $joins;

    /**
     * @var array
     */
    protected $columns;

    /**
     * @var NotSyncedDataProviderInterface
     */
    private $notSyncedDataProvider;

    /**
     * @var LastUpdateTimeCache
     */
    private $lastUpdateTimeCache;

    /**
     * Order grid rows batch size
     */
    const BATCH_SIZE = 100000;

    /**
     * @param Context $context
     * @param string $mainTableName
     * @param string $gridTableName
     * @param string $orderIdField
     * @param array $joins
     * @param array $columns
     * @param string $connectionName
     * @param NotSyncedDataProviderInterface|null $notSyncedDataProvider
     * @param LastUpdateTimeCache|null $lastUpdateTimeCache
     */
    public function __construct(
        Context                        $context,
                                       $mainTableName,
                                       $gridTableName,
                                       $orderIdField,
        array                          $joins = [],
        array                          $columns = [],
                                       $connectionName = null,
        NotSyncedDataProviderInterface $notSyncedDataProvider = null,
        LastUpdateTimeCache            $lastUpdateTimeCache = null
    )
    {
        $this->mainTableName = $mainTableName;
        $this->gridTableName = $gridTableName;
        $this->orderIdField = $orderIdField;
        $this->joins = $joins;
        $this->columns = $columns;
        $this->notSyncedDataProvider = $notSyncedDataProvider ??
            ObjectManager::getInstance()->get(NotSyncedDataProviderInterface::class);
        $this->lastUpdateTimeCache = $lastUpdateTimeCache ??
            ObjectManager::getInstance()->get(LastUpdateTimeCache::class);

        parent::__construct($context,
            $mainTableName,
            $gridTableName,
            $orderIdField,
            $joins,
            $columns,
            $connectionName,
            $notSyncedDataProvider,
            $lastUpdateTimeCache);
    }

    public function refreshBySchedule()
    {
        $lastUpdatedAt = null;
        $notSyncedIds = $this->notSyncedDataProvider->getIds($this->mainTableName, $this->gridTableName);
        foreach (array_chunk($notSyncedIds, self::BATCH_SIZE) as $bunch) {
            $select = $this->getGridOriginSelect()->where($this->mainTableName . '.entity_id IN (?)', $bunch);
            $fetchResult = $this->getConnection()->fetchAll($select);
            $this->getConnection()->insertOnDuplicate(
                $this->getTable($this->gridTableName),
                $fetchResult,
                array_keys($this->columns)
            );

            $timestamps = array_column($fetchResult, 'updated_at');
            if ($timestamps) {
                $lastUpdatedAt = max(max($timestamps), $lastUpdatedAt);
            }
        }

        if ($lastUpdatedAt) {
            $this->lastUpdateTimeCache->save($this->gridTableName, $lastUpdatedAt);
        }
    }
}
