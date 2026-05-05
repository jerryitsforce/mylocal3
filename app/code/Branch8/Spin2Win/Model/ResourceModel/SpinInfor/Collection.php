<?php
namespace Branch8\Spin2Win\Model\ResourceModel\SpinInfor;

class Collection extends \Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection{
    protected $_idFieldName = 'entity_id';
    protected $_eventPrefix = 'spintowin_info_collection';
    protected $_eventObject = 'spintowin_info_collection';

    /**
     * Define resource model
     *
     * @return void
     */
    protected function _construct(){
        $this->_init('Branch8\Spin2Win\Model\SpinInfor', 'Branch8\Spin2Win\Model\ResourceModel\SpinInfor');
    }
}