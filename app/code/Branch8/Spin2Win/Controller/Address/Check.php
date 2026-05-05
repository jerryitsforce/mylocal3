<?php

namespace Branch8\Spin2Win\Controller\Address;

use Magento\Framework\App\Action\Context;
use Magento\Framework\View\Result\PageFactory;

class Check extends \Magento\Framework\App\Action\Action
{
    /**
     * @var \Magento\Customer\Model\Session
     */
    protected $customerSession;
    /**
     * @var \Magento\Framework\DB\Adapter\AdapterInterface
     */
    protected $_conn;
    /**
     * @var \Magento\Framework\Stdlib\DateTime\TimezoneInterface
     */
    protected $timezone;
    /**
     * @var \Magento\Framework\Controller\Result\JsonFactory
     */
    protected $pageJsonFactory;


    /**
     * @param Context $context
     * @param \Magento\Customer\Model\Session $customerSession
     * @param \Magento\Framework\App\ResourceConnection $resourceConnection
     * @param \Magento\Framework\Stdlib\DateTime\TimezoneInterface $timezone
     * @param PageFactory $pageFactory
     */
    public function __construct(
        Context $context,
        \Magento\Customer\Model\Session  $customerSession,
        \Magento\Framework\App\ResourceConnection $resourceConnection,
        \Magento\Framework\Stdlib\DateTime\TimezoneInterface $timezone,
        \Magento\Framework\Controller\Result\JsonFactory $pageJsonFactory
    )
    {
        parent::__construct($context);
        $this->customerSession = $customerSession;
        $this->_conn = $resourceConnection->getConnection();
        $this->timezone = $timezone;
        $this->pageJsonFactory = $pageJsonFactory;
    }

    public function execute(){
    
        $resultPage = $this->pageJsonFactory->create();
        if(!$this->getRequest()->isPost()){
            return $resultPage->setData([
                'error' => true,
                'message' =>__('Invalid request.')
            ]);
        }
        if(!$this->customerSession->isLoggedIn()){
            return $resultPage->setData([
                'error' => true,
                'need_login' => true,
                'message' =>__('Please login.')
            ]);
        }
        try {
            $request = $this->getRequest();
            $postData = $request->getParams();
            $customerId = $this->customerSession->getCustomerId();
            $spinId = $postData['sid'];
            $sqlSpin = $this->_conn->select()
                ->from(['spin' => 'spintowin_info'])
                ->where('entity_id = ?', $spinId);
            $spin = $this->_conn->fetchRow($sqlSpin);
            if(empty($spin)){
                return $resultPage->setData([
                    'error' => true,
                    'message' =>__('Event does not exist.')
                ]);
            }
            
            try{
                $sqlCheck = $this->_conn->select()
                    ->from(['address' => 'spintowin_award_address'], ['address_id'])
                    ->where('customer_id = ?', $customerId)
                    ->where('spin_id = ?', $spinId);
                $data = $this->_conn->fetchCol($sqlCheck);
                if(empty($data)){
                    return $resultPage->setData([
                        'error' => false,
                        'existed' => false
                    ]);
                }

            }catch(\Exception $e){echo $e->getMessage();
                return $resultPage->setData([
                    'error' => true,
                    'message' =>__('Error updating address')
                ]);
            }

            return $resultPage->setData([
                'error' => false,
                'existed' => true
            ]);
        }catch (\Exception $e){echo $e->getMessage()();
            return $resultPage->setData([
                'error' => true
            ]);
        }
    }


    protected function getDetailPrize($prize)
    {
        $segmentType = $prize['segment_type'];
        $segmentId = $prize['segment_id'];
        $segmentSql = $this->_conn->select()
            ->from(['segment' => 'spintowin_segments'])
            ->where('entity_id=?', $segmentId);
        $segment = $this->_conn->fetchRow($segmentSql);
        $prizeDetail = [
            'label' => $segment['label'],
            'heading' => $segment['heading'],
            'description' => $segment['description'],
            'segment_type' => $segmentType
        ];

        if($segmentType == \Branch8\Spin2Win\Model\Config\Source\SegmentType::COUPON_TYPE){
            $sqlRule = $this->_conn->select()
                ->from(['rule' => 'salesrule'], ['name'])
                ->joinLeft(['rulelabel' => 'salesrule_label'], 'rulelabel.row_id = rule.row_id', ['label'])
                ->columns(new \Zend_Db_Expr('addtime(from_date, "08:00:00") as start_date'))
                ->columns(new \Zend_Db_Expr('addtime(to_date, "08:00:00") as end_date'))
                ->where('rule_id = ? ', $prize['rule_id'])
                ->where('store_id=0');
            $ruleData = $this->_conn->fetchRow($sqlRule);
            $prizeDetail['start_date'] = $ruleData['start_date'];
            $prizeDetail['end_date'] = $ruleData['end_date'];
            $prizeDetail['image'] = $segment['coupon_image'];

        }else if($segmentType == \Branch8\Spin2Win\Model\Config\Source\SegmentType::VIRTUAL_TYPE){
            $poolSql = $this->_conn->select()
                ->from(['ticket' => 'ticket_event_ticket'], [])
                ->columns(new \Zend_Db_Expr('addtime(start_date, "08:00:00") as start_date'))
                ->columns(new \Zend_Db_Expr('addtime(end_date, "08:00:00") as end_date'))
                ->joinLeft(['event' => 'ticket_event'], 'event.entity_id = ticket.event_id', ['image_url'])
                ->where('event_id = ?', $segment['pool_id'])
                ->where('serial_number = ?', $prize['reward_serial_number']);
            $poolData = $this->_conn->fetchRow($poolSql);
            $eventImageData = $poolData['image_url'];
            $eventImage = '';
            try{
                $imageData = json_decode($eventImageData, true);
                if(isset($imageData[0])){
                    $eventImage = $imageData[0]['url'];
                }
            }catch(\Exception $e){
                $eventImage = '';
            }
            $prizeDetail = [
                'serial' => $prize['reward_serial_number'],
                'start_date' => $poolData['start_date'],
                'end_date' => $poolData['end_date'],
                'image' => $eventImage
            ];
        }else if($segmentType == \Branch8\Spin2Win\Model\Config\Source\SegmentType::PHYSICAL_TYPE){
            $prizeDetail['sku'] = $prize['reward_sku'];
            $prizeDetail['name'] = $prize['product_name'];
            $prizeDetail['url'] = $prize['product_url'];
            $physicalExpireDate = $segment['physical_expire_date'];
            $prizeDetail['physical_expire_date'] = $physicalExpireDate;
            $prizeDetail['image'] = $segment['physical_image'];
        }else if($segmentType == \Branch8\Spin2Win\Model\Config\Source\SegmentType::REWARD_POINT_TYPE){
            $prizeDetail['point'] = $prize['reward_point'];
            $prizeDetail['point_expire_date'] = $prize['point_expire_date'];
        }

        return $prizeDetail;
    }

    protected function getDetailConsolation($consolationReport)
    {
        $consolationType = $consolationReport['segment_type'];
        $consolationId = $consolationReport['consolation_id'];
        $consolationSql = $this->_conn->select()
            ->from(['consolation' => 'spintowin_consolation'])
            ->where('consolation_id=?', $consolationId);
        $consolationData = $this->_conn->fetchRow($consolationSql);
        $consolationDetail = [
            'label' => $consolationReport['consolation_title'],
            'consolation_type' => $consolationReport['consolation_type']
        ];

        if($consolationType == \Branch8\Spin2Win\Model\Config\Source\SegmentType::COUPON_TYPE){
            $sqlRule = $this->_conn->select()
                ->from(['rule' => 'salesrule'], ['name'])
                ->joinLeft(['rulelabel' => 'salesrule_label'], 'rulelabel.row_id = rule.row_id', ['label'])
                ->columns(new \Zend_Db_Expr('addtime(from_date, "08:00:00") as start_date'))
                ->columns(new \Zend_Db_Expr('addtime(to_date, "08:00:00") as end_date'))
                ->where('rule_id = ? ', $consolationData['rule_id'])
                ->where('store_id=0');
            $ruleData = $this->_conn->fetchRow($sqlRule);
            $consolationDetail['start_date'] = $ruleData['start_date'];
            $consolationDetail['end_date'] = $ruleData['end_date'];
        }else if($consolationType == \Branch8\Spin2Win\Model\Config\Source\SegmentType::VIRTUAL_TYPE){
            $poolSql = $this->_conn->select()
                ->from(['ticket' => 'ticket_event_ticket'], [])
                ->columns(new \Zend_Db_Expr('addtime(start_date, "08:00:00") as start_date'))
                ->columns(new \Zend_Db_Expr('addtime(end_date, "08:00:00") as end_date'))
                ->joinLeft(['event' => 'ticket_event'], 'event.entity_id = ticket.event_id', ['image_url'])
                ->where('event_id = ?', $consolationData['pool_id'])
                ->where('serial_number = ?', $consolationReport['serial_number']);
            $poolData = $this->_conn->fetchRow($poolSql);
            $eventImageData = $poolData['image_url'];
            $eventImage = '';
            try{
                $imageData = json_decode($eventImageData, true);
                if(isset($imageData[0])){
                    $eventImage = $imageData[0]['url'];
                }
            }catch(\Exception $e){
                $eventImage = '';
            }
            $consolationDetail = [
                'serial' => $consolationReport['serial_number'],
                'start_date' => $poolData['start_date'],
                'end_date' => $poolData['end_date'],
                'image' => $eventImage
            ];
        }else if($consolationType == \Branch8\Spin2Win\Model\Config\Source\SegmentType::PHYSICAL_TYPE){
            $consolationDetail['sku'] = $consolationReport['sku'];
            $consolationDetail['name'] = $$consolationData['product_name'];
            $consolationDetail['url'] = $consolationData['product_url'];
            $consolationDetail['physical_expire_date'] = $consolationData['physical_expire_date'];
        }else if($consolationType == \Branch8\Spin2Win\Model\Config\Source\SegmentType::REWARD_POINT_TYPE){
            $consolationDetail['point'] = $consolationReport['reward_point'];
            $consolationDetail['point_expire_date'] = $consolationReport['point_expire_date'];
        }

        return $consolationDetail;
    }
}