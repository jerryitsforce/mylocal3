<?php

namespace Branch8\Customer\Model\ResourceModel;

class LevelHistory extends \Magento\Framework\Model\ResourceModel\Db\AbstractDb{
    /**
     * @param \Magento\Framework\Model\ResourceModel\Db\Context $context
     */
    public function __construct(
        \Magento\Framework\Model\ResourceModel\Db\Context $context
    ){
        parent::__construct($context);
    }

    /**
     * @return void
     */
    protected function _construct(){
        $this->_init('branch8_level_historys', 'entity_id');
    }
}