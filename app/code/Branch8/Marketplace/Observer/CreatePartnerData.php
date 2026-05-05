<?php
namespace Branch8\Marketplace\Observer;

class CreatePartnerData implements \Magento\Framework\Event\ObserverInterface
{

    protected $timezone;

    protected $wkHelperData;

    protected $_conn;

    public function __construct(
        \Magento\Framework\Stdlib\DateTime\TimezoneInterface $timezone,
        \Webkul\Marketplace\Helper\Data $wkHelperData,
        \Magento\Framework\App\ResourceConnection $resourceConnection
    )
    {
        $this->timezone = $timezone;
        $this->wkHelperData = $wkHelperData;
        $this->_conn = $resourceConnection->getConnection();
    }

    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        try{
            $sellerUserData = $observer->getData('data_object');
            $userdataEntityId = $sellerUserData->getData('entity_id');
            $oldEntityId = $sellerUserData->getOrigData('entity_id');
            /** Old seller */
            if($oldEntityId == $userdataEntityId){
                return;
            }
            /** Just create seller */
            $sellerId = $sellerUserData->getSellerId();
            $createPartnerDate = $this->timezone->convertConfigTimeToUtc($this->timezone->date());
            $commission = $this->wkHelperData->getConfigCommissionRate();
            $minCommission = 0;
            $sqlInsert = 'insert into marketplace_saleperpartner(entity_id, seller_id, commission_rate, created_at, min_commission_rate, commission_status) 
            values(NULL, '.$sellerId.', '.$commission.', "'.$createPartnerDate.'", '.$minCommission.', 0)';
            $this->_conn->query($sqlInsert);
        }catch(\Exception $e){

        }
    }
}