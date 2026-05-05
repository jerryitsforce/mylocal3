<?php
namespace Branch8\SellerContactInformation\Ui\DataProvider;

use Branch8\SellerContactInformation\Model\ResourceModel\ContractFiles\CollectionFactory;

class SellerContractForm extends \Magento\Ui\DataProvider\AbstractDataProvider{

    /**
     * @var array
     */
    protected $_loadedData;

    protected $request;

    public function __construct(
        $name,
        $primaryFieldName,
        $requestFieldName,
        CollectionFactory $contractCollectionFactory,
        \Magento\Framework\App\RequestInterface $request,
        array $meta = [],
        array $data = []
    ) {
        $this->collection = $contractCollectionFactory->create();
        parent::__construct($name, $primaryFieldName, $requestFieldName, $meta, $data);
        $this->request = $request;
    }
    public function getData()
    {
        if (isset($this->_loadedData)) {
            return $this->_loadedData;
        }

        $items = $this->collection->getItems();
        foreach ($items as $contract) {
            $this->_loadedData[$contract->getId()]['information'] = $contract->getData();
        }
        if(empty($this->_loadedData)){
            $this->_loadedData[0]['information'] = ['seller_id' => $this->request->getParam('seller_id')];
        }

        return $this->_loadedData;
    }

}