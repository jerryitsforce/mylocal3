<?php

namespace Branch8\Repayment\Model\Config\Source;

use Magento\Framework\Data\OptionSourceInterface;

/**
 * Provide admin options for repayment log files.
 */
class LogOption implements OptionSourceInterface
{
    /**
     * Controller log option value for checkout processor.
     */
    public const COMMAND_PROCESSOR = 'Processor';

    /**
     * Controller log option value for checkout verify.
     */
    public const COMMAND_VERIFY = 'Verify';

    /**
     * Model log option value for order management.
     */
    public const MODEL_ORDER_MANAGEMENT = 'OrderManagement';

    /**
     * Helper log option value for data helper.
     */
    public const HELPER_DATA = 'Data';

    /**
     * Block log option value for checkout processor block.
     */
    public const BLOCK_PROCESSOR = 'ProcessorBlock';

    /**
     * Return selectable log options.
     *
     * @return array<int, array<string, string>>
     */
    public function toOptionArray(): array
    {
        return [
            [
                'value' => self::COMMAND_PROCESSOR,
                'label' => __('Command_Processor (var/log/Repayment/Processor/{Y_m_d}.log)'),
            ],
            [
                'value' => self::COMMAND_VERIFY,
                'label' => __('Command_Verify (var/log/Repayment/Verify/{Y_m_d}.log)'),
            ],
            [
                'value' => self::MODEL_ORDER_MANAGEMENT,
                'label' => __('Model_OrderManagement (var/log/Repayment/OrderManagement/{Y_m_d}.log)'),
            ],
            [
                'value' => self::HELPER_DATA,
                'label' => __('Helper_Data (var/log/Repayment/Data/{Y_m_d}.log)'),
            ],
            [
                'value' => self::BLOCK_PROCESSOR,
                'label' => __('Block_Processor (var/log/Repayment/ProcessorBlock/{Y_m_d}.log)'),
            ],
        ];
    }
}
