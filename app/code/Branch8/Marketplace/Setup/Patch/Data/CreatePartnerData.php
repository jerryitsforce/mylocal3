<?php
namespace Branch8\Marketplace\Setup\Patch\Data;

use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Framework\Setup\Patch\DataPatchInterface;

class CreatePartnerData implements DataPatchInterface
{
    protected $timezone;

    protected $wkHelperData;

    protected $moduleDataSetup; 

    public function __construct(
        ModuleDataSetupInterface $moduleDataSetup,
        \Magento\Framework\Stdlib\DateTime\TimezoneInterface $timezone,
        \Webkul\Marketplace\Helper\Data $wkHelperData
    ){
        
        $this->moduleDataSetup = $moduleDataSetup;
        $this->timezone = $timezone;
        $this->wkHelperData = $wkHelperData;
    }

    public function apply(): self
    {
        $_conn = $this->moduleDataSetup->getConnection();
        $sellerIdSql = 'select seller_id from marketplace_userdata where seller_id not in(select seller_id from marketplace_saleperpartner)';
        $querySellerId = $_conn->query($sellerIdSql);

        $createPartnerDate = $this->timezone->convertConfigTimeToUtc($this->timezone->date());
        $commission = $this->wkHelperData->getConfigCommissionRate();
        $minCommission = 0;
        while($row = $querySellerId->fetch()){
            $sellerId = $row['seller_id'];
            $sqlInsert = 'insert into marketplace_saleperpartner(entity_id, seller_id, commission_rate, created_at, min_commission_rate, commission_status) 
            values(NULL, '.$sellerId.', '.$commission.', "'.$createPartnerDate.'", '.$minCommission.', 0)';

            $_conn->query($sqlInsert);
        }
        return $this;
    }

    /**
     * @inheritdoc
     */
    public static function getDependencies(): array
    {
        return [];
    }

    /**
     * @inheritdoc
     */
    public function getAliases(): array
    {
        return [];
    }
}
