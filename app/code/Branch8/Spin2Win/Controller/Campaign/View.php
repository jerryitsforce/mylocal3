<?php
namespace Branch8\Spin2Win\Controller\Campaign;
use Magento\Framework\App\Action\Context;
use Magento\Framework\View\Result\PageFactory;
use Magento\Framework\App\RequestInterface;

class View extends \Magento\Framework\App\Action\Action{

    protected $_conn;

    protected $customerSession;

    protected $timezone;

    protected $resultPageFactory;

    protected $infoFactory;

    protected $spinHelperData;
    
    /**
     * @var \Magento\Framework\Registry
     */
    protected $registry;

    public function __construct(
        Context $context,
        \Magento\Framework\App\ResourceConnection $resourceConnection,
        \Magento\Customer\Model\Session $customerSession,
        \Magento\Framework\Stdlib\DateTime\TimezoneInterface $timezone,
        PageFactory $pageJsonFactory,
        \Webkul\SpinToWin\Model\InfoFactory $infoFactory,
        \Magento\Framework\Registry $registry,
        \Branch8\Spin2Win\Helper\Data $spinHelperData
    ){
        parent::__construct($context);
        $this->_conn = $resourceConnection->getConnection();
        $this->customerSession = $customerSession;
        $this->timezone = $timezone;
        $this->resultPageFactory = $pageJsonFactory;
        $this->infoFactory = $infoFactory;
        $this->registry = $registry;
        $this->spinHelperData = $spinHelperData;
    }

    public function execute(){
        
        $customerId = $this->customerSession->getCustomer()->getId();
        $spinId = $this->getRequest()->getParam('id');
        $customerGroupId = $this->spinHelperData->getCustomerGroupId($customerId);
        if (!$this->registry->registry('current_spinid')) {
            $this->registry->register('current_spinid', $spinId);
        }
        $currentDateTime = $this->timezone->convertConfigTimeToUtc($this->timezone->date());
        $collectionSpin = $this->infoFactory->create()->getCollection()
            ->addFieldToSelect('*')
            ->addFieldToFilter('entity_id', $spinId)
            ->addFieldToFilter('status', 1)
            ->addFieldToFilter(
                ['start_date', 'start_date'],
                [
                    ['null' => true],
                    ['lteq' => $currentDateTime]
                ]
            )
            ->addFieldToFilter(
                ['end_date', 'end_date'],
                [
                    ['null' => true],
                    ['gt' => $currentDateTime]
                ]
                );

        // $collectionSpin->getSelect()->where("FIND_IN_SET('".$customerGroupId."', customergroup_ids)");
        $collectionSpin =  $collectionSpin->getFirstItem();
        $spinData = $collectionSpin->getData(); 
        if(empty($spinData)){
            $this->messageManager->addErrorMessage(__("We're sorry, but this event could not be found. Please try again later."));
            return $this->resultRedirectFactory->create()->setUrl('/');
        }
        
        $spinAllowedGroup = explode(',', (string)$spinData['customergroup_ids']);
        if(!in_array($customerGroupId, $spinAllowedGroup)){
            $this->messageManager->addErrorMessage(__("Your membership level does not meet the eligibility requirements for this event. We appreciate your understanding."));
            return $this->resultRedirectFactory->create()->setUrl('/');
        }

        $resultPage = $this->resultPageFactory->create();
        $spinLayoutSelect = $this->_conn->select()
            ->from(['layout' => 'spintowin_layout'], ['page_title'])
            ->where('spin_id = ?', $spinId);
        $pageTitle = $this->_conn->fetchOne($spinLayoutSelect);
        $resultPage->getConfig()->getTitle()->set($pageTitle);
        
        $this->getResponse()->setHeader('Cache-Control', 'no-store, no-cache, must-revalidate, max-age=0', true);
        $this->getResponse()->setHeader('Pragma', 'no-cache', true);
        $this->getResponse()->setHeader('Expires', 'Thu, 01 Jan 1970 00:00:00 GMT', true);


        return $resultPage;
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
}