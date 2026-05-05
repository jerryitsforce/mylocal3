<?php
declare(strict_types=1);

namespace Branch8\HelpDesk\Model\ResourceModel\Attachment;

use Branch8\HelpDesk\Api\Data\AttachmentSearchResultInterface;
use Magento\Framework\Event\ManagerInterface;
use Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;
use Magento\Store\Model\StoreManagerInterface;

/**
 * Attachment Collection
 */
class Collection extends AbstractCollection implements AttachmentSearchResultInterface
{
    /**
     * @var StoreManagerInterface
     */
    private $storeManager;
    /**
     * @var
     */
    private $searchCriteria;
    /**
     * @var string
     */
    protected $_idFieldName = 'attachment_id';

    /**
     * @param StoreManagerInterface $storeManager
     * @param \Magento\Framework\Data\Collection\EntityFactoryInterface $entityFactory
     * @param \Psr\Log\LoggerInterface $logger
     * @param \Magento\Framework\Data\Collection\Db\FetchStrategyInterface $fetchStrategy
     * @param ManagerInterface $eventManager
     * @param \Magento\Framework\Model\ResourceModel\Db\AbstractDb|null $resource
     * @param \Magento\Framework\DB\Adapter\AdapterInterface|null $connection
     */
    public function __construct(
        StoreManagerInterface                                        $storeManager,
        \Magento\Framework\Data\Collection\EntityFactoryInterface    $entityFactory,
        \Psr\Log\LoggerInterface                                     $logger,
        \Magento\Framework\Data\Collection\Db\FetchStrategyInterface $fetchStrategy,
        ManagerInterface                                             $eventManager,
        \Magento\Framework\Model\ResourceModel\Db\AbstractDb         $resource = null,
        \Magento\Framework\DB\Adapter\AdapterInterface               $connection = null
    )
    {
        parent::__construct(
            $entityFactory,
            $logger,
            $fetchStrategy,
            $eventManager,
            $connection,
            $resource
        );
        $this->storeManager = $storeManager;
    }

    /**
     * @return void
     */
    protected function _construct()
    {
        $this->_init(
            'Branch8\HelpDesk\Model\Attachment',
            'Branch8\HelpDesk\Model\ResourceModel\Attachment'
        );
    }

    /**
     * @param array $items
     * @return $this|\Branch8\HelpDesk\Model\ResourceModel\Message\Collection
     * @throws \Exception
     */
    public function setItems(array $items)
    {
        if (!$items) {
            return $this;
        }
        foreach ($items as $item) {
            $this->addItem($item);
        }

        return $this;
    }

    /**
     * @return \Magento\Framework\Api\SearchCriteriaInterface
     */
    public function getSearchCriteria()
    {
        return $this->searchCriteria;
    }

    /**
     * @param \Magento\Framework\Api\SearchCriteriaInterface $searchCriteria
     * @return void
     */
    public function setSearchCriteria(\Magento\Framework\Api\SearchCriteriaInterface $searchCriteria)
    {
        $this->searchCriteria = $searchCriteria;
    }

    /**
     * @return int
     */
    public function getTotalCount()
    {
        return $this->getSize();
    }

    /**
     * @param $totalCount
     * @return $this|Collection
     */
    public function setTotalCount($totalCount)
    {
        return $this;
    }
}
