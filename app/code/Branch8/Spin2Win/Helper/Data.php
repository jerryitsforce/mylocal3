<?php

namespace Branch8\Spin2Win\Helper;

use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\App\Http\Context as HttpContext;
use Magento\Customer\Model\Context as CustomerContext;

class Data extends AbstractHelper
{
    /**
     * @var CustomerSession
     */
    public $customerSession;

    /**
     * @var CookieManagerInterface
     */
    public $cookieManager;

    /**
     * @var \Magento\Framework\Stdlib\DateTime\TimezoneInterface
     */
    protected $timezone;

    /**
     * @var \Magento\Framework\Serialize\SerializerInterface
     */
    public $serializer;

    /**
     * @var \Webkul\SpinToWin\Model\ReportsFactory
     */
    public $reportsFactory;

    /**
     * @var \Webkul\SpinToWin\Model\InfoFactory
     */
    public $infoFactory;
    
    /**
     * @var \Magento\Store\Model\StoreManagerInterface
     */
    public $store;

    protected $_conn;

    private $httpContext;

    public function __construct(
        \Magento\Framework\App\Helper\Context $context,
        \Magento\Customer\Model\Session $customerSession,
        \Magento\Framework\Stdlib\CookieManagerInterface $cookieManager,
        \Magento\Framework\Stdlib\DateTime\TimezoneInterface $timezone,
        \Magento\Framework\Serialize\SerializerInterface $serializer,
        \Webkul\SpinToWin\Model\ReportsFactory $reportsFactory,
        \Webkul\SpinToWin\Model\InfoFactory $infoFactory,
        \Magento\Store\Model\StoreManagerInterface $store,
        \Magento\Framework\App\ResourceConnection $resourceConnection,
        HttpContext $httpContext
    ){
        parent::__construct($context);
        $this->timezone = $timezone;
        $this->_conn = $resourceConnection->getConnection();
        $this->customerSession = $customerSession;
        $this->cookieManager = $cookieManager;
        $this->serializer = $serializer;
        $this->reportsFactory = $reportsFactory;
        $this->infoFactory = $infoFactory;
        $this->store = $store;
        $this->httpContext = $httpContext;
    }

    public function getTotalChances($spin, $customerId){

        $data = [
            'event_chances' => 0,
            'redemption_chance' => 0,
            'total' => 0
        ];
        if(!is_array($spin)){
            $spinId = $spin;
            $sqlSpin = $this->_conn->select()
                ->from(['info' => 'spintowin_info'])
                ->where('entity_id = ?', $spinId);
            $spin = $this->_conn->fetchRow($sqlSpin);
        }else{
            $spinId = $spin['entity_id'];
        }
        if(!$spin || empty($spin)){
            return $data;
        }
        if($spin['status'] == 0){
            return $data;
        }

        $spinChanceType = $spin['spin_type'];
        $chances = $spin['x_times'];
        
        $sqlPrizes = $this->_conn->select()
                ->from(['report' => 'spintowin_reports'], [])
                ->columns(new \Zend_Db_Expr('ADDTIME(created_at, "08:00:00") as played_time'))
                ->where('customer_id = ?', $customerId)
                ->where('spin_id = ?', $spinId);
        $allPrizes = $this->_conn->fetchAll($sqlPrizes);

        if ($spinChanceType == \Branch8\Spin2Win\Model\Config\Source\SpinType::DAILY_X_TIME) {
            $chancesToDay = 0;
            $currentDate = $this->timezone->date()->format('Y-m-d');
            foreach ($allPrizes as $_pr) {
                $playedTime = substr($_pr['played_time'], 0, 10);
                if ($playedTime == $currentDate) {
                    $chancesToDay++;
                }
            }
            $chances -= $chancesToDay;
            $chances = max(0, $chances);
            $data['event_chances'] = $chances;
            /**
             * Get chance by redemption
             */
            // $sqlRedemption = $this->_conn->select()
            // ->from(['redemption' => 'spintowin_redemption'], [])
            // ->columns(new \Zend_Db_Expr('sum(chance_qty) as qty'))
            // ->where('spin_id = ?', $spinId)
            // ->where('is_used = 0')
            // ->where('customer_id = ?', $customerId);
            // $chanceRedemption = $this->_conn->fetchOne($sqlRedemption);
            // $chances = $chances + $chanceRedemption;
            // $data['total'] = max(0, $chances);

        } else {
            /** Mode fixed */
            $chances -= count($allPrizes);
            $data['event_chances'] = $chances;
            /**
             * Get chance by redemption
             */
            // $sqlRedemption = $this->_conn->select()
            //     ->from(['redemption' => 'spintowin_redemption'], [])
            //     ->columns(new \Zend_Db_Expr('sum(chance_qty) as qty'))
            //     ->where('spin_id = ?', $spinId)
            //     ->where('customer_id = ?', $customerId);
            // $chanceRedemption = $this->_conn->fetchOne($sqlRedemption);
            // $chances += (int)$chanceRedemption;
            // $data['total'] = max(0, $chances);
        }
        
        /**
         * Get chance by redemption
         */
        $sqlRedemption = $this->_conn->select()
            ->from(['redemption' => 'spintowin_redemption'], [])
            ->columns(new \Zend_Db_Expr('sum(chance_qty) as qty'))
            ->where('spin_id = ?', $spinId)
            ->where('is_used = 0')
            ->where('customer_id = ?', $customerId);
        $chanceRedemptionUnuse = $this->_conn->fetchOne($sqlRedemption);

        $chances += (int)$chanceRedemptionUnuse;
        $data['total'] = max(0, $chances);
        
        $data['redemption_chance'] = $chanceRedemptionUnuse;
        
        return $data;
    }

    public function updateRedemptIsUsed($spinId, $customerId){
        $sqlUpdate = 'update spintowin_redemption set is_used=1 where is_used = 0 and spin_id='.$spinId.' and customer_id='.$customerId.' limit 1';
        $this->_conn->query($sqlUpdate);
    }

    public function getTotalFailedChances($spinId, $spinType, $customerId){
        if(!is_numeric($spinId)){
            return 0;
        }

        $sqlCntConsolation = $this->_conn->select()
                        ->from(['report' => 'spintowin_reports'], [])
                        ->columns(new \Zend_Db_Expr('count(*) as total_fail'))
                        ->where('spin_id = ?', $spinId)
                        ->where('customer_id = ?', $customerId)
                        ->where('result = ?', \Branch8\Spin2Win\Model\Config\Source\SegmentType::LOSE_TYPE)
                        ->where('is_apply_consolation = ?', 0);
        if($spinType == \Branch8\Spin2Win\Model\Config\Source\SpinType::DAILY_X_TIME){
            $sqlCntConsolation =  $sqlCntConsolation->where('DATE(created_at) = CURDATE()'); 
        }
        $totalFail = $this->_conn->fetchOne($sqlCntConsolation);
        // var_dump($sqlCntConsolation->__toString());
        // var_dump($totalFail); 
        // die();
        return $totalFail;
    }

    public function getConfigEmailSlowStock(){
        $emails = $this->scopeConfig->getValue('spin_to_win/general/low_stock_emails');
        $emails = str_replace("\r", "", (string)$emails);
        $arrEmail = explode("\n", $emails);
        return $arrEmail;
    }

    public function spinEditFormConvert($date){
        if(!$date){
            return '';
        }
        $dt = new \DateTime($date);
        return $this->timezone->date($dt)->format('Y-m-d H:i:s');
    }

    

    /**
     * Get OAuth Name
     *
     * @param string $name
     * @return string
     */
    public function getOAuthName($name)
    {
        $name = trim($name);
        $length = mb_strlen($name);

        if ($length <= 0) {
            return '';
        }

        if ($length <= 1) {
            return $name;
        }

        return str_repeat('O', $length - 1) . mb_substr($name, -1);
    }
    
     /**
     * Get OAuth Phone
     *
     * @param string $phone
     * @return string
     */
    public function getOAuthPhone($phone)
    {
        $length = strlen($phone);

        if ($length <= 0) {
            return '';
        }

        if ($length <= 5) {
            return $phone; 
        }

        $masked = substr($phone, 0, 3) . str_repeat('*', $length - 5) . substr($phone, -2);
        return $masked;
    }
    
    /**
     * Get OAuth Street
     *
     * @param string $street
     * @return string
     */
    public function getOAuthStreet($street)
    {
        $street = str_replace(',','',trim($street));
        $length = mb_strlen($street);

        if ($length <= 0) {
            return '';
        }

        if ($length <= 4) {
            return $street; 
        }

        $masked = str_repeat('*', $length - 4).mb_substr($street, -4, 4, 'UTF-8');
        return $masked;
    }

    
    /**
     * @param $address
     * @return string
     */
    public function getAddress($address)
    {
        if (!$address || !is_array($address)) {
            return '';
        }

        $city = '';
        $region = '';

        if ($address['region_id'] || $address['region']) {
            $region = $address['region'];
        }

        if ($address['city']) {
            $city = $address['city'];
        }

        $street = $address['street'];
        
        return $region. $city. $this->getOAuthStreet($street);
    }

    public function getTotalGravity($spinId){
        $prizeSelect = $this->_conn->select()
            ->from('spintowin_segments', ['gravity'])
            ->where('spin_id = ?', $spinId)
            ->where('is_publish = 1');
        $totalGravity = $this->_conn->fetchOne($prizeSelect);
        return (int)$totalGravity;
    }

    /**
     * Get Spin To Show
     *
     * @return \Webkul\SpinToWin\Model\InfoFactory
     */
    public function getSpin($spinId = null)
    {
        $storeCode = $this->customerSession->getStoreCode();
        $currentWebSite = $this->store->getStore()->getWebsiteId();

        $customerId = $this->customerSession->getCustomer()->getId();
        $currentCustomerGroup = $this->getCustomerGroupId($customerId);

        $currentDateTime = $this->getCurrentDateTime();
        $collection = $this->infoFactory->create()->getCollection()
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
                                    )
                                    ->addFieldToFilter('status', 1)
                                    ->setOrder('priority', 'DESC');
        $collection->getSelect()->where("FIND_IN_SET('".$currentWebSite."', website_ids)")
            ->where("FIND_IN_SET('".$currentCustomerGroup."', customergroup_ids)");
            
        if ($spinId) {
            $collection->addFieldToFilter('entity_id', $spinId);
        } 

        $spin = $collection->getFirstItem();
        return $spin;
    }

    /**
     * Current Date Time
     *
     * @return datetime
     */
    public function getCurrentDateTime()
    {
        return $this->timezone->convertConfigTimeToUtc($this->timezone->date());
    }

     /**
      * Current Date Time
      *
      * @return datetime
      */
    public function getCurrentDate()
    {
        return $this->timezone->convertConfigTimeToUtc($this->timezone->date(), 'Y-m-d');
    }

    public function getCustomerGroupId($customerId){
        $sqlCustomer = $this->_conn->select()
            ->from(['customer' => 'customer_entity'], 'group_id')
            ->where('entity_id = ?', $customerId);
        return $this->_conn->fetchOne($sqlCustomer);
    }
}