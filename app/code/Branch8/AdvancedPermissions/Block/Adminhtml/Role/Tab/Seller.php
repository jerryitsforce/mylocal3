<?php
namespace Branch8\AdvancedPermissions\Block\Adminhtml\Role\Tab;

use Magento\Backend\Block\Widget\Form\Generic;

class Seller extends Generic
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

        $fieldset = $form->addFieldset('amrolepermissions_sellers_fieldset', ['legend' => __('Seller Access')]);

        $grid = $this->getChildBlock('grid');

        $mode = $fieldset->addField(
            'seller_access_mode',
            'select',
            [
                'label'  => __('Allow Access To'),
                'id'     => 'amrolepermissions[seller_access_mode]',
                'name'   => 'amrolepermissions[seller_access_mode]',
                'values' => [
                    self::MODE_ANY      => __('All Seller'),
                    self::MODE_SELECTED => __('Selected Seller')
                ]
            ]
        );

        $fieldset->addField(
            'seller_list',
            'hidden',
            [
                'after_element_html' => "<div>{$grid->toHtml()}</div>",
            ]
        );

        $form->addValues($model->getData());
        $this->setForm($form);
        $formAfter = $this->getLayout()->createBlock(\Magento\Backend\Block\Widget\Form\Element\Dependence::class)
            ->addFieldMap($mode->getHtmlId(), $mode->getName())
            ->addFieldMap('amrolepremissions_allowed_seller_grid', 'amrolepremissions_allowed_seller_grid')
            ->addFieldDependence(
                'amrolepremissions_allowed_seller_grid',
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
