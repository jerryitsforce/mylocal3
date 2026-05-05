<?php
declare(strict_types=1);
/**
 * @project    HotaiConnected
 * @package    Branch8
 * @author     cuongho803@gmail.com
 * @date       25/02/2026
 */

namespace Branch8\MarketPlaceProductDiscussion\Api\Data;

interface ThreadInterface
{
    const STATUS_APPROVED = 'approved';

    const STATUS_PENDING = 'pending';
    const STATUS_REJECTED = 'rejected';
    const STATUS_CLOSED = 'closed';
    const THREAD_TYPE_QUESTION = 'question';
    const THREAD_TYPE_ANSWER = 'answer';
    const THREAD_TYPE_GENERAL = 'general';
    const THREAD_AUTHOR_TYPE_CUSTOMER = 'customer';
    const THREAD_AUTHOR_TYPE_SELLER = 'author';
}
