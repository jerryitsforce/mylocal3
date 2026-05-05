<?php
declare(strict_types=1);
/**
 * @project    HotaiConnected
 * @package    Branch8
 * @author     cuongho803@gmail.com
 * @date       25/02/2026
 */

namespace Branch8\MarketPlaceProductDiscussion\Model\ResourceModel\Thread;
class Collection extends \Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection
{
    /**
     * @var string
     */
    protected $_idFieldName = 'thread_id';

    /**
     * Define resource model
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_init(
            \Branch8\MarketPlaceProductDiscussion\Model\Thread::class,
            \Branch8\MarketPlaceProductDiscussion\Model\ResourceModel\Thread::class
        );
    }
}
