<?php
/**
 *
 * @category    Branch8
 * @package     Branch8_Blog
 */

namespace Branch8\Blog\Model\ResourceModel\Like;

use Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;
use Branch8\Blog\Model\Like;
use Branch8\Blog\Model\ResourceModel\Like as LikeResourceModel;

/**
 * Class Collection
 * @package Branch8\Blog\Model\ResourceModel\Like
 */
class Collection extends AbstractCollection
{
    /**
     * Define model & resource model
     */
    protected function _construct()
    {
        $this->_init(Like::class, LikeResourceModel::class);
    }
}
