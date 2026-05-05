<?php
namespace Branch8\ProductExportRabbitMQ\Model\Config\Source;

class LogOption implements \Magento\Framework\Data\OptionSourceInterface
{
    public function toOptionArray() {
        return [
            ['value' => 'systemlog', 'label' => __('System Log(var/log/system.log)')],
            ['value' => 'exceptionlog', 'label' => __('Exception Log(var/log/exception.log)')],
            ['value' => 'productexport', 'label' => __('Product Export Log(var/log/branch8.product.export.synchronization.log)')],
        ];
    }
}
