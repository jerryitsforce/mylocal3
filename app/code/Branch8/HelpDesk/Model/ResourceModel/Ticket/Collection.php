<?php
declare(strict_types=1);

namespace Branch8\HelpDesk\Model\ResourceModel\Ticket;

use Branch8\HelpDesk\Model\Ticket;

/**
 * Ticket Grid Form
 */
class Collection extends \Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection
{
    /**
     * @var string
     */
    protected $_idFieldName = 'ticket_id';

    /**
     * Standard collection initialization.
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_init(Ticket::class, \Branch8\HelpDesk\Model\ResourceModel\Ticket::class);
    }
}
