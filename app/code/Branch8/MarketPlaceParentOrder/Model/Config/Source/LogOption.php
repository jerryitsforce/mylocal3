<?php
declare(strict_types=1);

namespace Branch8\MarketPlaceParentOrder\Model\Config\Source;

use Magento\Framework\Data\OptionSourceInterface;

/**
 * Provide available log options for Branch8_MarketPlaceParentOrder module.
 */
class LogOption implements OptionSourceInterface
{
    /**
     * Return options for the system configuration multiselect.
     *
     * - label format: "{Type}_{ClassName}"
     * - value format: "{ClassName}"
     *
     * @return array<int, array{value: string, label: \Magento\Framework\Phrase}>
     */
    public function toOptionArray(): array
    {
        return [
            ['value' => 'RegenerateParentOrderData', 'label' => __('Command_RegenerateParentOrderData (var/log/MarketPlaceParentOrder/RegenerateParentOrderData/Y_m_d.log)')],
            ['value' => 'OrderManagement', 'label' => __('Model_OrderManagement (var/log/MarketPlaceParentOrder/OrderManagement/Y_m_d.log)')],
            ['value' => 'LinkParentOrderWithChild', 'label' => __('Model_LinkParentOrderWithChild (var/log/MarketPlaceParentOrder/LinkParentOrderWithChild/Y_m_d.log)')],
            ['value' => 'SubOrderFinder', 'label' => __('Model_SubOrderFinder (var/log/MarketPlaceParentOrder/SubOrderFinder/Y_m_d.log)')],
            ['value' => 'ParentOrderStatusResolver', 'label' => __('Model_ParentOrderStatusResolver (var/log/MarketPlaceParentOrder/ParentOrderStatusResolver/Y_m_d.log)')],
            ['value' => 'UpdateStatusParentOrder', 'label' => __('Observer_UpdateStatusParentOrder (var/log/MarketPlaceParentOrder/UpdateStatusParentOrder/Y_m_d.log)')],
            ['value' => 'ParentOrderSaveCommitComeback', 'label' => __('Observer_ParentOrderSaveCommitComeback (var/log/MarketPlaceParentOrder/ParentOrderSaveCommitComeback/Y_m_d.log)')],
            ['value' => 'NotifySender', 'label' => __('Model_NotifySender (var/log/MarketPlaceParentOrder/NotifySender/Y_m_d.log)')],
        ];
    }
}

