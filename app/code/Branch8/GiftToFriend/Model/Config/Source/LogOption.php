<?php
namespace Branch8\GiftToFriend\Model\Config\Source;

class LogOption implements \Magento\Framework\Data\OptionSourceInterface
{
    public function toOptionArray() {
        return [
            ['value' => 'quote_set_gift_order', 'label' => __('Quote Set Gift Order Log(var/log/gift_order.log)')],
            ['value' => 'sale_presentative_api', 'label' => __('Sale Presentative API Log(var/log/sales_presentative_api.log)')],
            ['value' => 'push_sale_presentative_order_status', 'label' => __('Push Sale Presentative GiftOrder To Hotai Log(var/log/cancel_parent.log)')],
            ['value' => 'cancel_gift_order_not_confirmed', 'label' => __('Quote Set Gift Order Log(var/log/GiftToFriend/Cron/CancelNotConfirmedOrders/{Y_m_d}.log)')],
            ['value' => 'giftbox_update_last_access', 'label' => __('Last Access Time Update Exception(var/log/GiftToFriend/Helper/GiftBox/{Y_m_d}.log)')],
        ];
    }
}



