<?php
namespace Branch8\AdvancedIsNotRule\Model;

use Magento\Framework\Model\AbstractModel;

class Filter extends AbstractModel
{
    protected function _construct()
    {
        $this->_init(\Branch8\AdvancedIsNotRule\Model\ResourceModel\Filter::class);
    }
}
