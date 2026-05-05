<?php

declare(strict_types=1);

namespace Branch8\Catalog\Model\ResourceModel\Product\History\Grid;

use Magento\Backend\Model\Session as BackendSession;
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
     * @var BackendSession
     */
    private BackendSession $backendSession;

    /**
     * Constructor.
     *
     * @param RequestInterface $request
     * @param BackendSession $backendSession
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
        BackendSession $backendSession,
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
        $this->backendSession = $backendSession;
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

        $attribute = $this->getAttribute();
        $this->addFieldToFilter('changed_field', $attribute);
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

    /**
     * Get current attribute.
     *
     * @return string
     */
    private function getAttribute(): string
    {
        return (string)$this->backendSession->getData('product_attribute_trace');
    }
}

