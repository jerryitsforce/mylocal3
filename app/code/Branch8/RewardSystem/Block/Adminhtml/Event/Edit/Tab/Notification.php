<?php
namespace Branch8\RewardSystem\Block\Adminhtml\Event\Edit\Tab;

class Notification extends \Magento\Backend\Block\Widget\Form\Generic
{

    public function __construct(
        \Magento\Backend\Block\Template\Context $context,
        \Magento\Framework\Registry $registry,
        \Magento\Framework\Data\FormFactory $formFactory,
        array $data = []
    ) {
        parent::__construct($context, $registry, $formFactory, $data);
    }
    /**
     * Prepare form data
     *
     * @return \Magento\Backend\Block\Widget\Form
     */
    protected function _prepareForm()
    {
        $model = $this->_coreRegistry->registry('event_data');
        $form = $this->_formFactory->create();
        $form->setHtmlIdPrefix('event_');
        $fieldset = $form->addFieldset(
            'notification',
            [
                'legend' => __('Notification Settings Management'), 
                'class' => 'fieldset-wide'
            ]
        );
        
        $fieldset->addField(
            'notification_title',
            'text',
            [
                'name' => 'notification_title',
                'label' => __('Notification Title (Maximum 100 characters)'),
                'id' => 'notification_title',
                'title' => __('Notification Title (Maximum 100 characters)'),
                'class' => 'required-entry custom-noti-title-validate',
                'required' => true,
                'after_element_html' => '<span id="cnt_title">0</span>/100 '.__('characters').' '.__('Supported dynamic variables: {customer_name}, {reward_code}, {reward_title}, {expiry_date}')
            ]
        );
        $fieldset->addField(
            'notification_content',
            'textarea',
            [
                'name' => 'notification_content',
                'label' => __('Notification Content (Maximum 500 characters)'),
                'id' => 'notification_content',
                'title' => __('Notification Content (Maximum 500 characters)'),
                'class' => 'required-entry custom-noti-content-validate',
                'required' => true,
                'after_element_html' => '<span id="cnt_content">0</span>/500 '.__('characters').' '.__('Supported dynamic variables: {customer_name}, {reward_code}, {reward_title}, {expiry_date}')
            ]
        );

        $previewBlock = $this->getLayout()->createBlock(\Branch8\RewardSystem\Block\Adminhtml\Event\Edit\Tab\NotificationPreview::class)->toHtml();
        $fieldset->addField(
            'preview_notification',
            'note',
            [
                'text' => $previewBlock
            ]
        );
       
        
        $data = [];
        if($model){
            $data = $model->getData();
        }
        $form->setValues($data);
        $this->setForm($form);
        return parent::_prepareForm();
    }
}