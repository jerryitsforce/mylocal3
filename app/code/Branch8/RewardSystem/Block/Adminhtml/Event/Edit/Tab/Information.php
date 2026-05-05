<?php
namespace Branch8\RewardSystem\Block\Adminhtml\Event\Edit\Tab;

use Branch8\SalesRule\Block\Widget\Grid\Column\Filter\Datetime;

class Information extends \Magento\Backend\Block\Widget\Form\Generic
{
    protected $yesnoConfig;

    protected $wysiwygConfig;

    protected $userLimit;

    protected $eventLimit;

    protected $timezone;

    public function __construct(
        \Magento\Backend\Block\Template\Context $context,
        \Magento\Framework\Registry $registry,
        \Magento\Framework\Data\FormFactory $formFactory,
        \Magento\Config\Model\Config\Source\Yesno $yesnoConfig,
        \Magento\Cms\Model\Wysiwyg\Config $wysiwygConfig,
        \Branch8\RewardSystem\Model\Config\Source\EventLimit $eventLimit,
        \Branch8\RewardSystem\Model\Config\Source\UserLimit $userLimit,
        \Magento\Framework\Stdlib\DateTime\TimezoneInterface $timezone,
        array $data = []
    ) {
        parent::__construct($context, $registry, $formFactory, $data);
        $this->yesnoConfig = $yesnoConfig;
        $this->wysiwygConfig = $wysiwygConfig;
        $this->eventLimit = $eventLimit;
        $this->userLimit = $userLimit;
        $this->timezone = $timezone;
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
            'information',
            ['legend' => __('Information'), 'class' => 'fieldset-wide']
        );
        $fieldset->addField('entity_id', 'hidden', ['name' => 'entity_id']);
        $fieldset->addField(
            'title',
            'text',
            [
                'name' => 'title',
                'label' => __('Title'),
                'id' => 'title',
                'title' => __('Title'),
                'class' => 'required-entry',
                'required' => true,
            ]
        );
        $fieldset->addField(
            'status',
            'select',
            [
                'name' => 'status',
                'label' => __('Status'),
                'id' => 'status',
                'title' => __('Status'),
                'class' => 'required-entry',
                'required' => true,
                'values' => $this->yesnoConfig->toArray()
            ]
        );

        $fieldset->addField(
            'user_limit_daily',
            'text',
            [
                'name' => 'user_limit_daily',
                'label' => __('Daily Usage Limit per User'),
                'id' => 'user_limit_daily',
                'title' => __('Daily Usage Limit per User'),
                'class' => 'validate-number'
            ]
        );

        $fieldset->addField(
            'user_limit_total',
            'text',
            [
                'name' => 'user_limit_total',
                'label' => __('Total Usage Limit per User'),
                'id' => 'user_limit_total',
                'title' => __('Total Usage Limit per User'),
                'class' => 'validate-number'
            ]
        );

        $fieldset->addField(
            'event_limit_daily',
            'text',
            [
                'name' => 'event_limit_daily',
                'label' => __('Daily Usage Limit for the Entire Campaign'),
                'id' => 'event_limit_daily',
                'title' => __('Daily Usage Limit for the Entire Campaign'),
                'class' => 'validate-number'
            ]
        );

        $fieldset->addField(
            'event_limit_total',
            'text',
            [
                'name' => 'event_limit_total',
                'label' => __('Total Usage Limit for the Entire Campaign'),
                'id' => 'event_limit_total',
                'title' => __('Total Usage Limit for the Entire Campaign'),
                'class' => 'validate-number'
            ]
        );

        $fieldset->addField(
            'start_date',
            'date',
            [
                'name' => 'start_date',
                'label' => __('Start Time'),
                'id' => 'start_date',
                'title' => __('Start Time'),
                'required' => true,
                'class' => 'ars-readonly',
                'date_format' => 'yyyy-MM-dd',
                'time_format' => 'H:m:00'
            ]
        );
        $fieldset->addField(
            'end_date',
            'date',
            [
                'name' => 'end_date',
                'label' => __('End Time'),
                'id' => 'end_date',
                'title' => __('End Time'),
                'required' => true,
                'class' => 'ars-readonly ars-end-date',
                'date_format' => 'yyyy-MM-dd',
                'time_format' => 'H:m:00'
            ]
        );

        $fieldset->addField(
            'description',
            'editor',
            [
                'name' => 'description',
                'label' => __('Description'),
                'id' => 'description',
                'title' => __('Description'),
                'required' => true,
                'class' => '',
                'wysiwyg' => true,
                'config' => $this->wysiwygConfig->getConfig()
            ]
        );
        
        $data = [];
        if($model){
            $data = $model->getData();
        }
        if(isset($data['start_date']) && $data['start_date']){
            $data['start_date'] = $this->timezone->date(new \Datetime($data['start_date']))->format('Y-m-d H:i:s');
        }
        if(isset($data['end_date']) && $data['end_date']){
            $data['end_date'] = $this->timezone->date(new \Datetime($data['end_date']))->format('Y-m-d H:i:s');
        }
        $form->setValues($data);
        $this->setForm($form);
        return parent::_prepareForm();
    }
}