<?php

namespace Branch8\PromotionPage\Cron;

use Branch8\PromotionPage\Helper\Data;
use Magento\Catalog\Model\CategoryRepository;
use Magento\Catalog\Model\Product\Attribute\Source\Status;
use Magento\Catalog\Model\ResourceModel\Product\CollectionFactory;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\ResourceConnection;
use Magento\Store\Model\ScopeInterface;

class VipIndexer
{
    /**
     * @var Data
     */
    protected $helper;

    /**
     * @var ScopeConfigInterface
     */
    protected $scopeConfig;

    /**
     * @var CategoryRepository
     */
    protected $categoryRepository;

    /**
     * Product collection factory
     *
     * @var CollectionFactory
     */
    protected $productCollectionFactory;

    /**
     * @var ResourceConnection
     */
    protected $resource;

    public function __construct(
        Data $helper,
        CategoryRepository $categoryRepository,
        CollectionFactory $productCollectionFactory,
        ScopeConfigInterface $scopeConfig,
        ResourceConnection $resource
    ) {
        $this->helper = $helper;
        $this->categoryRepository = $categoryRepository;
        $this->productCollectionFactory = $productCollectionFactory;
        $this->scopeConfig = $scopeConfig;
        $this->resource = $resource;
    }

    public function execute()
    {
        // 3. Load rule + categories
        $vipCategoryId = $this->scopeConfig->getValue(
            'promotion_page/promotion_categories/vip_category',
            ScopeInterface::SCOPE_STORE
        );
        $connection = $this->resource->getConnection();

        $category = $this->categoryRepository->get($vipCategoryId);

        $categoryIds = $category->getAllChildren(true);

        $validCategoryIds = [];
        // Check each category to see if it has a matching rule for the customer group
        foreach ($categoryIds as $categoryId) {
            // Get the category object
            $currentCategory = $this->categoryRepository->get($categoryId);

            // Get the catalog_price_rule_vip_id attribute
            $vipRuleId = $currentCategory->getData('catalog_price_rule_vip_id');

            if ($vipRuleId) {
                // Perform SQL query to check if the rule matches the current customer group
                $select = $connection->select()
                    ->from('catalogrule_group_website', ['customer_group_id']) // Check customer group for rule
                    ->where('rule_id = ?', $vipRuleId);

                $validGroup = $connection->fetchCol($select); // Returns the customer group if it matches

                if (!empty($validGroup)) {
                    foreach ($validGroup as $groupId) {
                        $validCategoryIds[$groupId][] = $categoryId;
                    }
                }
            }
        }

        // Clear old
        $table = $this->resource->getTableName('catalogrule_is_vip');
        foreach ($validCategoryIds as $groupId => $catIds) {
            // Now fetch the products from the valid categories
            $vipCategoryCollection = $this->productCollectionFactory->create();
            $vipCategoryCollection->addAttributeToSelect('entity_id') // Only select product IDs
            ->addCategoriesFilter(['in' => $catIds]);

            $productIds = $vipCategoryCollection->getAllIds();
            $listProduct = [];
            // Insert new
            foreach ($productIds as $pid) {
                $listProduct[] = [
                    'product_id' => (int) $pid,
                    'customer_group_id' => (int) $groupId,
                ];
            }
            $connection->delete($table, [
                'customer_group_id = ?' => $groupId
            ]);
            if (!empty($listProduct)) {
                $connection->insertMultiple($table, $listProduct);
            }
        }

        return true;
    }
}
