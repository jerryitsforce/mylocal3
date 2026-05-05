<?php

namespace Branch8\WebkulMpsplitorder\Plugin\Magento\SalesRule\Model\Quote;

use Magento\Framework\Registry;
use Magento\Quote\Api\Data\ShippingAssignmentInterface;
use Magento\Quote\Model\Quote;
use Magento\Quote\Model\Quote\Address\Total;

class DiscountPlugin
{
    private $registry;

    /**
     * @param Registry $registry
     */
    public function __construct(
        Registry $registry
    )
    {
        $this->registry = $registry;
    }
    /**
     * @param \Magento\SalesRule\Model\Quote\Discount $subject
     * @param \Closure $proceed
     * @param Quote $quote
     * @param ShippingAssignmentInterface $shippingAssignment
     * @param Total $total
     * @return \Magento\SalesRule\Model\Quote\Discount
     */
    public function aroundCollect(
        \Magento\SalesRule\Model\Quote\Discount $subject,
        \Closure                                $proceed,
        Quote                                   $quote,
        ShippingAssignmentInterface             $shippingAssignment,
        Total                                   $total
    )
    {
        if ($quote->getIgnoreCollectSaleRules() || $this->registry->registry('ignore_calculate_discount')) {
            return $subject;
        }
        return $proceed($quote, $shippingAssignment, $total);
    }
}
