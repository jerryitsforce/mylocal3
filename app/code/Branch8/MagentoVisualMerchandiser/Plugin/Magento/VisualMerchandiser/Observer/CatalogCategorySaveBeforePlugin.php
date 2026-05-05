<?php

namespace Branch8\MagentoVisualMerchandiser\Plugin\Magento\VisualMerchandiser\Observer;

use Magento\Framework\Registry;
use Magento\VisualMerchandiser\Model\Category\Builder;
use Magento\VisualMerchandiser\Model\RulesFactory;

class CatalogCategorySaveBeforePlugin
{
    /**
     * @var Builder
     */
    protected $categoryBuilder;

    /**
     * @var RulesFactory
     */
    protected $rulesFactory;
    /**
     * @var \Branch8\MagentoVisualMerchandiser\Model\AdvanceRule\Builder
     */
    private \Branch8\MagentoVisualMerchandiser\Model\AdvanceRule\Builder $advanceRuleBuilder;

    private Registry $registry;

    /**
     * @param Registry $registry
     * @param Builder $categoryBuilder
     * @param \Branch8\MagentoVisualMerchandiser\Model\AdvanceRule\Builder $advanceRuleBuilder
     * @param RulesFactory $rulesFactory
     */
    public function __construct(
        Registry $registry,
        \Magento\VisualMerchandiser\Model\Category\Builder           $categoryBuilder,
        \Branch8\MagentoVisualMerchandiser\Model\AdvanceRule\Builder $advanceRuleBuilder,
        RulesFactory                                                 $rulesFactory
    )
    {
        $this->categoryBuilder = $categoryBuilder;
        $this->rulesFactory = $rulesFactory;
        $this->advanceRuleBuilder = $advanceRuleBuilder;
        $this->registry = $registry;
    }

    /**
     * @param $subject
     * @param callable $proceed
     * @param \Magento\Framework\Event\Observer $observer
     * @return void
     * @throws \Exception
     */
    public function aroundExecute($subject, callable $proceed, \Magento\Framework\Event\Observer $observer)
    {
        if (
            ($category = $observer->getEvent()->getDataObject())
            && $category->getIgnoreRebuildSmartCategory()
            || $this->registry->registry('ignore_rebuild_smart_category')
        ) {
            return;
        }
        /* @var \Magento\Catalog\Model\Category $category */
        $category = $observer->getEvent()->getDataObject();
        // Disable smart category rule after application
        $rules = $this->rulesFactory->create();
        $rule = $rules->loadByCategory($category);
        if ($rule->getId() && $rule->getIsActive()) {
            $useAdvanceRule = (bool)$rule->getData('use_advance_rule');
            if ($useAdvanceRule) {
                $this->advanceRuleBuilder->rebuildCategory($category);
            } else {
                $this->categoryBuilder->rebuildCategory($category);
            }
            $rule->setData([
                'rule_id' => $rule->getId(),
                'category_id' => $category->getId(),
                'is_active' => $rule->getIsActive()
            ]);
            $rule->save();
        }
    }
}
