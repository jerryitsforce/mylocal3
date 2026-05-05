<?php
declare(strict_types=1);

namespace Branch8\HelpDesk\Model\Message;

use Magento\Customer\Model\Customer;
use Branch8\HelpDesk\Model\Message;

/**
 * Assign Customer Data to Message
 */
class AssignCustomerData
{
    /**
     * @param Message $message
     * @param Customer $customer
     * @return Message
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function assign(Message $message, Customer $customer)
    {
        $message->setData('customer_id', (int)$customer->getId());
        $message->setData('customer_email', $customer->getEmail());
        $message->setData('customer_name', $customer->getName());
        return $message;
    }
}
