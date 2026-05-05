<?php

declare(strict_types=1);

namespace Branch8\Customer\Model\Indexer;

use Branch8\Customer\Model\Indexer\CustomerOrders\Action;
use Branch8\Customer\Model\Indexer\CustomerOrders\IndexerHandlerFactory;
use Magento\Framework\Indexer\ActionInterface as IndexerActionInterface;
use Magento\Framework\Indexer\SaveHandler\IndexerInterface;
use Magento\Framework\Mview\ActionInterface as MviewActionInterface;

class CustomerOrders extends \Magento\Framework\Model\AbstractModel implements IndexerActionInterface, MviewActionInterface
{
    public const INDEXER_ID = 'customer_orders_index';
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

    /**
     * @param IndexerHandlerFactory $indexerHandlerFactory
     * @param Action $indexAction
     * @param array $data
     */
    public function __construct(
        IndexerHandlerFactory $indexerHandlerFactory,
        Action $indexAction,
        array $data = ['indexer_id' => self::INDEXER_ID]
    ) {
        $this->indexerHandlerFactory = $indexerHandlerFactory;
        $this->indexAction = $indexAction;
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
