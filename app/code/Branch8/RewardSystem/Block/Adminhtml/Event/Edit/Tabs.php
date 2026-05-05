<?php
namespace Branch8\RewardSystem\Block\Adminhtml\Event\Edit;

class Tabs extends \Magento\Backend\Block\Widget\Tabs
{/**
     * @var \Magento\Framework\Registry
     */
    protected $_coreRegistry;
    /**
     * Dependency Initilization
     *
     * @param \Magento\Backend\Block\Template\Context $context
     * @param \Magento\Framework\Json\EncoderInterface $jsonEncoder
     * @param \Magento\Backend\Model\Auth\Session $authSession
     * @param \Magento\Framework\Registry $coreRegistry
     * @param array $data
     */
    public function __construct(
        \Magento\Backend\Block\Template\Context $context,
        \Magento\Framework\Json\EncoderInterface $jsonEncoder,
        \Magento\Backend\Model\Auth\Session $authSession,
        \Magento\Framework\Registry $coreRegistry,
        array $data = []
    ) {
        $this->_coreRegistry = $coreRegistry;
        parent::__construct($context, $jsonEncoder, $authSession, $data);
    }
    /**
     * Dependency Initilization
     *
     * @return void
     */
    protected function _construct()
    {
        parent::_construct();
        $this->setId('reward_event_tabs');
        $this->setDestElementId('edit_form');
        $this->setTitle(__('Event Data'));
    }
    /**
     * Prepare form data
     *
     * @return \Magento\Backend\Block\Widget\Form
     */
    protected function _prepareLayout()
    {
        $this->addTab(
            'information',
            [
                'label' => __('Information'),
                'content' => $this->getLayout()->createBlock(
                    \Branch8\RewardSystem\Block\Adminhtml\Event\Edit\Tab\Information::class
                )->toHtml(),
                'active' => true
            ]
        );
        $this->addTab(
            'triggers',
            [
                'label' => __('Triggers'),
                'content' => $this->getLayout()->createBlock(
                    \Branch8\RewardSystem\Block\Adminhtml\Event\Edit\Tab\Triggers::class
                )->toHtml()
            ]
        );

        $this->addTab(
            'reward',
            [
                'label' => __('Reward'),
                'content' => $this->getLayout()->createBlock(
                    'Branch8\RewardSystem\Block\Adminhtml\Event\Edit\Tab\Reward'
                )->toHtml()
            ]
        );
        
        $this->addTab(
            'notification',
            [
                'label' => __('Notification'),
                'content' => $this->getLayout()->createBlock(
                    'Branch8\RewardSystem\Block\Adminhtml\Event\Edit\Tab\Notification'
                )->toHtml()
            ]
        );
        return parent::_prepareLayout();
    }
}