<?php

declare(strict_types=1);

namespace Branch8\Sales\Block\Adminhtml\Form\Field;

use Magento\Config\Block\System\Config\Form\Field\FieldArray\AbstractFieldArray;

class LogisticsCompany extends AbstractFieldArray
{
    /**
     * @inheritdoc
     */
    protected function _prepareToRender(): void
    {
        $this->addColumn('name', [
            'label' => __('Company Name'),
            'class' => 'required-entry'
        ]);
        $this->addColumn('url', [
            'label' => __('URL'),
            'class' => 'required-entry'
        ]);

        $this->_addAfter = false;
        $this->_addButtonLabel = __('Add');
    }
}
