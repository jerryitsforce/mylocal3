<?php
namespace Branch8\Spin2Win\Controller\Prize;
use Magento\Framework\App\Action\Context;

class PrizeAddressCheck extends \Magento\Framework\App\Action\Action{

    protected $customerSession;

    protected $pageJsonFactory;

    protected $_conn;

    public function __construct(
        Context $context,
        \Magento\Customer\Model\Session  $customerSession,
        \Magento\Framework\App\ResourceConnection $resourceConnection,
        \Magento\Framework\Controller\Result\JsonFactory $pageJsonFactory
    ){
        parent::__construct($context);
        $this->customerSession = $customerSession;
        $this->_conn = $resourceConnection->getConnection();
    }

    public function execute(){
        $resultPage = $this->pageJsonFactory->create();

        if($$this->getRequest()->isPost()){
            return $resultPage->setData(['error' => true, 'message' => __('Invalid request')]);
        }

        $spinId = $this->getRequest()->getParam('id');
        $customerId = $this->customerSession->getCustomerId();
        /**
         * Has a physical prize
         */
        $prizeQuery = $this->_conn->select()
            ->from(['report' => 'spintowin_reports'], ['entity_id'])
            ->where('spin_id = ?', $spinId)
            ->where('customer_id = ?', $customerId)
            ->where('result = ?', \Branch8\Spin2Win\Model\Config\Source\SegmentType::PHYSICAL_TYPE);
        if(!$this->_conn->fetchOne($prizeQuery)){
            return $resultPage->setData(['error' => false, 'need_to_update' => false]);
        }

        $addressQuery = $this->_conn->select()
            ->from(['address' => 'spintowin_award_address'], ['address_id'])
            ->where('customer_id = ?', $customerId)
            ->where('spin_id = ?', $spinId);
        if($this->_conn->fetchOne($addressQuery)){
            return $resultPage->setData(['error' => false, 'need_to_update' => false]);
        }else{

            return $resultPage->setData(['error' => false, 'need_to_update' => true]);
        }
    }
}