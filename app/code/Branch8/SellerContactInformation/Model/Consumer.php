<?php
namespace Branch8\SellerContactInformation\Model;
use Magento\Framework\MessageQueue\ConsumerConfiguration;
use Magento\Eav\Model\Config as EavConfig;
use Magento\Catalog\Model\ResourceModel\Product as ProductResource;

class Consumer extends ConsumerConfiguration{
    const CONSUMER_NAME = "seller.contract.active.update_product";
    const QUEUE_NAME = "seller.contract.active.update_product";

    protected $scopeConfig;

    protected $transportBuilder;

    protected $inlineTranslation;

    protected $logger;

    protected $_conn;

    protected $eavConfig;

    protected $_sellerProduct;

    protected $productCollectionFactory;

    protected $productResource;

    protected $timezone;

    protected $salable;

    const COMMISSION_SOURCE_ATTR = 'commission_source';

    const COMMISSION_PERCENT_ATTR = 'commission_percent';

    const COST_ATTR = 'cost';

    const COST_SETTING_ATTR = 'cost_setting';

    protected $commissionSourceAttrId;

    protected $commissionPercentAttrId;

    protected $costSettingAttrId;

    protected $costAttrId;

    const ACTION_CONTRACT_ACTIVE = 'seller_contract_active';

    const ACTION_ACTIVE_CONTRACT_CHANGE = 'seller_active_contract_change';

    const ACTION_CONTRACT_EXPIRED = 'seller_contract_expired';

    const ACTION_DEFAULT_SETTING_CHANGED = 'seller_default_commission_change';

    public function __construct(
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Magento\Framework\App\ResourceConnection $resourceConnection,
        EavConfig $eavConfig,
        \Webkul\Marketplace\Model\ResourceModel\Product\Collection $sellerProduct,
        \Magento\Catalog\Model\ResourceModel\Product\CollectionFactory $productCollectionFactory,
        ProductResource $productResource,
        \Branch8\OptionsWithStockAndImages\Helper\Salable $salable,
        \Magento\Framework\Stdlib\DateTime\TimezoneInterface $timezone
        
    ){
        $this->scopeConfig = $scopeConfig;
        $this->_conn = $resourceConnection->getConnection();
        $this->eavConfig = $eavConfig;
        $this->_sellerProduct = $sellerProduct;
        $this->productCollectionFactory = $productCollectionFactory;
        $this->productResource = $productResource;
        $this->timezone = $timezone;
        $this->salable = $salable;
    }

    public function process($request){
        $data = json_decode($request, true);
        $sellerId = $data['seller_id'];
        $action = $data['action'];

        /** Init data */
        $this->commissionSourceAttrId = $this->eavConfig->getAttribute(\Magento\Catalog\Model\Product::ENTITY, self::COMMISSION_SOURCE_ATTR)->getId();
        $this->costSettingAttrId = $this->eavConfig->getAttribute(\Magento\Catalog\Model\Product::ENTITY, self::COST_SETTING_ATTR)->getId();
        $this->costAttrId = $this->eavConfig->getAttribute(\Magento\Catalog\Model\Product::ENTITY, self::COST_ATTR)->getId();
        $this->commissionPercentAttrId = $this->eavConfig->getAttribute(\Magento\Catalog\Model\Product::ENTITY, self::COMMISSION_PERCENT_ATTR)->getId();
        
        /**
         * query commission
         */
        $sql = 'select commission_rate from marketplace_saleperpartner where seller_id='.$sellerId;
        $commissionRate = $this->_conn->fetchOne($sql);

        $sqlActiveContract = 'select count(*) as cnt from seller_contract_files where seller_id='.$sellerId.' and is_active=1';
        $hasActiveContract = $this->_conn->fetchOne($sqlActiveContract);
        $commissionSource = \Branch8\SellerContactInformation\Model\Config\Source\CommissionSource::DEFAULT_SETTING;
        if($hasActiveContract){
            $commissionSource = \Branch8\SellerContactInformation\Model\Config\Source\CommissionSource::ACTIVE_PERIOD;
        }
        
        /**
         * Get List product  
         */
        $productIds = $this->getProducts($sellerId);
        if(count($productIds) == 0){
            return;
        }
        
        /** Update for all curent product and staging in the future */
        $this->updateForProductAndStaging($productIds, $commissionSource, $commissionRate, $action);

        /** Apply for draft product */
        $this->updateForDraftProduct($sellerId, $commissionSource, $commissionRate, $action);

        /** Seller schedule not approve yet */
        $this->updateForSellerScheduleNotApproveYet($sellerId, $commissionSource, $commissionRate, $action);

        /** Apply for waiting for approval product */
        $this->updateForWaitingApprove($sellerId, $commissionSource, $commissionRate, $action);
        
    }

    protected function getProducts($sellerIds){
        // $this->_sellerProduct->addBindParam('mageproduct_id', 'mageproduct_id');
        $productIds = $this->_sellerProduct->getAllAssignProducts(
            '`seller_id`='.$sellerIds
        );

        return $productIds;
    }

    public function updateForProductAndStaging($productIds, $commissionSource, $commissionRate, $action){
        $this->_conn->beginTransaction();
        try{
            $commissionSourceAttrId = $this->commissionSourceAttrId;
            $commissionPercentAttrId = $this->commissionPercentAttrId;
            $costAttrId = $this->costAttrId;
            $costSettingAttrId = $this->costSettingAttrId;

            $currentTime = strtotime($this->timezone->convertConfigTimeToUtc($this->timezone->date()));
            /** Update for all curent product and staging in the future */
            $sqlCurrentAndStaging = 'select * from catalog_product_entity where entity_id in('.implode(',', $productIds).') and 
                ( updated_in > "'.$currentTime.'")';

            $queryCurrentAndStaging = $this->_conn->query($sqlCurrentAndStaging);
            while($rowCurrentAndStaging = $queryCurrentAndStaging->fetch()){
                $rowId = $rowCurrentAndStaging['row_id'];
                $sqlStagingData = 'SELECT `e`.row_id, `at_price`.`value` AS `price`, `at_cost_setting`.`value` AS `cost_setting`, 
                    `at_commission_source`.`value` as `commission_source`, `at_special_price`.`value` AS `special_price`, 
                    `at_commission_percent`.`value` AS `commission_percent`, at_cost.value as cost FROM `catalog_product_entity` AS `e`
                    LEFT JOIN `catalog_product_entity_int` AS `at_cost_setting` ON (`at_cost_setting`.`row_id` = `e`.`row_id`) AND (`at_cost_setting`.`attribute_id` = "'.$costSettingAttrId.'") AND (`at_cost_setting`.`store_id` = 0)
                    LEFT JOIN `catalog_product_entity_int` AS `at_commission_source` ON (`at_commission_source`.`row_id` = `e`.`row_id`) AND (`at_commission_source`.`attribute_id` = "'.$commissionSourceAttrId.'") AND (`at_commission_source`.`store_id` = 0)
                    LEFT JOIN `catalog_product_entity_decimal` AS `at_price` ON (`at_price`.`row_id` = `e`.`row_id`) AND (`at_price`.`attribute_id` = "77") AND (`at_price`.`store_id` = 0)
                    LEFT JOIN `catalog_product_entity_decimal` AS `at_special_price` ON (`at_special_price`.`row_id` = `e`.`row_id`) AND (`at_special_price`.`attribute_id` = "78") AND (`at_special_price`.`store_id` = 0) 
                    LEFT JOIN `catalog_product_entity_decimal` AS `at_commission_percent` ON (`at_commission_percent`.`row_id` = `e`.`row_id`) AND (`at_commission_percent`.`attribute_id` = "'.$this->commissionPercentAttrId.'") AND (`at_commission_percent`.`store_id` = 0) 
                    LEFT JOIN `catalog_product_entity_decimal` AS `at_cost` ON (`at_cost`.`row_id` = `e`.`row_id`) AND (`at_cost`.`attribute_id` = "'.$this->costAttrId.'") AND (`at_cost`.`store_id` = 0)
                    WHERE e.row_id='.$rowId.' and (`at_cost_setting`.`value` ='.\Branch8\Catalog\Model\Source\CostSetting::FIXED.')';
                
                $stagingRowData = $this->_conn->fetchRow($sqlStagingData);
                if(empty($stagingRowData)){
                    continue;
                }
                $productCommissionSource = $stagingRowData['commission_source'];
                $productCommissionPercent = $stagingRowData['commission_percent'];
                if($productCommissionSource == \Branch8\SellerContactInformation\Model\Config\Source\CommissionSource::MANULLY_INPUT 
                    && $productCommissionPercent != $commissionRate){
                        
                    continue;
                }
                $specialPrice = $stagingRowData['special_price'];
                $priceToCalc = $stagingRowData['price'];

                if($specialPrice){
                    $priceToCalc = $specialPrice;
                }
                $costValue = round($priceToCalc * (100 - $commissionRate)/100);
                $dataBefore = [];
                $dataAfter = [];
                $isUpdate = false;

                if($stagingRowData['cost'] != $costValue){
                    $sqlUpdateCurrentAndStagingCost = 'update catalog_product_entity_decimal set value='.$costValue.' where store_id=0 and row_id='.$rowId.' and attribute_id='.$costAttrId;
                    $this->_conn->query($sqlUpdateCurrentAndStagingCost);
                    
                    $dataBefore['cost'] = $stagingRowData['cost'];
                    $dataAfter['cost'] = $costValue;
                    $isUpdate = true;
                }
                if($commissionSource != $productCommissionSource){
                    $sqlUpdateCurrentAndStagingCommissionSource = 'update catalog_product_entity_int set value='.$commissionSource.' where store_id=0 and row_id='.$rowId.' and attribute_id='.$commissionSourceAttrId;
                    $this->_conn->query($sqlUpdateCurrentAndStagingCommissionSource);

                    $dataBefore['commission_source'] = $productCommissionSource;
                    $dataAfter['commission_source'] = $commissionSource;
                    $isUpdate = true;
                }

                if($commissionRate != $productCommissionPercent){
                    $sqlUpdateCurrentAndStagingCommissionPercent = 'update catalog_product_entity_decimal set value='.$commissionRate.' where store_id=0 and row_id='.$rowId.' and attribute_id='.$commissionPercentAttrId;
                    $this->_conn->query($sqlUpdateCurrentAndStagingCommissionPercent);

                    $dataBefore['commission_percent'] = $productCommissionPercent;
                    $dataAfter['commission_percent'] = $commissionRate;
                    $isUpdate = true;
                }
                
                if($isUpdate){
                    /** Update Variations table */
                    list($wk_manage_variation_before, $wk_manage_variation_after) = $this->salable->changeCostContractRollover($rowId, $priceToCalc, $commissionRate, $costValue);
                    if(count($wk_manage_variation_before) && count($wk_manage_variation_after)){
                        $dataBefore['wk_manage_variation'] = json_encode($wk_manage_variation_before);
                        $dataAfter['wk_manage_variation'] = json_encode($wk_manage_variation_after);
                    }
                    /** Log product change */
                    $productId = $rowCurrentAndStaging['entity_id'];
                    $this->addChangeLog($productId, $action, $dataBefore, $dataAfter);
                }
            }
            $this->_conn->commit();
        }catch(\Exception $e){
            $this->_conn->rollBack();
            throw new \Exception($e->getMessage());
        }
    }

    public function updateForDraftProduct($sellerId, $commissionSource, $commissionRate, $action){
        $this->_conn->beginTransaction();

        try{
            $sqlDraftProducts = 'select * from marketplace_product_temp where seller_id='.$sellerId;
            $queryDraftProducts = $this->_conn->query($sqlDraftProducts);
            while($rowDraft = $queryDraftProducts->fetch()){
                $draftProductInfor = json_decode((string)$rowDraft['information'], true);
                if(!$draftProductInfor){
                    echo "JSON Error\n";
                    continue;
                }
                $draftProduct = $draftProductInfor['product'];
                if(!isset($draftProduct['cost_setting'])){
                    continue;
                }
                $draftProductCostSetting = $draftProduct['cost_setting'];
                if($draftProductCostSetting == \Branch8\Catalog\Model\Source\CostSetting::MANUALLY && $draftProduct['commission_percent'] != $commissionRate){
                    continue;
                }
                if(!isset($draftProduct['commission_source'])){
                    continue;
                }
                if($draftProduct['commission_source'] == \Branch8\SellerContactInformation\Model\Config\Source\CommissionSource::MANULLY_INPUT){
                    continue;
                }
                $specialPrice = $rowDraft['special_price'];
                $priceToCalc = $rowDraft['price'];
                if($specialPrice){
                    $priceToCalc = $specialPrice;
                }
                $costValue = round($priceToCalc * (100 - $commissionRate)/100);

                $dataBefore = [];
                $dataAfter = [];
                $isUpdate = false;

                if($draftProduct['cost'] != $costValue){
                    $dataBefore['cost'] = $draftProduct['cost'];
                    $dataAfter['cost'] = $costValue;
                    $isUpdate = true;

                    $draftProduct['cost'] = $costValue;
                }
                if($draftProduct['commission_percent'] != $commissionRate){
                    $dataBefore['commission_percent'] = $draftProduct['commission_percent'];
                    $dataAfter['commission_percent'] = $commissionRate;
                    $isUpdate = true;

                    $draftProduct['commission_percent'] = $commissionRate;
                }
                if($draftProduct['commission_source'] != $commissionSource){
                    $dataBefore['commission_source'] = $draftProduct['commission_source'];
                    $dataAfter['commission_source'] = $commissionSource;
                    $isUpdate = true;

                    $draftProduct['commission_source'] = $commissionSource;
                }
                if($isUpdate){
                    $draftProductInfor['product'] = $draftProduct;
                    $draftProductInfor = json_encode($draftProductInfor);
                    $sqlUpdateDraft = 'update marketplace_product_temp set information=:information, cost=:cost where temp_id=:temp_id';
                    $bind = [
                        'information' => $draftProductInfor, 
                        'temp_id'    => $rowDraft['temp_id'],
                        'cost' => $costValue
                    ];
                    $this->_conn->query($sqlUpdateDraft, $bind);

                    /** Log product change */
                    $productId = $rowDraft['product_id'];
                    $this->addChangeLog($productId, $action, $dataBefore, $dataAfter);
                }
            }
            $this->_conn->commit();
        }catch(\Exception $e){
            $this->_conn->rollBack();
            throw new \Exception($e->getMessage());
        }
    }

    public function updateForSellerScheduleNotApproveYet($sellerId, $commissionSource, $commissionRate, $action){
        $this->_conn->beginTransaction();
        try{
            $sqlSellerSchedule = 'SELECT vd.*, vs.product_id FROM `marketplace_product_version_data` as vd 
                    left join marketplace_product_version as vs on vs.id = vd.parent_id 
                    left join marketplace_product as mp on mp.mageproduct_id = vs.product_id 
                    where vs.status=0 and mp.seller_id='.$sellerId.' group by mp.mageproduct_id;';
            $querySellerSchedule = $this->_conn->query($sqlSellerSchedule);
            while($rowSellerSchedule = $querySellerSchedule->fetch()){
                $information = (string)$rowSellerSchedule['information'];
                $inforArr = json_decode($information, true);
                $product = $inforArr['product'];
                if(!isset($product['cost_setting']) && !isset($product['commission_source']) && !isset($product['commission_percent']) && !isset($product['cost'])){
                    continue;
                }
                /** check if cost_setting of temp data is manually input */
                if(isset($product['cost_setting']) && $product['cost_setting'] == \Branch8\Catalog\Model\Source\CostSetting::MANUALLY){
                    continue;
                }
                /** If Fixed commission but the value is manually input */
                if(isset($product['commission_source']) 
                    && $product['commission_source'] == \Branch8\SellerContactInformation\Model\Config\Source\CommissionSource::MANULLY_INPUT
                    && $product['commission_percent'] != $commissionRate
                ){
                    continue;
                }

                //To this line, can't detect the Product commission Source(seller do not edit Commission source), 
                //so we should load commission source from product to check
                $utcTime = $this->timezone->convertConfigTimeToUtc($this->timezone->date());
                $currentTime = strtotime($utcTime);
                $sqlCheckUpdate = 'SELECT `e`.row_id, at_commission_percent.value as commission_percent, at_commission_source.value as commission_source 
                    FROM `catalog_product_entity` AS `e`
                    LEFT JOIN `catalog_product_entity_int` AS `at_cost_setting` ON (`at_cost_setting`.`row_id` = `e`.`row_id`) AND (`at_cost_setting`.`attribute_id` = "'.$this->costSettingAttrId.'") AND (`at_cost_setting`.`store_id` = 0)
                    LEFT JOIN `catalog_product_entity_int` AS `at_commission_source` ON (`at_commission_source`.`row_id` = `e`.`row_id`) AND (`at_commission_source`.`attribute_id` = "'.$this->commissionSourceAttrId.'") AND (`at_commission_source`.`store_id` = 0)
                    LEFT JOIN `catalog_product_entity_decimal` AS `at_commission_percent` ON (`at_commission_percent`.`row_id` = `e`.`row_id`) AND (`at_commission_percent`.`attribute_id` = "'.$this->commissionPercentAttrId.'") AND (`at_commission_percent`.`store_id` = 0)
                    WHERE e.entity_id='.$rowSellerSchedule['product_id'].' and (created_in < "'.$currentTime.'") and updated_in > "'.$currentTime.'" and (`at_cost_setting`.`value` ='.\Branch8\Catalog\Model\Source\CostSetting::FIXED.')';
                
                $currentProductLoop = $this->_conn->fetchRow($sqlCheckUpdate);
                if(!$currentProductLoop){
                    continue;
                }

                $productCommissionSource = $currentProductLoop['commission_source'];
                $productCommissionPercent = $currentProductLoop['commission_percent'];
                if($productCommissionSource == \Branch8\SellerContactInformation\Model\Config\Source\CommissionSource::MANULLY_INPUT 
                    && $productCommissionPercent != $commissionRate){
                    continue;
                }

                $specialPrice = $product['special_price'];
                $priceToCalc = $product['price'];
                if($specialPrice){
                    $priceToCalc = $specialPrice;
                }
                $costValue = round($priceToCalc * (100 - $commissionRate)/100);

                $dataBefore = [];
                $dataAfter = [];
                $isUpdate = false;

                if($product['cost'] != $costValue){
                    $dataBefore['cost'] = $product['cost'];
                    $dataAfter['cost'] = $costValue;
                    $isUpdate = true;

                    $product['cost'] = $costValue;
                }
                if($product['commission_percent'] != $commissionRate){
                    $dataBefore['commission_percent'] = $product['commission_percent'];
                    $dataAfter['commission_percent'] = $costValue;
                    $isUpdate = true;
                    
                    $product['commission_percent'] = $commissionRate;
                }
                if($product['commission_source'] = $commissionSource){
                    $dataBefore['commission_source'] = $product['commission_source'];
                    $dataAfter['commission_source'] = $costValue;
                    $isUpdate = true;
                    
                    $product['commission_source'] = $commissionSource;
                }
                if($isUpdate){
                    $inforArr['product'] = $product;
                    $newInformation = json_encode($inforArr);
                    $bind = [
                        'information' => $newInformation, 
                        'id'    => $rowSellerSchedule['id']
                    ];
                    
                    $sqlUpdateSellerSchedule = 'update marketplace_product_version_data set information=:information where id=:id';
                    $this->_conn->query($sqlUpdateSellerSchedule, $bind);

                    /** Log product change */
                    $productId = $rowSellerSchedule['product_id'];
                    $this->addChangeLog($productId, $action, $dataBefore, $dataAfter);
                }
            }
            $this->_conn->commit();
        }catch(\Exception $e){
            $this->_conn->rollBack();
            throw new \Exception($e->getMessage());
        }
    }

    public function updateForWaitingApprove($sellerId, $commissionSource, $commissionRate, $action){
        $this->_conn->beginTransaction();
        $commissionSourceAttrId = $this->commissionSourceAttrId;
        $costSettingAttrId = $this->costSettingAttrId;
        $costAttrId = $this->costAttrId;

        try{

            $sqlApprovalProduct = 'SELECT vs.id, vs.additional_information, vs.price, vs.special_price, vs.cost,vs.commission_percent, vs.product_id , vs.cost  
                FROM `marketplace_product` as mp 
                left join marketplace_product_version as vs on vs.product_id = mp.mageproduct_id and vs.status=0
                where mp.status = 0 and vs.status=0 and mp.seller_id= '.$sellerId.' 
                group by mageproduct_id;';
                
            $queryApprrovalProduct = $this->_conn->query($sqlApprovalProduct);
            while($rowApproval = $queryApprrovalProduct->fetch()){
                
                $additionalInformation = (string)$rowApproval['additional_information'];
                try{
                    $addInforArr = json_decode($additionalInformation, true);
                }catch(\Exception $e){
                    continue;
                }
                if(!isset($addInforArr['cost_setting']) && !isset($addInforArr['commission_source']) 
                    && !isset($addInforArr['commission_percent']) && !isset($addInforArr['special_price'])
                ){
                    continue;
                }
                /** check if cost_setting of temp data is manually input */
                if(isset($addInforArr['cost_setting']) && $addInforArr['cost_setting']['after'] == \Branch8\Catalog\Model\Source\CostSetting::MANUALLY){
                    continue;
                }
                /** If Fixed commission but the value is manually input */
                if(isset($addInforArr['commission_source']) 
                    && $addInforArr['commission_source']['after'] == \Branch8\SellerContactInformation\Model\Config\Source\CommissionSource::MANULLY_INPUT
                    && $rowApproval['commission_percent'] != $commissionRate){
                    continue;
                }

                //To this line, can't detect the Product commission Source(seller do not edit Commission source), so we should load commission source from product to check
                $utcTime = $this->timezone->convertConfigTimeToUtc($this->timezone->date());
                $currentTime = strtotime($utcTime);
                $sqlCheckUpdate = 'SELECT `e`.row_id, at_cost.value as cost_value, at_commission_source.value as commission_source, 
                at_commission_percent.value as commission_percent FROM `catalog_product_entity` AS `e`
                LEFT JOIN `catalog_product_entity_int` AS `at_cost_setting` ON (`at_cost_setting`.`row_id` = `e`.`row_id`) AND (`at_cost_setting`.`attribute_id` = "'.$costSettingAttrId.'") AND (`at_cost_setting`.`store_id` = 0)
                LEFT JOIN `catalog_product_entity_int` AS `at_commission_source` ON (`at_commission_source`.`row_id` = `e`.`row_id`) AND (`at_commission_source`.`attribute_id` = "'.$commissionSourceAttrId.'") AND (`at_commission_source`.`store_id` = 0)
                LEFT JOIN `catalog_product_entity_decimal` AS `at_cost` ON (`at_cost`.`row_id` = `e`.`row_id`) AND (`at_cost`.`attribute_id` = "'.$costAttrId.'") AND (`at_cost`.`store_id` = 0)
                LEFT JOIN `catalog_product_entity_decimal` AS `at_commission_percent` ON (`at_commission_percent`.`row_id` = `e`.`row_id`) AND (`at_commission_percent`.`attribute_id` = "'.$this->commissionPercentAttrId.'") AND (`at_commission_percent`.`store_id` = 0)
                WHERE e.entity_id='.$rowApproval['product_id'].' and (created_in < "'.$currentTime.'") and updated_in > "'.$currentTime.'" and (`at_cost_setting`.`value` ='.\Branch8\Catalog\Model\Source\CostSetting::FIXED.')';
                
                $currentProductLoop = $this->_conn->fetchRow($sqlCheckUpdate);
                if(!$currentProductLoop){
                    continue;
                }
                $productCommissionSource = $currentProductLoop['commission_source'];
                $productCommissionPercent = $currentProductLoop['commission_percent'];
                if($productCommissionSource == \Branch8\SellerContactInformation\Model\Config\Source\CommissionSource::MANULLY_INPUT 
                    && $productCommissionPercent != $commissionRate){
                    continue;
                }

                $specialPrice = $rowApproval['special_price'];
                $priceToCalc = $rowApproval['price'];
                if($specialPrice){
                    $priceToCalc = $specialPrice;
                }
                $costValue = round($priceToCalc * (100 - $commissionRate)/100);
                $costUpdate = '';
                
                $dataBefore = [];
                $dataAfter = [];
                $isUpdate = false;

                if($rowApproval['cost'] != $costValue){
                    $dataBefore['cost'] = $rowApproval['cost'];
                    $dataAfter['cost'] = $costValue;
                    $isUpdate = true;

                    if(isset($addInforArr['cost'])){
                        $addInforArr['cost']['after'] = $costValue;
                        $costUpdate = ', cost="'.$costValue.'", is_cost_changed=1 ';
                    }else{
                        $addInforArr['cost'] = ['before' => $currentProductLoop['cost_value'], 'after' => $costValue];
                        $costUpdate = ', cost="'.$costValue.'", is_cost_changed=1 ';
                    }
                }
                
                $commissionPercentUpdate = '';
                if($commissionRate != $rowApproval['commission_percent']){
                    $dataBefore['commission_percent'] = $rowApproval['commission_percent'];
                    $dataAfter['commission_percent'] = $commissionRate;
                    $isUpdate = true;

                    if(isset($addInforArr['commission_percent'])){
                        $addInforArr['commission_percent']['after'] = $commissionRate;
                        $commissionPercentUpdate = ', commission_percent="'.$commissionRate.'" ';
                    }else{
                        $addInforArr['commission_percent'] = ['before' => $rowApproval['commission_percent'], 'after' => $commissionRate];
                        $commissionPercentUpdate = ', commission_percent="'.$commissionRate.'" ';
                    }
                }
                
                if($commissionSource != $productCommissionSource){
                    $isUpdate = true;

                    if(isset($addInforArr['commission_source'])){
                        $dataBefore['commission_source'] = $addInforArr['commission_source'];
                        $dataAfter['commission_source'] = $commissionSource;

                        $addInforArr['commission_source']['after'] = $commissionSource;
                    }else{
                        $dataBefore['commission_source'] = $productCommissionSource;
                        $dataAfter['commission_source'] = $commissionSource;

                        $addInforArr['commission_source'] = ['before' => $productCommissionSource, 'after' => $commissionSource];
                    }
                }
                
                if($isUpdate){
                    $additionalInformationNew = json_encode($addInforArr);
                    $bind = [
                        'additional_information' => $additionalInformationNew, 
                        'id'    => $rowApproval['id']
                    ];
    
                    $sqlUpdateApprroval = 'update marketplace_product_version set additional_information=:additional_information 
                    '.$costUpdate.' '.$commissionPercentUpdate.'  where id=:id';
                    
    
                    $this->_conn->query($sqlUpdateApprroval, $bind);

                    /** Log product change */
                    $productId = $rowApproval['product_id'];
                    $this->addChangeLog($productId, $action, $dataBefore, $dataAfter);
                }
                
            }
            $this->_conn->commit();
        }catch(\Exception $e){
            $this->_conn->rollBack();
            throw new \Exception($e->getMessage());
        }
    }

    protected function addChangeLog($productId, $action, $dataBefore, $dataAfter){
        $createdAt8 = $this->timezone->date();
        $logCreatedAt = $this->timezone->convertConfigTimeToUtc($createdAt8);
        $dataBeforeJson = json_encode($dataBefore);
        $dataAfterJson = json_encode($dataAfter);
        $trace = 'app/code/Branch8/SellerContactInformation/Model/Consumer.php';
        $trace = json_encode($trace);
        $this->_conn->insert('branch8_product_change_log', [
            'log_id' => NULL, 'product_id' => $productId, 'action' => $action, 'before_values' => $dataBeforeJson, 
            'after_values' => $dataAfterJson, 'user_type' => \Branch8\Report\Model\Source\UserType::TYPE_SYSTEM, 
            'created_at' => $logCreatedAt, 'debug_backtrace' => $trace
        ]);
    }

}