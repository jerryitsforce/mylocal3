<?php
declare(strict_types=1);

namespace Branch8\HelpDesk\Model\Ticket;

use Branch8\HelpDesk\Api\Data\GuardTicketInterface;

/**
 * class Authorize
 */
class Authorize
{
    private $customerSession;

    /**
     * @param \Magento\Customer\Model\Session\Proxy $customerSession
     */
    public function __construct(
        \Magento\Customer\Model\Session\Proxy $customerSession
    )
    {
        $this->customerSession = $customerSession;
    }

    /**
     * @param GuardTicketInterface $ticket
     * @return bool
     */
    public function authorize(GuardTicketInterface $ticket)
    {
        return $this->customerSession->getCustomerId() == $ticket->getCustomerId();
    }
}
