<?php
declare(strict_types=1);

namespace Branch8\WebkulMpBuyerSellerChatForParentOrder\Model\Sales\Config\Source\Order;
/**
 * Status Order Config Class
 */
class Status extends \Magento\Sales\Model\Config\Source\Order\Status
{
    /**
     * @return array|array[]
     */
    public function toOptionArray()
    {
        $statuses = $this->_orderConfig->getStatuses();
        $options = [['value' => '', 'label' => __('-- Please Select --')]];
        foreach ($statuses as $code => $label) {
            $options[] = ['value' => $code, 'label' => $label];
        }
        return $options;
    }
}
