<?php

declare(strict_types=1);

namespace Branch8\RmaAdminUi\Model\Indexer;

use Branch8\RmaAdminUi\Model\Indexer\Action;
use Branch8\RmaAdminUi\Model\Indexer\IndexerHandlerFactory;
use Magento\Framework\Indexer\ActionInterface as IndexerActionInterface;
use Magento\Framework\Indexer\SaveHandler\IndexerInterface;
use Magento\Framework\Mview\ActionInterface as MviewActionInterface;

class RmaFlatData implements IndexerActionInterface, MviewActionInterface
{
    public const INDEXER_ID = 'marketplace_rma_grid';
    /**
     * @var IndexerHandlerFactory
     */
    private $indexerHandlerFactory;

    /**
     * @var Action
     */
    private $indexAction;

    /**
     * @var array
     */
    private $data;
    private \Branch8\Sales\Model\Actions\ResyncOrdersToGrid $resyncOrdersToGrid;

    /**
     * @param IndexerHandlerFactory $indexerHandlerFactory
     * @param Action $indexAction
     * @param \Branch8\Sales\Model\Actions\ResyncOrdersToGrid $resyncOrdersToGrid
     * @param array $data
     */
    public function __construct(
        IndexerHandlerFactory $indexerHandlerFactory,
        Action $indexAction,
        \Branch8\Sales\Model\Actions\ResyncOrdersToGrid $resyncOrdersToGrid,
        array $data = ['indexer_id' => self::INDEXER_ID]
    ) {
        $this->indexerHandlerFactory = $indexerHandlerFactory;
        $this->indexAction = $indexAction;
        $this->data = $data;
        $this->resyncOrdersToGrid = $resyncOrdersToGrid;
    }

    public function execute($ids)
    {
        /** @var IndexerInterface $indexHandler */
        $indexHandler = $this->indexerHandlerFactory->create([
            'data' => $this->data
        ]);
        if (!count($ids)) {
            $indexHandler->cleanIndex([]);
            $indexHandler->saveIndex([], $this->indexAction->getIndexInsertIterator([]));
        } else {
            //$indexHandler->deleteIndex([], new \ArrayIterator($ids));
            $indexHandler->saveIndex([], $this->indexAction->getIndexInsertIterator($ids));
            $this->resyncOrdersToGrid->execute($ids);
        }
    }

    public function executeFull()
    {
        $this->execute([]);
    }

    public function executeList(array $ids)
    {
        $this->execute($ids);
    }

    public function executeRow($id)
    {
        $this->execute([$id]);
    }
}
