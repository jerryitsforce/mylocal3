<?php
/**
 *
 * @category    Branch8
 * @package     Branch8_Blog
 */

namespace Branch8\Blog\Model\ResourceModel\Traffic;

use Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;
use Branch8\Blog\Model\Traffic;
use Branch8\Blog\Model\ResourceModel\Traffic as TrafficResourceModel;

/**
 * Class Collection
 * @package Branch8\Blog\Model\ResourceModel\Traffic
 */
class Collection extends AbstractCollection
{
    /**
     * Define model & resource model
     */
    protected function _construct()
    {
        $this->_init(Traffic::class, TrafficResourceModel::class);
    }
}
