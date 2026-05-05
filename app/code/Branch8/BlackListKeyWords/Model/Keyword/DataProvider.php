<?php
declare(strict_types=1);
/**
 * @project    HotaiConnected
 * @package    Branch8
 * @author     cuongho803@gmail.com
 * @date       22/03/2026
 */

namespace Branch8\BlackListKeyWords\Model\Keyword;

use Branch8\BlackListKeyWords\Model\Keyword;
use Branch8\BlackListKeyWords\Model\ResourceModel\Keyword\CollectionFactory;
use Magento\Framework\App\Request\DataPersistorInterface;
use Magento\Ui\DataProvider\Modifier\PoolInterface;

class DataProvider extends \Magento\Ui\DataProvider\ModifierPoolDataProvider
{
    /**
     * @var \Branch8\MarketPlaceParentOrder\Model\ResourceModel\ParentOrder\Collection
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
        /** @var Keyword $keyword */
        foreach ($items as $item) {
            $this->loadedData[$item->getId()] = $item->getData();
        }
        $data = $this->dataPersistor->get('keyword');
        if (!empty($data)) {
            $keyword = $this->collection->getNewEmptyItem();
            $keyword->setData($data);
            $this->loadedData[$keyword->getId()] = $keyword->getData();
            $this->dataPersistor->clear('keyword');
        }
        return $this->loadedData;
    }
}
