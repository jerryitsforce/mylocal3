<?php
declare(strict_types=1);

namespace Branch8\HelpDesk\Model\Ticket;

use Branch8\HelpDesk\Model\ResourceModel\Message;
use Magento\Framework\Model\ResourceTest;

/**
 * Message Actions Class
 */
class MessageActions
{
    /**
     * @var \Magento\Framework\App\ResourceConnection
     */
    private $resource;

    /**
     * @param \Magento\Framework\App\ResourceConnection $resource
     */
    public function __construct(
        \Magento\Framework\App\ResourceConnection $resource
    )
    {
        $this->resource = $resource;
    }

    /**
     * Mark all message read
     * @param array $messageIds
     * @return void
     */
    private function markAsRead(array $messageIds = [])
    {
        if (count($messageIds)) {
            $connection = $this->resource->getConnection();
            $table = $connection->getTableName('branch8_helpdesk_message');
            $where = ['message_id' . ' IN (?)' => $messageIds];
            $connection->update(
                $table,
                ['is_read' => 1],
                $where
            );
        }
    }

    /**
     * Mask Message Read for type Customer Or Admin
     * @param array $searchResult
     * @param $type
     * @return void
     */
    public function proccessMaskRead(array $searchResult, $type)
    {
        if ($type == BelongTo::CUSTOMER) {
            $notIn = [BelongTo::CUSTOMER];
        } else {
            $notIn = [BelongTo::USER, BelongTo::ANOMYNOUS];
        }
        if (count($searchResult)) {
            $notRead = [];
            foreach ($searchResult as $item) {
                if (!in_array($item['belong_to'], $notIn)
                    && $item['is_read'] == 0
                ) {
                    $notRead[] = $item['message_id'];
                }
            }
            if ($notRead) {
                $this->markAsRead($notRead);
            }
        }
    }
}
