<?php
namespace Branch8\OptionsWithStockAndImages\Helper;

use Magento\Catalog\Model\Product;
use Magento\CatalogRule\Model\ResourceModel\Rule;
use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\App\Helper\Context;
use Magento\Framework\Stdlib\DateTime\TimezoneInterface;
use Magento\Store\Model\StoreManagerInterface;

class CatalogRuleCalculator extends AbstractHelper
{
    /**
     * @var Rule
     */
    protected $ruleResource;

    /**
     * @var StoreManagerInterface
     */
    protected $storeManager;

    /**
     * @var TimezoneInterface
     */
    protected $localeDate;

    /**
     * @param Context $context
     * @param Rule $ruleResource
     * @param StoreManagerInterface $storeManager
     * @param TimezoneInterface $localeDate
     */
    public function __construct(
        Context $context,
        Rule $ruleResource,
        StoreManagerInterface $storeManager,
        TimezoneInterface $localeDate
    ) {
        $this->ruleResource = $ruleResource;
        $this->storeManager = $storeManager;
        $this->localeDate = $localeDate;
        parent::__construct($context);
    }

    /**
     * Apply catalog rules on custom price
     *
     * @param int $productId
     * @param float $price
     * @param int $websiteId
     * @param int $customerGroupId
     * @return float
     */
    public function applyRuleOnCustomPrice(int $productId, float $price, int $websiteId, int $customerGroupId): float
    {
        $date = $this->localeDate->scopeDate($this->storeManager->getStore()->getId());
        $rules = $this->ruleResource->getRulesFromProduct($date, $websiteId, $customerGroupId, $productId);

        usort($rules, function ($a, $b) {
            if ($a['sort_order'] == $b['sort_order']) {
                return $a['rule_id'] <=> $b['rule_id'];
            }
            return $a['sort_order'] <=> $b['sort_order'];
        });

        $currentPrice = $price;
        foreach ($rules as $ruleData) {
            $currentPrice = $this->calcProductPriceRule($ruleData, $currentPrice);
            if ($ruleData['action_stop']) {
                break;
            }
        }
        
        return $currentPrice;
    }

    /**
     * Calculate price based on rule data
     *
     * @param array $ruleData
     * @param float $productPrice
     * @return float
     */
    protected function calcProductPriceRule(array $ruleData, float $productPrice): float
    {
        $price = $productPrice;
        if ($ruleData['action_operator'] == 'to_fixed') {
            $price = min($productPrice, $ruleData['action_amount']);
        } elseif ($ruleData['action_operator'] == 'to_percent') {
            $price = $productPrice * $ruleData['action_amount'] / 100;
        } elseif ($ruleData['action_operator'] == 'by_fixed') {
            $price = max(0, $productPrice - $ruleData['action_amount']);
        } elseif ($ruleData['action_operator'] == 'by_percent') {
            $price = $productPrice * (1 - $ruleData['action_amount'] / 100);
        }

        return $price;
    }
}
