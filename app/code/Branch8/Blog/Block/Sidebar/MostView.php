<?php
/**
 *
 * @category    Branch8
 * @package     Branch8_Blog
 */

namespace Branch8\Blog\Block\Sidebar;

use Branch8\Blog\Block\Frontend;
use Branch8\Blog\Model\ResourceModel\Post\Collection;

/**
 * Class MostView
 * @package Branch8\Blog\Block\Sidebar
 */
class MostView extends Frontend
{
    /**
     * @return Collection
     */
    public function getMostViewPosts()
    {
        $collection = $this->helperData->getPostList();
        $collection->getSelect()
            ->joinLeft(
                ['traffic' => $collection->getTable('branch8_blog_post_traffic')],
                'main_table.post_id=traffic.post_id',
                'numbers_view'
            )
            ->order('numbers_view DESC')
            ->limit((int)$this->helperData->getBlogConfig('sidebar/recent_post/number_mostview_posts') ?: 4);

        return $collection;
    }

    /**
     * @return Collection
     */
    public function getRecentPost()
    {
        $collection = $this->helperData->getPostList();
        $collection->getSelect()
            ->limit((int)$this->helperData->
            getBlogConfig('sidebar/recent_post/number_recent_posts') ?: 4);

        return $collection;
    }
}
