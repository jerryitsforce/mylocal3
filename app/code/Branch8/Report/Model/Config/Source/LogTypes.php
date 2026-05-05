<?php
declare(strict_types=1);

namespace Branch8\Report\Model\Config\Source;

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
            ['value' => 'exceptionlog', 'label' => __('Log(/var/log/exception.log)')],
            ['value' => 'branch8reportlog', 'label' => __('Log(/var/log/branch8_report.log')],
        ];
    }
}
