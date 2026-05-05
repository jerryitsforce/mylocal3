<?php

namespace Branch8\Customer\Helper;
use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Framework\App\ResourceConnection;
use mysql_xdevapi\Exception;

class Group extends \Magento\Framework\App\Helper\AbstractHelper{

    const CONFIG_DEFAULT_ORGANIZATION = 'customer/create_account/default_organization';

    const CONFIG_FINAL_ORDER_SUCCESS_STATUS = 'sales/status_setting/final_success_status';

    const COMBINE_OR = 1;

    const COMBINE_AND = 2;

    const ICON_FOLDER = 'level_icon/';
    /**
     * @var \Magento\Customer\Model\CustomerFactory
     */
    protected $customerFactory;
    /**
     * @var \Branch8\Customer\Model\OrganizationFactory
     */
    protected $organizationFactory;
    /**
     * @var \Magento\Customer\Model\GroupFactory
     */
    protected $groupFactory;
    /**
     * @var \Magento\Sales\Model\ResourceModel\Order\CollectionFactory
     */
    protected $orderCollectionFactory;
    /**
     * @var CustomerRepositoryInterface
     */
    private $customerRepository;
    /**
     * @var \Magento\Framework\Stdlib\DateTime\TimezoneInterface
     */
    protected $_timeZone;
    /**
     * @var GroupHistory
     */
    protected $groupHistory;
    /**
     * @var \Magento\Customer\Model\ResourceModel\Group\CollectionFactory
     */
    protected $groupCollectionFactory;
    /**
     * @var \Branch8\MarketPlaceParentOrder\Model\ResourceModel\ParentOrderDetail\CollectionFactory
     */
    protected $parentOrderCollectionFactory;
    /**
     * @var ResourceConnection
     */
    protected $resourceConnection;

    /**
     * @param \Magento\Framework\App\Helper\Context $context
     * @param \Magento\Customer\Model\CustomerFactory $customerFactory
     * @param \Branch8\Customer\Model\OrganizationFactory $organizationFactory
     * @param \Magento\Customer\Model\GroupFactory $groupFactory
     * @param \Magento\Sales\Model\ResourceModel\Order\CollectionFactory $orderCollectionFactory
     * @param CustomerRepositoryInterface $customerRepository
     * @param \Magento\Framework\Stdlib\DateTime\TimezoneInterface $_timeZone
     * @param GroupHistory $groupHistory
     * @param \Magento\Customer\Model\ResourceModel\Group\CollectionFactory $groupCollectionFactory
     * @param \Branch8\MarketPlaceParentOrder\Model\ResourceModel\ParentOrderDetail\CollectionFactory $parentOrderCollectionFactory
     * @param ResourceConnection $resourceConnection
     */
    public function __construct(
        \Magento\Framework\App\Helper\Context $context,
        \Magento\Customer\Model\CustomerFactory $customerFactory,
        \Branch8\Customer\Model\OrganizationFactory $organizationFactory,
        \Magento\Customer\Model\GroupFactory $groupFactory,
        \Magento\Sales\Model\ResourceModel\Order\CollectionFactory $orderCollectionFactory,
        CustomerRepositoryInterface $customerRepository,
        \Magento\Framework\Stdlib\DateTime\TimezoneInterface $_timeZone,
        GroupHistory $groupHistory,
        \Magento\Customer\Model\ResourceModel\Group\CollectionFactory $groupCollectionFactory,
        \Branch8\MarketPlaceParentOrder\Model\ResourceModel\ParentOrderDetail\CollectionFactory $parentOrderCollectionFactory,
        ResourceConnection $resourceConnection
    ){
        parent::__construct($context);
        $this->customerFactory = $customerFactory;
        $this->organizationFactory = $organizationFactory;
        $this->groupFactory = $groupFactory;
        $this->orderCollectionFactory = $orderCollectionFactory;
        $this->customerRepository = $customerRepository;
        $this->_timeZone  = $_timeZone;
        $this->groupHistory = $groupHistory;
        $this->groupCollectionFactory = $groupCollectionFactory;
        $this->parentOrderCollectionFactory = $parentOrderCollectionFactory;
        $this->resourceConnection = $resourceConnection;
    }

    public function getGroup($groupId){
        $group = $this->groupFactory->create()->load($groupId);
        return $group;
    }

    /**
     * @param $data
     * @return false|string
     */
    public function setGroupCondition($data){
        return json_encode($data);
    }

    /**
     * @param $data
     * @return mixed
     */
    public function parseGroupCondition($data){
        return json_decode($data, true);
    }

    /**
     * @param $request
     * @param $customerGroupExtensionAttributes
     * @return mixed
     */
    public function setExtAttributes($request, $customerGroupExtensionAttributes){
        $organization = $request->getParam('organization');
        $prevLevel = $request->getParam('prev_level');
        $nxtLevel = $request->getParam('nxt_level');
        $period = (int)$request->getParam('period');
        $label = $request->getParam('label');
        $iconObj = $request->getFiles('icon');
        $icon = $iconObj['name'];
        $customerGroupExtensionAttributes->setOrganization($organization);
        $customerGroupExtensionAttributes->setPrevLevel($prevLevel);
        $customerGroupExtensionAttributes->setNxtLevel($nxtLevel);
        $customerGroupExtensionAttributes->setPeriod($period);
        $customerGroupExtensionAttributes->setLabel($label);
        $customerGroupExtensionAttributes->setIcon($icon);
        $conditionsData = [
            'condition_num_orders' => $request->getParam('condition_num_orders'),
            'condition_combine' => $request->getParam('condition_combine'),
            'condition_total_value' => $request->getParam('condition_total_value')
        ];
        if(trim($request->getParam('condition_num_orders') == '')){
            unset($conditionsData['condition_combine']);
            unset($conditionsData['condition_num_orders']);
        }
        if(trim($request->getParam('condition_total_value')) == ''){
            unset($conditionsData['condition_combine']);
            unset($conditionsData['condition_total_value']);
        }
        $conditions = json_encode($conditionsData);
        $customerGroupExtensionAttributes->setConditions($conditions);

        //set fullname
        $org = $this->organizationFactory->create()->load($organization);
        $fullname = $org->getName().' - '.$request->getParam('code');
        $customerGroupExtensionAttributes->setFullname($fullname);

        return $customerGroupExtensionAttributes;
    }

    /**
     * @param $customerId
     * @return mixed
     */
    public function getNextLevel($customerId){
        $customer = $this->customerFactory->create()->load($customerId);
        //get current group
        $customerGroupId = $customer->getGroupId();
        $group = $this->groupFactory->create()->load($customerGroupId);

        $nxtLevel = $group->getNxtLevel();

        return $nxtLevel;
    }

    /**
     * @param $customerId
     * @return mixed
     */
    public function getPrevLevel($customerId){
        $customer = $this->customerFactory->create()->load($customerId);
        //get current group
        $customerGroupId = $customer->getGroupId();
        $group = $this->groupFactory->create()->load($customerGroupId);
        $prevLevel = $group->getPrevLevel();
        return $prevLevel;
    }

    /**
     * @param $level
     * @return array|mixed
     */
    public function getLevelCondition($level){
        $nxtLevelObj = $this->groupFactory->create()->load($level);
        $conditionsData = $nxtLevelObj->getConditions();
        if(!$conditionsData || (string)$conditionsData == ''){
            return [];
        }
        $conditions = $this->parseGroupCondition($conditionsData);
        return $conditions;
    }

    /**
     * @param $customerId
     * @return bool
     */
    public function canUpgradeToNextLevel($customerId){
        if((int)$customerId == 0){
            return false;
        }
        $nxtLevel = $this->getNextLevel($customerId);
        if(!$nxtLevel){
            return false;
        }
        //load next level
        $conditions = $this->getLevelCondition($nxtLevel);
        if(empty($conditions)){
            return false;
        }

        if(!isset($conditions['condition_combine'])){
            if(isset($conditions['condition_total_value'])){
                return $this->validateTotalValue($customerId, $conditions['condition_total_value']);
            }else if(isset($conditions['condition_num_orders'])){
                return $this->validateNumberOrders($customerId, $conditions['condition_num_orders']);
            }
        }else{

            if($conditions['condition_combine'] == self::COMBINE_OR){
                return $this->validateNumberOrders($customerId, $conditions['condition_num_orders']) || $this->validateTotalValue($customerId, $conditions['condition_total_value']);
            }
            if($conditions['condition_combine'] == self::COMBINE_AND){
                return $this->validateNumberOrders($customerId, $conditions['condition_num_orders']) && $this->validateTotalValue($customerId, $conditions['condition_num_orders']);
            }
        }

        return false;
    }


    public function setLevel($customerId, $organizationId, $action){
        $customer = $this->customerFactory->create()->load($customerId)->getDataModel();
        $groups = $this->groupCollectionFactory->create()
            ->addFieldToFilter('organization', $organizationId);
        $allGroup = [];
        $firstLevelToCheck = null;
        $initLevel = null;
        foreach($groups as $_group){
            $allGroup[$_group->getId()] = $_group;
            if($_group->getNxtLevel() == null){
                $firstLevelToCheck = $_group;
            }
            if($_group->getPrevLevel() == null){
                $initLevel = $_group->getId();
            }
        }
        if($firstLevelToCheck == null){
            if($initLevel == null){
                throw new \Exception(__('Can not found any level to assign'));
            }else{
                $customer->setGroupId($initLevel);
                $currentGroup = $customer->getGroupId();
                $this->customerRepository->save($customer);
                $this->groupHistory->addGroupHistory($customerId, $currentGroup, $initLevel, $action);
            }
        }else{
            while(true){
                $conditions = json_decode($firstLevelToCheck->getConditions(), true);
                $period = $firstLevelToCheck->getPeriod();
                $currentDate = $this->_timeZone->date();
                $startPeriod = $currentDate->sub(new \DateInterval('P'.$period.'M'));
                $periods = [
                    'to' => $this->_timeZone->convertConfigTimeToUtc($this->_timeZone->date()),
                    'from' => $this->_timeZone->convertConfigTimeToUtc($startPeriod)
                ];
                $validateLevelResult = $this->validateCondition($customerId, $conditions, $periods);

                if(!$validateLevelResult){
                    if(isset($allGroup[$firstLevelToCheck->getPrevLevel()])) {
                        $firstLevelToCheck = $allGroup[$firstLevelToCheck->getPrevLevel()];
                    }else{
                        //no prev level, that mean this is last level
                        $customer->setGroupId($firstLevelToCheck->getId());
                        $currentGroup = $customer->getGroupId();
                        $this->customerRepository->save($customer);
                        $this->groupHistory->addGroupHistory($customerId, $currentGroup, $initLevel, $action);
                        break;
                    }
                }else{
                    $customer->setGroupId($firstLevelToCheck->getId());
                    $currentGroup = $customer->getGroupId();
                    $this->customerRepository->save($customer);
                    $this->groupHistory->addGroupHistory($customerId, $currentGroup, $initLevel, $action);
                    break;
                }
            }
        }
    }
    public function setDefaultLevel($customerId, $actionLog){
        try {
            $defaultOrg = $this->scopeConfig->getValue(self::CONFIG_DEFAULT_ORGANIZATION);
            $this->setLevel($customerId, $defaultOrg, $actionLog);
        }catch (\Exception $e){
            if(\Branch8\HotaiCore\Helper\DebugLog::isEnable('Branch8_Customer', 'exceptionlog')){
                $this->_logger->error('Update To Default Organization error: Customer_id:'.$customerId.', New Organization: '.$defaultOrg);
            }
        }
    }


    public function validateCondition($customerId, $conditions, $period, $connection = null){
        if(!isset($conditions['condition_combine'])){
            if(isset($conditions['condition_total_value'])){
                return $this->validateTotalValue($customerId, $conditions['condition_total_value'], $period, $connection);
            }else if(isset($conditions['condition_num_orders'])){
                return $this->validateNumberOrders($customerId, $conditions['condition_num_orders'], $period, $connection);
            }
        }else{

            if($conditions['condition_combine'] == self::COMBINE_OR){
                return $this->validateNumberOrders($customerId, $conditions['condition_num_orders'], $period, $connection)
                    || $this->validateTotalValue($customerId, $conditions['condition_total_value'], $period, $connection);
            }
            if($conditions['condition_combine'] == self::COMBINE_AND){
                return $this->validateNumberOrders($customerId, $conditions['condition_num_orders'], $period, $connection)
                    && $this->validateTotalValue($customerId, $conditions['condition_num_orders'], $period, $connection);
            }
        }

        return false;
    }

    /**
     * @param $customerId
     * @param $totalValue
     * @return bool
     */
    public function validateTotalValue($customerId, $totalValue, $period, $conn = null){
        if($conn == null){
            $conn = $this->resourceConnection->getConnection();
        }
        $select = $conn->select()
            ->from(['main_table' => 'sales_order'],  ['total_value' => 'sum(grand_total)'])
            ->joinLeft(
                ['pco' => 'sales_parent_order_children'],
                'pco.children_id = main_table.entity_id',
                'parent_id')
            ->joinLeft(['pro' => 'sales_parent_order_detail'], 'pro.parent_id = pco.parent_id', 'customer_id')
            ->where('pro.customer_id = '.$customerId.' 
                        and main_table.status="'.$this->scopeConfig->getValue(self::CONFIG_FINAL_ORDER_SUCCESS_STATUS).'" 
                        and pro.created_at >="'.$period['from'].'" and pro.created_at <="'.$period['to'].'"')
            ->group('pro.customer_id');

        $value = $conn->fetchOne($select);
        if($value >= $totalValue){
            return true;
        }
        return false;
    }

    /**
     * @param $customerId
     * @param $num_of_orders
     * @return bool
     */
    public function validateNumberOrders($customerId, $num_of_orders, $period, $conn = null){
        if($conn == null){
            $conn = $this->resourceConnection->getConnection();
        }

        $select = $conn->select()
            ->from('sales_parent_order_detail', ['cnt' => 'count(entity_id)'])
            ->where('customer_id=:customer_id')
            ->where('created_at >=:created_at_from')
            ->where('created_at <=:create_at_to')
            ->where('status=:status');
        $bind = [
            "customer_id" => $customerId,
            'created_at_from' => $period['from'],
            'create_at_to' => $period['to'],
            'status' => $this->scopeConfig->getValue(self::CONFIG_FINAL_ORDER_SUCCESS_STATUS)
        ];
        $cnt = $conn->fetchOne($select, $bind);
        if($cnt >= $num_of_orders){
            return true;
        }

        return false;
    }

    /**
     * @param $customerId
     * @param $force
     * @return void|null
     */
    public function upgradeLevel($customerId, $action, $force = false){
        if(!$force && !$this->canUpgradeToNextLevel($customerId)){
            return;
        }

        $nextLevel = $this->getNextLevel($customerId);
        $conditions = $this->getLevelCondition($nextLevel);

        if(!isset($conditions['condition_combine'])){
            if(isset($conditions['condition_total_value'])){
                return $this->doUpgradeByTotalValue($customerId, $nextLevel, (int)$conditions['condition_total_value'], $action);
            }else if(isset($conditions['condition_num_orders'])){
                return $this->doUpgradeByNumberOfOrders($customerId, $nextLevel, (int)$conditions['condition_num_orders'], $action);

            }
        }else{
            if($conditions['condition_combine'] == self::COMBINE_OR){
                return $this->doUpgradeByNumOfOrderORTotal($customerId, $nextLevel, $conditions, $action);
            }
            if($conditions['condition_combine'] == self::COMBINE_AND){
                return $this->doUpgradeByNumOfOrderANDTotal($customerId, $nextLevel, $conditions, $action);
            }
        }

    }

    /**
     * @param $customerId
     * @param $groupId
     * @return void
     * @throws \Magento\Framework\Exception\InputException
     * @throws \Magento\Framework\Exception\LocalizedException
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     * @throws \Magento\Framework\Exception\State\InputMismatchException
     */
    protected function updateLevelOnly($customerId, $groupId){
        $customer = $this->customerRepository->getById($customerId)
            ->setGroupId($groupId)
            ->setCustomAttribute('group_date', $this->_timeZone->convertConfigTimeToUtc($this->_timeZone->date(), 'Y-m-d 00:00:00'));

        $this->customerRepository->save($customer);
    }

//    public function setLevel($customerId, $currentLevel,  $nxtLevel, $historyDetail){
//        $this->updateLevelOnly($customerId, $nxtLevel);
//        $this->groupHistory->addGroupHistory($customerId, $currentLevel, $nxtLevel, $historyDetail);
//    }

    /**
     * @param $customerId
     * @param $groupId
     * @param $numberOfOrders
     * @return void
     * @throws \Magento\Framework\Exception\InputException
     * @throws \Magento\Framework\Exception\LocalizedException
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     * @throws \Magento\Framework\Exception\State\InputMismatchException
     */
    public function doUpgradeByNumberOfOrders($customerId, $groupId, $numberOfOrders, $action){
        $customer = $this->customerFactory->create()->load($customerId);
        $currentLevel = $customer->getGroupId();
        //upgrade customer group
        $this->updateLevelOnly($customerId, $groupId);
        $orders = $this->orderCollectionFactory->create()
            ->addAttributeToFilter('customer_id', $customerId)
            ->addAttributeToFilter('status', ['eq' => $this->scopeConfig->getValue(self::CONFIG_FINAL_ORDER_SUCCESS_STATUS)])
            ->addAttributeToFilter('level', ['null' => true]);
        $select = $orders->getSelect();
        $select->order('entity_id asc')->limit($numberOfOrders);
        $orderIds = [];
        foreach($orders as $_order){
            $_order->setLevel($groupId);
            $_order->getResource()->saveAttribute($_order, 'level');
            $orderIds[] = $_order->getId();
        }

        //history level changes
        $historyDetail = json_encode([
            'action' => $action,
            'order ID' => implode(',', $orderIds),
            'condition' => 'Total orders: '.$numberOfOrders
        ]);
        $this->groupHistory->addGroupHistory($customerId, $currentLevel, $groupId, $historyDetail);
    }

    /**
     * @param $customerId
     * @param $groupId
     * @param $totalValue
     * @return void
     * @throws \Magento\Framework\Exception\InputException
     * @throws \Magento\Framework\Exception\LocalizedException
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     * @throws \Magento\Framework\Exception\State\InputMismatchException
     */
    public function doUpgradeByTotalValue($customerId, $groupId, $totalValue, $action){
        $customer = $this->customerFactory->create()->load($customerId);
        $currentLevel = $customer->getGroupId();
        //upgrade customer group
        $this->updateLevelOnly($customerId, $groupId);
        $orders = $this->orderCollectionFactory->create()
            ->addAttributeToFilter('customer_id', $customerId)
            ->addAttributeToFilter('status', ['eq' => $this->scopeConfig->getValue(self::CONFIG_FINAL_ORDER_SUCCESS_STATUS)])
            ->addAttributeToFilter('level', ['null' => true]);
        $select = $orders->getSelect();
        $select->order('entity_id asc');

        $sumValue = 0;
        $orderIds = [];
        foreach($orders as $_order){
            $beforeValue = $sumValue;
            $sumValue += $_order->getGrandTotal();
            if($sumValue < $totalValue || ($sumValue >= $totalValue && $beforeValue < $totalValue)){
                $_order->setLevel($groupId);
                $_order->getResource()->saveAttribute($_order, 'level');
                $orderIds[] = $_order->getId();
            }else{
                break;
            }
        }
        //history level changes
        $historyDetail = json_encode([
            'action' => $action,
            'order ID' => implode(',', $orderIds),
            'condition' => 'Total value: '.$totalValue
        ]);
        $this->groupHistory->addGroupHistory($customerId, $currentLevel, $groupId, $historyDetail);
    }

    /**
     * @param $customerId
     * @param $groupId
     * @param $conditions
     * @return void
     * @throws \Magento\Framework\Exception\InputException
     * @throws \Magento\Framework\Exception\LocalizedException
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     * @throws \Magento\Framework\Exception\State\InputMismatchException
     * @throws \Zend_Log_Exception
     */
    public function doUpgradeByNumOfOrderORTotal($customerId, $groupId, $conditions, $action){
        $customer = $this->customerFactory->create()->load($customerId);
        $currentLevel = $customer->getGroupId();
        //upgrade customer group
        $this->updateLevelOnly($customerId, $groupId);
        //update sales order, set level
        $orders = $this->orderCollectionFactory->create()
            ->addAttributeToFilter('customer_id', $customerId)
            ->addAttributeToFilter('status', ['eq' => $this->scopeConfig->getValue(self::CONFIG_FINAL_ORDER_SUCCESS_STATUS)])
            ->addAttributeToFilter('level', ['null' => true]);
        $select = $orders->getSelect();
        $select->order('entity_id asc');
        $cntOrders = 0;
        $sumValue = 0;
        $orderIds = [];
        foreach($orders as $_order){
            $cntOrders += 1;
            $beforeValue = $sumValue;
            $sumValue += $_order->getGrandTotal();
            if(
                ($sumValue < $conditions['condition_total_value'] || ($sumValue >= $conditions['condition_total_value'] && $beforeValue < $conditions['condition_total_value'])) &&
                ($cntOrders <= $conditions['condition_num_orders'])
            ){
                $_order->setLevel($groupId);
                $_order->getResource()->saveAttribute($_order, 'level');
                $orderIds[] = $_order->getId();
            }else{
                break;
            }
        }

        //history level changes
        $historyDetail = json_encode([
            'action' => $action,
            'order ID' => implode(',', $orderIds),
            'condition' => json_encode($conditions)
        ]);
        $this->groupHistory->addGroupHistory($customerId, $currentLevel, $groupId, $historyDetail);
    }

    /**
     * @param $customerId
     * @param $groupId
     * @param $conditions
     * @return void
     * @throws \Magento\Framework\Exception\InputException
     * @throws \Magento\Framework\Exception\LocalizedException
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     * @throws \Magento\Framework\Exception\State\InputMismatchException
     */
    public function doUpgradeByNumOfOrderANDTotal($customerId, $groupId, $conditions, $action){
        $customer = $this->customerFactory->create()->load($customerId);
        $currentLevel = $customer->getGroupId();
        //upgrade customer group
        $this->updateLevelOnly($customerId, $groupId);
        //update sales order, set level
        $orders = $this->orderCollectionFactory->create()
            ->addAttributeToFilter('customer_id', $customerId)
            ->addAttributeToFilter('status', ['eq' => $this->scopeConfig->getValue(self::CONFIG_FINAL_ORDER_SUCCESS_STATUS)])
            ->addAttributeToFilter('level', ['null' => true]);
        $select = $orders->getSelect();
        $select->order('entity_id asc');

        $cntOrders = 0;
        $sumValue = 0;
        $orderIds = [];
        foreach($orders as $_order){
            $cntOrders += 1;
            $beforeValue = $sumValue;
            $sumValue += $_order->getGrandTotal();
            if(
                ($cntOrders <= $conditions['condition_num_orders']) ||
                ($sumValue < $conditions['condition_total_value'] || ($sumValue >= $conditions['condition_total_value'] && $beforeValue < $conditions['condition_total_value']))
            ){
                $_order->setLevel($groupId);
                $_order->getResource()->saveAttribute($_order, 'level');
                $orderIds[] = $_order->getId();
            }else{
                break;
            }
        }
        //history level changes
        $historyDetail = json_encode([
            'action' => $action,
            'order ID' => implode(',', $orderIds),
            'condition' => json_encode($conditions)
        ]);
        $this->groupHistory->addGroupHistory($customerId, $currentLevel, $groupId, $historyDetail);
    }

    public function canDowngradeLevel($customerId){
        $customer = $this->customerRepository->getById($customerId);
        $customerGroupId = $customer->getGroupId();
        $conditions = $this->getLevelCondition($customerGroupId);
        if((int)$customerId == 0){
            return false;
        }
        $prevLevel = $this->getPrevLevel($customerId);
        if(!$prevLevel){
            return false;
        }
        if(empty($conditions)){
            return false;
        }

        if(!isset($conditions['condition_combine'])){
            if(isset($conditions['condition_total_value'])){
                return !$this->validateTotalValue($customerId, $conditions['condition_total_value']);
            }else if(isset($conditions['condition_num_orders'])){
                return !$this->validateNumberOrders($customerId, $conditions['condition_num_orders']);
            }
        }else{

            if($conditions['condition_combine'] == self::COMBINE_OR){
                return !($this->validateNumberOrders($customerId, $conditions['condition_num_orders']) || $this->validateTotalValue($customerId, $conditions['condition_total_value']));
            }
            if($conditions['condition_combine'] == self::COMBINE_AND){
                return !($this->validateNumberOrders($customerId, $conditions['condition_num_orders']) && $this->validateTotalValue($customerId, $conditions['condition_num_orders']));
            }
        }
        return false;
    }

    public function doDowngradeLevel($customerId, $action, $force = false){
        if(!$force && !$this->canDowngradeLevel($customerId)){
            return;
        }
        $prevLevel = $this->getPrevLevel($customerId);
        $conditions = $this->getLevelCondition($customerId);
        $customer = $this->customerRepository->getById($customerId);
        $currentLevel = $customer->getGroupId();
        $this->updateLevelOnly($customerId, $prevLevel);
        $detailHistory = json_encode([
            'action' => $action,
            'conditions' => json_encode($conditions)
        ]);
        $this->groupHistory->addGroupHistory($customerId, $currentLevel, $prevLevel, $detailHistory);
    }
}