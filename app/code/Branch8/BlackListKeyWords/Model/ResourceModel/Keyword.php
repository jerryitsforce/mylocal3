<?php
declare(strict_types=1);
/**
 * @project    HotaiConnected
 * @package    Branch8
 * @author     cuongho803@gmail.com
 * @date       20/03/2026
 */

namespace Branch8\BlackListKeyWords\Model\ResourceModel;

use Magento\Framework\Model\ResourceModel\Db\AbstractDb;

class Keyword extends AbstractDb
{
    protected function _construct()
    {
        $this->_init('branch8_blacklist_keywords', 'entity_id');
    }
}
