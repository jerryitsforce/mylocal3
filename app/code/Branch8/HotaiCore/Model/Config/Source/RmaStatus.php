<?php

namespace Branch8\HotaiCore\Model\Config\Source;

class RmaStatus extends \Magento\Eav\Model\Entity\Attribute\Source\AbstractSource
{

    const RMA_STATUSES = [
        'rma_processing',
        'rma_completed',
        'rma_failed',
        'rma_other',
        'rma_apply_return_cancel',
        'rma_apply_replace_cancel',
        'rma_other',
        'applying_return_cancel',
        'applying_replace_cancel'
    ];

    protected $collectionFactory;

    protected $_options = null;

    public function __construct(
        \Magento\Sales\Model\ResourceModel\Order\Status\CollectionFactory $collectionFactory
    ){
        $this->collectionFactory = $collectionFactory;
    }

    public function getAllOptions(){

        if ($this->_options === null) {
            $collection = $this->collectionFactory->create()
                ->addFieldToSelect('*')
                ->addFieldToFilter('status', ['in' => self::RMA_STATUSES]);
            $options = [];
            foreach($collection as $_status){
                $options[] = [
                    'value' => $_status->getStatus(),
                    'label' => __($_status->getLabel())
                ];
            }

            $options[] = [
                'value' => 'n/a',
                'label' => __('N/A'),
            ];

            $this->_options = $options;
        }
        return $this->_options;
    }

    public function getOptionArray()
    {
        $allOptions = $this->getAllOptions();
        $optArray = [];
        foreach($allOptions as $_option){
            $optArray[$_option['value']] = $_option['label'];
        }
        return $optArray;
    }
}
