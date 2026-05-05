<?php
declare(strict_types=1);

namespace Branch8\MarketPlaceParentOrder\Model\TotalHandler;

use Branch8\MarketPlaceParentOrder\Model\ParentOrder;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\DataObject;
use Magento\Store\Model\StoreManagerInterface;

class PointUsedTotal implements TotalHandlerInterface
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
        $pointUsedTotal = __('Point Used Total');
        $total = [
            'code' => 'point_used_total',
            'strong' => false,
            'value' => 0,
            'base_value' => 0,
            'value_include_tax' => 0,
            'base_value_include_tax' => 0,
            'label' => $pointUsedTotal,
            'area' => '',
            'not_show' => false
        ];
        /**
         * @var $order \Magento\Sales\Model\Order
         */
        foreach ($parentOrder->getSubOrders() as $order) {
            $total['value'] += (float)$order->getData('point_used_total');
            $total['base_value'] += (float)$order->getData('point_used_total');
        }
        $total['sort'] = 58;
        return [
            'sort' => 58,
            'code' => 'point_used_total',
            'data' => new DataObject($total)
        ];
    }
}
