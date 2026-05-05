<?php
declare(strict_types=1);

namespace Branch8\MagentoVisualMerchandiser\Block\Adminhtml\Widget;

/**
 * @api
 * @since 100.0.2
 */
class UseAdvanceRuleSwitcher extends \Magento\Backend\Block\Widget
{
    /**
     * @var \Magento\Framework\Registry
     */
    protected $registry;

    /**
     * @var \Magento\VisualMerchandiser\Model\Rules
     */
    protected $rules;

    private $loadedRules;

    /**
     * @param \Magento\Backend\Block\Template\Context $context
     * @param \Magento\VisualMerchandiser\Model\Rules $rules
     * @param \Magento\Framework\Registry $registry
     * @param array $data
     */
    public function __construct(
        \Magento\Backend\Block\Template\Context $context,
        \Magento\VisualMerchandiser\Model\Rules $rules,
        \Magento\Framework\Registry             $registry,
        array                                   $data = []
    )
    {
        parent::__construct($context, $data);
        $this->registry = $registry;
        $this->rules = $rules;
    }

    /**
     * @return bool
     */
    public function isUseAdvanceRuleSwitcher()
    {
        if ($this->getRules()) {
            return (bool)$this->getRules()->getData('use_advance_rule');
        }
        return false;
    }

    /**
     * @return bool
     */
    public function smartCategoryEnabled()
    {
        if ($this->getRules()) {
            return (bool)$this->getRules()->getIsActive();
        }
        return false;
    }

    /**
     * @return \Magento\VisualMerchandiser\Model\Rules
     */
    private function getRules()
    {
        $category = $this->registry->registry('current_category');
        if ($category) {
            $this->loadedRules = $this->rules->loadByCategory($category);
        }
        return $this->loadedRules;
    }
}
