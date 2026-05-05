<?php
namespace Branch8\HotaiAuth\Block\Adminhtml\Form\Field;

use Magento\Config\Block\System\Config\Form\Field\FieldArray\AbstractFieldArray;

/**
 * Class PointRange
 */
class AesIV extends AbstractFieldArray
{

    /**
     * Prepare rendering the new field by adding all the needed columns
     */
    protected function _prepareToRender()
    {
        $this->addColumn('platform', ['label' => __('Platform'), 'class' => 'required-entry']);
        $this->addColumn('aes_iv', ['label' => __('AES IV'), 'class' => 'required-entry']);
        $this->_addAfter = false;
        $this->_addButtonLabel = __('Add');
    }
}
