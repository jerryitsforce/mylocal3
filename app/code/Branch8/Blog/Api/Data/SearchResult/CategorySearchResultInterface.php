<?php
/**
 *
 * @category    Branch8
 * @package     Branch8_Blog
 */

namespace Branch8\Blog\Api\Data\SearchResult;

use Magento\Framework\Api\SearchResultsInterface;

/**
 * Interface CategorySearchResultInterface
 * @package Branch8\Blog\Api\Data\SearchResult
 */
interface CategorySearchResultInterface extends SearchResultsInterface
{
    /**
     * @return \Branch8\Blog\Api\Data\CategoryInterface[]
     */
    public function getItems();

    /**
     * @param \Branch8\Blog\Api\Data\CategoryInterface[] $items
     * @return $this
     */
    public function setItems(?array $items = null);
}
