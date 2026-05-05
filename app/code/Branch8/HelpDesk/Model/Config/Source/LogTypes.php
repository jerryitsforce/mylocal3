<?php
declare(strict_types=1);

namespace Branch8\HelpDesk\Model\Config\Source;

use Magento\Framework\Data\OptionSourceInterface;

/**
 * Log types source model
 */
class LogTypes implements OptionSourceInterface
{
    /**
     * @return array
     */
    public function toOptionArray(): array
    {
        return [
            ['value' => 'branch8_helpdesk', 'label' => __('branch8_helpdesk.log')]
        ];
    }
}
