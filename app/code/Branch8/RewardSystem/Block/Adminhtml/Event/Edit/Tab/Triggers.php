<?php
namespace Branch8\RewardSystem\Block\Adminhtml\Event\Edit\Tab;

class Triggers extends \Magento\Backend\Block\Widget\Form\Generic
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
            'triggers',
            ['legend' => __('Triggers'), 'class' => 'fieldset-wide']
        );

        $triggersBlock = $this->getLayout()->createBlock(\Branch8\RewardSystem\Block\Adminhtml\Event\Edit\Tab\TriggersDetail::class);
        $triggersBlock->setData('triggerData', $model->getData('trigger_condition'));
        $fieldset->addField(
            'trigger_html',
            'note',
            [
                'text' => $triggersBlock->toHtml()
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