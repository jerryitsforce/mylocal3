<?php
/**
 *
 * @category    Branch8
 * @package     Branch8_Blog
 */

namespace Branch8\Blog\Block\Topic;

use Exception;
use Branch8\Blog\Model\ResourceModel\Author\Collection as AuthorCollection;
use Branch8\Blog\Model\ResourceModel\Category\Collection as CategoryCollection;
use Branch8\Blog\Model\ResourceModel\Post\Collection;
use Branch8\Blog\Model\ResourceModel\Tag\Collection as TagCollection;
use Branch8\Blog\Model\ResourceModel\Topic\Collection as TopicCollection;
use Branch8\Blog\Block\Frontend;
use Branch8\Blog\Helper\Data;
use Branch8\Blog\Model\Topic;

/**
 * Class Widget
 * @package Branch8\Blog\Block\Topic
 */
class Widget extends Frontend
{
    /**
     * @return AuthorCollection|CategoryCollection|Collection|TagCollection|TopicCollection|null
     */
    public function getTopicList()
    {
        try {
            return $this->helperData->getObjectList(Data::TYPE_TOPIC);
        } catch (Exception $e) {
            return null;
        }
    }

    /**
     * @param Topic $topic
     *
     * @return string
     */
    public function getTopicUrl($topic)
    {
        return $this->helperData->getBlogUrl($topic, Data::TYPE_TOPIC);
    }
}
