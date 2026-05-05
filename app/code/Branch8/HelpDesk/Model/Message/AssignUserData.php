<?php
declare(strict_types=1);

namespace Branch8\HelpDesk\Model\Message;

use Branch8\HelpDesk\Model\Message;
use Magento\User\Model\User;

/**
 * Assign User Class
 */
class AssignUserData
{
    /**
     * @param Message $message
     * @param User $user
     * @return Message
     */
    public function assign(Message $message, User $user)
    {
        $message->setData('user_id', (int)$user->getId());
        $message->setData('user_email', $user->getEmail());
        $message->setData('user_name', $user->getName());
        return $message;
    }
}
