<?php
declare(strict_types=1);

namespace Branch8\HelpDesk\Model\Ticket;

use Branch8\HelpDesk\Model\Ticket;
use Magento\User\Model\User;
use Magento\User\Model\UserFactory;

/**
 * Class AssignTicketToUser
 */
class AssignTicketToUser
{
    private $userFactory;

    /**
     * @param UserFactory $userFactory
     */
    public function __construct(
        UserFactory $userFactory
    )
    {
        $this->userFactory = $userFactory;
    }

    /**
     * @param Ticket $ticket
     * @param $user
     * @return Ticket
     */
    public function assign(Ticket $ticket, $user)
    {
        if ($user instanceof User) {
            $ticket->setData('user_id', (int)$user->getId());
            $ticket->setData('user_email', $user->getEmail());
            $ticket->setData('user_name', $user->getName());
        } else {
            $user = $this->userFactory->create()->load($user);
            if ($user) {
                $ticket->setData('user_id', (int)$user->getId());
                $ticket->setData('user_email', $user->getEmail());
                $ticket->setData('user_name', $user->getName());
            }
        }
        return $ticket;
    }

}
