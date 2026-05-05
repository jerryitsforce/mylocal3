<?php
declare(strict_types=1);

namespace Branch8\RmaAdminUi\Override\Webkul\MpRmaSystem\Block\Adminhtml\Reasons\Edit;
class Form extends \Magento\Backend\Block\Widget\Form\Generic
{
    /**
     * Initialize form
     */
    protected function _construct()
    {
        parent::_construct();
        $this->setId('reasons_form');
        $this->setTitle(__('Reason'));
    }

    /**
     * Prepare form.
     *
     * @return $this
     */
    protected function _prepareForm()
    {
        $model = $this->_coreRegistry->registry('mprmasystem_reasons');
        $form = $this->_formFactory->create(
            ['data' =>
                [
                    'id' => 'edit_form',
                    'enctype' => 'multipart/form-data',
                    'action' => $this->getData('action'),
                    'method' => 'post'
                ]
            ]
        );
        $fieldset = $form->addFieldset(
            'base_fieldset',
            ['legend' => __('About Reason'), 'class' => 'fieldset-wide']
        );
        if ($model->getId()) {
            $fieldset->addField('id', 'hidden', ['name' => 'id']);
        }

        $fieldset->addField(
            'reason',
            'text',
            [
                'name' => 'reason',
                'label' => __('Reason'),
                'title' => __('Reason'),
                'required' => true,
            ]
        );
        $fieldset->addField(
            'status',
            'select',
            [
                'label' => __('Status'),
                'title' => __('Status'),
                'name' => 'status',
                'required' => true,
                'options' => ['1' => __('Enabled'), '0' => __('Disabled')],
            ]
        );

        $tags = $fieldset->addField(
            'tags',
            'note',
            [
                'label' => __('Tags'),
                'title' => __('Tags'),
                'name' => 'tags',
                'required' => false,
            ]
        );
        $note = $fieldset->addField(
            'note',
            'textarea',
            [
                'label' => __('Note'),
                'title' => __('Note'),
                'name' => 'note',
                'required' => false,
            ]
        );

        $tagRenderer = $this->getLayout()->createBlock(
            \Branch8\RmaAdminUi\Block\Adminhtml\Form\Renderer\Tags::class
        )->setReason(
            $model
        );
        $tags->setRenderer($tagRenderer);
        $form->setValues($model->getData());
        $form->setUseContainer(true);
        $this->setForm($form);
        return parent::_prepareForm();
    }
}
