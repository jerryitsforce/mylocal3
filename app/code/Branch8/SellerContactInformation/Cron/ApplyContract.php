<?php
namespace Branch8\SellerContactInformation\Cron;

use Branch8\SellerContactInformation\Model\Config\Source\ContractStatus;

class ApplyContract
{
    protected $timezone;

    protected $_conn;

    protected $mpProductColl;

    protected $publisher;

    protected $_sellerProduct;

    public function __construct(
        \Magento\Framework\Stdlib\DateTime\TimezoneInterface $timezone,
        \Magento\Framework\App\ResourceConnection $resourceConnection,
        \Webkul\Marketplace\Model\ResourceModel\Product\Collection $mpProductColl,
        \Magento\Framework\MessageQueue\PublisherInterface $publisher,
        \Webkul\Marketplace\Model\ResourceModel\Product\Collection $sellerProduct
    ){
        $this->timezone = $timezone;    
        $this->_conn = $resourceConnection->getConnection();
        $this->mpProductColl = $mpProductColl;
        $this->publisher = $publisher;
        $this->_sellerProduct = $sellerProduct;
    }

    public function execute(){
        $currentTime = $this->timezone->convertConfigTimeToUtc($this->timezone->date());
        $sql = 'select * from seller_contract_files where is_active = '.ContractStatus::STATUS_PENDING.' and contracts_from <= "'.$currentTime.'" 
            and contracts_to >= "'.$currentTime.'"';
        $query = $this->_conn->query($sql);
        try{
            
            while($row = $query->fetch()){
                try{
                    $this->_conn->beginTransaction();

                    $commission = $row['commission'];
                    $minCommission = $row['min_commission'];
                    $sellerId = $row['seller_id'];
                    $contractId = $row['entity_id'];
                    $updatedTime = $this->timezone->convertConfigTimeToUtc($this->timezone->date());
                
                    $sqlCheckPartner = 'select entity_id from marketplace_saleperpartner where seller_id='.$sellerId;
                    $sellerPartner = $this->_conn->fetchOne($sqlCheckPartner);
                    if(!$sellerPartner){
                        $createPartnerDate = $this->timezone->convertConfigTimeToUtc($this->timezone->date());
                        $sqlInsert = 'insert into marketplace_saleperpartner(entity_id, seller_id, commission_rate, created_at, min_commission_rate, commission_status) 
                            values(NULL, '.$sellerId.', '.$commission.', "'.$createPartnerDate.'", '.$minCommission.', 1)';
                        $this->_conn->query($sqlInsert);
                    }else{
                        /**
                         * Update sale partner
                         */
                        $sql = 'update marketplace_saleperpartner set commission_rate="'.$commission.'", min_commission_rate="'.$minCommission.'", commission_status = 1 where seller_id='.$sellerId;
                        
                        $this->_conn->query($sql);
                    }
                    
                    
                    $sqlUpdateContractInactive = 'update seller_contract_files set is_active='.ContractStatus::STATUS_EXPIRED.' 
                        where seller_id='.$sellerId.' and is_active='.ContractStatus::STATUS_ACTIVE;
                    $this->_conn->query($sqlUpdateContractInactive);
                    $sqlUpdateContractActive = 'update seller_contract_files set is_active=1, updated_at="'.$updatedTime.'" where entity_id='.$contractId;
                    $this->_conn->query($sqlUpdateContractActive);

                    /** Update contract history */
                    $sqlInsertContractLogActive = 'insert into seller_contract_logs(entity_id, contract_id, seller_id, is_active, updated_by, updated_at) values(NULL, '.$contractId.', '.$sellerId.', 1, "cron_active_contract", "'.$updatedTime.'")';
                    $this->_conn->query($sqlInsertContractLogActive);
                    /**
                     * push product to message queue
                    */
                    if($row['update_existed_product']){
                        $queueData = [
                            'seller_id' => $sellerId,
                            'action' => \Branch8\SellerContactInformation\Model\Consumer::ACTION_CONTRACT_ACTIVE
                        ];
                        $this->publisher->publish(
                            'seller.contract.active.update_product',
                            json_encode($queueData)
                        );
                    }
                    $this->_conn->commit();
                }catch(\Exception $e){
                    $this->_conn->rollBack();
                }
            }

            /** 
             * If no next active contract but the current contract is expired
             * - set current active contract to expired
             * - set commission rate and min commission rate to use default rate
             * */
            $sqlExpired = 'select entity_id, seller_id from seller_contract_files where is_active = '.ContractStatus::STATUS_ACTIVE.' and contracts_to < "'.$currentTime.'"';
            $queryExpired = $this->_conn->query($sqlExpired);
            
            while($rowExpired = $queryExpired->fetch()){
                $this->_conn->beginTransaction();
                try{
                    $sqlToExpired = 'update seller_contract_files set is_active='.ContractStatus::STATUS_EXPIRED.' where entity_id='.$rowExpired['entity_id'];
                    $this->_conn->query($sqlToExpired);

                    $sellerId = $rowExpired['seller_id'];
                    $sqlUpdateDefaultRate = 'update marketplace_saleperpartner set commission_status=1, commission_rate=default_commission_rate,
                        min_commission_rate=default_min_commission_rate where seller_id='.$sellerId;
                    $this->_conn->query($sqlUpdateDefaultRate);
                    /** Set queue to update default rate to all products */
                    $queueData = [
                        'seller_id' => $sellerId,
                        'action' => \Branch8\SellerContactInformation\Model\Consumer::ACTION_CONTRACT_EXPIRED
                    ];
                    $this->publisher->publish(
                        'seller.contract.active.update_product',
                        json_encode($queueData)
                    );
                    $this->_conn->commit();

                }catch(\Exception $e){
                    $this->_conn->rollBack();
                }
            }
            /** Set Expired for all contract has contracts_to in the past */
            $sqlToExpired = 'update seller_contract_files set is_active = '.ContractStatus::STATUS_EXPIRED.' where is_active='.ContractStatus::STATUS_PENDING.' and contracts_to < "'.$currentTime.'"';
            $this->_conn->query($sqlToExpired);
            
        }catch(\Exception $e){
            $this->_conn->rollBack();
        }
    }

    
}
