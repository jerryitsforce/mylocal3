<?php
declare(strict_types=1);
/**
 * @project    HotaiConnected
 * @package    Branch8
 * @author     cuongho803@gmail.com
 * @date       25/02/2026
 */

namespace Branch8\MarketPlaceProductDiscussion\Api;
use Branch8\MarketPlaceProductDiscussion\Api\Data\MessageInterface;
use Magento\Framework\Api\SearchCriteriaInterface;
interface MessageRepositoryInterface
{
    public function save(MessageInterface $message);

    public function getById($messageId);

    public function getList(SearchCriteriaInterface $searchCriteria);

    public function delete(MessageInterface $message);

    public function deleteById($messageId);
}
