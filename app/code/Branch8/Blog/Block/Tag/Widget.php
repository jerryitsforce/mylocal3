<?php
/**
 *
 * @category    Branch8
 * @package     Branch8_Blog
 */

namespace Branch8\Blog\Block\Tag;

use Exception;
use Magento\Framework\Exception\NoSuchEntityException;
use Branch8\Blog\Block\Frontend;
use Branch8\Blog\Helper\Data;
use Branch8\Blog\Model\ResourceModel\Post\Collection;
use Branch8\Blog\Model\ResourceModel\Author\Collection as AuthorCollection;
use Branch8\Blog\Model\ResourceModel\Category\Collection as CategoryCollection;
use Branch8\Blog\Model\ResourceModel\Tag\Collection as TagCollection;
use Branch8\Blog\Model\ResourceModel\Topic\Collection as TopicCollection;
use Branch8\Blog\Model\Tag;

/**
 * Class Widget
 * @package Branch8\Blog\Block\Tag
 */
class Widget extends Frontend
{
    /**
     * @var TagCollection
     */
    protected $_tagList;

    /**
     * @return AuthorCollection|CategoryCollection|Collection|TagCollection|TopicCollection|null
     */
    public function getTagList()
    {
        try {
            if (!$this->_tagList) {
                $this->_tagList = $this->helperData->getObjectList(Data::TYPE_TAG);
            }

            return $this->_tagList;
        } catch (Exception $e) {
            return null;
        }
    }

    /**
     * @param Tag $tag
     *
     * @return string
     */
    public function getTagUrl($tag)
    {
        return $this->helperData->getBlogUrl($tag, Data::TYPE_TAG);
    }

    /**
     * Get tags size based on num of post
     *
     * @param $tag
     *
     * @return false|float|int
     * @throws NoSuchEntityException
     */
    public function getTagSize($tag)
    {
        /** @var Collection $postList */
        $postList = $this->helperData->getPostList();
        if ($postList && ($max = $postList->getSize()) > 1) {
            $maxSize = 22;
            $tagPost = $this->helperData->getPostCollection(Data::TYPE_TAG, $tag->getId());
            if ($tagPost && ($countTagPost = $tagPost->getSize()) > 1) {
                $size = $maxSize * $countTagPost / $max;

                return round($size) + 8;
            }
        }

        return 8;
    }
}
