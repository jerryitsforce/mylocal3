<?php
declare(strict_types=1);

namespace Branch8\MagentoVisualMerchandiser\Model\AdvanceRule;

use Branch8\MagentoVisualMerchandiser\Model\AdvanceRule;
use Branch8\MagentoVisualMerchandiser\Model\BuilderInterface;
use Branch8\MagentoVisualMerchandiser\Model\Products\TemporaryTableFactory;
use \Magento\Framework\DB\Select;
use Psr\Log\LoggerInterface;

/**
 *
 */
class Builder implements BuilderInterface
{
    /**
     * @var \Magento\Catalog\Model\ResourceModel\Product\CollectionFactory
     */
    protected $productCollectionFactory;

    /**
     * @var \Magento\Store\Model\StoreManagerInterface
     */
    protected $storeManager;

    /**
     * @var \Magento\VisualMerchandiser\Model\Sorting
     */
    protected $sorting;

    /**
     * @var \Magento\Framework\App\ResourceConnection
     */
    private $resource;

    /**
     * @var \Magento\VisualMerchandiser\Model\Rules
     */
    private $rules;

    private \Branch8\MagentoVisualMerchandiser\Model\AdvanceRuleFactory $advanceRuleFactory;
    private \Magento\Framework\Registry $registry;
    private LoggerInterface $logger;
    private TemporaryTableFactory $tempTableFactory;
    private \Magento\VisualMerchandiser\Model\RulesFactory $rulesFactory;

    /**
     * @param \Magento\Framework\App\ResourceConnection $resource
     * @param \Branch8\MagentoVisualMerchandiser\Model\AdvanceRuleFactory $advanceRuleFactory
     * @param \Magento\VisualMerchandiser\Model\Rules $rules
     * @param \Magento\Catalog\Model\ResourceModel\Product\CollectionFactory $productCollectionFactory
     * @param \Magento\Store\Model\StoreManagerInterface $storeManager
     * @param \Magento\VisualMerchandiser\Model\Sorting $sorting
     * @param \Magento\Framework\Registry $registry
     * @param \Magento\VisualMerchandiser\Model\RulesFactory $rulesFactory
     * @param TemporaryTableFactory $temporaryTableFactory
     * @param LoggerInterface $logger
     */
    public function __construct(
        \Magento\Framework\App\ResourceConnection                      $resource,
        \Branch8\MagentoVisualMerchandiser\Model\AdvanceRuleFactory    $advanceRuleFactory,
        \Magento\VisualMerchandiser\Model\Rules                        $rules,
        \Magento\Catalog\Model\ResourceModel\Product\CollectionFactory $productCollectionFactory,
        \Magento\Store\Model\StoreManagerInterface                     $storeManager,
        \Magento\VisualMerchandiser\Model\Sorting                      $sorting,
        \Magento\Framework\Registry                                    $registry,
        \Magento\VisualMerchandiser\Model\RulesFactory                 $rulesFactory,
        TemporaryTableFactory                                          $temporaryTableFactory,
        LoggerInterface                                                $logger
    )
    {
        $this->resource = $resource;
        $this->advanceRuleFactory = $advanceRuleFactory;
        $this->rules = $rules;
        $this->productCollectionFactory = $productCollectionFactory;
        $this->storeManager = $storeManager;
        $this->sorting = $sorting;
        $this->registry = $registry;
        $this->logger = $logger;
        $this->rulesFactory = $rulesFactory;
        $this->tempTableFactory = $temporaryTableFactory;
    }

    /***
     * @param \Magento\Catalog\Model\Category $category
     * @return void
     * @throws \Magento\Framework\Exception\LocalizedException
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function rebuildCategory(\Magento\Catalog\Model\Category $category)
    {
        try {
            /**
             * @var $advanceRule \Branch8\MagentoVisualMerchandiser\Model\AdvanceRule
             */
            $collection = $this->productCollectionFactory->create()->setStoreId(
                $this->storeManager->getStore()->getId()
            );
            if (!$this->registry->registry('amasty_ignore_product_filter')) {
                $this->registry->register('amasty_ignore_product_filter', 1);
            }
            // Apply 'smart rules', this works on the whole product collection
            $this->rules->loadByCategory($category);
            $existingProducts = $this->getPostedProducts($this->rules, $category, $collection);
            $existingProducts = array_keys($existingProducts);
            $collection->getSelect()->reset(Select::WHERE);
            $collection->getSelect()->reset(Select::HAVING);
            $collection->getSelect()->reset(Select::SQL_HAVING);
            $collection->addAttributeToFilter('entity_id', ['in' => $existingProducts]);
            if (count($existingProducts) > 0) {
                $collection->getSelect()->reset(Select::ORDER);
                $collection->getSelect()
                    ->order(new \Zend_Db_Expr('FIELD(e.entity_id, ' . implode(',', $existingProducts) . ')'));
            }
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
            $category->setPostedProducts($positions);
        } catch (\Exception $e) {
            $this->logger->critical($e->getMessage());
            $this->logger->info('Branch8\MagentoVisualMerchandiser\Model\AdvanceRule\Builder::rebuildCategory');
            $this->logger->info('CategoryID:' . $category->getId());
        }

    }

    /**
     * @param \Magento\Catalog\Model\Category $category
     * @param $createIndexTable
     * @return array
     * @throws \Magento\Framework\Exception\LocalizedException
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     * @throws \Zend_Db_Exception
     */
    public function buildCategory(\Magento\Catalog\Model\Category $category, $createIndexTable = true)
    {
        /**
         * @var $advanceRule \Branch8\MagentoVisualMerchandiser\Model\AdvanceRule
         */
        $collection = $this->productCollectionFactory->create()->setStoreId(
            $this->storeManager->getStore()->getId()
        );
        /**
         * @var $ruleCategory \Magento\VisualMerchandiser\Model\Rules
         */
        $ruleCategory = $this->rulesFactory->create()->loadByCategory($category);
        $existingProducts = $this->getPostedProducts($ruleCategory, $category, $collection);
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
     * @param $ruleCategory
     * @param \Magento\Catalog\Model\Category $category
     * @param \Magento\Catalog\Model\ResourceModel\Product\Collection $collection
     * @return array
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function getPostedProducts($ruleCategory, \Magento\Catalog\Model\Category $category, \Magento\Catalog\Model\ResourceModel\Product\Collection $collection): array
    {
        /**
         * @var $advanceRule AdvanceRule
         */
        $advanceRuleConditions = $ruleCategory->getData('advance_conditions_serialized');
        $advanceRule = $this->advanceRuleFactory->create()->setConditionsSerialized($advanceRuleConditions);
        $advanceRule->applyAllRules($category, $collection);
        // Limit the product set to only the products needed
        $existingProducts = $category->getPostedProducts();
        if ($existingProducts === null) {
            $existingProducts = $category->getProductsPosition();
        }
        asort($existingProducts, SORT_NUMERIC);
        return $existingProducts;
    }
}
