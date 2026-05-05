<?php
namespace Branch8\Spin2Win\Block\Notification;

use Magento\Customer\Helper\Address;
use Magento\Framework\App\ObjectManager;

class Detail extends \Magento\Directory\Block\Data {
    /**
     * @var \Magento\Customer\Model\Session
     */
    protected $customerSession;

    /**
     * @var \Webkul\SpinToWin\Model\ReportsFactory
     */
    protected $reportsFactory;

    /**
     * @var \Branch8\Spin2Win\Helper\Data
     */
    protected $b8SpinHelper;

    /**
     * @var  \Webkul\SpinToWin\Helper\Data
     */
    protected $helper;

    /**
     * @var \Magento\Framework\Stdlib\DateTime\TimezoneInterface
     */
    protected $timezone;

    /**
     * @var \Magento\Framework\Registry
     */
    protected $registry;

    /**
     * @var \Magento\Framework\DB\Adapter\AdapterInterface
     */
    protected $_conn;

    /**
     * @param \Magento\Framework\View\Element\Template\Context $context
     * @param \Magento\Customer\Model\Session $customerSession
     * @param \Webkul\SpinToWin\Model\ReportsFactory $reportsFactory
     * @param \Magento\Framework\App\ResourceConnection $resourceConnection
     * @param \Branch8\Spin2Win\Helper\Data $b8SpinHelper
     * @param \Webkul\SpinToWin\Helper\Data $helper
     * @param \Magento\Framework\Stdlib\DateTime\TimezoneInterface $timezone
     * @param \Magento\Framework\Registry $registry
     * @param \Magento\Directory\Helper\Data $directoryHelper
     * @param \Magento\Framework\Json\EncoderInterface $jsonEncoder
     * @param \Magento\Framework\App\Cache\Type\Config $configCacheType
     * @param \Magento\Directory\Model\ResourceModel\Region\CollectionFactory $regionCollectionFactory
     * @param \Magento\Directory\Model\ResourceModel\Country\CollectionFactory $countryCollectionFactory
     * @param Address|null $addressHelper
     * @param array $data
     */
    public function __construct(
        \Magento\Framework\View\Element\Template\Context $context,
        \Magento\Customer\Model\Session $customerSession,
        \Webkul\SpinToWin\Model\ReportsFactory $reportsFactory,
        \Magento\Framework\App\ResourceConnection $resourceConnection,
        \Branch8\Spin2Win\Helper\Data $b8SpinHelper,
        \Webkul\SpinToWin\Helper\Data $helper,
        \Magento\Framework\Stdlib\DateTime\TimezoneInterface $timezone,
        \Magento\Framework\Registry $registry,
        \Magento\Directory\Helper\Data $directoryHelper,
        \Magento\Framework\Json\EncoderInterface $jsonEncoder,
        \Magento\Framework\App\Cache\Type\Config $configCacheType,
        \Magento\Directory\Model\ResourceModel\Region\CollectionFactory $regionCollectionFactory,
        \Magento\Directory\Model\ResourceModel\Country\CollectionFactory $countryCollectionFactory,
        Address $addressHelper = null,
        array $data = []
    ) {
        $data['addressHelper'] = $addressHelper ?: ObjectManager::getInstance()->get(Address::class);
        $data['directoryHelper'] = $directoryHelper;
        parent::__construct(
            $context,
            $directoryHelper,
            $jsonEncoder,
            $configCacheType,
            $regionCollectionFactory,
            $countryCollectionFactory,
            $data
        );
        $this->reportsFactory = $reportsFactory;
        $this->customerSession = $customerSession;
        $this->b8SpinHelper = $b8SpinHelper;
        $this->helper = $helper;
        $this->timezone = $timezone;
        $this->registry = $registry;
        $this->_conn = $resourceConnection->getConnection();
        $this->directoryHelper = $directoryHelper;
    }

    public function getCurrentPrizeId()
    {
        return $this->registry->registry('current_smrpid');
    }

    public function getCurrentConsolationId()
    {
        return $this->registry->registry('current_comid');
    }

    public function getNotification()
    {
        $currentPrizeId = $this->getCurrentPrizeId();
        $currentConsolationId = $this->getCurrentConsolationId();
        $customerId = $this->getCustomerId();
        if (!$currentPrizeId && !$currentConsolationId) {
            return null;
        }
        if ($currentPrizeId) {
            $sqlNotification = $this->_conn->select()
                ->from(['notification' => 'magenest_customer_notification'])
                ->where('segment_report_id = ?', $currentPrizeId);
            $notification = $this->_conn->fetchRow($sqlNotification);
            return $notification;
        } elseif ($currentConsolationId) {
            // $report = $this->reportsFactory->create()->load($currentConsolationId);
            return []; // No consolation prize detail available
        } else {
            return null;
        }
    }

    public function getConsolation() {
        $report = $this->getReportData();
        $customerId = $this->getCustomerId();
        if (!$report || !$customerId) {
            return null;  
        }
        if ($report['result'] == 0 && $report['is_apply_consolation'] == 1) {
            $prizeId = $report['entity_id'];
            $spinId = $report['spin_id'];
            // Get Consolation
            $consolationSql = $this->_conn->select()
            ->from(['report' => 'spintowin_consolation_reports'])
            ->columns(new \Zend_Db_Expr('ADDTIME(created_at, "08:00:00") as created_at'))
            ->where('customer_id = ?', $customerId)
            ->where('spin_id = ?', $spinId)
            ->where('prize_report_id = ?', $prizeId);

            $consolation = $this->_conn->fetchRow($consolationSql);
            if (!isset($consolation) || !isset($consolation['consolation_report_id'])) {
                return null;
            }
            
            return $this->getDetailConsolation($consolation);
        }
    }

    public function getAddress($spinId = null, $reportId = null) {
        
        $customerId = $this->getCustomerId();
        if (!$spinId || !$customerId || !$reportId) {
            return null;
        }

        $sqlAddress = $this->_conn->select()
            ->from(['address' => 'spintowin_award_address'])
            ->where('spin_id = ?', $spinId)
            ->where('customer_id = ?', $customerId)
            ->where('segment_report_id = ?', $reportId);
            
        $address = $this->_conn->fetchRow($sqlAddress);
        return $address?? null;
    }

    public function getImageUrl() {
        $report = $this->getReportData();
        if (!$report) {
            return '';  
        }
        $type = $report['result'] ?? '';
        $imageUrl = '';
        if($type == \Branch8\Spin2Win\Model\Config\Source\SegmentType::COUPON_TYPE){
            $imageUrl = ($report['coupon_image'] || $report['coupon_image'] != '')? $this->getMediaUrl() .  $report['coupon_image'] : '';
        }
        if($type == \Branch8\Spin2Win\Model\Config\Source\SegmentType::VIRTUAL_TYPE){
            
        }
        if($type == \Branch8\Spin2Win\Model\Config\Source\SegmentType::PHYSICAL_TYPE){
            $imageUrl = ($report['physical_image'] || $report['physical_image'] != '')? $this->getMediaUrl() .  $report['physical_image'] : '';
        } 
        if($type == \Branch8\Spin2Win\Model\Config\Source\SegmentType::REWARD_POINT_TYPE){
            
        }
        return $imageUrl;  
    }

    public function getAddressSaveUrl() {
        return $this->getUrl('spintowin/address/add');
    }

    public function getReportData() {
        return $this->getData('reportData');
    }

    public function formatPrizeDate($date) {
        if(!$date) {
            return '';
        }
        return $this->timezone->date($date)->format('Y/m/d');
    }

    public function formatRewardDate($date) {
        if($date && strlen($date) == 7 /**yyyy-mm */){
            $yearPoint = substr($date, 0, 4);
            $monthPoint = substr($date, 5, 2);
            $dayPoint = \cal_days_in_month(CAL_GREGORIAN, $monthPoint, $yearPoint);
            return $yearPoint.':'.$monthPoint.':'.$dayPoint;
        }
        return '';
    }

    public function getCustomer() {
        return $this->customerSession->getCustomer();
    }

    public function getCustomerId() {
        return $this->customerSession->getCustomerId();
    }

    /**
     * Get config value.
     *
     * @param string $path
     * @return string|null
     */
    public function getConfig($path)
    {
        return $this->_scopeConfig->getValue($path, \Magento\Store\Model\ScopeInterface::SCOPE_STORE);
    }

    /**
     * Returns country id
     *
     * @return string
     */
    public function getCountryId()
    {
        $countryId = $this->getData('country_id');
        if ($countryId === null) {
            $countryId = $this->directoryHelper->getDefaultCountry();
        }
        return $countryId;
    }

    /**
     * Return the id of the region being edited.
     *
     * @return int region id
     */
    public function getRegionId()
    {
        return 0 ;
    }

    public function getMediaUrl()
    {
       return $this->helper->getMediaDirectory();
    }

    public function getDetailPrize()
    {
        $prizeReport = $this->getReportData();
        $segmentType = $prizeReport['result'];
        
        if($segmentType == \Branch8\Spin2Win\Model\Config\Source\SegmentType::COUPON_TYPE){
            $prizeReport['expire_date'] = $prizeReport['coupon_expired_date']? $this->timezone->date($prizeReport['coupon_expired_date'])->format('Y/m/d') : '';
            $prizeReport['image'] = ($prizeReport['coupon_image'] && $prizeReport['coupon_image'] != '')? $this->getMediaUrl() .  $prizeReport['coupon_image'] : '';
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
            $prizeReport['start_date'] = $poolData['start_date'];
            $prizeReport['end_date'] = $poolData['end_date'];
            $prizeReport['create_date'] = $poolData['start_date'];
            $prizeReport['expire_date'] = $poolData['end_date']? $this->timezone->date($poolData['end_date'])->format('Y/m/d') : '';
            $prizeReport['image'] = $eventImage? $this->getBaseUrl().$eventImage : '';
        }else if($segmentType == \Branch8\Spin2Win\Model\Config\Source\SegmentType::PHYSICAL_TYPE){
            $physicalExpireDate = $prizeReport['physical_expire_date'];
            $prizeReport['image'] = ($prizeReport['physical_image'] && $prizeReport['physical_image'] != '')? $this->getMediaUrl() .  $prizeReport['physical_image'] : '';
            $prizeReport['expire_date'] = $physicalExpireDate? $this->timezone->date($physicalExpireDate)->format('Y/m/d') : '';
        } else if($segmentType == \Branch8\Spin2Win\Model\Config\Source\SegmentType::REWARD_POINT_TYPE){
            $prizeReport['image'] = '';
            if($prizeReport['point_expire_date'] && strlen($prizeReport['point_expire_date']) == 7 /**yyyy-mm */){
                $yearPoint = substr($prizeReport['point_expire_date'], 0, 4);
                $monthPoint = substr($prizeReport['point_expire_date'], 5, 2);
                if(function_exists('cal_days_in_month')){
                    $dayPoint = cal_days_in_month(CAL_GREGORIAN, $monthPoint, $yearPoint);
                    $prizeReport['expire_date'] =  $yearPoint.'/'.$monthPoint.'/'.$dayPoint;
                }else{
                    $dayPoint = '';
                    $prizeReport['expire_date'] =  '';
                }
                
            }else{
                $prizeReport['expire_date'] = '';
            }
        } 
        return $prizeReport;
    }

    protected function getDetailConsolation($consolationReport)
    {
        // return $consolationReport;
        if(!isset($consolationReport['consolation_type']) || !isset($consolationReport['consolation_id'])){
            return [];
        }
        $consolationType = $consolationReport['consolation_type'];
        
        if($consolationType == \Branch8\Spin2Win\Model\Config\Source\SegmentType::COUPON_TYPE){
            $consolationReport['expire_date'] = ($consolationReport['coupon_expired_date'] && $consolationReport['coupon_expired_date'] != '')? $this->timezone->date($consolationReport['coupon_expired_date'])->format('Y/m/d') : '';
            $consolationReport['image'] = ($consolationReport['coupon_image'] && $consolationReport['coupon_image'] != '')? $this->getMediaUrl() .  $consolationReport['coupon_image'] : '';
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
                $consolationReport['start_date'] = $poolData['start_date'];
                $consolationReport['end_date'] = $poolData['end_date'];
                $consolationReport['expire_date'] =  $poolData['end_date']? $this->timezone->date($poolData['end_date'])->format('Y/m/d') : '';
                $consolationReport['image'] = $eventImage? $this->getBaseUrl().$eventImage : '';
            }
        }else if($consolationType == \Branch8\Spin2Win\Model\Config\Source\SegmentType::PHYSICAL_TYPE){
            $consolationReport['expire_date'] =  $consolationReport['physical_expire_date'] ? $this->timezone->date($consolationReport['physical_expire_date'])->format('Y/m/d') : '';
            $consolationReport['image'] = ($consolationReport['physical_image'] && $consolationReport['physical_image'] != '')? $this->getMediaUrl() .  $consolationReport['physical_image'] : '';
        }else if($consolationType == \Branch8\Spin2Win\Model\Config\Source\SegmentType::REWARD_POINT_TYPE){
            $consolationReport['image'] = '';
            if($consolationReport['point_expire_date'] && strlen($consolationReport['point_expire_date']) == 7 /**yyyy-mm */){
                $yearPoint = substr($consolationReport['point_expire_date'], 0, 4);
                $monthPoint = substr($consolationReport['point_expire_date'], 5, 2);
                if(function_exists('cal_days_in_month')){
                    $dayPoint = cal_days_in_month(CAL_GREGORIAN, $monthPoint, $yearPoint);
                    $consolationReport['expire_date'] =  $yearPoint.'/'.$monthPoint.'/'.$dayPoint;
                }else{
                    $consolationReport['expire_date'] =  '';
                }
                
            }else{
                $consolationReport['expire_date'] = '';
            }
        }
        
        return $consolationReport;
    }
}