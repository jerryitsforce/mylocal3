<?php
declare(strict_types=1);
/**
 * @project    HotaiConnected
 * @package    Branch8
 * @author     cuongho803@gmail.com
 * @date       22/04/2026
 */

namespace Branch8\MarketPlaceParentOrderEcPayLogisticIntegration\Model\Config\Source;

class LogOption implements \Magento\Framework\Data\OptionSourceInterface
{
    public function toOptionArray() {
        return [
            ['value' => 'marketplaceemaillog', 'label' => __('Log(var/log/marketplace_email.log)')],
        ];
    }
}
