<?php

namespace Branch8\MagentoVisualMerchandiser\Model;

use Magento\Framework\Model\AbstractModel;
use Magento\VisualMerchandiser\Model\ResourceModel\Rules as ResourceModelRules;

class RuleIndex extends AbstractModel
{
    const STATUS_PENDING = 'pending';
    const STATUS_PUBLISHED = 'published';
    const STATUS_PROCESSING = 'processing';
    const STATUS_DONE = 'done';

    /**
     * @return void
     */
    protected function _construct()
    {
        parent::_construct();
        $this->_init(\Branch8\MagentoVisualMerchandiser\Model\ResourceModel\RuleIndex::class);
        $this->setIdFieldName('entity_id');
    }
}
