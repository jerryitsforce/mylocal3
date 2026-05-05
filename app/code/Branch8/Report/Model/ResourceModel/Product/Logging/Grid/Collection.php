<?php

declare(strict_types=1);

namespace Branch8\Report\Model\ResourceModel\Product\Logging\Grid;

use Magento\Framework\App\RequestInterface;
use Magento\Framework\Data\Collection\Db\FetchStrategyInterface as FetchStrategy;
use Magento\Framework\Data\Collection\EntityFactoryInterface as EntityFactory;
use Magento\Framework\Event\ManagerInterface as EventManager;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\View\Element\UiComponent\DataProvider\SearchResult;
use Psr\Log\LoggerInterface as Logger;

class Collection extends SearchResult
{
    /**
     * @var RequestInterface
     */
    private RequestInterface $request;

    /**
     * Constructor.
     *
     * @param RequestInterface $request
     * @param EntityFactory $entityFactory
     * @param Logger $logger
     * @param FetchStrategy $fetchStrategy
     * @param EventManager $eventManager
     * @param string $mainTable
     * @param string|null $resourceModel
     * @param string|null $identifierName
     * @param string|null $connectionName
     *
     * @throws LocalizedException
     */
    public function __construct(
        RequestInterface $request,
        EntityFactory    $entityFactory,
        Logger           $logger,
        FetchStrategy    $fetchStrategy,
        EventManager     $eventManager,
        string           $mainTable,
        string           $resourceModel = null,
        string           $identifierName = null,
        string           $connectionName = null
    ) {
        $this->request = $request;
        parent::__construct(
            $entityFactory,
            $logger,
            $fetchStrategy,
            $eventManager,
            $mainTable,
            $resourceModel,
            $identifierName,
            $connectionName
        );
    }

    /**
     * @inheritdoc
     */
    protected function _initSelect(): void
    {
        parent::_initSelect();

        $productId = $this->getProductId();
        $this->addFieldToFilter('product_id', $productId);
    }

    /**
     * Get current product ID.
     *
     * @return int
     */
    private function getProductId(): int
    {
        return (int)$this->request->getParam('id');
    }
}

