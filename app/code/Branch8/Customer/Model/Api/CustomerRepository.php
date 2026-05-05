<?php

namespace Branch8\Customer\Model\Api;

use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Framework\App\ResourceConnection;
class CustomerRepository implements \Branch8\Customer\Api\CustomerInterface
{
    /**
     * @var \Magento\Customer\Model\ResourceModel\Customer\CollectionFactory
     */
    protected $customerFactory;
    /**
     * @var CustomerResponse
     */
    protected $customerResponse;
    /**
     * @var \Branch8\Customer\Model\ResourceModel\Organization\CollectionFactory
     */
    protected $organizationCollectionFactory;
    /**
     * @var \Branch8\Customer\Helper\Group
     */
    protected $groupHelper;
    /**
     * @var \Psr\Log\LoggerInterface
     */
    protected $_logger;
    /**
     * @var \Branch8\Customer\Helper\OrganizationAPI
     */
    protected $organizationAPIHelper;
    /**
     * @var ResourceConnection
     */
    protected $resourceConnection;

    /**
     * @param \Magento\Customer\Model\ResourceModel\Customer\CollectionFactory $customerFactory
     * @param CustomerResponse $customerResponse
     * @param \Branch8\Customer\Model\ResourceModel\Organization\CollectionFactory $organizationCollectionFactory
     * @param \Branch8\Customer\Helper\Group $groupHelper
     * @param \Psr\Log\LoggerInterface $logger
     * @param \Branch8\Customer\Helper\OrganizationAPI $organizationAPIHelper
     * @param ResourceConnection $resourceConnection
     */
    public function __construct(
        \Magento\Customer\Model\ResourceModel\Customer\CollectionFactory $customerFactory,
        \Branch8\Customer\Model\Api\CustomerResponse  $customerResponse,
        \Branch8\Customer\Model\ResourceModel\Organization\CollectionFactory $organizationCollectionFactory,
        \Branch8\Customer\Helper\Group $groupHelper,
        \Psr\Log\LoggerInterface $logger,
        \Branch8\Customer\Helper\OrganizationAPI $organizationAPIHelper,
        ResourceConnection $resourceConnection
    ){
        $this->customerFactory = $customerFactory;
        $this->customerResponse = $customerResponse;
        $this->organizationCollectionFactory = $organizationCollectionFactory;
        $this->groupHelper = $groupHelper;
        $this->_logger = $logger;
        $this->organizationAPIHelper = $organizationAPIHelper;
        $this->resourceConnection = $resourceConnection;
    }
    public function updateOrganization($member_seq, $new_organization)
    {
        $response = $this->customerResponse;

        $customer = $this->customerFactory->create()
            ->addAttributeToFilter('member_seq', $member_seq)
            ->getFirstItem();
        if(!$customer->getId()) {
            $response->setResult(false);
            $response->setMessage(__('Customer does not exist.'));
            return $response;
        }
        $organization = $this->organizationCollectionFactory->create()
            ->addFieldToFilter('hotai1_name', $new_organization)
            ->getFirstItem();
        if(!$organization->getId()){
            $response->setResult(false);
            $response->setMessage(__('Organization does not exist.'));
            return $response;
        }
        try {
            $action = 'REST API update Organization to '.$new_organization;
            $this->groupHelper->setLevel($customer->getId(), $organization->getId(), $action);
            $response->setResult(true);
            $response->setMessage(__('Organization updated successfully.'));
            return $response;
        }catch(\Exception $e) {
            $response->setResult(false);
            $response->setMessage(__('Somethings went wrong.'));
            $errorMessage = 'REST API update organization error';
            $this->writeLog($errorMessage);
            $data = ['member_seq' => $member_seq, 'organization' => $new_organization];
            $this->writeLog(print_r($data, true));
            $this->writeLog($e->getMessage());
        }
        return $response;
    }

    public function updateOrganizationAction($requestBody)
    {
        $errorList = [];
        $connection = $this->resourceConnection->getConnection();
        $requestData = $this->organizationAPIHelper->decryptData($requestBody);
        try {
            $requestDataArr = json_decode($requestData, true);
        }catch (\Exception $e){
            $resultData = [
                'error' => true,
                'message' => 'Invalid data'
            ];
            return $resultData;
        }
        $hotaiEMP = [];
        $notEmp = [];
        foreach($requestDataArr as $_item){
            if($_item['isEnabled'] == 'true' && $_item['categoryIdentity'] == 'HotaiEMP'){
                $hotaiEMP[] = $_item['Cellphone'];
            }else{
                $notEmp[] = $_item['Cellphone'];
            }
        }

        //update to hotaiEMP
        if(count($hotaiEMP) > 0){
            array_walk($hotaiEMP, function (&$value, $key){
                $value = '"'.$value.'"';
            });
            $empOrg = $this->organizationAPIHelper->getHotaiEMP();
            if(count($hotaiEMP)) {
                $sqlCustoomerIdToEMP = 'select entity_id, phone_number from customer_entity where phone_number in(' . implode(',', $hotaiEMP) . ')';
                $resultCustoomerIdToEMP = $connection->query($sqlCustoomerIdToEMP);
                while ($rowCustoomerIdToEMP = $resultCustoomerIdToEMP->fetch()) {
                    $rowUpdate = $this->updateToOrg($rowCustoomerIdToEMP['entity_id'], $empOrg);
                    if (!$rowUpdate['success']) {
                        $errorList[] = [
                            'Cellphone' => $rowCustoomerIdToEMP['phone_number'],
                            'Detail' => 'Error on update to Hotai EMP'
                        ];
                    }
                }
            }
        }

        //update EC Org
        if(count($notEmp)) {
            $ecOrg = $this->organizationAPIHelper->getEcOrg();
            $sqlCustomerIdToECOrg = 'select entity_id, phone_number from customer_entity where phone_number in(' . implode(',', $notEmp) . ')';
            $resultUpdateHotaiEMPToEC = $connection->query($sqlCustomerIdToECOrg);
            while ($row = $resultUpdateHotaiEMPToEC->fetch()) {
                $rowUpdate = $this->updateToOrg($row['entity_id'], $ecOrg);
                if (!$rowUpdate['success']) {
                    $errorList[] = [
                        'Cellphone' => $rowCustoomerIdToEMP['phone_number'],
                        'Detail' => 'Error on update to EC'
                    ];
                }
            }
        }
        
        // 將所有進來的記入 customer_pending_employee 表
        foreach($requestDataArr as $_item) {
            $this->insertPendingEmployee($connection, $_item);
        }
        
        if(empty($errorList)){
            $resultData = [
                'error' => false,
                'message' => 'Success'
            ];

        }else{
            $resultData = [
                'error' => true,
                'message' => $errorList
            ];
        }
        return $resultData;
    }

    public function updateOrganizationByPhone($requestBody)
    {
        $response = $this->customerResponse;
        $errorList = [];
        $connection = $this->resourceConnection->getConnection();
        $requestData = $this->organizationAPIHelper->decryptData($requestBody);
        $requestDataArr = json_decode($requestData, true);
        $hotaiEMP = [];

        foreach($requestDataArr as $_item){
            if($_item['isEnabled'] == 'true' && $_item['categoryIdentity']){
                $hotaiEMP[$_item['Cellphone']] = $_item;
            }
        }
        $hotaiEMPPhones = array_keys($hotaiEMP);
        $sqlEMP = 'select ce.phone_number from customer_entity as ce
        left join customer_group as cg on ce.group_id = cg.customer_group_id
        left join branch8_customer_organization bco on cg.organization = bco.entity_id
        where ce.phone_number in('.implode(',', $hotaiEMPPhones).') and bco.hotai1_name="HotaiEMP" and ce.platform <> "seller" ';
        $result = $connection->query($sqlEMP);
        $empl = [];
        while($row = $result->fetch()){
            $empl[] = $row['phone_number'];
        }
        $customerEMPNeedUpdate = array_diff($hotaiEMPPhones, $empl);
        //update to hotaiEMP for $customerEMPNeedUpdate
        if(count($customerEMPNeedUpdate) > 0){
            $empOrg = $this->organizationAPIHelper->getHotaiEMP();
            $sqlCustoomerIdToEMP = 'select entity_id, phone_number from customer_entity where phone_number in('.implode(',', $customerEMPNeedUpdate).')';
            $resultCustoomerIdToEMP = $connection->query($sqlCustoomerIdToEMP);
            while ($rowCustoomerIdToEMP = $resultCustoomerIdToEMP->fetch()){
                $rowUpdate = $this->updateToOrg($rowCustoomerIdToEMP['entity_id'], $empOrg);
                if(!$rowUpdate['success']){
                    $errorList[] = [
                        'Cellphone' => $rowCustoomerIdToEMP['phone_number'],
                        'Detail' => 'Error on update to Hotai EMP'
                        ];
                }
            }
        }

        //update hotaiEMP to EC customer if !$customerEMPNeedUpdate
        $sqlUpdateHotaiEMPToEC = 'select ce.entity_id, ce.phone_number from customer_entity as ce
        left join customer_group as cg on ce.group_id = cg.customer_group_id
        left join branch8_customer_organization bco on cg.organization = bco.entity_id
        where ce.phone_number not in('.implode(',', $hotaiEMPPhones).') and bco.hotai1_name="HotaiEMP"  and ce.platform <> "seller" ';
        $resultUpdateHotaiEMPToEC = $connection->query($sqlUpdateHotaiEMPToEC);
        $ecOrg = $this->organizationAPIHelper->getEcOrg();
        while($row = $resultUpdateHotaiEMPToEC->fetch()){
            $rowUpdate = $this->updateToOrg($row['entity_id'], $ecOrg);
            if(!$rowUpdate['success']){
                $errorList[] = [
                    'Cellphone' => $rowCustoomerIdToEMP['phone_number'],
                    'Detail' => 'Error on update to EC'
                    ];
            }
        }
        if(empty($errorList)){
            $resultData = [
                'error' => false,
                'message' => 'Success'
            ];

        }else{
            $resultData = [
                'error' => true,
                'message' => $errorList
            ];
        }
        $resultDataEncrypted = $this->organizationAPIHelper->encryptData($resultData);
        $response->setResult($resultDataEncrypted);



        return $response;
    }

    public function updateToOrg($customer_id, $new_organization)
    {
        $result = ['success' => true];
        try {
            $action = 'REST API update Organization to '.$new_organization;
            $this->groupHelper->setLevel($customer_id, $new_organization, $action);
            $result = [
                'success' => true,
                'msg' => 'Organization updated successfully.'
            ];
            return $result;
        }catch(\Exception $e) {
            $result = [
                'success' => false,
                'msg' => 'Somethings went wrong.'
            ];

            $data = ['customer_id' => $customer_id, 'organization' => $new_organization];
            $this->writeLog(print_r($data, true));
            $errorMessage = 'REST API update organization error';
            $this->writeLog($errorMessage.':'.$e->getMessage());

        }
        return $result;
    }


    private function insertPendingEmployee($connection, $employeeData)
    {
        try {
            $now = date('Y-m-d H:i:s');
            $now = date(
                'Y-m-d H:i:s', // 為了方便驗證時區調整，這裡多加了 H:i:s
                strtotime('+8 hour', strtotime($now))
            );
            $tableName = $connection->getTableName('customer_pending_employee');
            $cellphone = $employeeData['Cellphone'];

            $select = $connection->select()->from($tableName)->where('cellphone = ?', $cellphone);
            $existingRecord = $connection->fetchRow($select);

            $data = [
                'isEnabled' => $employeeData['isEnabled'] == 'true' ? 1 : 0,
                'organizationIdentity' => $employeeData['organizationIdentity'] ?? null,
                'categoryIdentity' => $employeeData['categoryIdentity'],
                'updated_at' => $now
            ];

            if ($existingRecord) {
                // Record exists, check rank before updating organizationIdentity
                $newOrgIdentity = $employeeData['organizationIdentity'] ?? null;
                $existingOrgIdentity = $existingRecord['organizationIdentity'] ?? null;

                $newRank = $this->getOrganizationRank($connection, $newOrgIdentity);
                $existingRank = $this->getOrganizationRank($connection, $existingOrgIdentity);

                // Only update organizationIdentity if new rank is higher
                if ($newRank <= $existingRank) {
                    unset($data['organizationIdentity']);
                    if(\Branch8\HotaiCore\Helper\DebugLog::isEnable('Branch8_Customer', 'systemlog')){
                        $this->_logger->info('Skipping organizationIdentity update: new rank (' . $newRank . ') <= existing rank (' . $existingRank . ')', [
                            'cellphone' => $cellphone,
                            'newOrgIdentity' => $newOrgIdentity,
                            'existingOrgIdentity' => $existingOrgIdentity
                        ]);
                    }
                }

                $connection->update(
                    $tableName,
                    $data,
                    ['cellphone = ?' => $cellphone]
                );
            } else {
                // Record does not exist, perform INSERT
                $data['cellphone'] = $cellphone;
                $data['created_at'] = $now;
                $connection->insert($tableName, $data);
            }
        } catch (\Exception $e) {
            $this->writeLog('Error inserting/updating pending employee: ' . $e->getMessage());
            $this->writeLog('Employee data: ' . print_r($employeeData, true));
        }
    }

    /**
     * Get organization rank from organization_info table
     *
     * @param \Magento\Framework\DB\Adapter\AdapterInterface $connection
     * @param string|null $organizationIdentity
     * @return int
     */
    private function getOrganizationRank($connection, $organizationIdentity): int
    {
        if (empty($organizationIdentity)) {
            return 0;
        }

        try {
            $tableName = $connection->getTableName('organization_info');
            $select = $connection->select()
                ->from($tableName, ['org_rank'])
                ->where('organization_identity = ?', $organizationIdentity);

            $rank = $connection->fetchOne($select);

            return $rank !== false ? (int)$rank : 0;
        } catch (\Exception $e) {
            $this->writeLog('Error getting organization rank: ' . $e->getMessage());
            return 0;
        }
    }

    protected function writeLog($message){
        if(\Branch8\HotaiCore\Helper\DebugLog::isEnable('Branch8_Customer', 'exceptionlog')){
            $this->_logger->error($message);
        }
    }
}