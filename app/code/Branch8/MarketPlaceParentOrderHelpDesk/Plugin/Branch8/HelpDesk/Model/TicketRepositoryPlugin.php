<?php
declare(strict_types=1);


namespace Branch8\MarketPlaceParentOrderHelpDesk\Plugin\Branch8\HelpDesk\Model;

class TicketRepositoryPlugin
{
    public function beforeSave($subject, $ticket)
    {
        if (is_array($ticket->getData('parent_order'))) {
            $ticket->setData('parent_order', implode(',', $ticket->getData('parent_order')));
        }
        return [$ticket];
    }
}
