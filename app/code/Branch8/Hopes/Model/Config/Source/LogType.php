<?php
declare(strict_types=1);

namespace Branch8\Hopes\Model\Config\Source;

use Magento\Framework\Data\OptionSourceInterface;

/**
 * Provide available log types for Branch8_Hopes module.
 *
 * The value format is "{Group}_{ClassName}", e.g. "Model_TCatEodManagement".
 * The log file folder will be mapped to "{ClassName}".
 */
class LogType implements OptionSourceInterface
{
    /**
     * Return options for adminhtml multiselect.
     *
     * @return array<int, array{value:string, label:\Magento\Framework\Phrase}>
     */
    public function toOptionArray(): array
    {
        return [
            ['value' => 'SendRmaToHopes', 'label' => __('Cron_SendRmaToHopes (var/log/Hopes/SendRmaToHopes/Y_m_d.log)')],

            // Models
            ['value' => 'CreateRmaManagement', 'label' => __('Model_CreateRmaManagement (var/log/Hopes/CreateRmaManagement/Y_m_d.log)')],
            ['value' => 'OrderStackDetailsManagement', 'label' => __('Model_OrderStackDetailsManagement (var/log/Hopes/OrderStackDetailsManagement/Y_m_d.log)')],
            ['value' => 'OrderStackShipmentSinManagement', 'label' => __('Model_OrderStackShipmentSinManagement (var/log/Hopes/OrderStackShipmentSinManagement/Y_m_d.log)')],
            ['value' => 'RmaDetailsManagement', 'label' => __('Model_RmaDetailsManagement (var/log/Hopes/RmaDetailsManagement/Y_m_d.log)')],
            ['value' => 'RmaVoidInvoiceManagement', 'label' => __('Model_RmaVoidInvoiceManagement (var/log/Hopes/RmaVoidInvoiceManagement/Y_m_d.log)')],
            ['value' => 'TCatCreateRmaShippingNumberManagement', 'label' => __('Model_TCatCreateRmaShippingNumberManagement (var/log/Hopes/TCatCreateRmaShippingNumberManagement/Y_m_d.log)')],
            ['value' => 'TCatEodManagement', 'label' => __('Model_TCatEodManagement (var/log/Hopes/TCatEodManagement/Y_m_d.log)')],
            ['value' => 'TCatSodManagement', 'label' => __('Model_TCatSodManagement (var/log/Hopes/TCatSodManagement/Y_m_d.log)')],
            ['value' => 'UpdateReturnStatusManagement', 'label' => __('Model_UpdateReturnStatusManagement (var/log/Hopes/UpdateReturnStatusManagement/Y_m_d.log)')],
            ['value' => 'UpdateShipmentEinManagement', 'label' => __('Model_UpdateShipmentEinManagement (var/log/Hopes/UpdateShipmentEinManagement/Y_m_d.log)')],
            ['value' => 'UpdateShipmentEtaManagement', 'label' => __('Model_UpdateShipmentEtaManagement (var/log/Hopes/UpdateShipmentEtaManagement/Y_m_d.log)')],
            ['value' => 'UpdateShipmentPPSManagement', 'label' => __('Model_UpdateShipmentPPSManagement (var/log/Hopes/UpdateShipmentPPSManagement/Y_m_d.log)')],
            ['value' => 'UpdateShipmentSrpManagement', 'label' => __('Model_UpdateShipmentSrpManagement (var/log/Hopes/UpdateShipmentSrpManagement/Y_m_d.log)')],
            ['value' => 'UpdateShippingTrackNumberManagement', 'label' => __('Model_UpdateShippingTrackNumberManagement (var/log/Hopes/UpdateShippingTrackNumberManagement/Y_m_d.log)')],

            // Helpers
            ['value' => 'Email', 'label' => __('Helper_Email (var/log/Hopes/Email/Y_m_d.log)')],
        ];
    }
}

