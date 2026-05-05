<?php
/**
 *
 * @category    Branch8
 * @package     Branch8_Blog
 */

namespace Branch8\Blog\Api\Data\SearchResult;

use Magento\Framework\Api\SearchResultsInterface;

/**
 * Interface TagSearchResultInterface
 * @package Branch8\Blog\Api\Data\SearchResult
 */
interface TagSearchResultInterface extends SearchResultsInterface
{
    /**
     * @return \Branch8\Blog\Api\Data\TagInterface[]
     */
    public function getItems();

    /**
     * @param \Branch8\Blog\Api\Data\TagInterface[] $items
     * @return $this
     */
    public function setItems(?array $items = null);
}
