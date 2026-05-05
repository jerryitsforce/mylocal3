<?php

namespace Branch8\FlagshipStore\Ui\DataProvider;

use Branch8\FlagshipStore\Model\ResourceModel\FlagshipStore\CollectionFactory;
use Magento\Framework\Registry;

class FormDataProvider extends \Magento\Ui\DataProvider\AbstractDataProvider{

    /**
     * @var array
     */
    protected $_loadedData;
    /**
     * @var \Magento\Framework\Serialize\Serializer\Json
     */
    protected $serialize;

    protected $registry;

    protected $request;
    public function __construct(
        $name,
        $primaryFieldName,
        $requestFieldName,
        CollectionFactory $collectionFactory,
        \Magento\Framework\Serialize\Serializer\Json $serialize,
        \Magento\Framework\App\RequestInterface $request,
        Registry $registry,
        array $meta = [],
        array $data = []
    ) {
        $this->collection = $collectionFactory->create();
        parent::__construct($name, $primaryFieldName, $requestFieldName, $meta, $data);
        $this->serialize = $serialize;
        $this->registry = $registry;
        $this->request = $request;
    }

    /**
     * @return array
     */
    public function getData()
    {
        if (isset($this->_loadedData)) {
            return $this->_loadedData;
        }
        $storeCollection = $this->collection;
        $storeCollection->getSelect()
            ->joinLeft(['fts' => 'flagship_store_seller'], 'fts.flagship_store_id = main_table.entity_id', ['seller_ids' => 'group_concat(seller_id)']);

        $store = $storeCollection->getFirstItem();
        $storeData = $store->getData();
        $storeData['seller_ids'] = explode(',', (string)$store['seller_ids']);
        $this->_loadedData[$store->getId()] = $storeData;
        $this->registry->register('current_flagship_store', $store->getId());

        return $this->_loadedData;
    }

}