<?php
/**
 *
 * @category    Branch8
 * @package     Branch8_Blog
 */

namespace Branch8\Blog\Model\ResourceModel\Author;

use Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;
use Branch8\Blog\Model\Author;
use Branch8\Blog\Model\ResourceModel\Author as AuthorResourceModel;

/**
 * Class Collection
 * @package Branch8\Blog\Model\ResourceModel\Author
 */
class Collection extends AbstractCollection
{
    /**
     * @inheritdoc
     */
    protected $_idFieldName = 'user_id';

    /**
     * Construct
     */
    protected function _construct()
    {
        $this->_init(Author::class, AuthorResourceModel::class);
    }
}
