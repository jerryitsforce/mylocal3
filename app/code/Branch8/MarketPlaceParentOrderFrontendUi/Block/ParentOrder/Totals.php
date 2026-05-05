<?php
declare(strict_types=1);

namespace Branch8\MarketPlaceParentOrderFrontendUi\Block\ParentOrder;

use Branch8\MarketPlaceParentOrder\Api\ParentOrderManagementInterface;
use Magento\Framework\DataObject;
use Magento\Sales\Model\Order;


class Totals extends \Magento\Framework\View\Element\Template
{
    /**
     * Associated array of totals
     * array(
     *  $totalCode => $totalObject
     * )
     *
     * @var array
     */
    protected $_totals = null;

    /**
     * @var Order|null
     */
    protected $_order = null;

    /**
     * Core registry object
     *
     * @var \Magento\Framework\Registry
     */
    protected $_coreRegistry = null;
    /**
     * @var ParentOrderManagementInterface
     */
    private $parentOrderManagement;


    private $avaiableCodes = [
        'subtotal',
        'subtotal_include_tax',
        'shipping',
        'shipping_include_tax',
        'discount',
        'point_discount',
        'tax',
        'grand_total'
    ];

    /**
     * @param \Magento\Framework\View\Element\Template\Context $context
     * @param \Magento\Framework\Registry $registry
     * @param ParentOrderManagementInterface $parentOrderManagement
     * @param array $data
     */
    public function __construct(
        \Magento\Framework\View\Element\Template\Context $context,
        \Magento\Framework\Registry                      $registry,
        ParentOrderManagementInterface                   $parentOrderManagement,
        array                                            $data = []
    )
    {
        $this->_coreRegistry = $registry;
        parent::__construct($context, $data);
        $this->parentOrderManagement = $parentOrderManagement;
    }

    /**
     * Initialize self totals and children blocks totals before html building
     *
     * @return $this
     */
    protected function _beforeToHtml()
    {
        $this->_initTotals();
        foreach ($this->getLayout()->getChildBlocks($this->getNameInLayout()) as $child) {
            if (method_exists($child, 'initTotals') && is_callable([$child, 'initTotals'])) {
                $child->initTotals();
            }
        }
        return parent::_beforeToHtml();
    }

    /**
     * Get order object
     *
     * @return Order
     */
    public function getOrder()
    {
        if ($this->_order === null) {
            if ($this->hasData('order')) {
                $this->_order = $this->_getData('order');
            } elseif ($this->_coreRegistry->registry('current_parent_order')) {
                $this->_order = $this->_coreRegistry->registry('current_parent_order');
            } elseif ($this->getParentBlock()->getOrder()) {
                $this->_order = $this->getParentBlock()->getOrder();
            }
        }
        return $this->_order;
    }

    /**
     * Sets order.
     *
     * @param Order $order
     * @return $this
     */
    public function setOrder($order)
    {
        $this->_order = $order;
        return $this;
    }

    /**
     * Get totals source object
     *
     * @return Order
     */
    public function getSource()
    {
        return $this->getOrder();
    }

    /**
     * Initialize order totals array
     *
     * @return $this
     */
    protected function _initTotals()
    {
        $this->_totals = $this->parentOrderManagement->getTotals($this->getOrder());
        return $this;
    }

    /**
     * Add shipping total
     *
     * @param Order|Order\Invoice $source
     * @retrurn void
     */
    private function addShippingTotal($source)
    {
        if (!$source->getIsVirtual()
            && ($source->getShippingAmount() !== null
                || $source->getShippingDescription())
        ) {
            $shippingLabel = __('Shipping & Handling');

            if (!isset($this->_totals['discount'])) {
                if ($source->getCouponCode()) {
                    $shippingLabel .= " ({$source->getCouponCode()})";
                } elseif ($source->getDiscountDescription()) {
                    $shippingLabel .= " ({$source->getDiscountDescription()})";
                }
            }
            $this->_totals['shipping'] = new DataObject(
                [
                    'code' => 'shipping',
                    'field' => 'shipping_amount',
                    'value' => $source->getShippingAmount(),
                    'label' => $shippingLabel,
                ]
            );
        }
    }

    /**
     * Add new total to totals array after specific total or before last total by default
     *
     * @param \Magento\Framework\DataObject $total
     * @param null|string $after
     * @return  $this
     */
    public function addTotal(\Magento\Framework\DataObject $total, $after = null)
    {
        if ($after !== null && $after != 'last' && $after != 'first') {
            $totals = [];
            $added = false;
            foreach ($this->_totals as $code => $item) {
                $totals[$code] = $item;
                if ($code == $after) {
                    $added = true;
                    $totals[$total->getCode()] = $total;
                }
            }
            if (!$added) {
                $last = array_pop($totals);
                $totals[$total->getCode()] = $total;
                $totals[$last->getCode()] = $last;
            }
            $this->_totals = $totals;
        } elseif ($after == 'last') {
            $this->_totals[$total->getCode()] = $total;
        } elseif ($after == 'first') {
            $totals = [$total->getCode() => $total];
            $this->_totals = array_merge($totals, $this->_totals);
        } else {
            $last = array_pop($this->_totals);
            $this->_totals[$total->getCode()] = $total;
            $this->_totals[$last->getCode()] = $last;
        }
        return $this;
    }

    /**
     * Add new total to totals array before specific total or after first total by default
     *
     * @param \Magento\Framework\DataObject $total
     * @param null|string $before
     * @return  $this
     */
    public function addTotalBefore(\Magento\Framework\DataObject $total, $before = null)
    {
        if ($before !== null) {
            if (!is_array($before)) {
                $before = [$before];
            }
            foreach ($before as $beforeTotals) {
                if (isset($this->_totals[$beforeTotals])) {
                    $totals = [];
                    foreach ($this->_totals as $code => $item) {
                        if ($code == $beforeTotals) {
                            $totals[$total->getCode()] = $total;
                        }
                        $totals[$code] = $item;
                    }
                    $this->_totals = $totals;
                    return $this;
                }
            }
        }
        $totals = [];
        $first = array_shift($this->_totals);
        $totals[$first->getCode()] = $first;
        $totals[$total->getCode()] = $total;
        foreach ($this->_totals as $code => $item) {
            $totals[$code] = $item;
        }
        $this->_totals = $totals;
        return $this;
    }

    /**
     * Get Total object by code
     *
     * @param string $code
     * @return mixed
     */
    public function getTotal($code)
    {
        if (isset($this->_totals[$code])) {
            return $this->_totals[$code];
        }
        return false;
    }

    /**
     * Delete total by specific
     *
     * @param string $code
     * @return  $this
     */
    public function removeTotal($code)
    {
        unset($this->_totals[$code]);
        return $this;
    }

    /**
     * Apply sort orders to totals array.
     * Array should have next structure
     * array(
     *  $totalCode => $totalSortOrder
     * )
     *
     * @param array $order
     * @return  $this
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function applySortOrder($order)
    {
        \uksort(
            $this->_totals,
            function ($code1, $code2) use ($order) {
                return ($order[$code1] ?? 0) <=> ($order[$code2] ?? 0);
            }
        );
        return $this;
    }

    /**
     * Get totals array for visualization
     *
     * @param array|null $area
     * @return array
     */
    public function getTotals($area = null)
    {
        $totals = [];
        if ($area === null) {
            $totals = $this->_totals;
        } else {
            $area = (string)$area;
            foreach ($this->_totals as $total) {
                $totalArea = (string)$total->getArea();
                if ($totalArea == $area) {
                    $totals[] = $total;
                }
            }
        }
        return $totals;
    }

    /**
     * Format total value based on order currency
     *
     * @param \Magento\Framework\DataObject $total
     * @return  string
     */
    public function formatValue($total)
    {
        if (!$total->getIsFormated()) {
            return $this->getOrder()->formatPrice($total->getValue());
        }
        return $total->getValue();
    }

    /**
     * @param $total
     * @return bool
     */
    public function shouldDisplay($total)
    {
        return in_array($total->getCode(), $this->avaiableCodes) && ((bool)$total->getNotShow() === false);
    }
}
