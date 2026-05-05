<?php
namespace Branch8\RewardSystem\Controller\Adminhtml\Reward;

class MassEnable extends \Magento\Backend\App\Action
{
    const ADMIN_RESOURCE = 'Branch8_RewardSystem::massenable';

    protected $filter;

    protected $collectionFactory;

    protected $eventCollectionFactory;

    /**
     * @param \Magento\Backend\App\Action\Context $context
     */
    public function __construct(
       \Magento\Backend\App\Action\Context $context,
       \Magento\Ui\Component\MassAction\Filter $filter,
       \Branch8\RewardSystem\Model\ResourceModel\Event\CollectionFactory $collectionFactory
    )
    {
        parent::__construct($context);
        $this->filter = $filter;
        $this->eventCollectionFactory = $collectionFactory;
    }

    /**
     * Index action
     *
     * @return \Magento\Framework\View\Result\Page
     */
    public function execute()
    {
        try{
            $collection = $this->filter->getCollection($this->eventCollectionFactory->create());
            foreach ($collection as $model) {
                $model->setStatus(1);
                $model->save();
            }
            $this->messageManager->addSuccessMessage(__('Update successfully'));
        }catch(\Exception $e){
            $this->messageManager->addSuccessMessage(__('Update failed'));
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
