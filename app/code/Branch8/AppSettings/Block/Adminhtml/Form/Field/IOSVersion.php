<?php
namespace Branch8\AppSettings\Block\Adminhtml\Form\Field;


class IOSVersion extends AbstractVersion
{
    /**
     * Prepare rendering the new field by adding all the needed columns
     */
    protected function _prepareToRender()
    {
        $this->addColumn('major', ['label' => __('Major'), 'class' => 'required-entry validate-zero-or-greater validate-digits']);
        $this->addColumn('minor', ['label' => __('Minor'), 'class' => 'required-entry validate-zero-or-greater validate-digits']);
        $this->addColumn('build', ['label' => __('Build Number'), 'class' => 'required-entry validate-zero-or-greater validate-digits']);
        $this->_addAfter = false;
        $this->_addButtonLabel = __('Add');
    }
}
