<?php
namespace Branch8\AdvancedPermissions\Block\Adminhtml\Role\Tab;

use Magento\Backend\Block\Widget\Form\Generic;

class Block extends Generic
{
    public const MODE_ANY = 0;

    public const MODE_SELECTED = 1;

    protected function _prepareForm()
    {
        /** @var \Amasty\Rolepermissions\Model\Rule $model */
        $model = $this->_coreRegistry->registry('amrolepermissions_current_rule');

        /** @var \Magento\Framework\Data\Form $form */
        $form = $this->_formFactory->create();
        $form->setHtmlIdPrefix('rule_');

        $fieldset = $form->addFieldset(
            'amrolepermissions_blocks_fieldset',
            ['legend' => __('Blocks Access')]
        );

        $grid = $this->getChildBlock('grid');

        $mode = $fieldset->addField(
            'block_access_mode',
            'select',
            [
                'label'  => __('Allow Access To'),
                'id'     => 'amrolepermissions[block_access_mode]',
                'name'   => 'amrolepermissions[block_access_mode]',
                'values' => [
                    self::MODE_ANY      => __('All Block'),
                    self::MODE_SELECTED => __('Selected Block')
                ]
            ]
        );

        $fieldset->addField(
            'block_list',
            'hidden',
            [
                'after_element_html' =>  "<div>{$grid->toHtml()}</div>",
            ]
        );

        $form->addValues($model->getData());
        $this->setForm($form);
        $formAfter = $this->getLayout()->createBlock(\Magento\Backend\Block\Widget\Form\Element\Dependence::class)
            ->addFieldMap($mode->getHtmlId(), $mode->getName())
            ->addFieldMap('amrolepremissions_allowed_block_grid', 'amrolepremissions_allowed_block_grid')
            ->addFieldDependence(
                'amrolepremissions_allowed_block_grid',
                $mode->getName(),
                self::MODE_SELECTED
            );
        $this->setChild(
            'form_after',
            $formAfter
        );

        return parent::_prepareForm();
    }
}
