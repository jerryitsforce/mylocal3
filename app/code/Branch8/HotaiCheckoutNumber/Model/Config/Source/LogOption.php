<?php

namespace Branch8\HotaiCheckoutNumber\Model\Config\Source;

/**
 * 對應本模組內透過 HotaiCore Common::writeLog 寫出的 log 位置（相對於 var/log）。
 * 選項 value 供 branch8_debug／DebugLog::isEnable('Branch8_HotaiCheckoutNumber', …) 使用。
 */
class LogOption implements \Magento\Framework\Data\OptionSourceInterface
{
    /** var/log/HotaiCheckoutNumber/Observer/SetCheckoutNumberForNoMoney/{Y_m_d}.log */
    public const LOG_OBSERVER_SET_CHECKOUT_NUMBER_FOR_NO_MONEY =
        'hotai_checkout_number_observer_set_checkout_number_for_no_money';

    /** var/log/HotaiCheckoutNumber/Plugin/SetCheckoutNumberAfterEcpayInvoice/{Y_m_d}.log */
    public const LOG_PLUGIN_SET_CHECKOUT_NUMBER_AFTER_ECPAY_INVOICE =
        'hotai_checkout_number_plugin_set_checkout_number_after_ecpay_invoice';

    public function toOptionArray(): array
    {
        return [
            [
                'value' => self::LOG_OBSERVER_SET_CHECKOUT_NUMBER_FOR_NO_MONEY,
                'label' => __(
                    'Observer SetCheckoutNumberForNoMoney (var/log/HotaiCheckoutNumber/Observer/SetCheckoutNumberForNoMoney/{Y_m_d}.log)'
                ),
            ],
            [
                'value' => self::LOG_PLUGIN_SET_CHECKOUT_NUMBER_AFTER_ECPAY_INVOICE,
                'label' => __(
                    'Plugin SetCheckoutNumberAfterEcpayInvoice (var/log/HotaiCheckoutNumber/Plugin/SetCheckoutNumberAfterEcpayInvoice/{Y_m_d}.log)'
                ),
            ],
        ];
    }
}
