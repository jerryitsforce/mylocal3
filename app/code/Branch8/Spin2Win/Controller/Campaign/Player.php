<?php

namespace Branch8\Spin2Win\Controller\Campaign;

use Magento\Framework\App\Action\Context;
use Magento\Framework\View\Result\PageFactory;
use Magento\Catalog\Helper\ImageFactory;

class Player extends \Magento\Framework\App\Action\Action
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
     * @var \Magento\Catalog\Model\ProductRepository
     */
    protected $productRepository;
    
    /**
     * @var \Magento\Framework\Controller\Result\JsonFactory
     */
    protected $pageJsonFactory;

    /**
     * @var \Branch8\Spin2Win\Helper\Data
     */
    protected $b8SpinHelper;

    /**
     * @var ImageFactory
     */
    public $imageFactory;

    /**
     * @param Context $context
     * @param \Magento\Customer\Model\Session $customerSession
     * @param \Magento\Framework\App\ResourceConnection $resourceConnection
     * @param \Magento\Framework\Stdlib\DateTime\TimezoneInterface $timezone
     * @param \Magento\Catalog\Model\ProductRepository $productRepository
     * @param \Magento\Framework\Controller\Result\JsonFactory $pageJsonFactory
     * @param \Branch8\Spin2Win\Helper\Data $b8SpinHelper
     * @param ImageFactory $imageFactory
     */
    public function __construct(
        Context $context,
        \Magento\Customer\Model\Session  $customerSession,
        \Magento\Framework\App\ResourceConnection $resourceConnection,
        \Magento\Framework\Stdlib\DateTime\TimezoneInterface $timezone,
        \Magento\Catalog\Model\ProductRepository $productRepository,
        \Magento\Framework\Controller\Result\JsonFactory $pageJsonFactory,
        \Branch8\Spin2Win\Helper\Data $b8SpinHelper,
        ImageFactory $imageFactory
    )
    {
        parent::__construct($context);
        $this->customerSession = $customerSession;
        $this->_conn = $resourceConnection->getConnection();
        $this->timezone = $timezone;
        $this->productRepository = $productRepository;
        $this->pageJsonFactory = $pageJsonFactory;
        $this->b8SpinHelper = $b8SpinHelper;
        $this->imageFactory = $imageFactory;
        
    }

    public function execute(){
    
        $resultPage = $this->pageJsonFactory->create();
        try {
            $customerId = $this->customerSession->getCustomerId();
            $spinId = $this->getRequest()->getParam('sid');
            $sqlSpin = $this->_conn->select()
                ->from(['spin' => 'spintowin_info'])
                ->where('entity_id = ?', $spinId);
            $spin = $this->_conn->fetchRow($sqlSpin);

            /**
             * Load prizes
             */
            $sqlPrizes = $this->_conn->select()
                ->from(['report' => 'spintowin_reports'])
                ->columns(new \Zend_Db_Expr('ADDTIME(created_at, "08:00:00") as played_time'))
                ->where('customer_id = ?', $customerId)
                ->where('spin_id = ?', $spinId)
                ->order('created_at DESC');
            
            $allPrizesReport = $this->_conn->fetchAll($sqlPrizes);
            $prizes = [];
            $consolationData = [];
            
            foreach ($allPrizesReport as $_prizeReport) {
                if ($_prizeReport['result'] == 0  && $_prizeReport['is_apply_consolation'] == 0) {
                    continue;
                }
                if ($_prizeReport['result'] == 0 && $_prizeReport['is_apply_consolation'] == 1) {
                    $prizeId = $_prizeReport['entity_id'];
                    // Get Consolation
                    $consolationSql = $this->_conn->select()
                    ->from(['report' => 'spintowin_consolation_reports'])
                    ->columns(new \Zend_Db_Expr('ADDTIME(created_at, "08:00:00") as played_time'))
                    ->where('customer_id = ?', $customerId)
                    ->where('spin_id = ?', $spinId)
                    ->where('prize_report_id = ?', $prizeId);

                    $consolation = $this->_conn->fetchRow($consolationSql);
                    if (!isset($consolation) || !isset($consolation['consolation_report_id'])) {
                        continue;
                    }
                    
                    $consolationData = [
                        'consolation_name' => $consolation['consolation_title'],
                        'consolation_detail' => $this->getDetailConsolation($consolation),
                        'consolation_type' => $consolation['consolation_type']
                    ];
                }
                $prizes[] = [
                    'prize_name' => $_prizeReport['segment_label'],
                    'prize_type' => $_prizeReport['result'],
                    'prize_detail' => $this->getDetailPrize($_prizeReport),
                    'consolation' => $consolationData
                ];
            }

            /** Get consolation */
            $consolations = [];
            if($allPrizesReport && count($allPrizesReport) > 0){
                $sqlConsolations = $this->_conn->select()
                    ->from(['report' => 'spintowin_consolation_reports'])
                    ->columns(new \Zend_Db_Expr('ADDTIME(created_at, "08:00:00") as played_time'))
                    ->where('customer_id = ?', $customerId)
                    ->where('spin_id = ?', $spinId);
                $allConsolations = $this->_conn->fetchAll($sqlConsolations);
                foreach ($allConsolations as $_consolation) {
                    if ($_consolation['consolation_type'] == 0) {
                        continue;
                    }
                    $consolations[] = [
                        'consolation_name' => $_consolation['consolation_title'],
                        'consolation_detail' => $this->getDetailConsolation($_consolation),
                        'consolation_type' => $_consolation['consolation_type']
                    ];
                }
            }

            /**
             * Get chances
             */
            $chanceData = $this->b8SpinHelper->getTotalChances($spin, $customerId);
            $chances = $chanceData['total'];

            /**
             * Get total failed chances
             * This is the total chances that player has failed to win a prize 
             */
            $failedChances = 0;
            
            if(isset($spin['spin_type'])){
                $failedChances = $this->b8SpinHelper->getTotalFailedChances($spinId, $spin['spin_type'], $customerId);
            }

            /**
             * Is allow redeem
             */
            $redemption = [];
            if ($spin['allow_redeem_point']) {
                $redemption['point'] = [
                    'point_to_drawn' => $spin['point_to_drawn']
                ];
            }

            return $resultPage->setData([
                'error' => false,
                'data' => [
                    'prizes' => $prizes,
                    'consolations' => $consolations,
                    'chances' => $chances,
                    'failed_chances' => $failedChances,
                    'redemption' => $redemption
                ]
            ]);
        }catch (\Exception $e){
            return $resultPage->setData([
                'error' => true,
                'message' => __('An error occurred while retrieving player data: %1', $e->getMessage())
            ]);
        }
    }

    protected function getDetailPrize($prizeReport)
    {
        $segmentType = $prizeReport['result'];
        $prizeDetail = [
            'label' => $prizeReport['segment_label'],
            'heading' => $prizeReport['segment_heading'],
            'segment_type' => $segmentType,
            'smrpid' => $prizeReport['entity_id']
        ];
        
        if($segmentType == \Branch8\Spin2Win\Model\Config\Source\SegmentType::COUPON_TYPE){
            $prizeDetail['expire_date'] = $this->timezone->date($prizeReport['coupon_expired_date'])->format('Y/m/d');
            $prizeDetail['image'] = $prizeReport['coupon_image']?? '';
            $prizeDetail['coupon'] = $prizeReport['coupon'];
        }else if($segmentType == \Branch8\Spin2Win\Model\Config\Source\SegmentType::VIRTUAL_TYPE){
            $poolSql = $this->_conn->select()
                ->from(['ticket' => 'ticket_event_ticket'], [])
                ->columns(new \Zend_Db_Expr('addtime(start_date, "08:00:00") as start_date'))
                ->columns(new \Zend_Db_Expr('addtime(end_date, "08:00:00") as end_date'))
                ->joinLeft(['event' => 'ticket_event'], 'event.entity_id = ticket.event_id', ['image_url'])
                ->where('event_id = ?', $prizeReport['pool_id'])
                ->where('serial_number = ?', $prizeReport['reward_serial_number']);
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
            $prizeDetail['serial_number_id']  = $prizeReport['serial_number_id'];
            $prizeDetail['serial'] = $prizeReport['reward_serial_number'];
            $prizeDetail['start_date'] = $poolData['start_date'];
            $prizeDetail['end_date'] = $poolData['end_date'];
            $prizeDetail['expire_date'] = $this->timezone->date($poolData['end_date'])->format('Y/m/d');
            $prizeDetail['image'] = $eventImage?? '';

        }else if($segmentType == \Branch8\Spin2Win\Model\Config\Source\SegmentType::PHYSICAL_TYPE){
            $prizeDetail['sku'] = NULL;//$prize['reward_sku'];
            $prizeDetail['name'] = NULL;//$prize['product_name'];
            $prizeDetail['url'] = NULL;//$prize['product_url'];
            $physicalExpireDate = $prizeReport['physical_expire_date'];
            $prizeDetail['expire_date'] = $this->timezone->date($physicalExpireDate)->format('Y/m/d');
            $prizeDetail['image'] = $prizeReport['physical_image']?? '';
        } else if($segmentType == \Branch8\Spin2Win\Model\Config\Source\SegmentType::REWARD_POINT_TYPE){
            $prizeDetail['point'] = $prizeReport['reward_point'];
            $prizeDetail['image'] = '';
            if($prizeReport['point_expire_date'] && $prizeReport['point_expire_date'] != '' && strlen($prizeReport['point_expire_date']) == 7 /**yyyy-mm */){
                $yearPoint = substr($prizeReport['point_expire_date'], 0, 4);
                $monthPoint = substr($prizeReport['point_expire_date'], 5, 2);
                if(function_exists('cal_days_in_month')){
                    $dayPoint = cal_days_in_month(CAL_GREGORIAN, $monthPoint, $yearPoint);
                    $prizeDetail['expire_date'] =  $yearPoint.'/'.$monthPoint.'/'.$dayPoint;
                }else{
                    $dayPoint = '';
                    $prizeDetail['expire_date'] =  '';
                }
                
            }else{
                $prizeDetail['expire_date'] = '';
            }
        } 
        return $prizeDetail;
    }

    protected function getDetailConsolation($consolationReport)
    {
        if(!isset($consolationReport['consolation_type']) || !isset($consolationReport['consolation_id'])){
            return [];
        }
        $consolationType = $consolationReport['consolation_type'];
        $consolationDetail = [
            'label' => $consolationReport['consolation_title'],
            'heading' => $consolationReport['consolation_title'],
            'consolation_type' => $consolationReport['consolation_type'],
            'comid' => $consolationReport['consolation_report_id'],
        ];

        if($consolationType == \Branch8\Spin2Win\Model\Config\Source\SegmentType::COUPON_TYPE){
            $consolationDetail['expire_date'] = $this->timezone->date($consolationReport['coupon_expired_date'])->format('Y/m/d');
            $consolationDetail['coupon'] = $consolationReport['coupon'];
            $consolationDetail['image'] = $consolationReport['coupon_image']?? '';
        }else if($consolationType == \Branch8\Spin2Win\Model\Config\Source\SegmentType::VIRTUAL_TYPE){
            $poolSql = $this->_conn->select()
                ->from(['ticket' => 'ticket_event_ticket'], [])
                ->columns(new \Zend_Db_Expr('addtime(start_date, "08:00:00") as start_date'))
                ->columns(new \Zend_Db_Expr('addtime(end_date, "08:00:00") as end_date'))
                ->joinLeft(['event' => 'ticket_event'], 'event.entity_id = ticket.event_id', ['image_url'])
                ->where('event_id = ?', $consolationReport['pool_id'])
                ->where('serial_number = ?', $consolationReport['serial_number']);
            $poolData = $this->_conn->fetchRow($poolSql);
            if ($poolData) {
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
                $consolationDetail['start_date'] = $poolData['start_date'];
                $consolationDetail['end_date'] = $poolData['end_date'];
                $consolationDetail['expire_date'] = $this->timezone->date($poolData['end_date'])->format('Y/m/d');
                $consolationDetail['image'] = $eventImage?? '';
            }
            
            $consolationDetail['serial'] = $consolationReport['serial_number'];
            $consolationDetail['serial_number_id'] = $consolationReport['serial_number_id'];
        }else if($consolationType == \Branch8\Spin2Win\Model\Config\Source\SegmentType::PHYSICAL_TYPE){
            $consolationDetail['sku'] = NULL;
            $consolationDetail['name'] = NULL;
            $consolationDetail['url'] = NULL;
            $consolationDetail['expire_date'] =  $this->timezone->date($consolationReport['physical_expire_date'])->format('Y/m/d');
            $consolationDetail['image'] = $consolationReport['physical_image']?? '';
        }else if($consolationType == \Branch8\Spin2Win\Model\Config\Source\SegmentType::REWARD_POINT_TYPE){
            $consolationDetail['point'] = $consolationReport['reward_point'];
            $consolationDetail['image'] = '';
            if(isset($consolationReport['point_expire_date']) && $consolationReport['point_expire_date'] != '' && strlen($consolationReport['point_expire_date']) == 7 /**yyyy-mm */){
                $yearPoint = substr($consolationReport['point_expire_date'], 0, 4);
                $monthPoint = substr($consolationReport['point_expire_date'], 5, 2);
                if(function_exists('cal_days_in_month')){
                    $dayPoint = cal_days_in_month(CAL_GREGORIAN, $monthPoint, $yearPoint);
                    $consolationDetail['expire_date'] =  $yearPoint.'/'.$monthPoint.'/'.$dayPoint;
                }else{
                    $consolationDetail['expire_date'] =  '';
                }
                
            }else{
                $consolationDetail['expire_date'] = '';
            }
        }
        
        return $consolationDetail;
    }
}