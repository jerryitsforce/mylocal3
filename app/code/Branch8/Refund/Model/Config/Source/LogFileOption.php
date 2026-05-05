<?php

declare(strict_types=1);

namespace Branch8\Refund\Model\Config\Source;

use Magento\Framework\Data\OptionSourceInterface;

/**
 * Multiselect options for Branch8_Refund debug file logging (Stores > Configuration > branch8_debug).
 */
class LogFileOption implements OptionSourceInterface
{
    /**
     * Option rows: display name prefix (e.g. Command_RefundCron) and config/storage value (short class name).
     */
    private const OPTION_ROWS = [
        ['label' => 'Command_RefundCron', 'value' => 'RefundCron'],
        ['label' => 'Command_RefundProcessor', 'value' => 'RefundProcessor'],
        ['label' => 'Command_RefundManualApi', 'value' => 'RefundManualApi'],
        ['label' => 'Command_SendRefundResultEmail', 'value' => 'SendRefundResultEmail'],
        ['label' => 'Cron_RefundStatusCheckInquiry', 'value' => 'RefundStatusCheckInquiry'],
        ['label' => 'Cron_RefundScheduleExecute', 'value' => 'RefundScheduleExecute'],
        ['label' => 'Helper_RefundOperation', 'value' => 'RefundOperation'],
        ['label' => 'Helper_Email', 'value' => 'Email'],
        ['label' => 'Helper_CancelTicket', 'value' => 'CancelTicket'],
        ['label' => 'Helper_CreateCreditMemo', 'value' => 'CreateCreditMemo'],
        ['label' => 'Helper_CreditmemoRefundData', 'value' => 'CreditmemoRefundData'],
        ['label' => 'Observer_CancelObserver', 'value' => 'CancelObserver'],
        ['label' => 'Observer_CancelPaidOrderObserver', 'value' => 'CancelPaidOrderObserver'],
        ['label' => 'Observer_CancellationCreditMemo', 'value' => 'CancellationCreditMemo'],
        ['label' => 'Observer_SetCreditmemoRefundData', 'value' => 'SetCreditmemoRefundData'],
        ['label' => 'Observer_LogCreditmemoCreatorAfterCommit', 'value' => 'LogCreditmemoCreatorAfterCommit'],
        ['label' => 'Plugin_Creditmemo', 'value' => 'Creditmemo'],
        ['label' => 'Model_SalesRefundRepository', 'value' => 'SalesRefundRepository'],
    ];

    /**
     * Path template segment shown in admin labels (actual file uses PHP date Y_m_d).
     */
    private const PATH_TEMPLATE = '(var/log/Refund/%s/{Y_m_d}.log)';

    /**
     * Return multiselect options: label includes human-readable log path template; value is the class key stored in config.
     *
     * @return array<int, array{value: string, label: \Magento\Framework\Phrase}>
     */
    public function toOptionArray(): array
    {
        $options = [];
        foreach (self::OPTION_ROWS as $row) {
            $pathSuffix = sprintf(self::PATH_TEMPLATE, $row['value']);
            $options[] = [
                'value' => $row['value'],
                'label' => __($row['label'] . ' ' . $pathSuffix),
            ];
        }

        return $options;
    }
}
