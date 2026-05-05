<?php
namespace Branch8\RewardSystem\Block\Adminhtml\Event\Edit\Tab;

class Reward extends \Magento\Backend\Block\Widget\Form\Generic
{
    protected $rewardType;

    protected $ruleOptions;

    protected $poolOptions;

    public function __construct(
        \Magento\Backend\Block\Template\Context $context,
        \Magento\Framework\Registry $registry,
        \Magento\Framework\Data\FormFactory $formFactory,
        \Branch8\RewardSystem\Model\Config\Source\RewardType $rewardType,
        \Branch8\RewardSystem\Model\Config\Source\RuleOptions $ruleOptions,
        \Branch8\RewardSystem\Model\Config\Source\PoolOptions $poolOptions,
        array $data = []
    ) {
        parent::__construct($context, $registry, $formFactory, $data);
        $this->rewardType = $rewardType;
        $this->ruleOptions = $ruleOptions;
        $this->poolOptions = $poolOptions;
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
            'reward',
            ['legend' => __('Reward'), 'class' => 'fieldset-wide']
        );
        $fieldset->addField(
            'reward_type',
            'select',
            [
                'name' => 'reward_type',
                'label' => __('Reward Type'),
                'id' => 'reward_type',
                'title' => __('Reward Type'),
                'class' => 'required-entry',
                'required' => true,
                'values' => array_merge(['' => __('Please select')], $this->rewardType->toOptionArray())
            ]
        );
        /*
        * Coupon select
        */
        // $fieldset->addField(
        //     'reward_rule_id',
        //     'select',
        //     [
        //         'name' => 'rule_id',
        //         'label' => __('Rule Name'),
        //         'id' => 'rule_id',
        //         'title' => __('Rule Name'),
        //         'required' => true,
        //         'values' => array_merge(['' => __('Please select')], $this->ruleOptions->getAllOptions())
        //     ]
        // );

        /**
         * Virtual item
         * (pool ID)
         */
        $fieldset->addField(
            'reward_pool_id',
            'select',
            [
                'name' => 'reward_pool_id',
                'label' => __('Pool Name'),
                'id' => 'reward_pool_id',
                'title' => __('Pool Name'),
                'required' => true,
                'after_element_html' => '<script> var loadBatchCodeUrl = "'.$this->getUrl('rewardsystem/reward/loadBatchCode').'";</script>',
                'values' => array_merge(['' => __('Please select')], $this->poolOptions->getAllOptions())
            ]
        );
        $fieldset->addField(
            'reward_pool_batch_code',
            'select',
            [
                'name' => 'reward_pool_batch_code',
                'label' => __('Batch code'),
                'id' => 'reward_pool_batch_code',
                'title' => __('Batch code'),
                'required' => true,
                'values' => []
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