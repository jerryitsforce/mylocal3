<?php

namespace Branch8\SellerContactInformation\Setup\Patch\Data;


use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Framework\Setup\Patch\DataPatchInterface;

class UpdateDefaultSellerCommission implements DataPatchInterface
{
    

    /**
     * @var ModuleDataSetupInterface
     */
    private $moduleDataSetup;

    protected $wkHelperData;

    protected $timezone;

    public function __construct(
        ModuleDataSetupInterface $moduleDataSetup,
        \Webkul\Marketplace\Helper\Data $wkHelperData,
        \Magento\Framework\Stdlib\DateTime\TimezoneInterface $timezone
    ) {
        $this->moduleDataSetup = $moduleDataSetup;
        $this->wkHelperData = $wkHelperData;
        $this->timezone = $timezone;
    }

    public function apply()
    {
        $this->moduleDataSetup->startSetup();
        $conn = $this->moduleDataSetup->getConnection();
        $commissionRateConfig = $this->wkHelperData->getConfigCommissionRate();

        $sqlCommissionStatus1 = 'select * from marketplace_saleperpartner where commission_status=1';
        $queryCommissionStatus1 = $conn->query($sqlCommissionStatus1);
        while($rowCommissionStatus1 = $queryCommissionStatus1->fetch()){
            $sqlUpdateCommissionStatus1 = 'update marketplace_saleperpartner set default_commission_rate='.$rowCommissionStatus1['commission_rate'].', 
             default_min_commission_rate='.(int)$rowCommissionStatus1['min_commission_rate'].' where entity_id='.$rowCommissionStatus1['entity_id'] ;
             $conn->query($sqlUpdateCommissionStatus1);
        }

        $commissionStatus0 = 'select * from marketplace_saleperpartner where commission_status=0';
        $queryCommissionStatus0 = $conn->query($commissionStatus0);
        
        while($rowCommissionStatus0 = $queryCommissionStatus0->fetch()){
            $sqlUpdateCommissionStatus0 = 'update marketplace_saleperpartner set commission_status=1, commission_rate='.$commissionRateConfig.', 
             min_commission_rate='.$commissionRateConfig.', default_commission_rate='.$commissionRateConfig.', 
             default_min_commission_rate='.$commissionRateConfig.' where entity_id='.$rowCommissionStatus0['entity_id'] ;
             $conn->query($sqlUpdateCommissionStatus0);
        }

        $sqlNoExited = 'select seller_id from marketplace_userdata where seller_id not in(select seller_id from marketplace_saleperpartner where seller_id <> 0)';
        $queryNoExisted = $conn->query($sqlNoExited);
        $createdAt = $this->timezone->convertConfigTimeToUtc($this->timezone->date());
        while($rowNotExisted = $queryNoExisted->fetch()){
            $sellerId = $rowNotExisted['seller_id'];
            $sqlInsert = 'insert into marketplace_saleperpartner(entity_id, seller_id, created_at, commission_rate, min_commission_rate, default_commission_rate, default_min_commission_rate) 
                values(NULL, '.$sellerId.', "'.$createdAt.'", '.$commissionRateConfig.', '.$commissionRateConfig.', '.$commissionRateConfig.', '.$commissionRateConfig.');';
            $conn->query($sqlInsert);
        }


        $this->moduleDataSetup->endSetup();
    }

    public function getAliases()
    {
        return [];
    }

    public static function getDependencies()
    {
        return [];
    }
}
