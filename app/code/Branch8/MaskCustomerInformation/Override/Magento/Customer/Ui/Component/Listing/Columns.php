<?php

namespace Branch8\MaskCustomerInformation\Override\Magento\Customer\Ui\Component\Listing;

use Branch8\MaskCustomerInformation\Model\Customer\RuleConfig;
use Branch8\MaskCustomerInformation\Model\PermissionInterface;
use Branch8\MaskInformation\Model\MaskRulesComposite;
use Magento\Customer\Api\CustomerMetadataInterface;
use Magento\Customer\Api\Data\AttributeMetadataInterface as AttributeMetadata;
use Magento\Customer\Ui\Component\ColumnFactory;
use Magento\Customer\Ui\Component\Listing\Column\InlineEditUpdater;
use Magento\Framework\View\Element\UiComponent\ContextInterface;

/**
 * Columns component
 */
class Columns extends \Magento\Customer\Ui\Component\Listing\Columns
{
    private $ruleConfig;

    private $permission;

    private $maskRuleComposite;

    /**
     * @param PermissionInterface $permission
     * @param ContextInterface $context
     * @param ColumnFactory $columnFactory
     * @param RuleConfig $ruleConfig
     * @param \Magento\Customer\Ui\Component\Listing\AttributeRepository $attributeRepository
     * @param InlineEditUpdater $inlineEditor
     * @param MaskRulesComposite $maskRulesComposite
     * @param array $components
     * @param array $data
     * @param array $filterConfigProviders
     */
    public function __construct(
        PermissionInterface                                        $permission,
        ContextInterface                                           $context,
        ColumnFactory                                              $columnFactory,
        RuleConfig                                                 $ruleConfig,
        \Magento\Customer\Ui\Component\Listing\AttributeRepository $attributeRepository,
        InlineEditUpdater                                          $inlineEditor,
        MaskRulesComposite                                         $maskRulesComposite,
        array                                                      $components = [],
        array                                                      $data = [],
        array                                                      $filterConfigProviders = []
    )
    {
        $this->maskRuleComposite = $maskRulesComposite;
        $this->ruleConfig = $ruleConfig;
        $this->permission = $permission;
        parent::__construct($context,
            $columnFactory,
            $attributeRepository,
            $inlineEditor,
            $components,
            $data,
            $filterConfigProviders
        );

    }

    /**
     * Update column in component
     *
     * @param array $attributeData
     * @param string $newAttributeCode
     * @return void
     */
    public function updateColumn(array $attributeData, $newAttributeCode)
    {
        $disableEditorFields = $this->ruleConfig->getDisableEditorFields();
        if ($this->permission->canView() || !in_array($attributeData['attribute_code'], $disableEditorFields)) {
            return parent::updateColumn($attributeData, $newAttributeCode);
        }
        $component = $this->components[$attributeData[AttributeMetadata::ATTRIBUTE_CODE]];
        $this->addOptions($component, $attributeData);
        if ($attributeData[AttributeMetadata::BACKEND_TYPE] != 'static') {
            if ($attributeData[AttributeMetadata::IS_USED_IN_GRID]) {
                $config = array_merge(
                    $component->getData('config'),
                    [
                        'name' => $newAttributeCode,
                        'dataType' => $attributeData[AttributeMetadata::BACKEND_TYPE],
                        'visible' => (bool)$attributeData[AttributeMetadata::IS_VISIBLE_IN_GRID]
                    ]
                );
                if ($attributeData[AttributeMetadata::IS_FILTERABLE_IN_GRID]) {
                    $config['filter'] = $this->getFilterConfig(
                        $attributeData,
                        $this->getFilterType($attributeData[AttributeMetadata::FRONTEND_INPUT])
                    );
                }
                $component->setData('config', $config);
            }
        } else {
            if ($attributeData['entity_type_code'] == CustomerMetadataInterface::ENTITY_TYPE_CUSTOMER
                && !empty($component->getData('config')['editor'])
            ) {
                $this->inlineEditUpdater->applyEditing(
                    $component,
                    $attributeData[AttributeMetadata::FRONTEND_INPUT],
                    $attributeData[AttributeMetadata::VALIDATION_RULES],
                    $attributeData[AttributeMetadata::REQUIRED]
                );
            }
            $component->setData(
                'config',
                array_merge(
                    $component->getData('config'),
                    ['visible' => (bool)$attributeData[AttributeMetadata::IS_VISIBLE_IN_GRID]]
                )
            );
        }
        $config = $component->getData('config');
        if (isset($attributeData['options'])) {
            $config['options'] = $this->modifyOptions(
                $newAttributeCode, $attributeData['options']
            );
        }
        $config['editor'] = false;
        $component->setData('config', $config);
    }

    /**
     * @param $code
     * @param $options
     * @return mixed
     */
    private function modifyOptions($code, $options)
    {
        foreach ($options as &$option) {
            if (!$option['value']) {
                continue;
            }
            $option['label'] = $this->maskRuleComposite->mask($code, $option['label']);
        }
        return $options;
    }
}
