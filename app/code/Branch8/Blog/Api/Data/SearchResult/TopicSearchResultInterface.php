<?php
/**
 *
 * @category    Branch8
 * @package     Branch8_Blog
 */

namespace Branch8\Blog\Api\Data\SearchResult;

use Magento\Framework\Api\SearchResultsInterface;

/**
 * Interface TopicSearchResultInterface
 * @package Branch8\Blog\Api\Data\SearchResult
 */
interface TopicSearchResultInterface extends SearchResultsInterface
{
    /**
     * @return \Branch8\Blog\Api\Data\TopicInterface[]
     */
    public function getItems();

    /**
     * @param \Branch8\Blog\Api\Data\TopicInterface[] $items
     * @return $this
     */
    public function setItems(?array $items = null);
}
