<?php
/**
 *
 * @category    Branch8
 * @package     Branch8_Blog
 */

namespace Branch8\Blog\Model\ResourceModel\PostLike;

use Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;
use Branch8\Blog\Model\PostLike;

/**
 * Class Collection
 * @package Branch8\Blog\Model\ResourceModel\PostLike
 */
class Collection extends AbstractCollection
{
    /**
     * Define model & resource model
     */
    protected function _construct()
    {
        $this->_init(PostLike::class, \Branch8\Blog\Model\ResourceModel\PostLike::class);
    }
}
