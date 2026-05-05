<?php
declare(strict_types=1);

namespace Branch8\MarketPlaceParentOrderHelpDesk\Plugin\Branch8\HelpDesk\Model;

use Branch8\HelpDesk\Model\Ticket;


/**
 * Email Notification
 */
class EmailNotificationPlugin
{
    /**
     * @var
     */
    private $ticketOrderIds = [];
    /**
     * @var \Magento\Framework\DB\Adapter\AdapterInterface
     */
    private \Magento\Framework\DB\Adapter\AdapterInterface $connection;

    /**
     * @param \Magento\Framework\App\ResourceConnection $resource
     */
    public function __construct(
        \Magento\Framework\App\ResourceConnection $resource
    )
    {
        $this->connection = $resource->getConnection();
    }

    /**
     * @param $subject
     * @param callable $process
     * @param Ticket $ticket
     * @return mixed|string
     */
    public function aroundGetOrdersIncrementId($subject, callable $process, Ticket $ticket)
    {
        if (isset($this->ticketOrderIds[$ticket->getId()])) {
            return $this->ticketOrderIds[$ticket->getId()];
        }
        try {
            $orderIds = explode(',', (string)$ticket->getParentOrder());
            if ($orderIds) {
                $table = 'sales_parent_order_detail';
                $select = $this->connection->select();
                $select->from($table, ['increment_id'])->where('parent_id in (?)', $orderIds);
                $orderIds = $this->connection->fetchCol($select);
            }

        } catch (\Exception $exception) {
            $orderIds = [];
        }
        if ($orderIds) {
            $orderIds = array_map(function ($value) {
                return "#" . $value;
            }, $orderIds);
            $this->ticketOrderIds[$ticket->getId()] = join(',', $orderIds);
        } else {
            $this->ticketOrderIds[$ticket->getId()] = '';
        }
        return $this->ticketOrderIds[$ticket->getId()];
    }
}
