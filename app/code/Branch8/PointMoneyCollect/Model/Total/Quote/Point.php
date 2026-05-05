<?php
namespace Branch8\PointMoneyCollect\Model\Total\Quote;

use Branch8\PointMoneyConfig\Helper\Common as PointMoneyConfigCommon;
use Branch8\PointMoneyConfig\Model\Product\PointMoneyConfigFreeRatioRedeemLimitType;
use Magento\Quote\Api\Data\ShippingAssignmentInterface;
use Magento\Quote\Model\Quote\Address\Total;
use Magento\Quote\Model\Quote\Address\Total\AbstractTotal;
use Magento\Quote\Model\Quote;
use Magento\Quote\Model\Quote\Item;

/**
 * @method float getPointMoneyConfig()
 * @method float getPointMoneyConfigFreeRatioUpperRedeemLimitValue()
 * @method \Magento\Catalog\Model\AbstractModel setPointMoneyConfigFreeRatioUpperRedeemLimitValue()
 *
 *
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 * @since 100.0.2
 */
class Point extends AbstractTotal
{
    /**
     * @var int
     */
    private  $allocatedPoint = 0;

    /**
     * @var int
     */
    private  $allocatedPointDiscount = 0;

    /**
     * @var int
     */
    private  $appliedPoint = 0;

    /**
     * @var float
     */
    private  $pointConvertRate = 0;

    /**
     * @var float
     */
    private  $freeRatioItemsTotal = 0;

    /**
     * @var array
     */
    protected $requireProductPointItems = [];

    /**
     * @var array
     */
    protected $freeRatioItems = [];

    /**
     * @var \Branch8\PointMoneyCollect\Helper\Data
     */
    protected $pointHelper;

    protected $_priceCurrency;

    /**
     * @inheritDoc
     */
    public function __construct(
        \Branch8\PointMoneyCollect\Helper\Data $pointHelper,
        \Magento\Framework\Pricing\PriceCurrencyInterface $priceCurrency
    ) {
        $this->pointHelper = $pointHelper;
        $this->_priceCurrency = $priceCurrency;
        $this->setCode('point_used');
    }


    /**
     * @param Quote $quote
     * @param ShippingAssignmentInterface $shippingAssignment
     * @param Total $total
     * @return $this|Point
     */
    public function collect(Quote $quote, ShippingAssignmentInterface $shippingAssignment, Total $total)
    {
        $address = $shippingAssignment->getShipping()->getAddress();
        if((!$quote->getIsVirtual() && $address->getAddressType() == 'billing') || ($quote->getIsVirtual() && $address->getAddressType() == 'shipping')){
            return $this;
        }
        parent::collect($quote, $shippingAssignment, $total);

        $totalPointUsed = $quote->getPointUsedTotal();
        $discountAmount = $totalPointUsed * $this->pointHelper->getPointConvertRate();
        $total->addTotalAmount($this->getCode(), -$discountAmount);
        $total->addBaseTotalAmount($this->getCode(), -$discountAmount);
//        $quote->setData($discountAmount);

//        $label = $this->getLabel();
//        $description = 'Apply '.$totalPointUsed. ' point, total value '.$totalPointValueUsed;
//
//        $total->setDiscountDescription($description);
//        $total->setDiscountDescription($label);
//        $total->setDiscountAmount($discountAmount);
//        $total->setBaseDiscountAmount($discountAmount);
//        $total->setSubtotalWithDiscount($total->getSubtotal() + $discountAmount);
//        $total->setBaseSubtotalWithDiscount($total->getBaseSubtotal() + $discountAmount);


//        $total->setBaseGrandTotal($total->getBaseGrandTotal() + $discountAmount);

        return $this;
    }



    /**
     * @param Quote $quote
     * @param Total $total
     * @return array|void
     */
    public function fetch(Quote $quote, Total $total)
    {
        $point = $quote->getData('point_used_total');
        if ($point && is_numeric($point)) {
            // TODO: calculate the point discount amount based on the point rate
            $pointRate = $this->pointHelper->getPointConvertRate();
            $pointDiscount = $point * $pointRate;

            return [
                'code' => $this->getCode(),
                'title' => $this->getLabel(),
                'value' => -$pointDiscount
            ];
        }
    }


    /**
     * Get Subtotal label
     *
     * @return \Magento\Framework\Phrase
     */
    public function getLabel()
    {
        return __('Point Discount');
    }

}
