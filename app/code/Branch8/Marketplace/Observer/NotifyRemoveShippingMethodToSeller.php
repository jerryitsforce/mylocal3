<?php

namespace Branch8\Marketplace\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Event\Observer as EventObserver;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\App\Config\Storage\WriterInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Message\ManagerInterface;
use Magento\Shipping\Model\CarrierFactory;
use Magento\Shipping\Model\Config;
use Magento\Framework\App\ResourceConnection;
class NotifyRemoveShippingMethodToSeller implements ObserverInterface{
    /**
     * @var RequestInterface
     */
    private $request;
    /**
     * @var Config
     */
    protected $_deliveryModelConfig;
    /**
     * @var \Magento\Store\Model\StoreManagerInterface
     */
    protected $_storeManager;
    /**
     * @var \Webkul\Marketplace\Helper\Data
     */
    protected $sellerHelper;
    /**
     * @var ManagerInterface
     */
    protected $messageManager;
    /**
     * @var ResourceConnection
     */
    protected $resourceConnection;
    /**
     * @var CarrierFactory
     */
    protected $_carrierFactory;
    /**
     * @var \Magento\Framework\Stdlib\DateTime\TimezoneInterface
     */
    protected $timeZone;

    /**
     * @param RequestInterface $request
     * @param Config $deliveryModelConfig
     * @param \Magento\Store\Model\StoreManagerInterface $storeManager
     * @param \Webkul\Marketplace\Helper\Data $sellerHelper
     * @param ResourceConnection $resourceConnection
     * @param ManagerInterface $messageManager
     * @param CarrierFactory $carrierFactory
     * @param \Magento\Framework\Stdlib\DateTime\TimezoneInterface $timeZone
     */
    public function __construct(
        RequestInterface $request,
        Config $deliveryModelConfig,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Webkul\Marketplace\Helper\Data $sellerHelper,
        ResourceConnection $resourceConnection,
        ManagerInterface $messageManager,
        CarrierFactory $carrierFactory,
        \Magento\Framework\Stdlib\DateTime\TimezoneInterface $timeZone

    ){
        $this->request = $request;
        $this->_deliveryModelConfig = $deliveryModelConfig;
        $this->_storeManager = $storeManager;
        $this->sellerHelper = $sellerHelper;
        $this->resourceConnection = $resourceConnection;
        $this->messageManager = $messageManager;
        $this->_carrierFactory = $carrierFactory;
        $this->timeZone = $timeZone;
    }

    /**
     * @param EventObserver $observer
     * @return void
     */
    public function execute(\Magento\Framework\Event\Observer $observer) {
        $groupsSetting = $this->request->getParam('groups');
        $changedPaths = $observer->getEvent()->getChangedPaths();
        $disabledCarriers = [];
        foreach ($changedPaths as $path) {
            if(strpos($path, 'active') !== false){
                $explPath = explode('/', $path);
                $carrierCode = $explPath[1];

                if(isset($groupsSetting[$carrierCode]['fields']['active']['value'])){
                    $postValue = $groupsSetting[$carrierCode]['fields']['active']['value'];
                    if($postValue == 0){
                        $carrierObj = $this->_carrierFactory->create($carrierCode, (int)$observer->getEvent()->getStore());
                        $disabledCarriers[] = $carrierCode.'_'.$carrierObj->getCarrierCode();
                    }
                }

            }
        }

        if(!empty($disabledCarriers)){
            //get list seller using the methods that is disabled
            $connection = $this->resourceConnection->getConnection();
            //set data to database and run the related process by cronjob
            //1: Remove method from product
            //2: Remove method from seller
            //3: create notification to seller
            foreach($disabledCarriers as $_carrier){
                $sql = 'insert into branch8_shipping_method_update_queue values(null, "'.$_carrier.'", '.(int)$observer->getEvent()->getStore().', "'.$this->timeZone->convertConfigTimeToUtc($this->timeZone->date()).'", 0, NULL)';
                $connection->query($sql);
            }
        }
    }

}