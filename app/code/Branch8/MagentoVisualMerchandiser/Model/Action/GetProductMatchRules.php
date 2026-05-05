<?php

namespace Branch8\MagentoVisualMerchandiser\Model\Action;

use Branch8\MagentoVisualMerchandiser\Model\AdvanceRuleFactory;
use Magento\Catalog\Model\ProductRepository;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Store\Model\StoreManagerInterface;
use Magento\VisualMerchandiser\Model\ResourceModel\Rules\CollectionFactory;
use Branch8\MagentoVisualMerchandiser\Helper\Logger as LoggerInterface;
use Magento\Framework\DB\Select;
use Magento\VisualMerchandiser\Model\Rules\Rule\Collection\Fetcher;

/**
 *
 */
class GetProductMatchRules
{
    /**
     * @var LoggerInterface
     */
    private LoggerInterface $logger;
    /**
     * @var ProductRepository
     */
    private ProductRepository $productRepository;

    private CollectionFactory $ruleCollectionFactory;

    private \Magento\VisualMerchandiser\Model\Rules\Factory $ruleFactory;
    /**
     * @var StoreManagerInterface
     */
    private $_storeManager;
    /**
     * @var \Magento\Catalog\Model\ResourceModel\Product\CollectionFactory
     */
    private $productCollectionFactory;
    /**
     * @var Fetcher
     */
    private Fetcher $fetcher;
    private AdvanceRuleFactory $advanceRuleFactory;

    /**
     * @param ProductRepository $productRepository
     * @param CollectionFactory $collectionFactory
     * @param \Magento\VisualMerchandiser\Model\Rules\Factory $ruleFactory
     * @param AdvanceRuleFactory $advanceRuleFactory
     * @param \Magento\Catalog\Model\ResourceModel\Product\CollectionFactory $productCollectionFactory
     * @param StoreManagerInterface $storeManager
     * @param Fetcher $fetcher
     * @param LoggerInterface $logger
     */
    public function __construct(
        ProductRepository                                              $productRepository,
        CollectionFactory                                              $collectionFactory,
        \Magento\VisualMerchandiser\Model\Rules\Factory                $ruleFactory,
        AdvanceRuleFactory                                             $advanceRuleFactory,
        \Magento\Catalog\Model\ResourceModel\Product\CollectionFactory $productCollectionFactory,
        StoreManagerInterface                                          $storeManager,
        Fetcher                                                        $fetcher,
        LoggerInterface                                                $logger
    )
    {
        $this->ruleFactory = $ruleFactory;
        $this->advanceRuleFactory = $advanceRuleFactory;
        $this->ruleCollectionFactory = $collectionFactory;
        $this->productRepository = $productRepository;
        $this->logger = $logger;
        $this->productCollectionFactory = $productCollectionFactory;
        $this->_storeManager = $storeManager;
        $this->fetcher = $fetcher;
    }

    /**
     * @param int $productId
     * @param $returnObject
     * @return array
     * @throws NoSuchEntityException
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function get(int $productId, $returnObject = false)
    {
        $ruleMatch = [];
        try {
            $product = $this->productRepository->getById($productId);
        } catch (NoSuchEntityException $exception) {
            return $ruleMatch;
        }
        $rules = $this->ruleCollectionFactory->create()->addFieldToFilter('is_active', 1);
        /**
         * @var $rule \Magento\VisualMerchandiser\Model\Rules
         */
        foreach ($rules as $rule) {
            try {
                $isAdvanceRule = (bool)$rule->getData('use_advance_rule');
                if ($isAdvanceRule) {
                    $this->handleAdvanceRule($rule, $productId, $ruleMatch, $returnObject);
                } else {
                    $this->handleBasicRule($rule, $productId, $ruleMatch, $returnObject);
                }

            } catch (\InvalidArgumentException $e) {
                $this->logger->critical("Branch8\MagentoVisualMerchandiser\Model\Action::get" . $e->getMessage(), ['trace' => $e->getTraceAsString()]);
                continue;
            }
        }
        return $ruleMatch;
    }

    /**
     * @param \Magento\VisualMerchandiser\Model\Rules $rule
     * @param $productId
     * @param $ruleMatch
     * @param $returnObject
     * @return void
     * @throws NoSuchEntityException
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    private function handleBasicRule(\Magento\VisualMerchandiser\Model\Rules $rule, $productId, &$ruleMatch, $returnObject)
    {
        $conditions = $rule->getConditions();
        if (!empty($conditions)
            && $this->isProductRuleAvaiable($productId, $conditions)
        ) {
            $ruleMatch[$rule->getId()] = $returnObject ? $rule : ['rule_id' => $rule->getId(), 'category_id' => $rule->getCategoryId()];
        }
    }

    /**
     * @param \Magento\VisualMerchandiser\Model\Rules $rule
     * @param $productId
     * @param $returnObject
     * @return void
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    private function handleAdvanceRule(\Magento\VisualMerchandiser\Model\Rules $rule, $productId, &$ruleMatch, $returnObject)
    {
        /**
         * @var $advanceRule \Branch8\MagentoVisualMerchandiser\Model\AdvanceRule
         */
        $advanceRuleConditions = $rule->getData('advance_conditions_serialized');
        $advanceRule = $this->advanceRuleFactory->create()->setConditionsSerialized($advanceRuleConditions);
        if ($advanceRule->checkProductCanMatchWith($productId)) {
            $ruleMatch[$rule->getId()] = $returnObject ? $rule : ['rule_id' => $rule->getId(), 'category_id' => $rule->getCategoryId()];
        }
    }

    /**
     * @param $productId
     * @param array $conditions
     * @return bool
     * @throws NoSuchEntityException
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    private function isProductRuleAvaiable($productId, array $conditions)
    {
        $ids = [];
        $logic = "";
        foreach ($conditions as $rule) {
            $websiteId = $this->_storeManager->getStore()->getWebsiteId();// only 1 store
            $_collection = $this->getProductCollectionByWebsite($websiteId, $productId);
            $ruleType = $this->ruleFactory->create($rule);
            $ruleType->applyToCollection($_collection);
            $ids = ($logic == Select::SQL_AND)
                ? array_intersect($ids, $this->fetcher->fetchIds($_collection))
                // phpcs:ignore Magento2.Performance.ForeachArrayMerge
                : array_merge($ids, $this->fetcher->fetchIds($_collection));
            $logic = strtoupper($rule['logic']);
            unset($_collection);
        }
        gc_collect_cycles();
        return count($ids) > 0;
    }

    /**
     * @param int $websiteId
     * @param int $productId
     * @return \Magento\Catalog\Model\ResourceModel\Product\Collection
     * @throws NoSuchEntityException
     */
    private function getProductCollectionByWebsite(int $websiteId, int $productId)
    {
        $productCollection = $this->productCollectionFactory->create()->setStoreId(
            $this->_storeManager->getStore()->getId()
        );
        if ($websiteId) {
            $productCollection->addWebsiteFilter($websiteId);
        }
        $productCollection->addFieldToFilter('entity_id', ['in' => [$productId]]);
        return $productCollection;
    }
}
