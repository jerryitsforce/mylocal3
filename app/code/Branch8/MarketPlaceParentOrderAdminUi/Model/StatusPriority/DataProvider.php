<?php
declare(strict_types=1);

namespace Branch8\MarketPlaceParentOrderAdminUi\Model\StatusPriority;

use Magento\Framework\App\Request\DataPersistorInterface;
use Magento\Ui\DataProvider\AbstractDataProvider;
use Magento\Ui\DataProvider\Modifier\PoolInterface;
use Branch8\MarketPlaceParentOrder\Model\ResourceModel\StatusPriority\CollectionFactory;

/**
 * @property CollectionFactory $collectionFactory
 */
class DataProvider extends AbstractDataProvider
{
    /**
     * @var DataPersistorInterface
     */
    protected $datPerssistent;

    /**
     * @var array
     */
    protected $loadedData;
    private CollectionFactory $collectionFactory;

    /**
     * @param DataPersistorInterface $dataPerssistent
     * @param array $meta
     * @param string $name
     * @param string $primaryFieldName
     * @param string $requestFieldName
     * @param array $data
     * @param PoolInterface|null $pool
     */
    public function __construct(
        DataPersistorInterface $dataPerssistent,
        CollectionFactory      $collectionFactory,
        string                 $name,
        string                 $primaryFieldName = 'entity_id',
                               $requestFieldName = 'entity_id',
        array                  $meta = [],
        array                  $data = []
    )
    {
        $this->datPerssistent = $dataPerssistent;
        $this->collectionFactory = $collectionFactory;
        $this->collection = $this->collectionFactory->create();
        parent::__construct($name, $primaryFieldName, $requestFieldName, $meta, $data);
    }

    /**
     * Get data
     *
     * @return array
     */
    public function getData()
    {
        if (isset($this->loadedData)) {
            return $this->loadedData;
        }

        $this->collection->getSelect()->reset('where');
        $items = $this->collection->load();
        $newItem = $this->collection->getNewEmptyItem();
        $statues = [];
        foreach ($items as $item) {
            $statues[] = [
                'entity_id' => $item->getId(),
                'status' => $item->getData('status'),
                'priority' => $item->getData('priority')
            ];
        }
        $this->loadedData[$newItem->getId()]['records'] = $statues;
        return $this->loadedData;
    }
}
