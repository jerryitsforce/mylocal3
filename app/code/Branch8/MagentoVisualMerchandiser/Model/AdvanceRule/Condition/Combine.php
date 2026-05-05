<?php
declare(strict_types=1);

namespace Branch8\MagentoVisualMerchandiser\Model\AdvanceRule\Condition;

use Branch8\Catalog\Model\Source\HiddenType;
use Branch8\Catalog\Ui\DataProvider\Product\IsHidden;
use Magento\Framework\App\Config\ScopeConfigInterface;

/**
 * Combination of product conditions
 */
class Combine extends \Magento\Rule\Model\Condition\Combine
{
    /**
     * @var \Branch8\MagentoVisualMerchandiser\Model\Rule\Condition\ProductFactory
     */
    protected $productFactory;

    /**
     * {@inheritdoc}
     */
    protected $elementName = 'smart_category_advance_rules';

    /**
     * @param \Magento\Rule\Model\Condition\Context $context
     * @param ProductFactory $conditionFactory
     * @param \Magento\VisualMerchandiser\Model\Rules $rules
     * @param ScopeConfigInterface $scopeConfig
     * @param array $data
     */
    public function __construct(
        \Magento\Rule\Model\Condition\Context                                         $context,
        \Branch8\MagentoVisualMerchandiser\Model\AdvanceRule\Condition\ProductFactory $conditionFactory,
        \Magento\VisualMerchandiser\Model\Rules                                       $rules,
        ScopeConfigInterface                                                          $scopeConfig,
        array                                                                         $data = []
    )
    {
        $this->productFactory = $conditionFactory;
        parent::__construct($context, $data);
        $this->scopeConfig = $scopeConfig;
        $this->setType(\Branch8\MagentoVisualMerchandiser\Model\AdvanceRule\Condition\Combine::class);
    }

    /**
     * @return array
     */
    public function getNewChildSelectOptions()
    {
        $attributesString = $this->scopeConfig->getValue(\Magento\VisualMerchandiser\Model\Rules::XML_PATH_AVAILABLE_ATTRIBUTES);
        $attributes = is_string($attributesString) ? explode(',', $attributesString) : [];
        $configAttributes = array_map('trim', $attributes);
        $productAttributes = $this->productFactory->create()->loadAttributeOptions()->getAttributeOption();
        $attributes = [];
        foreach ($productAttributes as $code => $label) {
            if (in_array($code, $configAttributes)) {
                $attributes[$code] = [
                    'value' => Product::class . '|' . $code,
                    'label' => $label,
                ];
            }
        }
        asort($attributes);
        $conditions = parent::getNewChildSelectOptions();
        return array_merge_recursive(
            $conditions,
            [
                [
                    'value' => \Branch8\MagentoVisualMerchandiser\Model\AdvanceRule\Condition\Combine::class,
                    'label' => __('Conditions Combination'),
                ],
                ['label' => __('Product Attribute'), 'value' => $attributes]
            ]
        );
    }

    /**
     * Collect validated attributes for Product Collection
     *
     * @param \Magento\Catalog\Model\ResourceModel\Product\Collection $productCollection
     * @return $this
     */
    public function collectValidatedAttributes($productCollection)
    {
        $hasIsHidden = false;
        foreach ($this->getConditions() as $condition) {
            if ($condition->getAttribute() === 'is_hidden') {
                $hasIsHidden = true;
            }
            $condition->collectValidatedAttributes($productCollection);
        }
        if (!$hasIsHidden) {
            $productCollection->addFieldToFilter('is_hidden', ['eq' => HiddenType::NOT_HIDDEN]);
        }
        return $this;
    }

    /**
     * @return \Magento\Framework\Data\Form\Element\AbstractElement
     */
    public function getTypeElement()
    {
        return $this->getForm()->addField(
            $this->getPrefix() . '__' . $this->getId() . '__type',
            'hidden',
            [
                'name' => $this->elementName . '[' . $this->getPrefix() . '][' . $this->getId() . '][type]',
                'value' => $this->getType(),
                'no_span' => true,
                'class' => 'hidden',
                'data-form-part' => $this->getFormName() ?: 'category_form'
            ]
        );
    }

    /**
     * @return string
     */
    public function getFormName()
    {
        return 'category_form';
    }
}
