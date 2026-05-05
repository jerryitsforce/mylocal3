<?php

namespace Branch8\MagentoVisualMerchandiser\Plugin\Magento\VisualMerchandiser\Observer;

use Branch8\MagentoVisualMerchandiser\Model\AdvanceRule;
use Magento\Catalog\Model\Category;

class CategorySaveMerchandiserDataPlugin
{
    /**
     * @var \Magento\VisualMerchandiser\Model\Position\Cache
     */
    protected $_cache;
    /**
     * @var \Magento\VisualMerchandiser\Model\Rules
     */
    protected $_rules;

    /**
     * @var \Magento\Catalog\Api\CategoryRepositoryInterface
     */
    protected $categoryRepository;
    /**
     * @var AdvanceRule
     */
    private AdvanceRule $advanceRule;

    /**
     * @param \Magento\VisualMerchandiser\Model\Position\Cache $cache
     * @param \Magento\VisualMerchandiser\Model\Rules $rules
     * @param \Magento\Catalog\Api\CategoryRepositoryInterface $categoryRepository
     * @param AdvanceRule $advanceRule
     */
    public function __construct(
        \Magento\VisualMerchandiser\Model\Position\Cache $cache,
        \Magento\VisualMerchandiser\Model\Rules          $rules,
        \Magento\Catalog\Api\CategoryRepositoryInterface $categoryRepository,
        AdvanceRule                                      $advanceRule
    )
    {
        $this->_cache = $cache;
        $this->_rules = $rules;
        $this->categoryRepository = $categoryRepository;
        $this->advanceRule = $advanceRule;
    }

    /**
     * @param $subject
     * @param callable $proceed
     * @param \Magento\Framework\Event\Observer $observer
     * @return void
     */
    public function aroundExecute($subject, callable $proceed, \Magento\Framework\Event\Observer $observer)
    {
        if (($category = $observer->getEvent()->getCategory()) && $category->getIgnoreRebuildSmartCategory()) {
            return;
        }
        $category = $observer->getEvent()->getCategory();
        $cacheKey = $observer->getEvent()->getRequest()->getPostValue(
            \Magento\VisualMerchandiser\Model\Position\Cache::POSITION_CACHE_KEY
        );
        $positions = $this->_cache->getPositions($cacheKey);
        if (is_array($positions)) {
            $category->setPostedProducts(
                $positions
            );
        }
        if (!$this->validateData($observer)) {
            return;
        }
        // Save smart category rules (or clear it)
        /**
         * @var \Magento\VisualMerchandiser\Model\Rules
         */
        $postData = $observer->getEvent()->getRequest()->getPostValue();
        $rule = $this->_rules->loadByCategory($category);
        $useAdvanceRule = (bool)$observer->getEvent()->getRequest()->getPostValue('use_advance_rule', false);
        if ($rule->getId() !== null || !empty($postData['smart_category_rules'] || !empty($postData['smart_category_advance_rules']))) {
            $ruleOrigData = $rule->getOrigData();
            $advanceRuleData = '';
            if (isset($postData['smart_category_advance_rules'])) {
                $params = ['conditions' => $postData['smart_category_advance_rules']['conditions']];
                $advanceRuleData = $this->advanceRule->loadPost($params)->serializedConditions();
            }
            if ($ruleOrigData) {
                $category->setOrigData('is_smart_category', $ruleOrigData['is_active']);
                $category->setOrigData('smart_category_rules', $ruleOrigData['conditions_serialized']);
            }
            $rule->setData([
                'rule_id' => $rule->getId(),
                'category_id' => $category->getId(),
                'is_active' => $postData['is_smart_category'] == 1 ? '1' : '0',
                'conditions_serialized' => $postData['smart_category_rules'] ?? '',
                'use_advance_rule' => $useAdvanceRule,
                'advance_conditions_serialized' => $advanceRuleData ?: '',
            ]);
            $rule->save();
        }

    }

    /**
     * @param \Magento\Framework\Event\Observer $observer
     * @return bool
     */
    private function validateData(\Magento\Framework\Event\Observer $observer): bool
    {
        /** @var Category $category */
        $category = $observer->getEvent()->getCategory();
        $postData = $observer->getEvent()->getRequest()->getPostValue();

        return !($category->isObjectNew() || !$category->getId() || empty($category->getOrigData())
            || !isset($postData['is_smart_category']));
    }
}
