<?php
/**
 *
 * @category    Branch8
 * @package     Branch8_Blog
 */

namespace Branch8\Blog\Api\Data\SearchResult;

use Magento\Framework\Api\SearchResultsInterface;

/**
 * Interface PostSearchResultInterface
 * @api
 */
interface PostSearchResultInterface extends SearchResultsInterface
{
    /**
     * @return \Branch8\Blog\Api\Data\PostInterface[]
     */
    public function getItems();

    /**
     * @param \Branch8\Blog\Api\Data\PostInterface[] $items
     * @return $this
     */
    public function setItems(?array $items = null);
}
