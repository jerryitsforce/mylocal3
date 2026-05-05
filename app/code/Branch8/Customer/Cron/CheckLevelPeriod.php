<?php

namespace Branch8\Customer\Cron;
use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Framework\App\ResourceConnection;

class CheckLevelPeriod{
    /**
     * @var \Magento\Framework\Stdlib\DateTime\TimezoneInterface
     */
    protected $_timeZone;
    /**
     * @var \Branch8\Customer\Helper\Group
     */
    protected $groupHelper;
    /**
     * @var ResourceConnection
     */
    protected $resourceConnection;
    /**
     * @var \Branch8\Customer\Helper\GroupHistory
     */
    protected $groupHistory;
    /**
     * @var \Magento\Eav\Model\ResourceModel\Entity\Attribute
     */
    protected $_eavAttribute;
    /**
     * @var CustomerRepositoryInterface
     */
    protected $customerRepository;

    protected $scopeConfig;

    protected $groupCollectionFactory;

    protected $_eventManager;

    /**
     * @param \Magento\Framework\Stdlib\DateTime\TimezoneInterface $_timeZone
     * @param ResourceConnection $resourceConnection
     * @param \Branch8\Customer\Helper\Group $groupHelper
     * @param \Branch8\Customer\Helper\GroupHistory $groupHistory
     * @param \Magento\Eav\Model\ResourceModel\Entity\Attribute $eavAttribute
     * @param CustomerRepositoryInterface $customerRepository
     */
    public function __construct(
        \Magento\Framework\Stdlib\DateTime\TimezoneInterface $_timeZone,
        ResourceConnection $resourceConnection,
        \Branch8\Customer\Helper\Group $groupHelper,
        \Branch8\Customer\Helper\GroupHistory $groupHistory,
        \Magento\Eav\Model\ResourceModel\Entity\Attribute $eavAttribute,
        CustomerRepositoryInterface $customerRepository,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Magento\Customer\Model\ResourceModel\Group\CollectionFactory $groupCollectionFactory,
        \Magento\Framework\Event\Manager $eventManager
    ){
        $this->_timeZone  = $_timeZone;
        $this->resourceConnection = $resourceConnection;
        $this->groupHelper = $groupHelper;
        $this->groupHistory = $groupHistory;
        $this->_eavAttribute = $eavAttribute;
        $this->customerRepository = $customerRepository;
        $this->scopeConfig = $scopeConfig;
        $this->groupCollectionFactory = $groupCollectionFactory;
        $this->_eventManager = $eventManager;
    }

    /**
     * @return void
     * @throws \Zend_Db_Statement_Exception
     */
    public function execute(){
        //load seller group
        $defaultSellerGroup = $this->scopeConfig->getValue('seller_info/group_management/default_seller_group');
        $defaultGroup = $this->groupHelper->getGroup($defaultSellerGroup);
        $defaultOrg = $defaultGroup->getOrganization();
        $sellerGroups = $this->groupCollectionFactory->create()
            ->addFieldToSelect('customer_group_id')
            ->addFieldToFilter('organization', $defaultOrg);
        $sellerGroupList = [];
        foreach($sellerGroups as $_sellerGroup){
            $sellerGroupList[] = $_sellerGroup->getCustomerGroupId();
        }

        $attributeId = $this->_eavAttribute
            ->getIdByCode(\Magento\Customer\Model\Customer::ENTITY, 'cron_level_run_on');
        $batchs = 100;
        for($i = 1; $i <= $batchs; ++$i){
            $this->processBatch($sellerGroupList, $attributeId);
        }

    }

    protected function processBatch($sellerGroupList, $attributeId){
        $currentTime = $this->_timeZone->convertConfigTimeToUtc($this->_timeZone->date(), 'Y-m-d 00:00:00');
        //get list customer not running
        $sql = 'select customer_entity.entity_id, is_active, group_id, customer_entity.cron_level_run_on as cron_level_run_on from customer_entity
        where is_active=1 and group_id not in('.implode(',', $sellerGroupList).')
        and (customer_entity.cron_level_run_on <> "'.$currentTime.'" OR customer_entity.cron_level_run_on IS NULL)
        limit 0, 1000';
        $connection = $this->resourceConnection->getConnection();
        $result = $connection->query($sql);
        while($row = $result->fetch()){
            try{
                $customerId = $row['entity_id'];
                $groupId = $row['group_id'];
                /**
                * Get current organization and all levels of it
                * Check the condition to get max level
                */
                $groupsSQL = 'select customer_group_id, prev_level, nxt_level, period, conditions
                        from customer_group where organization in (select organization as org from customer_group where customer_group_id='.$groupId.')';
                $resultGroups = $connection->query($groupsSQL);
                $groups = [];
                $lastLevel = null;
                while($rowGroups = $resultGroups->fetch()){
                $groups[$rowGroups['customer_group_id']] = $rowGroups;
                    if($rowGroups['nxt_level'] == null){
                        $lastLevel = $rowGroups;
                    }
                }
                /**
                * If no last level, do no things
                */
                if($lastLevel == null){
                    continue;
                }
                $loopGroup = true;
                $levelToCheck = $lastLevel;
                $passedLevel = null;
                while($loopGroup){
                    $condition = $levelToCheck['conditions'];

                    $periodFrom = $this->_timeZone->date($currentTime)->modify('-'.$levelToCheck['period'].' months');
                    $periodFromConverted = $this->_timeZone->convertConfigTimeToUtc($periodFrom, 'Y-m-d 00:00:00');
                    $periodTo = $currentTime;
                    $period = ['from' => $periodFromConverted, 'to' => $periodTo];

                    if((string)$condition == '' || (string)$condition == '[]'){
                        $passedLevel = $levelToCheck['customer_group_id'];
                        break;
                    }
                    $conditionParsed = json_decode($condition, true);

                    if($this->groupHelper->validateCondition($customerId, $conditionParsed, $period, $connection)){
                        $passedLevel = $levelToCheck['customer_group_id'];
                        $loopGroup = false;
                    }else{
                        if(isset($groups[$levelToCheck['prev_level']])) {
                            $levelToCheck = $groups[$levelToCheck['prev_level']];
                        }else{
                            $passedLevel = $levelToCheck['customer_group_id'];
                            $loopGroup = false;
                        }
                    }
                }

                if($passedLevel == null){
                 /*   $sqlUpdateLevelCron = 'insert into customer_entity_datetime values(NUll, '.$attributeId.', '.$customerId.', "'.$currentTime.'") ON DUPLICATE KEY UPDATE value="'.$currentTime.'";';
                    $connection->query($sqlUpdateLevelCron);*/
                    $connection->update('customer_entity',
                        ['cron_level_run_on' => $currentTime], ['entity_id = ?' => $customerId]
                    );
                    continue;
                }
                if($groupId == $passedLevel){
                   /*
                    $sqlUpdateLevelCron = 'insert into customer_entity_datetime values(NUll, '.$attributeId.', '.$customerId.', "'.$currentTime.'") ON DUPLICATE KEY UPDATE value="'.$currentTime.'";';
                    $connection->query($sqlUpdateLevelCron);*/
                    $connection->update('customer_entity',
                        ['cron_level_run_on' => $currentTime], ['entity_id = ?' => $customerId]
                    );
                    continue;
                }
                $sqlUpdate = 'update customer_entity set group_id='.$passedLevel.' where entity_id='.$customerId;
                $connection->query($sqlUpdate);
                $historyDetail = json_encode([
                'action' => 'Daily cron update level',
                'run_on' => $currentTime,
                'period' => $period
                ]);
                $this->groupHistory->addGroupHistory($customerId, $groupId, $passedLevel, $historyDetail, $connection);
                //            //update the date processed
/*                $sqlUpdateLevelCron = 'insert into customer_entity_datetime values(NUll, '.$attributeId.', '.$customerId.', "'.$currentTime.'") ON DUPLICATE KEY UPDATE value="'.$currentTime.'";';*/

                $this->_eventManager->dispatch(
                    'branch8_customer_level_change',
                    ['customer_id' => $customerId, 'old_level' => $groupId, 'new_level' => $passedLevel]
                );
                $connection->update('customer_entity',
                    ['cron_level_run_on' => $currentTime], ['entity_id = ?' => $customerId]
                );
                //$connection->query($sqlUpdateLevelCron);
            }catch(\Exception $e){

            }

        }
    }
}
