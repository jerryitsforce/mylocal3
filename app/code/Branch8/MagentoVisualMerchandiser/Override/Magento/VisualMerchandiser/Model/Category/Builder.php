<?php
/**
 * This class use to optimize
 * \Magento\VisualMerchandiser\Model\Category\Builder when a large products assigned to category by rules
 */

namespace Branch8\MagentoVisualMerchandiser\Override\Magento\VisualMerchandiser\Model\Category;

use Branch8\MagentoVisualMerchandiser\Model\BuilderInterface;
use Branch8\MagentoVisualMerchandiser\Model\Products\TemporaryTableFactory;
use \Magento\Framework\DB\Select;

/**
 * Class Builder
 *
 * @package Magento\VisualMerchandiser\Model\Category
 *
 * @api
 *
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 * @since 100.0.2
 */
class Builder extends \Magento\VisualMerchandiser\Model\Category\Builder implements BuilderInterface
{
    const PRODUCT_INDEX_TABLE_PREFIX = 'visual_merchandiser_rule_product_';
    /**
     * @var \Magento\Framework\App\ResourceConnection
     */
    private $resource;
    /**
     * @var \Magento\VisualMerchandiser\Model\Rules
     */
    private $rules;
    /**
     * @var \Magento\VisualMerchandiser\Model\RulesFactory
     */
    private \Magento\VisualMerchandiser\Model\RulesFactory $rulesFactory;
    /**
     * @var TemporaryTableFactory
     */
    private TemporaryTableFactory $tempTableFactory;

    /**
     * @param \Magento\Framework\App\ResourceConnection $resource
     * @param \Magento\VisualMerchandiser\Model\Rules $rules
     * @param \Magento\VisualMerchandiser\Model\RulesFactory $rulesFactory
     * @param \Magento\Catalog\Model\ResourceModel\Product\CollectionFactory $productCollectionFactory
     * @param \Magento\Store\Model\StoreManagerInterface $storeManager
     * @param TemporaryTableFactory $tempTableFactory
     * @param \Magento\VisualMerchandiser\Model\Sorting $sorting
     */
    public function __construct(
        \Magento\Framework\App\ResourceConnection                      $resource,
        \Magento\VisualMerchandiser\Model\Rules                        $rules,
        \Magento\VisualMerchandiser\Model\RulesFactory                 $rulesFactory,
        \Magento\Catalog\Model\ResourceModel\Product\CollectionFactory $productCollectionFactory,
        \Magento\Store\Model\StoreManagerInterface                     $storeManager,
        TemporaryTableFactory                                          $tempTableFactory,
        \Magento\VisualMerchandiser\Model\Sorting                      $sorting
    )
    {
        parent::__construct(
            $resource,
            $rules,
            $productCollectionFactory,
            $storeManager,
            $sorting
        );
        $this->tempTableFactory = $tempTableFactory;
        $this->resource = $resource;
        $this->rules = $rules;
        $this->rulesFactory = $rulesFactory;
    }

    /**
     * @param \Magento\Catalog\Model\Category $category
     * @param $createIndexTable
     * @return array
     * @throws \Magento\Framework\Exception\LocalizedException
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function buildCategory(\Magento\Catalog\Model\Category $category, $createIndexTable = true)
    {
        $collection = $this->productCollectionFactory->create()->setStoreId(
            $this->storeManager->getStore()->getId()
        );
        /**
         * @var $ruleCategory \Magento\VisualMerchandiser\Model\Rules
         */
        $ruleCategory = $this->rulesFactory->create()->loadByCategory($category);
        // Apply 'smart rules', this works on the whole product collection
        $this->rules->applyAllRules($category, $collection);
        // Limit the product set to only the products needed
        $existingProducts = $category->getPostedProducts();
        if ($existingProducts === null) {
            $existingProducts = $category->getProductsPosition();
        }
        asort($existingProducts, SORT_NUMERIC);
        if ($createIndexTable) {
            $this->tempTableFactory->createTable($ruleCategory->getId());
        }
        $indexTableName = $this->tempTableFactory->populateData($ruleCategory->getId(), $existingProducts);
        $existingProducts = array_keys($existingProducts);
        $collection->getSelect()->reset(Select::WHERE);
        $collection->getSelect()->reset(Select::HAVING);
        $collection->getSelect()->reset(Select::SQL_HAVING);
        $collection->getSelect()->reset(Select::ORDER);
        $collection->getSelect()->join(
            "{$indexTableName} as index_table",
            "index_table.product_id = e.entity_id",
            []
        );
        // Apply 'sort'
        $sortedCollection = $this->sorting->applySorting($category, $collection);
        $positions = [];
        $idx = 0;
        if (count($existingProducts) > 0) {
            foreach ($sortedCollection as $item) {
                /* @var $item \Magento\Catalog\Api\Data\ProductInterface */
                $positions[$item->getId()] = $idx;
                $idx++;
            }
        }
        return $positions;
    }

    /**
     * @param \Magento\Catalog\Model\Category $category
     * @return mixed
     * @throws \Magento\Framework\Exception\LocalizedException
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function getMatchingProducts(\Magento\Catalog\Model\Category $category)
    {
        return $this->buildCategory($category);
    }
}
