<?php
namespace Branch8\ProductPoint\Block\Adminhtml\System\Config;

use Magento\Config\Block\System\Config\Form\Field\FieldArray\AbstractFieldArray;
use Magento\Framework\DataObject;
use Magento\Framework\Exception\LocalizedException;

/**
 * Class PointRange
 */
class PointRange extends AbstractFieldArray
{

    /**
     * Prepare rendering the new field by adding all the needed columns
     */
    protected function _prepareToRender()
    {
        $this->addColumn('from', ['label' => __('From of Point'), 'class' => 'required-entry validate-number']);
        $this->addColumn('to', ['label' => __('To of Point'), 'class' => 'required-entry validate-number']);
        $this->addColumn('rec1', ['label' => __('Price Range1'), 'class' => 'required-entry']);
        $this->addColumn('rec2', ['label' => __('Price Range2'), 'class' => 'required-entry']);
        $this->addColumn('rec3', ['label' => __('Price Range3'), 'class' => 'required-entry']);
        $this->_addAfter = false;
        $this->_addButtonLabel = __('Add');
    }
}
