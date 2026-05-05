<?php
/**
 * @author Amasty Team
 * @copyright Copyright (c) Amasty (https://www.amasty.com)
 * @package Advanced Permissions for Magento 2
 */

namespace Branch8\AdvancedPermissions\Block\Adminhtml\Role\Tab;

use Magento\Backend\Block\Widget\Form\Generic;
use Magento\Catalog\Model\Category as CategoryModel;
use Magento\Catalog\Model\ResourceModel\Category\CollectionFactory as CategoryCollectionFactory;

class Categories extends Generic implements \Magento\Backend\Block\Widget\Tab\TabInterface
{
    public const MODE_ALL = 0;
    public const MODE_SELECTED = 1;

    /**
     * @var \Magento\Store\Model\System\Store
     */
    protected $_systemStore;

    /**
     * @var CategoryCollectionFactory
     */
    protected CategoryCollectionFactory $collectionFactory;

    public function __construct(
        \Magento\Backend\Block\Template\Context $context,
        \Magento\Framework\Registry $registry,
        \Magento\Framework\Data\FormFactory $formFactory,
        \Magento\Store\Model\System\Store $systemStore,
        CategoryCollectionFactory $collectionFactory,
        array $data = []
    ) {
        $this->_systemStore = $systemStore;
        $this->collectionFactory = $collectionFactory;
        parent::__construct($context, $registry, $formFactory, $data);
    }

    /**
     * Get tab label
     *
     * @return \Magento\Framework\Phrase
     */
    public function getTabLabel()
    {
        return __('Advanced: Categories');
    }

    /**
     * Get tab title
     *
     * @return string
     */
    public function getTabTitle()
    {
        return $this->getTabLabel();
    }

    /**
     * Whether tab is available
     *
     * @return bool
     */
    public function canShowTab()
    {
        return true;
    }

    /**
     * Whether tab is visible
     *
     * @return bool
     */
    public function isHidden()
    {
        return false;
    }

    protected function _prepareForm()
    {
        /** @var \Amasty\Rolepermissions\Model\Rule $model */
        $model = $this->_coreRegistry->registry('amrolepermissions_current_rule');

        /** @var \Magento\Framework\Data\Form $form */
        $form = $this->_formFactory->create();
        $form->setHtmlIdPrefix('rule_');

        $fieldset = $form->addFieldset('amrolepermissions_categories_fieldset', ['legend' => __('Category Access')]);

        $mode = $fieldset->addField('category_access_mode', 'select', [
            'label'  => __('Allow Access To'),
            'id'     => 'amrolepermissions[category_access_mode]',
            'name'   => 'amrolepermissions[category_access_mode]',
            'values' => [
                __('All Categories'),
                __('Selected Categories')
            ]
        ]);

        $tree = $fieldset->addField(
            'categories',
            'hidden',
            [
                'id'   => 'category_id',
                'name' => 'amrolepermissions[categories]',
            ]
        );

        $this->setChild(
            'form_after',
            $this->getLayout()->createBlock(
                \Magento\Backend\Block\Widget\Form\Element\Dependence::class
            )
            ->addFieldMap($mode->getHtmlId(), $mode->getName())
            ->addFieldMap($tree->getHtmlId(), $tree->getName())
            ->addFieldDependence(
                $tree->getName(),
                $mode->getName(),
                1
            )
        );

        $form->addValues($model->getData());
        if (is_array($model->getCategories())) {
            $tree->setValue(implode(',', $model->getCategories()));
        }

        $this->setForm($form);

        return parent::_prepareForm();
    }

    public function getTreeBlock()
    {
        /** @var \Amasty\Rolepermissions\Model\Rule $model */
        $model = $this->_coreRegistry->registry('amrolepermissions_current_rule');

        $categories = $model->getCategories() ?: [];

        $block = $this->getLayout()->createBlock(
            \Magento\Catalog\Block\Adminhtml\Category\Checkboxes\Tree::class,
            'amrolepermissions_widget_chooser_category_ids',
            ['data' => ['js_form_object' => 'amrolepermissions_js_object']]
        )->setCategoryIds($categories);

        return $block;
    }

    public function getSelectedCategories()
    {
        /** @var \Amasty\Rolepermissions\Model\Rule $model */
        $model = $this->_coreRegistry->registry('amrolepermissions_current_rule');

        return $model->getCategories() ?: [];
    }

    public function getCategoriesTree()
    {
        /* @var $collection \Magento\Catalog\Model\ResourceModel\Category\Collection */
        $collection = $this->collectionFactory->create()
            ->addAttributeToSelect('name')
            ->addAttributeToSort('position','asc');

        $categoryById = [
            CategoryModel::TREE_ROOT_ID => [
                'value'    => CategoryModel::TREE_ROOT_ID,
                'optgroup' => null,
            ],
        ];

        foreach ($collection as $category) {
            foreach ([$category->getId(), $category->getParentId()] as $categoryId) {
                if (!isset($categoryById[$categoryId])) {
                    $categoryById[$categoryId] = ['value' => $categoryId];
                }
            }

            $categoryById[$category->getId()]['is_active'] = 1;
            $categoryById[$category->getId()]['label'] = $category->getName();
            $categoryById[$category->getParentId()]['optgroup'][] = &$categoryById[$category->getId()];
        }

        return $categoryById[CategoryModel::TREE_ROOT_ID]['optgroup'];
    }
}
