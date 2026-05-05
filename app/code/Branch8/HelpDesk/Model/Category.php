<?php
declare(strict_types=1);

namespace Branch8\HelpDesk\Model;

use Magento\Framework\Model\AbstractModel;

/**
 *
 */
class Category extends AbstractModel
{
    const ENABLE = '1';
    const DISABLE = '0';

    /**
     * @return void
     */
    protected function _construct()
    {
        $this->_init('Branch8\HelpDesk\Model\ResourceModel\Category');
    }
}
