<?php

namespace Branch8\HotaiCore\Model\Config\Source;

/**
 * 對應本模組內透過不同 logger 寫出的 log 位置（相對於 var/log）。
 * 選項 value 供 branch8_debug／DebugLog::isEnable('Branch8_HotaiCore', …) 使用。
 */
class LogOption implements \Magento\Framework\Data\OptionSourceInterface
{
    /** var/log/hotai_core_email_service/{Y_m_d}.log — Service\MailService */
    public const LOG_MAIL_SERVICE = 'hotai_core_mail_service';

    /** var/log/HotaiCore/Helper/TicketRetry/{Y_m_d}.log — Helper\TicketRetry */
    public const LOG_TICKET_RETRY = 'hotai_core_ticket_retry';

    /** var/log/HotaiCore/Observer/CheckImportTypeTicketQuantity/{Y_m_d}.log — Observer\CheckImportTypeTicketQuantity */
    public const LOG_CHECK_IMPORT_TYPE_TICKET_QUANTITY = 'hotai_core_check_import_type_ticket_quantity';

    /** var/log/HotaiCore/Patch/CreateBatchSettingCustomOption/{Y_m_d}.log — Setup\Patch\Data\CreateBatchSettingCustomOption */
    public const LOG_CREATE_BATCH_SETTING_CUSTOM_OPTION = 'hotai_core_create_batch_setting_custom_option';

    public function toOptionArray(): array
    {
        return [
            [
                'value' => self::LOG_MAIL_SERVICE,
                'label' => __('MailService (var/log/hotai_core_email_service/{Y_m_d}.log)'),
            ],
            [
                'value' => self::LOG_TICKET_RETRY,
                'label' => __('TicketRetry (var/log/HotaiCore/Helper/TicketRetry/{Y_m_d}.log)'),
            ],
            [
                'value' => self::LOG_CHECK_IMPORT_TYPE_TICKET_QUANTITY,
                'label' => __(
                    'CheckImportTypeTicketQuantity (var/log/HotaiCore/Observer/CheckImportTypeTicketQuantity/{Y_m_d}.log)'
                ),
            ],
            [
                'value' => self::LOG_CREATE_BATCH_SETTING_CUSTOM_OPTION,
                'label' => __(
                    'CreateBatchSettingCustomOption (var/log/HotaiCore/Patch/CreateBatchSettingCustomOption/{Y_m_d}.log)'
                ),
            ],
        ];
    }
}
