<?php

declare(strict_types=1);

/**
 * @author Amasty Team
 * @copyright Copyright (c) Amasty (https://www.amasty.com)
 * @package Advanced Permissions for Magento 2
 */

namespace Branch8\AdvancedPermissions\Model\ResourceModel\Product\Collection;

use Webkul\Marketplace\Model\ResourceModel\Product\Collection as ProductCollection;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\DB\Select;

class ResourceAdapter
{
    public const CATEGORY_PRODUCT_TABLE_ALIAS = 'cp';
    public const PRODUCT_WEBSITE_TABLE_ALIAS = 'am_product_website';

    private const CATEGORY_PRODUCT_TABLE_NAME = 'catalog_category_product';
    private const PRODUCT_WEBSITE_TABLE_NAME = 'catalog_product_website';
    private const PRODUCT_JOIN_FORMAT = '%s.product_id = mageproduct_id';

    /**
     * @var ResourceConnection
     */
    private $resourceConnection;

    public function __construct(
        ResourceConnection $resourceConnection
    ) {
        $this->resourceConnection = $resourceConnection;
    }

    public function resolveCategoryCondition(ProductCollection $productCollection, array $categoryIds): string
    {
        $categoryTable = $productCollection->getTable(self::CATEGORY_PRODUCT_TABLE_NAME);

        $productCollection->getSelect()->joinLeft(
            [self::CATEGORY_PRODUCT_TABLE_ALIAS =>$categoryTable],
            sprintf(self::PRODUCT_JOIN_FORMAT, self::CATEGORY_PRODUCT_TABLE_ALIAS),
            []
        );

        return $this->resourceConnection->getConnection()->quoteInto(
            self::CATEGORY_PRODUCT_TABLE_ALIAS . '.category_id IN (?)',
            $categoryIds
        );
    }

    public function resolveWebsiteCondition(ProductCollection $productCollection, array $websiteIds): string
    {
        $websiteTable = $this->resourceConnection->getTableName(self::PRODUCT_WEBSITE_TABLE_NAME);

        $productCollection->getSelect()->join(
            [self::PRODUCT_WEBSITE_TABLE_ALIAS => $websiteTable],
            sprintf(self::PRODUCT_JOIN_FORMAT, self::PRODUCT_WEBSITE_TABLE_ALIAS),
            []
        );

        return $this->resourceConnection->getConnection()->quoteInto(
            self::PRODUCT_WEBSITE_TABLE_ALIAS . '.website_id IN (?)',
            $websiteIds
        );
    }

    public function formatProductCondition(array $productIds): string
    {
        return $this->resourceConnection->getConnection()->quoteInto(
            'mageproduct_id IN (?)',
            $productIds
        );
    }

    public function applyRuleCondition(
        ProductCollection $productCollection,
        array $ruleConditions
    ): void {
        $ruleConditionsSql = implode(sprintf(' %s ', Select::SQL_AND), $ruleConditions);

        $productCollection->getSelect()->where($ruleConditionsSql);
    }
}
