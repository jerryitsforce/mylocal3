<?php
declare(strict_types=1);

namespace Branch8\HelpDesk\Model\Ticket;

class FrontendStatus extends Status
{
    /**
     * ToOptionArray
     * @return array[]
     */
    public function toOptionArray()
    {
        return [
            ['label' => __('Unprocessed'), 'value' => self::UNPROCESSED],
            ['label' => __('Customer Service Replied'), 'value' => self::CSR_REPLIED],

        ];
    }

    /**
     * toOptions
     * @return array
     */
    public function toOptions()
    {
        return [
            self::UNPROCESSED => __('Unprocessed'),
            self::PROCESSING => __('Processing'),
            self::PROCESSED => __('Processed'),
            self::CSR_REPLIED => __('Customer Service Replied')
        ];
    }

}
