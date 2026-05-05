<?php
declare(strict_types=1);

namespace Branch8\MarketPlaceParentOrder\Model\TotalHandler;

use Branch8\MarketPlaceParentOrder\Model\ParentOrder;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\DataObject;
use Magento\Store\Model\StoreManagerInterface;

class Subtotal implements TotalHandlerInterface
{

    private ScopeConfigInterface $scopeConfig;
    private \Magento\Tax\Model\Config $config;
    private StoreManagerInterface $storeManager;

    protected $flagshipHelper;

    /**
     * @param StoreManagerInterface $storeManager
     * @param ScopeConfigInterface $scopeConfig
     * @param \Magento\Tax\Model\Config $config
     */
    public function __construct(
        StoreManagerInterface     $storeManager,
        ScopeConfigInterface      $scopeConfig,
        \Magento\Tax\Model\Config $config,
        \Branch8\FlagshipStore\Helper\Sales $flagshipHelper
    )
    {
        $this->storeManager = $storeManager;
        $this->config = $config;
        $this->scopeConfig = $scopeConfig;
        $this->flagshipHelper = $flagshipHelper;
    }

    public function handle(ParentOrder $parentOrder)
    {
        $store = $this->storeManager->getStore();
        $showTax = $this->config->displaySalesShippingInclTax($store) || $this->config->displaySalesShippingBoth($store);
        $label = __('Sub Total');
        if ($showTax) {
            $label = __('Subtotal (Excl.Tax)');
        }
        $total = [
            'code' => 'subtotal',
            'strong' => false,
            'value' => 0,
            'base_value' => 0,
            'base_subtotal_incl_tax' => 0,
            'label' => $label,
            'area' => ''
        ];
        /**u
         * @var $order \Magento\Sales\Model\Order
         */
        foreach ($parentOrder->getSubOrders() as $order) {
            if(!$order->getData('is_flagship_store_process_order')) {
                $total['value'] += $order->getSubtotal();
                $total['base_value'] += $order->getBaseSubtotal();
                $total['base_subtotal_incl_tax'] += $order->getBaseSubtotalInclTax();
            }
        }
        $total['sort'] = 5;
        return ['code' => 'subtotal', 'sort' => 5, 'data' => new DataObject($total)];
    }
}
