<?php
declare(strict_types=1);

namespace Branch8\HelpDesk\Model\Ticket\Create;

use Branch8\HelpDesk\Model\Ticket;
use Magento\Framework\App\ObjectManager;
use Magento\Framework\Registry;
use Magento\Ui\DataProvider\Modifier\ModifierInterface;
use Magento\Ui\DataProvider\Modifier\PoolInterface;
use  Branch8\HelpDesk\Model\ResourceModel\Ticket\CollectionFactory;
use Magento\Ui\DataProvider\ModifierPoolDataProvider;

/**
 * Data Provider for Ticket Form
 */
class DataProvider extends ModifierPoolDataProvider
{
    /**
     * @var PoolInterface|null
     */
    private $pool;
    /**
     * @var Registry
     */
    private $registry;

    /**
     * @param CollectionFactory $collectionFactory
     * @param Registry $registry
     * @param string $name
     * @param string $primaryFieldName
     * @param string $requestFieldName
     * @param array $meta
     * @param array $data
     * @param PoolInterface|null $pool
     */
    public function __construct(
        CollectionFactory $collectionFactory,
        Registry          $registry,
        string            $name,
        string            $primaryFieldName,
        string            $requestFieldName,
        array             $meta = [],
        array             $data = [],
        PoolInterface     $pool = null
    )
    {

        parent::__construct($name, $primaryFieldName, $requestFieldName, $meta, $data, $pool);
        $this->collection = $collectionFactory->create();
        $this->pool = $pool;
        $this->registry = $registry;
    }
}
