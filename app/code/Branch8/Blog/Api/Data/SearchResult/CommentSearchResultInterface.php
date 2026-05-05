<?php
/**
 *
 * @category    Branch8
 * @package     Branch8_Blog
 */

namespace Branch8\Blog\Api\Data\SearchResult;

use Magento\Framework\Api\SearchResultsInterface;

/**
 * Interface CommentSearchResultInterface
 * @api
 */
interface CommentSearchResultInterface extends SearchResultsInterface
{
    /**
     * @return \Branch8\Blog\Api\Data\CommentInterface[]
     */
    public function getItems();

    /**
     * @param \Branch8\Blog\Api\Data\CommentInterface[] $items
     * @return $this
     */
    public function setItems(?array $items = null);
}
