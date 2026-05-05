<?php
namespace Branch8\Spin2Win\Model\ResourceModel\SpinDraft;

class Collection extends \Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection{
    protected $_idFieldName = 'draft_id';
    protected $_eventPrefix = 'spintowin_draft_collection';
    protected $_eventObject = 'spintowin_draft_collection';

    /**
     * Define resource model
     *
     * @return void
     */
    protected function _construct(){
        $this->_init('Branch8\Spin2Win\Model\SpinDraft', 'Branch8\Spin2Win\Model\ResourceModel\SpinDraft');
    }
}