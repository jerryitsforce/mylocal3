<?php
namespace Branch8\OptionsWithStockAndImages\Model\Indexer;

use Magento\Framework\Indexer\ActionInterface;
use Magento\Framework\Mview\ActionInterface as MviewActionInterface;
use Branch8\OptionsWithStockAndImages\Model\Indexer\VariationPrice\Processor;

class VariationPrice implements ActionInterface, MviewActionInterface
{
    /**
     * @var Processor
     */
    protected $processor;

    public function __construct(Processor $processor)
    {
        $this->processor = $processor;
    }

    /**
     * Execute full reindex
     */
    public function executeFull()
    {
        $this->processor->reindexAll();
    }

    /**
     * Execute partial reindex for a list of entities from command line
     * This method is required by \Magento\Framework\Indexer\ActionInterface
     *
     * @param array $ids
     */
    public function executeList(array $ids)
    {
        $this->processor->reindexList($ids);
    }

    /**
     * Execute partial reindex for a single entity from command line
     *
     * @param int $id
     */
    public function executeRow($id)
    {
        $this->processor->reindexRow($id);
    }

    /**
     * Execute partial reindex for a list of entities from Mview
     * This method is required by \Magento\Framework\Mview\ActionInterface
     *
     * @param int[] $ids
     */
    public function execute($ids)
    {
        $this->processor->reindexList($ids);
    }
}
