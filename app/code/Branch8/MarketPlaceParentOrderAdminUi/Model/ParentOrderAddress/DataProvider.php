<?php
declare(strict_types=1);

namespace Branch8\MarketPlaceParentOrderAdminUi\Model\ParentOrderAddress;

use Branch8\MarketPlaceParentOrder\Model\ParentOrderAddress;
use Branch8\MarketPlaceParentOrder\Model\ResourceModel\ParentOrderAddress\CollectionFactory;
use Magento\Framework\App\Request\DataPersistorInterface;
use Magento\Ui\DataProvider\Modifier\PoolInterface;

class DataProvider extends \Magento\Ui\DataProvider\ModifierPoolDataProvider
{
    /**
     * @var \Branch8\MarketPlaceParentOrder\Model\ResourceModel\ParentOrderAddress\Collection
     */
    protected $collection;

    /**
     * @var DataPersistorInterface
     */
    protected $dataPersistor;

    /**
     * @var array
     */
    protected $loadedData;

    /**
     * Constructor
     *
     * @param string $name
     * @param string $primaryFieldName
     * @param string $requestFieldName
     * @param CollectionFactory $collectionFactory
     * @param DataPersistorInterface $dataPersistor
     * @param array $meta
     * @param array $data
     * @param PoolInterface|null $pool
     */
    public function __construct(
        string                 $name,
        string                 $primaryFieldName,
        string                 $requestFieldName,
        CollectionFactory      $collectionFactory,
        DataPersistorInterface $dataPersistor,
        array                  $meta = [],
        array                  $data = [],
        PoolInterface          $pool = null
    )
    {
        $this->collection = $collectionFactory->create();
        $this->dataPersistor = $dataPersistor;
        parent::__construct($name, $primaryFieldName, $requestFieldName, $meta, $data, $pool);
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
        $items = $this->collection->getItems();
        /** @var ParentOrderAddress $address */
        foreach ($items as $address) {
            $this->loadedData[$address->getId()] = $address->getData();
        }
        $data = $this->dataPersistor->get('address');
        if (!empty($data)) {
            $address = $this->collection->getNewEmptyItem();
            $address->setData($data);
            $this->loadedData[$address->getId()] = $address->getData();
            $this->loadedData[$address->getId()]['street'] = $address->getStreet();
            $this->dataPersistor->clear('address');
        }
        if (isset($this->loadedData[$address->getId()])) {
            $this->loadedData[$address->getId()]['street'] = $address->getStreet();
        }

        return $this->loadedData;
    }
}
