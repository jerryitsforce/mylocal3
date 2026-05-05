<?php

namespace Branch8\MagentoVisualMerchandiser\Model\ResourceModel;

use Magento\Framework\Model\AbstractModel;

class RuleIndex extends \Magento\Framework\Model\ResourceModel\Db\AbstractDb
{
    /**
     * Initialize vm rules index model
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_init('visual_merchandiser_rule_index', 'entity_id');
    }
}
