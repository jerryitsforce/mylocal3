<?php
declare(strict_types=1);

namespace Branch8\MarketPlaceParentOrder\Model\TotalHandler;

use Branch8\MarketPlaceParentOrder\Model\ParentOrder;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\DataObject;
use Magento\Store\Model\StoreManagerInterface;

class Shipping implements TotalHandlerInterface
{
    private ScopeConfigInterface $scopeConfig;
    private \Magento\Tax\Model\Config $config;
    private StoreManagerInterface $storeManager;
    /**
     * @var \Branch8\FlagshipStore\Helper\Sales
     */
    protected $flagshipHelper;

    /**
     * @param StoreManagerInterface $storeManager
     * @param ScopeConfigInterface $scopeConfig
     * @param \Magento\Tax\Model\Config $config
     * @param \Branch8\FlagshipStore\Helper\Sales $flagshipHelper
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

    /**
     * @param ParentOrder $parentOrder
     * @return array
     */
    public function handle(ParentOrder $parentOrder)
    {
        $store = $this->storeManager->getStore();
        $showTax = $this->config->displaySalesShippingInclTax($store) || $this->config->displaySalesShippingBoth($store);
        $shippingLabel = __('Shipping & Handling');
        if ($showTax) {
            $shippingLabel = __('Shipping & Handling (Excl.Tax)');
        }

        $total = [
            'code' => 'shipping',
            'strong' => false,
            'value' => 0,
            'base_value' => 0,
            'value_include_tax' => 0,
            'base_value_include_tax' => 0,
            'label' => $shippingLabel,
            'area' => ''
        ];
        /**
         * @var $order \Magento\Sales\Model\Order
         */
        foreach ($parentOrder->getSubOrders() as $order) {
            $total['value'] += $order->getShippingAmount();
            $total['value_include_tax'] += ($order->getShippingInclTax());
            $total['base_value'] += $order->getBaseShippingAmount();
            $total['base_value_include_tax'] += $order->getBaseShippingInclTax();
            if($order->getData('is_flagship_store_process_order')){
                $total['value'] += $order->getSubtotal();
                $total['value_include_tax'] += ($order->getSubtotalInclTax());
                $total['base_value'] += $order->getBaseSubtotal();
                $total['base_value_include_tax'] += $order->getBaseSubtotalInclTax();
            }
        }
        $total['sort'] = 30;
        return [
            'sort' => 40,
            'code' => 'shipping',
            'data' => new DataObject($total)
        ];
    }
}
