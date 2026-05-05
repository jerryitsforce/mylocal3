<?php
namespace Branch8\Spin2Win\Model\ResourceModel;

class SpinDraft extends \Magento\Framework\Model\ResourceModel\Db\AbstractDb{
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
        $this->_init('spintowin_draft', 'draft_id');
    }

}