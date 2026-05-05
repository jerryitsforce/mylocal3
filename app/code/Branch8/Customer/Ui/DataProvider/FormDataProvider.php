<?php

namespace Branch8\Customer\Ui\DataProvider;
use Branch8\Customer\Model\ResourceModel\Organization\CollectionFactory;

class FormDataProvider extends \Magento\Ui\DataProvider\AbstractDataProvider{

    /**
     * @var array
     */
    protected $_loadedData;

    public function __construct(
        $name,
        $primaryFieldName,
        $requestFieldName,
        CollectionFactory $employeeCollectionFactory,
        array $meta = [],
        array $data = []
    ) {
        $this->collection = $employeeCollectionFactory->create();
        parent::__construct($name, $primaryFieldName, $requestFieldName, $meta, $data);
    }
    public function getData()
    {
        if (isset($this->_loadedData)) {
            return $this->_loadedData;
        }
        $items = $this->collection->getItems();
        foreach ($items as $org) {
            $this->_loadedData[$org->getId()]['information'] = $org->getData();
        }
        return $this->_loadedData;
    }

}