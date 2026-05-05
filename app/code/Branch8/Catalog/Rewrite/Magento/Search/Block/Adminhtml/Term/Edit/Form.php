<?php
/**
 * Copyright ©  All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Branch8\Catalog\Rewrite\Magento\Search\Block\Adminhtml\Term\Edit;

class Form extends \Magento\Search\Block\Adminhtml\Term\Edit\Form
{
    protected function _prepareForm()
    {
        parent::_prepareForm();
        $form = $this->getForm();
        $model = $this->_coreRegistry->registry('current_catalog_search');
        $yesno = [['value' => 0, 'label' => __('No')], ['value' => 1, 'label' => __('Yes')]];
        $baseFieldset = $form->getElement('base_fieldset');
        $baseFieldset->addField(
            'is_pin_search_term',
            'select',
            [
                'name' => 'is_pin_search_term',
                'label' => __('Is pin search term'),
                'title' => __('Is pin search term'),
                'values' => $yesno
            ]
        );
        $form->setValues($model->getData());
        return $this;
    }
}