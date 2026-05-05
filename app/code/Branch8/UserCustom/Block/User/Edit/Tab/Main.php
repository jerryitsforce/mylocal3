<?php

namespace Branch8\UserCustom\Block\User\Edit\Tab;

class Main extends \Magento\Backend\Block\Widget\Form\Generic
{
    protected $cmspage;
    public function __construct(
        \Magento\Backend\Block\Template\Context $context,
        \Magento\Framework\Registry $registry,
        \Magento\Framework\Data\FormFactory $formFactory,
        \Branch8\UserCustom\Model\Source\CmsPages $cmspage,
        array $data = []
    ) {
        $this->cmspage = $cmspage;
        parent::__construct($context, $registry, $formFactory, $data);
    }

    /**
     * Prepare form fields
     *
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     * @return \Magento\Backend\Block\Widget\Form
     */
    protected function _prepareForm()
    {
        $model = $this->_coreRegistry->registry('permissions_user');
        $form = $this->_formFactory->create();
        $form->setHtmlIdPrefix('user_');
        $baseFieldset = $form->addFieldset('base_fieldset', ['legend' => __('CMS Page')]);
        if ($model->getUserId()) {
            $baseFieldset->addField('user_id', 'hidden', ['name' => 'user_id']);
        } else {
            if (!$model->hasData('is_active')) {
                $model->setIsActive(1);
            }
        }
        $baseFieldset->addField(
            'cms_page',
            'multiselect',
            [
                'name' => 'cms_page',
                'data-form-part' => $this->getData('target_form'),
                'label' => __('All Page'),
                'title' => __('All Page'),
                'value' => '',
                'values' => $this->cmspage->toOptionArray(),
            ]
        );
        $data = $model->getData();
        $form->setValues($data);

        $this->setForm($form);

        return parent::_prepareForm();
    }
}
