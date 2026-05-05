<?php
namespace Branch8\MarketPlaceOrderExportRabbitMQ\Model\Config\Source;

class LogOption implements \Magento\Framework\Data\OptionSourceInterface
{
    public function toOptionArray() {
        return [
            ['value' => 'systemlog', 'label' => __('System Log(var/log/system.log)')],
            ['value' => 'exceptionlog', 'label' => __('Exception Log(var/log/exception.log)')],
            ['value' => 'order_notification_download', 'label' => __('Order Notification Download Log(var/log/branch8.order.notification.synchronization.log)')],
            ['value' => 'order_export', 'label' => __('Order Export Log(var/log/branch8.order.export.synchronization.log)')],
        ];
    }
}
