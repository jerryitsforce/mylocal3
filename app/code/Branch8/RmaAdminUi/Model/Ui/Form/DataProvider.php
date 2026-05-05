<?php
declare(strict_types=1);

namespace Branch8\RmaAdminUi\Model\Ui\Form;

use Webkul\MpRmaSystem\Model\Details;
use Webkul\MpRmaSystem\Model\ResourceModel\Details\CollectionFactory;
use Magento\Framework\App\Request\DataPersistorInterface;
use Magento\Ui\DataProvider\Modifier\PoolInterface;

/**
 *
 */
class DataProvider extends \Magento\Ui\DataProvider\ModifierPoolDataProvider
{
    /**
     * @var \Webkul\MpRmaSystem\Model\ResourceModel\Details\Collection
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
     * @param CollectionFactory $detailCollectionFactory
     * @param DataPersistorInterface $dataPersistor
     * @param array $meta
     * @param array $data
     * @param PoolInterface|null $pool
     */
    public function __construct(
        string                 $name,
        string                 $primaryFieldName,
        string                 $requestFieldName,
        CollectionFactory      $detailCollectionFactory,
        DataPersistorInterface $dataPersistor,
        array                  $meta = [],
        array                  $data = [],
        PoolInterface          $pool = null
    )
    {
        $this->collection = $detailCollectionFactory->create();
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
        /** @var Details $rmaDetail */
        foreach ($items as $rmaDetail) {
            $this->loadedData[$rmaDetail->getId()] = $rmaDetail->getData();
        }

        $data = $this->dataPersistor->get('rma_detail');
        if (!empty($data)) {
            $rmaDetail = $this->collection->getNewEmptyItem();
            $rmaDetail->setData($data);
            $this->loadedData[$rmaDetail->getId()] = $rmaDetail->getData();
            $this->dataPersistor->clear('rma_detail');
        }

        return $this->loadedData;
    }
}
