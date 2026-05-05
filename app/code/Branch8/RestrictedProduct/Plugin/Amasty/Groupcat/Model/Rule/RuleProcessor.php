<?php
/**
 * Copyright ©  All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Branch8\RestrictedProduct\Plugin\Amasty\Groupcat\Model\Rule;

use Amasty\Groupcat\Model\ResourceModel\InventoryResolver;
use Amasty\Groupcat\Model\ResourceModel\Rule as RuleResource;
use Amasty\Groupcat\Model\Rule;
use Magento\Store\Model\StoreManagerInterface;
use Amasty\Groupcat\Model\Rule\StoreResolver;

class RuleProcessor
{
    /**
     * @var StoreManagerInterface
     */
    private $storeManager;

    /**
     * @var InventoryResolver
     */
    private $inventoryResolver;

    /**
     * @var RuleResource
     */
    private $ruleResource;

    /**
     * @var StoreResolver
     */
    private $storeResolver;

    public function __construct(
        StoreManagerInterface $storeManager,
        InventoryResolver $inventoryResolver,
        RuleResource $ruleResource,
        StoreResolver $storeResolver
    ) {
        $this->storeManager = $storeManager;
        $this->inventoryResolver = $inventoryResolver;
        $this->ruleResource = $ruleResource;
        $this->storeResolver = $storeResolver;
    }

    public function aroundGetMatchingProductIds(
        \Amasty\Groupcat\Model\Rule\RuleProcessor $subject,
        \Closure $proceed,
        Rule $rule
    ) {
        $outOfStockProductIds = [];

        $matchingIds = $params = [];

        if ($rule->getStoreIds() != [0]) {
            $params['website_id'] = $rule->getWebsiteIds();
        }

        foreach ($this->storeResolver->resolveStoreIds($rule->getStoreIds()) as $storeId) {
            if(!$rule->getHideProduct()){
                continue;
            }
            if ($rule->getApplyToOutOfStock()) {
                $currentStore = $this->storeManager->getStore();
                $this->storeManager->setCurrentStore($storeId);
                $outOfStockProductIds = $this->inventoryResolver->getOutOfStockProductIds();
                $this->storeManager->setCurrentStore($currentStore);
            }

            $params['store_id'] = $storeId;
            $productIds = $rule->getConditions()->getSatisfiedIds($params);
            $bundleProductIds = $this->ruleResource->getRestrictedBundleProductIds($productIds);
            // phpcs:ignore Magento2.Performance.ForeachArrayMerge.ForeachArrayMerge
            $productIds = array_unique(array_merge($productIds, $bundleProductIds, $outOfStockProductIds));

            foreach ($productIds as $productId) {
                $matchingIds[$productId][$storeId] = true;
            }
        }

        return $matchingIds;
    }
}
