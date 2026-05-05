<?php

namespace Branch8\MarketplaceStaging\Model\Ticket;

use Branch8\MarketplaceStaging\Api\TicketSynchronizerInterface;
use Branch8\MarketplaceStaging\Helper\Data as MarketplaceStagingHelper;
use Magento\Catalog\Model\Product;
use Magento\Framework\App\ResourceConnection;
use Magento\Catalog\Api\ProductRepositoryInterface;

/**
 * Common logic for ticket stock synchronization.
 */
abstract class AbstractSynchronizer implements TicketSynchronizerInterface
{
    /** @var MarketplaceStagingHelper */
    protected $marketplaceStagingHelper;

    /** @var ResourceConnection */
    protected $resourceConnection;

    /** @var \Magento\Catalog\Api\ProductRepositoryInterface */
    protected $productRepository;

    /** @var array Shared cache for stock counts */
    protected static $_stockCache = [];

    /**
     * @param MarketplaceStagingHelper $marketplaceStagingHelper
     * @param ResourceConnection $resourceConnection
     * @param ProductRepositoryInterface $productRepository
     */
    public function __construct(
        MarketplaceStagingHelper $marketplaceStagingHelper,
        ResourceConnection $resourceConnection,
        ProductRepositoryInterface $productRepository
    ) {
        $this->marketplaceStagingHelper = $marketplaceStagingHelper;
        $this->resourceConnection       = $resourceConnection;
        $this->productRepository        = $productRepository;
    }

    /**
     * Standard implementation for ticket variation synchronization.
     *
     * @param Product $product
     * @return bool
     */
    public function sync(Product $product): bool
    {
        try {
            // Invalidate the cache for this product ID before fetching counts
            $productId = $product->getData('entity_id') ?: $product->getId();
            unset(self::$_stockCache[$productId]);

            // Force reload the product to ensure any newly inserted Custom Options (by DB direct queries) are caught
            $freshProduct = $this->productRepository->getById($product->getId(), false, $product->getStoreId(), true);

            $options = $freshProduct->getOptions();
            if (!$options) {
                return false;
            }

            $combinations = $this->marketplaceStagingHelper->getVariationCombination($freshProduct);
            if (empty($combinations)) {
                return false;
            }

            foreach ($combinations as $comb => $data) {
                $stock = $this->getAvailableCount($product, $comb);
                // Update variation data directly in DB via helper.
                // Using getRowId() because wk_osi_variations links to row_id on Staging environments.
                $this->marketplaceStagingHelper->updateVariationDataDirectly(
                    $freshProduct->getRowId(),
                    $comb,
                    $stock,
                    [
                        'mageproduct_id' => $freshProduct->getId(),
                        'sku'            => !empty($data['sku']) ? $data['sku'] : $freshProduct->getSku(),
                        'price'          => $freshProduct->getPrice() ?: 0,
                        'cost'           => $freshProduct->getCost() ?: 0,
                        'cost_setting'   => $freshProduct->getData('cost_setting') ?: 0,
                        'commission_percent' => $freshProduct->getData('commission_percent') ?: 0,
                        'image'          => ''
                    ]
                );
            }
            return true;
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Optimized bulk counting logic using JOIN and GROUP BY.
     * 
     * @param Product $product
     * @param string $recordTable
     * @param string $batchTable
     * @return array Mapping of batch_code => count
     */
    protected function fetchAllBatchCounts(Product $product, string $recordTable, string $batchTable): array
    {
        $productId = $product->getData('entity_id') ?: $product->getId();
        $connection = $this->resourceConnection->getConnection();
        
        $tableName = $connection->getTableName($recordTable);
        $batchTableName = $connection->getTableName($batchTable);
        
        $select = $connection->select()
            ->from(['r' => $tableName], [])
            ->join(
                ['bs' => $batchTableName],
                'r.batch_setting_id = bs.setting_id',
                []
            )
            ->columns([
                'b_code' => 'bs.batch_code',
                'total_available' => new \Zend_Db_Expr('COUNT(r.record_id)')
            ])
            ->where('r.belong_to_product_id = ?', $productId)
            ->where('r.status = ?', 0)
            ->group('bs.batch_code');
        
        return $connection->fetchPairs($select) ?: [];
    }
}
