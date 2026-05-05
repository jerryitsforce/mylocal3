<?php

namespace Branch8\CustomerTicketTable\Model\Config\Source;

use Magento\Framework\Data\OptionSourceInterface;

class LogFileOption implements OptionSourceInterface
{
    /**
     * Return selectable log targets for admin config multiselect.
     *
     * @return array<int, array{value: string, label: \Magento\Framework\Phrase}>
     */
    public function toOptionArray(): array
    {
        return [
            ['value' => 'CheckVirtualOrderTicketStatus', 'label' => __('Cron_CheckVirtualOrderTicketStatus (var/log/CustomerTicketTable/CheckVirtualOrderTicketStatus/Y_m_d.log)')],
            ['value' => 'SetRecordsToOverDue', 'label' => __('Cron_SetRecordsToOverDue (var/log/CustomerTicketTable/SetRecordsToOverDue/Y_m_d.log)')],
            ['value' => 'CustomerTicketRepository', 'label' => __('Model_CustomerTicketRepository (var/log/CustomerTicketTable/CustomerTicketRepository/Y_m_d.log)')],
            ['value' => 'CustomerTicketOverDueRepository', 'label' => __('Model_CustomerTicketOverDueRepository (var/log/CustomerTicketTable/CustomerTicketOverDueRepository/Y_m_d.log)')],
        ];
    }
}

