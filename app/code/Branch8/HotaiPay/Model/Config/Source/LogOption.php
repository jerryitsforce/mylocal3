<?php

namespace Branch8\HotaiPay\Model\Config\Source;

use Magento\Framework\Data\OptionSourceInterface;

class LogOption implements OptionSourceInterface
{
    /**
     * Build selectable log file options.
     *
     * @return array<int, array<string, string>>
     */
    public function toOptionArray(): array
    {
        $classMap = [
            'Command' => ['Test'],
            'Model' => ['AbstractModel', 'Payment', 'Update', 'Ajax', 'MessageRepository', 'CreditcardRepository'],
            'Helper' => ['OrderManagement', 'CurlApi', 'CacheLock'],
            'Cron' => [],
            'Block' => ['Fail'],
            'Controller' => ['Checkout', 'Response', 'Add', 'FastAdd'],
            'Observer' => ['DataAssignObserver', 'ParentOrderPaymentData'],
        ];

        $options = [];
        foreach ($classMap as $type => $classes) {
            foreach ($classes as $className) {
                $options[] = [
                    'label' => sprintf(
                        '%s_%s (var/log/HotaiPay/%s/Y_m_d.log)',
                        $type,
                        $className,
                        $className
                    ),
                    'value' => $className,
                ];
            }
        }

        return $options;
    }
}
