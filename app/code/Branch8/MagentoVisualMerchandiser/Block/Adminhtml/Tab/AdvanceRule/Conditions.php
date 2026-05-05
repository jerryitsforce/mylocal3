<?php
declare(strict_types=1);

namespace Branch8\MagentoVisualMerchandiser\Block\Adminhtml\Tab\AdvanceRule;

use Magento\Backend\Block\Widget\Form\Renderer\Fieldset;

/**
 *
 */
class Conditions extends \Magento\Backend\Block\Widget\Form\Generic implements
    \Magento\Backend\Block\Widget\Tab\TabInterface
{
    /**
     * @var \Branch8\MagentoVisualMerchandiser\Block\Adminhtml\AdvanceRule\Conditions
     */
    private $_conditions;
    /**
     * @var \Magento\Backend\Block\Widget\Form\Renderer\Fieldset
     */
    private $_fieldset;
    /**
     * @var \Magento\VisualMerchandiser\Model\Rules
     */
    private $_rules;
    /**
     * @var \Branch8\MagentoVisualMerchandiser\Model\AdvanceRuleFactory
     */
    private $advanceRuleFactory;

    /**
     * @param \Magento\Backend\Block\Template\Context $context
     * @param \Magento\Framework\Registry $registry
     * @param \Magento\Framework\Data\FormFactory $formFactory
     * @param Fieldset $fieldset
     * @param \Branch8\MagentoVisualMerchandiser\Block\Adminhtml\AdvanceRule\Conditions $conditions
     * @param \Branch8\MagentoVisualMerchandiser\Model\AdvanceRuleFactory $advanceRuleFactory
     * @param \Magento\VisualMerchandiser\Model\Rules $rules
     * @param array $data
     */
    public function __construct(
        \Magento\Backend\Block\Template\Context                                   $context,
        \Magento\Framework\Registry                                               $registry,
        \Magento\Framework\Data\FormFactory                                       $formFactory,
        \Magento\Backend\Block\Widget\Form\Renderer\Fieldset                      $fieldset,
        \Branch8\MagentoVisualMerchandiser\Block\Adminhtml\AdvanceRule\Conditions $conditions,
        \Branch8\MagentoVisualMerchandiser\Model\AdvanceRuleFactory               $advanceRuleFactory,
        \Magento\VisualMerchandiser\Model\Rules                                   $rules,
        array                                                                     $data = []
    )
    {
        $this->_conditions = $conditions;
        $this->_fieldset = $fieldset;
        $this->advanceRuleFactory = $advanceRuleFactory;
        $this->_rules = $rules;
        parent::__construct($context, $registry, $formFactory, $data);
    }

    public function getSmartCategoryRules()
    {
        $category = $this->_coreRegistry->registry('current_category');
        $rules = $this->_rules->loadByCategory($category);
        return $this->escapeHtml($rules->getConditionsSerialized());
    }

    /**
     * @return Conditions
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    protected function _prepareForm()
    {
        $category = $this->_coreRegistry->registry('current_category');
        $model = $this->advanceRuleFactory->create();
        $model->setData('form_name', 'category_form');
        $rules = $this->_rules->loadByCategory($category);

        if ($rules->getData('advance_conditions_serialized')) {
            $model->setConditionsSerialized($rules->getData('advance_conditions_serialized'));
        }
        /** @var \Magento\Framework\Data\Form $form */
        $form = $this->_formFactory->create();
        $form->setHtmlIdPrefix('rule_');
        $fieldset = $form->addFieldset(
            'conditions_fieldset',
            []
        );
        $newCondUrl = $this->getUrl('merchandiser/visualMerchandiser/newConditionHtml/', ['form' => $fieldset->getHtmlId()]);
        $renderer = $this->getLayout()->createBlock(Fieldset::class);
        $renderer->setTemplate(
            'Branch8_MagentoVisualMerchandiser::category/advance_rules_fieldset.phtml'
        )->setNewChildUrl(
            $newCondUrl
        );

        $fieldset->setRenderer($renderer);
        $element = $fieldset->addField('conditions', 'text', ['name' => 'conditions', 'required' => true]);
        $element->setRule($model);
        $element->setRenderer($this->_conditions);
        $model->getConditions()->setJsFormObject($fieldset->getHtmlId());
        $form->setValues($model->getData());
        $this->setForm($form);
        return parent::_prepareForm();
    }

    /**
     * Retrieve Tab label
     *
     * @return \Magento\Framework\Phrase
     */
    public function getTabLabel()
    {
        return __('Products to Match');
    }

    /**
     * Retrieve Tab title
     *
     * @return \Magento\Framework\Phrase
     */
    public function getTabTitle()
    {
        return __('Products to Match');
    }

    /**
     * Check is can show tab
     *
     * @return bool
     */
    public function canShowTab()
    {
        return true;
    }

    /**
     * Check tab is hidden
     *
     * @return bool
     */
    public function isHidden()
    {
        return false;
    }
}
