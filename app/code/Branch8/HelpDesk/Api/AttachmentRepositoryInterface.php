<?php
declare(strict_types=1);

namespace Branch8\HelpDesk\Api;

use Branch8\HelpDesk\Api\Data\AttachmentInterface;
use Branch8\HelpDesk\Api\Data\AttachmentSearchResultInterface;
use Branch8\HelpDesk\Api\Data\TicketInterface;

/**
 * Attachment CRUD interface
 */
interface AttachmentRepositoryInterface
{
    /**
     * @param int $messageId
     * @param AttachmentInterface $attachment
     * @return bool
     */
    public function assignAttachmentToMessage(int $messageId, AttachmentInterface $attachment);

    /**
     * @param \Magento\Framework\Api\SearchCriteriaInterface $searchCriteria
     * @return AttachmentSearchResultInterface
     */
    public function getList(\Magento\Framework\Api\SearchCriteriaInterface $searchCriteria);
}
