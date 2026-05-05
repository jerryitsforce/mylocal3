<?php

declare(strict_types=1);

namespace Branch8\Customer\Model\Indexer;

use Branch8\Customer\Model\Actions\ResynCustomerToGrid;
use Branch8\Customer\Model\Indexer\CustomerLatestOrder\Action;
use Branch8\Customer\Model\Indexer\CustomerLatestOrder\IndexerHandlerFactory;
use Magento\Framework\Indexer\ActionInterface as IndexerActionInterface;
use Magento\Framework\Indexer\SaveHandler\IndexerInterface;
use Magento\Framework\Mview\ActionInterface as MviewActionInterface;

class CustomerLatestOrder extends \Magento\Framework\Model\AbstractModel implements IndexerActionInterface, MviewActionInterface
{
    public const INDEXER_ID = 'customer_orders_latest_index';
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

    private $resyncCustomerToGrid;

    /**
     * @param IndexerHandlerFactory $indexerHandlerFactory
     * @param Action $indexAction
     * @param ResyncCustomerToGrid $resyncCustomerToGrid
     * @param array $data
     */
    public function __construct(
        IndexerHandlerFactory $indexerHandlerFactory,
        Action $indexAction,
        ResynCustomerToGrid $resyncCustomerToGrid,
        array $data = ['indexer_id' => self::INDEXER_ID]
    ) {
        $this->indexerHandlerFactory = $indexerHandlerFactory;
        $this->indexAction = $indexAction;
        $this->resyncCustomerToGrid = $resyncCustomerToGrid;
        $this->data = $data;
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
            $this->resyncCustomerToGrid->execute($ids);

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
