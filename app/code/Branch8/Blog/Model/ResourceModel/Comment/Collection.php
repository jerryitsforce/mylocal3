<?php
/**
 *
 * @category    Branch8
 * @package     Branch8_Blog
 */

namespace Branch8\Blog\Model\ResourceModel\Comment;

use Magento\Sales\Model\ResourceModel\Collection\AbstractCollection;
use Branch8\Blog\Api\Data\SearchResult\CommentSearchResultInterface;
use Branch8\Blog\Model\Comment;

/**
 * Class Collection
 * @package Branch8\Blog\Model\ResourceModel\Comment
 */
class Collection extends AbstractCollection implements CommentSearchResultInterface
{
    /**
     * @var string
     */
    protected $_idFieldName = 'comment_id';

    /**
     * Define model & resource model
     */
    protected function _construct()
    {
        $this->_init(Comment::class, \Branch8\Blog\Model\ResourceModel\Comment::class);
    }
}
