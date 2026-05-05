<?php
namespace Branch8\RewardSystem\Controller\Adminhtml\Reward;

class Edit extends \Magento\Backend\App\Action
{
    const ADMIN_RESOURCE = 'Branch8_RewardSystem::edit';

    /**
     * @var \Magento\Framework\View\Result\PageFactory
     */
    protected $_pageFactory;

    /**
     *
     * @var \Branch8\RewardSystem\Model\EventFactory
     */
    protected $eventFactory;

    protected $_coreRegistry;

    // protected $recipientInfoAuto;

    /**
     * @param \Magento\Backend\App\Action\Context $context
     */
    public function __construct(
       \Magento\Backend\App\Action\Context $context,
       \Magento\Framework\View\Result\PageFactory $pageFactory,
       \Branch8\RewardSystem\Model\EventFactory $eventFactory,
       \Magento\Framework\Registry $registry
    //    \Branch8\GiftToFriend\Plugin\Checkout\RecipientInfoAuto $recipientInfoAuto
    )
    {
        $this->_pageFactory = $pageFactory;
        parent::__construct($context);
        $this->eventFactory = $eventFactory;
        $this->_coreRegistry = $registry;
        // $this->recipientInfoAuto = $recipientInfoAuto;
        
    }

    /**
     * Index action
     *
     * @return \Magento\Framework\View\Result\Page
     */
    public function execute()
    {
        

        $id = $this->getRequest()->getParam('id');
        $event = $this->eventFactory->create()->load((int)$id);
        $this->_coreRegistry->register('event_data', $event);

        /** @var \Magento\Framework\View\Result\Page $resultPage */
        $resultPage = $this->_pageFactory->create();
        $resultPage->setActiveMenu(static::ADMIN_RESOURCE);
        if(!$event->getId()){
            $resultPage->getConfig()->getTitle()->set(__('Create Automated Reward'));
        }else{
            $resultPage->getConfig()->getTitle()->set(__('Edit %1', $event->getTitle()));
        }

        return $resultPage;
    }

    /**
     * Is the user allowed to view the page.
    *
    * @return bool
    */
    protected function _isAllowed()
    {
        return $this->_authorization->isAllowed(static::ADMIN_RESOURCE);
    }
}
