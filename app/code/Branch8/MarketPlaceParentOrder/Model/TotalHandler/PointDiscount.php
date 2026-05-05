<?php
declare(strict_types=1);

namespace Branch8\MarketPlaceParentOrder\Model\TotalHandler;

use Branch8\MarketPlaceParentOrder\Model\ParentOrder;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\DataObject;
use Magento\Store\Model\StoreManagerInterface;

class PointDiscount implements TotalHandlerInterface
{
    private ScopeConfigInterface $scopeConfig;
    private \Magento\Tax\Model\Config $config;
    private StoreManagerInterface $storeManager;

    /**
     * @param StoreManagerInterface $storeManager
     * @param ScopeConfigInterface $scopeConfig
     * @param \Magento\Tax\Model\Config $config
     */
    public function __construct(
        StoreManagerInterface     $storeManager,
        ScopeConfigInterface      $scopeConfig,
        \Magento\Tax\Model\Config $config
    )
    {
        $this->storeManager = $storeManager;
        $this->config = $config;
        $this->scopeConfig = $scopeConfig;
    }

    /**
     * @param ParentOrder $parentOrder
     * @return array
     */
    public function handle(ParentOrder $parentOrder)
    {
        $pointDiscountLabel = __('Point discount');
        $total = [
            'code' => 'point_discount',
            'strong' => false,
            'value' => 0,
            'base_value' => 0,
            'value_include_tax' => 0,
            'base_value_include_tax' => 0,
            'label' => $pointDiscountLabel,
            'area' => '',
            'not_show' => false
        ];
        /**
         * @var $order \Magento\Sales\Model\Order
         */
        foreach ($parentOrder->getSubOrders() as $order) {
            $total['value'] += $order->getData('point_discount_total');
            $total['base_value'] += $order->getData('point_discount_total');
        }
        $total['value'] = -$total['value'];
        $total['base_value'] = -$total['base_value'];
        $total['sort'] = 56;
        return [
            'sort' => 56,
            'code' => 'point_discount',
            'data' => new DataObject($total)
        ];
    }
}
