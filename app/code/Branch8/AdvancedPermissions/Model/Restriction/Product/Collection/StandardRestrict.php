<?php

declare(strict_types=1);

/**
 * @author Amasty Team
 * @copyright Copyright (c) Amasty (https://www.amasty.com)
 * @package Advanced Permissions for Magento 2
 */

namespace Branch8\AdvancedPermissions\Model\Restriction\Product\Collection;

use Amasty\Rolepermissions\Block\Adminhtml\Role\Tab\Categories;
use Amasty\Rolepermissions\Block\Adminhtml\Role\Tab\Products;
use Amasty\Rolepermissions\Helper\Data as Helper;
use Branch8\AdvancedPermissions\Model\ResourceModel\Product\Collection\ResourceAdapter as ProductCollectionResourceAdapter;
use Magento\Framework\DB\Select;
use Webkul\Marketplace\Model\ResourceModel\Product\Collection as ProductCollection;
use Magento\Framework\App\State;
use Magento\Framework\Exception\LocalizedException;

class StandardRestrict implements RestrictInterface
{
    /**
     * @var array
     */
    private $restrictedObjects = [];

    /**
     * @var Helper
     */
    private $helper;

    /**
     * @var State
     */
    private $appState;

    /**
     * @var ProductCollectionResourceAdapter
     */
    private $productCollectionResourceAdapter;

    public function __construct(
        Helper $helper,
        State $appState,
        ProductCollectionResourceAdapter $productCollectionResourceAdapter
    ) {
        $this->helper = $helper;
        $this->appState = $appState;
        $this->productCollectionResourceAdapter = $productCollectionResourceAdapter;
    }

    public function execute(ProductCollection $productCollection): void
    {
        try {
            if (!in_array($this->appState->getAreaCode(), Helper::ALLOWED_AREA_CODES)) {
                return;
            }
        } catch (LocalizedException $e) {
            return;
        }

        $objectId = spl_object_hash($productCollection);

        if (isset($this->restrictedObjects[$objectId])) {
            return;
        }

        $rule = $this->helper->currentRule();
        if (is_object($rule)) {
            $this->restrictProductCollection($rule, $productCollection);
            $this->restrictedObjects[$objectId] = true;
        }
    }

    public function restrictProductCollection($rule, ProductCollection $collection): void
    {
        $ruleConditions = [];

        switch ($rule->getProductAccessMode()) {
            case Products::MODE_ANY:
                break;
            case Products::MODE_SELECTED:
                if ($rule->getProducts()) {
                    $ruleConditions[] = $this->productCollectionResourceAdapter->formatProductCondition(
                        $rule->getProducts()
                    );
                }
                break;
        }

        try {
            $fromSelect = $collection->getSelect()->getPart(Select::FROM);
        } catch (\Zend_Db_Select_Exception $e) {
            return;
        }

        if ($rule->getCategoryAccessMode() == Categories::MODE_SELECTED
            && !isset($fromSelect[ProductCollectionResourceAdapter::CATEGORY_PRODUCT_TABLE_ALIAS])
            && $rule->getCategories()
        ) {
            $ruleConditions[] = $this->productCollectionResourceAdapter->resolveCategoryCondition(
                $collection,
                $rule->getCategories()
            );
        }

        /*if ($rule->getScopeAccessMode()
            && !isset($fromSelect[ProductCollectionResourceAdapter::PRODUCT_WEBSITE_TABLE_ALIAS])
            && $partiallyAccessibleWebsites = $rule->getPartiallyAccessibleWebsites()
        ) {
            $ruleConditions[] = $this->productCollectionResourceAdapter->resolveWebsiteCondition(
                $collection,
                $partiallyAccessibleWebsites
            );
        }*/

        if ($ruleConditions) {
            $this->productCollectionResourceAdapter->applyRuleCondition(
                $collection,
                $ruleConditions
            );
        }
        switch ($rule->getSellerAccessMode()) {
            case Products::MODE_ANY:
                break;
            case Products::MODE_SELECTED:
                if ($rule->getSellers()) {
                    if ($collection->getMainTable() == 'marketplace_product') {
                        $collection->getSelect()->where("main_table.seller_id IN (?)", $rule->getSellers());
                    } else {
                        $collection->getSelect()->where("seller_id IN (?)", $rule->getSellers());
                    }
                }
                break;
        }

        $collection->getSelect()->distinct();
    }
}
