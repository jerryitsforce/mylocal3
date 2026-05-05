<?php
namespace Branch8\GiftToFriend\Controller\Adminhtml\Parent\GiftOrder;

use Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;

class MassRemoveGiftBox extends \Magento\Sales\Controller\Adminhtml\Order\AbstractMassAction
{
    const ADMIN_RESOURCE = 'Branch8_GiftToFriend::mass_actions_remove_gift_box';

    protected $orderCollectionFactory;

    protected $transaction;

    protected $_conn;

    public function __construct(
        \Magento\Backend\App\Action\Context $context,
        \Magento\Ui\Component\MassAction\Filter $filter,
        \Magento\Framework\DB\Transaction $transaction,
        \Magento\Framework\App\ResourceConnection $resourceConnection
    ){
        parent::__construct($context, $filter);
        $this->transaction = $transaction;
        $this->_conn = $resourceConnection->getConnection();
    }

    /**
     * Index action
     *
     * @return \Magento\Framework\View\Result\Page
     */
    public function execute()
    {
        if(!$this->getRequest()->isPost()){
            return $this->_redirect('/')->sendResponse();
        }
        $collection = $this->filter->getCollection($this->getOrderCollection()->create());
        
        try{
            $this->massAction($collection);
            $this->messageManager->addSuccessMessage(__('Remove Gift Box successfully.'));
            return $this->_redirect('sales/parent_giftOrder/index')->sendResponse();
        }catch(\Exception $e){
            $this->messageManager->addSuccessMessage($e->getMessage());
            return $this->_redirect('sales/parent_giftOrder/index')->sendResponse();
        }
    }

    private function getOrderCollection()
    {
        if ($this->orderCollectionFactory === null) {
            $this->orderCollectionFactory = \Magento\Framework\App\ObjectManager::getInstance()->get(
                \Branch8\MarketPlaceParentOrder\Model\ResourceModel\ParentOrder\CollectionFactory::class
            );
        }
        return $this->orderCollectionFactory;
    }
    protected function massAction(AbstractCollection $collection){
        $this->_conn->beginTransaction();
        foreach($collection as $_parentOrder){
            $parentId = $_parentOrder->getId();
            $sqlParent = 'update sales_parent_order_detail set is_removed_gift_box = 1 where parent_id='.$parentId.';';
            $this->_conn->query($sqlParent);
            $sqlParentGrid = 'update sales_parent_order_grid set is_removed_gift_box = 1 where entity_id='.$parentId.';';
            $this->_conn->query($sqlParentGrid);
        }
        $this->_conn->commit();
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
