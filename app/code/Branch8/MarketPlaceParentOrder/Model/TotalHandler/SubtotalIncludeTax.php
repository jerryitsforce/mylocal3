<?php
declare(strict_types=1);

namespace Branch8\MarketPlaceParentOrder\Model\TotalHandler;

use Branch8\MarketPlaceParentOrder\Model\ParentOrder;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\DataObject;
use Magento\Store\Model\StoreManagerInterface;

class SubtotalIncludeTax implements TotalHandlerInterface
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
        $isShown = $this->config->displaySalesSubtotalBoth($store) || $this->config->displaySalesSubtotalInclTax($store);
        $total = [
            'code' => 'subtotal_include_tax',
            'strong' => false,
            'value' => 0,
            'base_value' => 0,
            'label' => __('Subtotal (Incl.Tax)'),
            'area' => '',
            'not_show' => $isShown === false
        ];
        /**u
         * @var $order \Magento\Sales\Model\Order
         */
        foreach ($parentOrder->getSubOrders() as $order) {
            if(!$order->getData('is_flagship_store_process_order')) {
                $total['value'] += $order->getSubtotalInclTax();
                $total['base_value'] += $order->getBaseSubtotalInclTax();
            }
        }
        $total['sort'] = 10;
        return ['code' => 'subtotal_include_tax', 'sort' => 10, 'data' => new DataObject($total)];
    }
}
