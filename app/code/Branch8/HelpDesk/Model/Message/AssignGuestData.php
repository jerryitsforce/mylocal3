<?php
declare(strict_types=1);

namespace Branch8\HelpDesk\Model\Message;

use Branch8\HelpDesk\Model\Message;

/**
 * Assign Guest Data to Message
 */
class AssignGuestData
{
    /**
     * @param Message $message
     * @param array $customer
     * @return Message
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function assign(Message $message, array $customer)
    {
        $message->setData('customer_id', null);
        $message->setData('customer_email', $customer['email']);
        $message->setData('customer_name', $customer['name']);
        return $message;
    }
}
