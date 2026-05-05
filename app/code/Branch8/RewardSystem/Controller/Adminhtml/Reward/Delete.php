<?php
namespace Branch8\RewardSystem\Controller\Adminhtml\Reward;

class Delete extends \Magento\Backend\App\Action
{
    const ADMIN_RESOURCE = 'Branch8_RewardSystem::manage';

    /**
     * @var \Magento\Framework\View\Result\PageFactory
     */
    protected $_pageFactory;

    /**
     *
     * @var \Branch8\RewardSystem\Model\EventFactory
     */
    protected $eventFactory;

    /**
     * @param \Magento\Backend\App\Action\Context $context
     */
    public function __construct(
       \Magento\Backend\App\Action\Context $context,
       \Branch8\RewardSystem\Model\EventFactory $eventFactory
    )
    {
        parent::__construct($context);
        $this->eventFactory = $eventFactory;
    }

    /**
     * Index action
     *
     * @return \Magento\Framework\View\Result\Page
     */
    public function execute()
    {
        $eventId = $this->getRequest()->getParam('id'); 
        $event = $this->eventFactory->create()->load($eventId);
        if($event->getId()){
            $event->delete();
            $this->messageManager->addSuccessMessage(__('Delete successfully'));
        }else{
            $this->messageManager->addErrorMessage(__('Delete failed'));
        }
        return $this->_redirect('rewardsystem/reward/index')->sendResponse();
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
