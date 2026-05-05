<?php
declare(strict_types=1);

namespace Branch8\HelpDesk\Api;

use Branch8\HelpDesk\Api\Data\MessageInterface;

/**
 * Message CRUD interface
 */
interface MessageRepositoryInterface
{
    /**
     * .
     * @param \Magento\Framework\Api\SearchCriteriaInterface $searchCriteria
     * @return \Branch8\HelpDesk\Api\Data\MessageSearchResultInterface
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function getList(
        \Magento\Framework\Api\SearchCriteriaInterface $searchCriteria
    );

    /**
     * @param MessageInterface $message
     * @return MessageInterface
     */
    public function save(MessageInterface $message);

    /**
     * @param int $id
     * @return MessageInterface
     */
    public function get(int $id);
}
