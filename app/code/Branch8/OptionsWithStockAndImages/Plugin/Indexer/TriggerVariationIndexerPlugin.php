<?php
namespace Branch8\OptionsWithStockAndImages\Plugin\Indexer;

use Magento\Framework\Indexer\IndexerRegistry;
use Magento\Indexer\Model\Indexer;

class TriggerVariationIndexerPlugin
{
    /**
     * @var IndexerRegistry
     */
    protected $indexerRegistry;

    /**
     * @param IndexerRegistry $indexerRegistry
     */
    public function __construct(IndexerRegistry $indexerRegistry)
    {
        $this->indexerRegistry = $indexerRegistry;
    }



    /**
     * Run branch8_variations_price after catalog_product_price reindexRow
     *
     * @param Indexer $subject
     * @param mixed $result
     * @param int $id
     * @return mixed
     */
    public function afterReindexRow(Indexer $subject, $result, $id)
    {
        if ($subject->getId() === 'catalog_product_price') {
            $this->runVariationIndexer([$id]);
        }
        return $result;
    }

    /**
     * Run branch8_variations_price after catalog_product_price reindexList
     *
     * @param Indexer $subject
     * @param mixed $result
     * @param array $ids
     * @return mixed
     */
    public function afterReindexList(Indexer $subject, $result, $ids)
    {
        if ($subject->getId() === 'catalog_product_price') {
            $this->runVariationIndexer($ids);
        }
        return $result;
    }

    /**
     * Execute Branch8 Variation Price Indexer
     *
     * @param array|null $ids
     * @return void
     */
    protected function runVariationIndexer($ids = null)
    {
        try {
            $b8Indexer = $this->indexerRegistry->get('branch8_variations_price');
            
            if ($ids === null) {
                $b8Indexer->reindexAll();
            } else {
                $b8Indexer->reindexList($ids);
            }
        } catch (\Exception $e) {
        }
    }
}
