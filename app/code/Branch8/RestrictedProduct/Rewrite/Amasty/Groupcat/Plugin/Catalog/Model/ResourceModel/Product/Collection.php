<?php
/**
 * @author Branch8 Team
 * @package Restricted Product
 */
declare(strict_types=1);

namespace Branch8\RestrictedProduct\Rewrite\Amasty\Groupcat\Plugin\Catalog\Model\ResourceModel\Product;

use Amasty\Groupcat\Model\ConfigProvider;
use Amasty\Groupcat\Model\ProductRuleProvider;
use Branch8\RestrictedProduct\Model\ConfigData;
use Magento\Catalog\Model\ResourceModel\Product\Collection as ProductCollection;
use Magento\Framework\DB\Select;
use Magento\Framework\Registry;

class Collection extends \Amasty\Groupcat\Plugin\Catalog\Model\ResourceModel\Product\Collection
{
    const USE_OPTIMIZE_PATH = 'amasty_groupcat/general/optimize_collection_loading';
    /**
     * @var ProductRuleProvider
     */
    private $ruleProvider;

    /**
     * @var Registry
     */
    private $coreRegistry;

    /**
     * @var ConfigProvider
     */
    private $configProvider;

    /**
     * @var string|null|bool
     */
    private $temporaryTableName = null;
    private ConfigData $configData;

    /**
     * @param ProductRuleProvider $ruleProvider
     * @param ConfigProvider $configProvider
     * @param ConfigData $configData
     * @param Registry $coreRegistry
     */
    public function __construct(
        ProductRuleProvider $ruleProvider,
        ConfigProvider      $configProvider,
        ConfigData          $configData,
        Registry            $coreRegistry
    )
    {
        $this->ruleProvider = $ruleProvider;
        $this->coreRegistry = $coreRegistry;
        $this->configData = $configData;
        $this->configProvider = $configProvider;
        parent::__construct($ruleProvider, $configProvider, $coreRegistry);
    }

    /**
     * Rewritten to use temporary table for performance optimization when filtering out restricted products.
     *
     * @param ProductCollection $subject
     * @param Select $productSelect
     * @return void
     */
    protected function addRestrictedProductFilter(
        ProductCollection $subject,
        Select            $productSelect
    ): void
    {
        $subject->setFlag('groupcat_filter_applied', true);
        $enabled = $this->configData->enableOptimize();
        $productIds = $this->ruleProvider->getRestrictedProductIds();
        if ($productIds
            && ($subject->getIdFieldName() === 'entity_id' || $subject->getIdFieldName() === 'selection_id')
        ) {
            $idField = $subject::MAIN_TABLE_ALIAS . '.entity_id';
            // Optimization: if there are few restricted products, use NOT IN directly.
            // Temporary tables are more efficient for large sets of IDs.
            if (count($productIds) < 100 || !$enabled) {
                $productSelect->where($idField . ' NOT IN (?)', $productIds);
                return;
            }
            /** @var \Magento\Framework\DB\Adapter\AdapterInterface $connection */
            $connection = $productSelect->getAdapter();
            $temporaryTableName = $this->getTemporaryTable($connection, $productIds);
            if ($temporaryTableName) {
                $productSelect->joinLeft(
                    ['tmp_exclude_table' => $temporaryTableName],
                    'tmp_exclude_table.entity_id=' . $idField,
                    []
                )->where('tmp_exclude_table.entity_id IS NULL');
            } else {
                // Fallback to original behavior if temporary table fails
                $productSelect->where($idField . ' NOT IN (?)', $productIds);
            }
        }
    }

    /**
     * Create and populate temporary table with restricted product IDs.
     * Table is created once per request.
     *
     * @param \Magento\Framework\DB\Adapter\AdapterInterface $connection
     * @param array $productIds
     * @return string|null
     */
    private function getTemporaryTable($connection, array $productIds): ?string
    {
        if ($this->temporaryTableName === null) {
            try {
                $tableName = $connection->getTableName('amasty_groupcat_restricted_ids_' . bin2hex(random_bytes(4)));
                $table = $connection->newTable($tableName)
                    ->addColumn(
                        'entity_id',
                        \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
                        null,
                        ['unsigned' => true, 'nullable' => false, 'primary' => true],
                        'Entity ID'
                    )->addIndex('amasty_groupcat_restricted_ids_index', ['entity_id'])
                    ->setOption('type', 'InnoDB')
                    ->setOption('charset', 'utf8');
                //$connection->createTable($table);
                $connection->createTemporaryTable($table);

                $data = [];
                foreach ($productIds as $id) {
                    $data[] = ['entity_id' => (int)$id];
                }

                if (!empty($data)) {
                    // Split into chunks if necessary (though 500-1000 IDs is usually fine in one batch)
                    $chunks = array_chunk($data, 1000);
                    foreach ($chunks as $chunk) {
                        $connection->insertOnDuplicate($tableName, $chunk, ['entity_id']);
                    }
                }
                $this->temporaryTableName = $tableName;
            } catch (\Exception $e) {
                // Fallback or log if temporary table creation fails
                $this->temporaryTableName = false;
            }
        }

        return is_string($this->temporaryTableName) ? $this->temporaryTableName : null;
    }
}
