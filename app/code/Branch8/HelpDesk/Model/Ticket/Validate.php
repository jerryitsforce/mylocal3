<?php
declare(strict_types=1);


namespace Branch8\HelpDesk\Model\Ticket;

use Branch8\HelpDesk\Model\Ticket;
use Laminas\Validator\EmailAddress;
use Laminas\Validator\InArray;
use Magento\Framework\Validator\NotEmpty;
use Magento\Framework\Validator\StringLength;
use Magento\Framework\Validator\ValidatorChain;

/**
 * Class Validate
 */
class Validate
{
    private $priority;

    /**
     * @param Priority $priority
     */
    public function __construct(Priority $priority)
    {
        $this->priority = $priority;
    }

    /**
     * Validate
     * @param Ticket $ticket
     * @return array
     * @throws \Magento\Framework\Validator\ValidateException
     */
    public function validate(Ticket $ticket)
    {
        $errors = [];
        $priorityValidValues = array_keys($this->priority->toOptions());
        if (!ValidatorChain::is($ticket->getTitle(), NotEmpty::class)) {
            $errors[] = __('"%fieldName" is required. Enter and try again.', ['fieldName' => 'Title']);
        }
        if (!ValidatorChain::is($ticket->getPhone(), NotEmpty::class)) {
            $errors[] = __('"%fieldName" is required. Enter and try again.', ['fieldName' => 'Phone']);
        }

        if (!ValidatorChain::is($ticket->getCategoryId(), NotEmpty::class)) {
            $errors[] = __('"%fieldName" is required. Enter and try again.', ['fieldName' => 'Category']);
        }
        if (!ValidatorChain::is($ticket->getCustomerEmail(), EmailAddress::class)) {
            $errors[] = __('"%fieldName" is not valid.', ['fieldName' => 'Email']);
        }

        if (!ValidatorChain::is($ticket->getCustomerName(), NotEmpty::class)) {
            $errors[] = __('"%fieldName" is not valid.', ['fieldName' => 'Customer Name']);
        }

        if (!ValidatorChain::is($ticket->getContent(), NotEmpty::class)) {
            $errors[] = __('"%fieldName" is required. Enter and try again.', ['fieldName' => 'Message']);
        } elseif (!ValidatorChain::is($ticket->getContent(), StringLength::class, [
            'min' => 1,
            'max' => 2048
        ])) {
            $errors[] = __(
                '"%fieldName" not valid,the value is not within the specified range [1-2048]',
                ['fieldName' => 'Message']
            );
        }
        return $errors;
    }


}
