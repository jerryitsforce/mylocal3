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
        $prizeQuery = $this->_conn->select()
            ->from(['report' => 'spintowin_reports'], ['entity_id'])
            ->where('spin_id = ?', $spinId)
            ->where('customer_id = ?', $customerId)
            ->where('result = ?', \Branch8\Spin2Win\Model\Config\Source\SegmentType::PHYSICAL_TYPE);
        if(!$this->_conn->fetchOne($prizeQuery)){
            return $resultPage->setData(['error' => true, 'message' => __('Invalid request')]);
        }

        $addressQuery = $this->_conn->select()
            ->from(['address' => 'spintowin_award_address'], ['address_id'])
            ->where('customer_id = ?', $customerId)
            ->where('spin_id = ?', $spinId);
        if(!$this->_conn->fetchOne($addressQuery)){
            return $resultPage->setData(['error' => true, 'message' => __('Invalid request')]);
        }
        $postData = $this->getRequest()->getParams();
        try{
            $data = [
                'address_id' => NULL,
                'spin_id' => $spinId,
                'customer_id' => $customerId,
                'name' => $postData['name'],
                'phone_number' => $postData['phone_number'],
                'street' => $postData['street'],
                'region' => $postData['region'],
                'region_id' => $postData['region_id'],
                'city' => $postData['city'],
                'city_id' => $postData['city_id'],
                'country' => 'Taiwan'
            ];
            $this->_conn->insert('spintowin_award_address', $data, ['name', 'phone_number', 'street', 'region', 'region_id', 'city', 'city_id']);
            return $resultPage->setData(['error' => false]);
        }catch(\Exception $e){
            return $resultPage->setData(['error' => true, 'message' => __('Update address error, please try again.')]);
        }
    }

}