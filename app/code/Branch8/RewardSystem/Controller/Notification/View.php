<?php
namespace Branch8\RewardSystem\Controller\Notification;

class View extends \Magento\Framework\App\Action\Action
{
    /**
     * @var \Magento\Framework\View\Result\PageFactory
     */
    protected $_pageFactory;

    protected $customerSession;

    protected $registry;

    protected $_conn;

    /**
     * @param \Magento\Framework\App\Action\Context $context
     */
    public function __construct(
       \Magento\Framework\App\Action\Context $context,
       \Magento\Framework\View\Result\PageFactory $pageFactory,
       \Magento\Customer\Model\Session  $customerSession,
       \Magento\Framework\Registry $registry,
       \Magento\Framework\App\ResourceConnection $resourceConnection
    )
    {
        $this->_pageFactory = $pageFactory;
        parent::__construct($context);
        $this->customerSession = $customerSession;
        $this->registry = $registry;
        $this->_conn = $resourceConnection->getConnection();
    }
    /**
     * View page action
     *
     * @return \Magento\Framework\Controller\ResultInterface
     */
    public function execute()
    {
        $reportId = $this->getRequest()->getParam('rid', null);
        $customerId = $this->customerSession->getCustomerId();

        if(!$reportId || !$customerId){
            $this->messageManager->addErrorMessage(__("We're sorry, but this event could not be found. Please try again later."));
            $resultRedirect = $this->resultRedirectFactory->create();
            $resultRedirect->setPath('/');
            return $resultRedirect;
        }
        $resultPage = $this->_pageFactory->create();
        $layout = $resultPage->getLayout();

        if (!$this->registry->registry('current_rid')) {
            $this->registry->register('current_rid', $reportId);
        }

        $sqlReward = $this->_conn->select()
            ->from(['report' => 'branch8_rewardsystem_report'])
            ->columns(new \Zend_Db_Expr('ADDTIME(created_at, "08:00:00") as created_at'))
            ->where('status', 1)
            ->where('customer_id = ?', $customerId)
            ->where('entity_id = ?', $reportId);
        
        $report = $this->_conn->fetchRow($sqlReward);

        if(!$report || ($report && $customerId != $report['customer_id'])){
            $resultRedirect = $this->resultRedirectFactory->create();
            $resultRedirect->setPath('/');
            return $resultRedirect;
        }

        $mainBlock = $layout->getBlock('Branch8_RewardSystem_Notification');
        $mainBlock->setData('reportData', $report);

        $sqlNoti = $this->_conn->select()
            ->from(['report' => 'magenest_customer_notification'])
            ->where('ars_report_id = '.$reportId);
        $noti = $this->_conn->fetchRow($sqlNoti);
        $mainBlock->setData('notiData', $noti);

        $resultPage->getConfig()->getTitle()->set($noti['description']);
        
        return $resultPage;
    }
}
