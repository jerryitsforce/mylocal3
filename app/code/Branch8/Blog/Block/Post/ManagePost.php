<?php
/**
 *
 * @category    Branch8
 * @package     Branch8_Blog
 */

namespace Branch8\Blog\Block\Post;

use Branch8\Blog\Block\Frontend;
use Branch8\Blog\Helper\Data;

/**
 * Class ManagePost
 * @package Branch8\Blog\Block\Post
 */
class ManagePost extends Frontend
{
    /**
     * @return string
     */
    public function getCategoriesTree()
    {
        return Data::jsonEncode($this->categoryOptions->getCategoriesTree());
    }

    /**
     * @return string
     */
    public function getTopicTree()
    {
        return Data::jsonEncode($this->topicOptions->getTopicsCollection());
    }

    /**
     * @return string
     */
    public function getTagTree()
    {
        return Data::jsonEncode($this->tagOptions->getTagsCollection());
    }
}
