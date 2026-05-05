<?php
namespace Branch8\Spin2Win\Controller\Notification;

use Magento\Framework\App\RequestInterface;

class View extends \Magento\Framework\App\Action\Action{

    /**
     * @var \Webkul\SpinToWin\Model\ReportsFactory
     */
    protected $reportsFactory;

    /**
     * @var \Magento\Customer\Model\Session
     */
    protected $customerSession;

    /**
     * @var \Magento\Framework\View\Result\PageFactory
     */
    protected $resultPageFactory;
    
    /**
     * @var \Magento\Framework\Registry
     */
    protected $registry;

    /**
     * @var \Magento\Framework\DB\Adapter\AdapterInterface
     */
    protected $_conn;

    /**
     * View constructor.
     *
     * @param \Magento\Framework\App\Action\Context $context
     * @param \Webkul\SpinToWin\Model\ReportsFactory $reportsFactory
     * @param \Magento\Framework\App\ResourceConnection $resourceConnection
     * @param \Magento\Customer\Model\Session $customerSession
     * @param \Magento\Framework\View\Result\PageFactory $pageFactory
     * @param \Magento\Framework\Registry $registry
     */
    public function __construct(
        \Magento\Framework\App\Action\Context $context,
        \Webkul\SpinToWin\Model\ReportsFactory $reportsFactory,
        \Magento\Framework\App\ResourceConnection $resourceConnection,
        \Magento\Customer\Model\Session  $customerSession,
        \Magento\Framework\View\Result\PageFactory $pageFactory,
        \Magento\Framework\Registry $registry
    )
    {
        parent::__construct($context);
        $this->_conn = $resourceConnection->getConnection();
        $this->reportsFactory = $reportsFactory;
        $this->customerSession = $customerSession;
        $this->resultPageFactory = $pageFactory;
        $this->registry = $registry;
    }

    /**
     * Retrieve customer session object
     *
     * @return \Magento\Customer\Model\Session
     */
    protected function _getSession()
    {
        return $this->customerSession;
    }
    
    /**
     * Check customer authentication
     *
     * @param RequestInterface $request
     * @return \Magento\Framework\App\ResponseInterface
     */
    public function dispatch(RequestInterface $request)
    {
        if (!$this->_getSession()->authenticate()) {
            $this->_actionFlag->set('', 'no-dispatch', true);
        }
        return parent::dispatch($request);
    }

    public function execute() {
        $reportId = $this->getRequest()->getParam('smrpid', null);
        $customerId = $this->customerSession->getCustomerId();

        if(!$reportId || !$customerId){
            $this->messageManager->addErrorMessage(__("We're sorry, but this event could not be found. Please try again later."));
            $resultRedirect = $this->resultRedirectFactory->create();
            $resultRedirect->setPath('/');
            return $resultRedirect;
        }

        $resultPage = $this->resultPageFactory->create();
        $layout = $resultPage->getLayout();

        if (!$this->registry->registry('current_smrpid')) {
            $this->registry->register('current_smrpid', $reportId);
        }

        $sqlPrizes = $this->_conn->select()
            ->from(['report' => 'spintowin_reports'])
            ->columns(new \Zend_Db_Expr('ADDTIME(created_at, "08:00:00") as created_at'))
            ->where('customer_id = ?', $customerId)
            ->where('entity_id = ?', $reportId);
        
        $report = $this->_conn->fetchRow($sqlPrizes);
        // var_dump($sqlPrizes->__toString());
        // var_dump($report);

        if(!$report || ($report && $customerId != $report['customer_id'])){
            $resultRedirect = $this->resultRedirectFactory->create();
            $resultRedirect->setPath('/');
            return $resultRedirect;
        }

        $mainBlock = $layout->getBlock('prize.notification');
        $mainBlock->setData('reportData', $report);
        return $resultPage;
    }
}