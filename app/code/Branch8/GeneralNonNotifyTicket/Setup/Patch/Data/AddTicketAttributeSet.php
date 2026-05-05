<?php

namespace Branch8\GeneralNonNotifyTicket\Setup\Patch\Data;

use Branch8\HotaiCore\Helper\CreateTicketAttributeSet as CreateTicketAttributeSetHelper;
use Branch8\HotaiCore\Model\Ticket\AttributeCodes as TicketAttributeCodes;

use Magento\Framework\Setup\Patch\DataPatchInterface;

class AddTicketAttributeSet implements DataPatchInterface
{
    const NEW_ATTRIBUTE_SET_NAME = "ticket_non_redeem";

    /** @var CreateTicketAttributeSetHelper */
    protected $createTicketAttributeSetHelper;

    public function __construct(
        CreateTicketAttributeSetHelper $createTicketAttributeSetHelper
    ) {
        $this->createTicketAttributeSetHelper = $createTicketAttributeSetHelper;
    }

    public function apply()
    {
        $this->createTicketAttributeSetHelper->execute(
            self::NEW_ATTRIBUTE_SET_NAME,
            TicketAttributeCodes::SKIP_CODES_GENERAL_NON_NOTIFY_TICKET
        );
    }

    /**
     * {@inheritdoc}
     */
    public static function getDependencies()
    {
        return [];
    }

    /**
     * {@inheritdoc}
     */
    public function getAliases()
    {
        return [];
    }

    /**
     * {@inheritdoc}
     */
    public static function getVersion()
    {
        return '1.0.0';
    }
}