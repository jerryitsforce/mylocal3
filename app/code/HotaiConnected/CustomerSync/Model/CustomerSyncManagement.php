<?php
/**
 * Copyright © Hotai Connected Co.,Ltd All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace HotaiConnected\CustomerSync\Model;

use Psr\Log\LoggerInterface as Logger;
use Magento\Sales\Api\OrderRepositoryInterface as OrderRepository;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Customer\Model\ResourceModel\CustomerRepository;
use Magento\Customer\Api\AddressRepositoryInterface;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Model\Order;
use Magento\Framework\Api\FilterBuilder;
use Magento\Framework\Webapi\Rest\Response;
use Magento\Eav\Model\Config as EavConfig;
use Magento\Customer\Model\Customer;
use HotaiConnected\CustomerSync\Api\CustomerSyncManagementInterface;
use Magento\Framework\App\RequestInterface;
use Branch8\HotaiCore\Model\Ticket\Status as TicketStatus;

class CustomerSyncManagement implements CustomerSyncManagementInterface
{

    protected $logger;
    protected $request;
    protected $resource;
    protected $orderRepository;
    protected $searchCriteriaBuilder;
    protected $filterBuilder;
    protected $customerRepository;
    protected $addressRepository;
    protected $response;
    protected $eavConfig;

    /**
     * __construct
     *
     * @return void
     */
    public function __construct(
        \Magento\Framework\App\ResourceConnection $resource,
        Logger $logger,
        OrderRepository $orderRepository,
        SearchCriteriaBuilder $searchCriteriaBuilder,
        FilterBuilder $filterBuilder,
        CustomerRepository $customerRepository,
        Response $response,
        EavConfig $eavConfig,
        AddressRepositoryInterface $addressRepository,
        RequestInterface $request
    ) {
        $this->resource = $resource;
        $this->logger = $logger;
        $this->orderRepository = $orderRepository;
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;
        $this->filterBuilder = $filterBuilder;
        $this->customerRepository = $customerRepository;
        $this->response = $response;
        $this->eavConfig = $eavConfig;
        $this->addressRepository = $addressRepository;
        $this->request = $request;
    }

    /**
     * {@inheritdoc}
     */
    public function getCustomerSync()
    {
        try {
            $customer = $this->validateAndGetCustomerId();

            //OneId格式錯誤, 查無此顧客,顧客已刪除 回應成功
            if($customer['customerId'] == 0) {
                $this->serverResponseSuccess(0, true, []);
                return;
            }
            
            $customerId = $customer['customerId'];
            $oneId = $customer['oneId'];

            // 檢查訂單
            $orderCheck = $this->checkCustomerOrders($customerId, $oneId);
            if($orderCheck) {
                $this->serverResponseSuccess(1, false, [$orderCheck]);
                return;
            }

            // 檢查虛擬票券
            $ticketCheck = $this->checkVirtualTickets($customerId, $oneId);
            if($ticketCheck) {
                $this->serverResponseSuccess(1, false, [$ticketCheck]);
                return;
            }

            $this->serverResponseSuccess(0, true, []);
            return;

        } catch (\Exception $e) {
            $this->logger->error('[customer_sync] get customer status fail: '.$e->getMessage());
            $this->serverResponseError("Occurred an Exception", $e->getMessage());
            return;
        }
    }

    public function deleteCustomerSync()
    {
        try {
            $customer = $this->validateAndGetCustomerId();

            //OneId格式錯誤, 查無此顧客,顧客已刪除 回應成功
            if($customer['customerId'] == 0) {
                $this->serverResponseSuccess(0, true, []);
                return;
            }

            $customerId = $customer['customerId'];
            $oneId = $customer['oneId'];

            // 檢查訂單
            $orderCheck = $this->checkCustomerOrders($customerId, $oneId);
            if($orderCheck) {
                $this->serverResponseSuccess(1, false, [$orderCheck]);
                return;
            }

            // 檢查虛擬票券
            $ticketCheck = $this->checkVirtualTickets($customerId, $oneId);
            if($ticketCheck) {
                $this->serverResponseSuccess(1, false, [$ticketCheck]);
                return;
            }

            $customer = $this->customerRepository->getById($customerId);

            $connection = $this->resource->getConnection();
            $connection->beginTransaction();

            $this->encryptCustomerData($customer);

            // 使用 raw SQL 更新客戶資料
            $tableName = $connection->getTableName('customer_entity');
            $sql = "UPDATE " . $tableName . "
               SET buyer_email = :buyer_email,
                   is_deleted = :is_deleted,
                   lock_expires = :lock_expires
               WHERE entity_id = :customer_id";

            $binds = [
                'buyer_email' => 'delete@mail.com',
                'is_deleted' => 1,
                'lock_expires' => '2038-01-01 00:00:00',
                'customer_id' => $customerId
            ];

            $connection->query($sql, $binds);

            $this->encryptCustomerAddresses($customer);
            $this->encryptSalesOrderAddresses($customer->getId());

            $connection->commit();
            $this->serverResponseSuccess(0, true, []);
            return;

        } catch (\Exception $e) {
            if ($connection) {
                $connection->rollBack();
            }
            $this->logger->error('[customer_sync] delete customer data fail: '.$e->getMessage());
            $this->serverResponseError("Occurred an Exception", $e->getMessage());
            return;
        }
    }

    private function encryptCustomerData($customer)
    {
        // 需要加密的欄位及其類型
        $fieldsToEncrypt = [
            // varchar fields
            'prefix' => ['type' => 'varchar', 'length' => 40],
            'firstname' => ['type' => 'varchar', 'length' => 5],
            'middlename' => ['type' => 'varchar', 'length' => 5],
            'lastname' => ['type' => 'varchar', 'length' => 5],
            'suffix' => ['type' => 'varchar', 'length' => 5],
            'password_hush' => ['type' => 'varchar', 'length' => 255],
            'rp_token' => ['type' => 'varchar', 'length' => 255],
            'taxvat' => ['type' => 'varchar', 'length' => 255],
            'phone_number' => ['type' => 'varchar', 'length' => 20],
            'platform' => ['type' => 'varchar', 'length' => 50],
            'seller_phone' => ['type' => 'varchar', 'length' => 255],
            // date fields
            'dob' => ['type' => 'date'],
            // smallint fields
            'gender' => ['type' => 'smallint'],
            // int
            'default_billing' => ['type' => 'int'],
            'default_shipping' => ['type' => 'int']
        ];

        // 處理每個需要加密的欄位
        foreach ($fieldsToEncrypt as $field => $config) {
            try {
                $attribute = $customer->getCustomAttribute($field);
                $originalValue = $attribute ? $attribute->getValue() : null;

                if ($originalValue === null) {
                    continue;
                }

                $encryptedValue = $this->generateEncryptedValue(
                    $config['type'],
                    $originalValue,
                    null
                );

                if ($config['type'] === 'varchar') {
                    $encryptedValue = substr($encryptedValue, 0, $config['length']);
                }

                $customer->setCustomAttribute($field, $encryptedValue);
            } catch (\Exception $e) {
                continue;
            }
        }

        $attributes = $this->eavConfig->getEntityAttributes(Customer::ENTITY);
        $randomData = [];

        foreach ($attributes as $attribute) {
            $attributeCode = $attribute->getAttributeCode();
            $backendType = $attribute->getBackendType();

            if (in_array($attributeCode, ['id', 'created_at', 'updated_at', 'eighteen']) || $backendType === 'static') {
                continue;
            }

            $originalValue = $customer->getCustomAttribute($attributeCode)
            ? $customer->getCustomAttribute($attributeCode)->getValue()
            : null;

            $randomData[$attributeCode] = $this->generateEncryptedValue($backendType, $originalValue, $attribute);
        }

        foreach ($randomData as $attributeCode => $value) {
            if ($value !== null) {
                $customer->setCustomAttribute($attributeCode, $value);
            }
        }

        $this->customerRepository->save($customer);
    }

    private function encryptCustomerAddresses($customer)
    {
        $addresses = $customer->getAddresses();

        // 需要加密的基本欄位
        $fieldsToEncrypt = [
            'increment_id',
            'city',
            'company',
            'country_id',
            'fax',
            'firstname',
            'lastname',
            'middlename',
            'postcode',
            'prefix',
            'region',
            'street',
            'suffix',
            'telephone',
            'vat_id',
            'vat_request_date',
            'vat_request_id'
        ];

        foreach ($addresses as $address) {
            // 處理基本欄位
            foreach ($fieldsToEncrypt as $field) {
                $attribute = $address->getCustomAttribute($field);
                if (!$attribute) {
                    continue;
                }

                $originalValue = $attribute->getValue();
                if ($originalValue === null) {
                    continue;
                }

                $encryptedValue = $this->generateEncryptedValue(
                    'varchar',
                    $originalValue,
                    null
                );

                $address->setCustomAttribute($field, $encryptedValue);
            }

            // 處理EAV屬性
            $addressAttributes = $this->eavConfig->getEntityAttributes('customer_address');
            foreach ($addressAttributes as $attribute) {
                $attributeCode = $attribute->getAttributeCode();
                $backendType = $attribute->getBackendType();
                $frontendInput = $attribute->getFrontendInput();

                // 跳過不需處理的欄位
                if (in_array($attributeCode, ['id', 'created_at', 'updated_at', 'eighteen']) || $backendType === 'static') {
                    continue;
                }

                $customAttribute = $address->getCustomAttribute($attributeCode);
                if (!$customAttribute) {
                    continue;
                }

                $originalValue = $customAttribute->getValue();
                if ($originalValue === null) {
                    continue;
                }

                // 處理選擇類型的屬性
                if (in_array($frontendInput, ['select', 'multiselect']) && $originalValue) {
                    $options = $attribute->getSource()->getAllOptions();
                    $validOptionIds = array_column($options, 'value');

                    $validOptionIds = array_filter(
                        $validOptionIds, function ($value) {
                            return $value !== false && $value !== '';
                        }
                    );

                    if (!empty($validOptionIds)) {
                        $randomIndex = random_int(0, count($validOptionIds) - 1);
                        $encryptedValue = $validOptionIds[$randomIndex];
                    } else {
                        $encryptedValue = null;
                    }
                }
                else {
                    $encryptedValue = $this->generateEncryptedValue($backendType, $originalValue, $attribute);
                }

                if ($encryptedValue !== null) {
                    $address->setCustomAttribute($attributeCode, $encryptedValue);
                }
                else {
                    $encryptedValue = $this->generateEncryptedValue($backendType, $originalValue, $attribute);
                    $address->setCustomAttribute($attributeCode, $encryptedValue);
                }
            }

            $customer->setCustomAttribute('email', 'delete@mail.com');
            $this->addressRepository->save($address);
        }
    }

    private function encryptSalesOrderAddresses($customerId)
    {
        $connection = $this->resource->getConnection();
        $orderAddressTable = $connection->getTableName('sales_order_address');
        $orderTable = $connection->getTableName('sales_order');

        $tableDescription = $connection->describeTable($orderAddressTable);

        // 先查詢 sales_order 表獲取該客戶的所有訂單
        $orderSelect = $connection->select()
            ->from($orderTable, ['entity_id'])
            ->where('customer_id = ?', $customerId);
        $orderIds = $connection->fetchCol($orderSelect);

        if (empty($orderIds)) {
            return; // 如果沒有訂單，直接返回
        }

        // 根據訂單 ID 查詢對應的地址資料
        $select = $connection->select()
            ->from($orderAddressTable)
            ->where('parent_id IN (?)', $orderIds);
        $orderAddresses = $connection->fetchAll($select);

        $columnsToEncrypt = [
            'city' => 'varchar',
            'company' => 'varchar',
            'country_id' => 'varchar',
            'fax' => 'varchar',
            'firstname' => 'varchar',
            'lastname' => 'varchar',
            'middlename' => 'varchar',
            'postcode' => 'varchar',
            'prefix' => 'varchar',
            'region' => 'varchar',
            'street' => 'text',
            'suffix' => 'varchar',
            'telephone' => 'varchar',
            'email' => 'varchar',
        ];

        foreach ($orderAddresses as $orderAddress) {
            $updateData = [];
            foreach ($columnsToEncrypt as $columnName => $backendType) {
                if (!isset($tableDescription[$columnName])) {
                    continue;
                }

                if (isset($orderAddress[$columnName]) && $orderAddress[$columnName] !== null) {
                    $originalValue = $orderAddress[$columnName];

                    $encryptedValue = $this->generateEncryptedValue($backendType, $originalValue);

                    if ($encryptedValue !== null) {
                        $updateData[$columnName] = $encryptedValue;
                    }
                }
            }

            if (!empty($updateData)) {
                $connection->update(
                    $orderAddressTable,
                    $updateData,
                    ['entity_id = ?' => $orderAddress['entity_id']]
                );
            }
        }
    }

    private function validateAndGetCustomerId()
    {
        $defaultReturn = ['customerId' => 0, 'oneId' => 0];
    
        $postData = json_decode($this->request->getContent(), true);
        if (!isset($postData['OneID'])) {
            $this->logger->info("[customer_sync] oneId not found in request body");
            return $defaultReturn;
        }

        $oneId = $postData['OneID'];
        if (!preg_match('/^[a-z0-9]{8}-[a-z0-9]{4}-[a-z0-9]{4}-[a-z0-9]{4}-[a-z0-9]{12}$/', $oneId)) {
            $this->logger->info("[customer_sync] invalid oneId: " . $oneId);
            return $defaultReturn;
        }

        $connection = $this->resource->getConnection();
        $result = $connection->fetchRow(
            'SELECT `entity_id`, `is_deleted` FROM `' . $connection->getTableName('customer_entity') . '` WHERE member_seq = ?',
            [$oneId]
        );

        if (!$result || !isset($result['entity_id']) || ($result['is_deleted'] ?? 0) == 1) {
            $this->logger->info('[customer_sync] invalid or deleted oneId: ' . $oneId);
            return $defaultReturn;
        }

        return [
            'customerId' => (int)$result['entity_id'],
            'oneId' => $oneId
        ];
    }

    private function checkCustomerOrders($customerId, $oneId)
    {
        $searchCriteria = $this->searchCriteriaBuilder
            ->addFilter(OrderInterface::CUSTOMER_ID, $customerId)
            ->addFilter(OrderInterface::STATE, implode(',', array(Order::STATE_CANCELED, Order::STATE_CLOSED, Order::STATE_COMPLETE)), 'nin');
        $customerOrder = $this->orderRepository->getList($searchCriteria->create())->getItems();

        if (count($customerOrder) > 0) {
            $this->logger->info('[customer_sync] oneId has unfinished order: '.$oneId);
            return '刪除失敗，顧客於 HOTAI 購尚有未完成訂單';
        }

        $searchCriteria = $this->searchCriteriaBuilder
            ->addFilter(OrderInterface::CUSTOMER_ID, $customerId)
            ->addFilter(OrderInterface::CREATED_AT, date('Y-m-d', strtotime('-3 months')), 'gteq');
        $customerOrder = $this->orderRepository->getList($searchCriteria->create())->getItems();

        if (count($customerOrder) > 0) {
            $this->logger->info('[customer_sync] oneId has order in current 3 months: '.$oneId);
            return '刪除失敗，顧客於 HOTAI 購三個月內有訂單紀錄';
        }

        return null;
    }

    private function checkVirtualTickets($customerId, $oneId)
    {
        $connection = $this->resource->getConnection();
        $tableName = $connection->getTableName('customer_ticket');

        $sql = "SELECT COUNT(*) FROM " . $tableName . "
                WHERE use_end_time > NOW()
                AND (customer_id = :customer_id OR member_seq = :one_id)
                AND status NOT IN (" . TicketStatus::STATUS_USED . ", " . TicketStatus::STATUS_OVER_DUE . ")";

        $count = (int)$connection->fetchOne($sql, [
            'customer_id' => $customerId,
            'one_id' => $oneId
        ]);

        if ($count > 0) {
            $this->logger->info('[customer_sync] oneId has available virtual ticket: ' . $oneId);
            return '刪除失敗，顧客於 HOTAI 購仍有尚未使用的虛擬票券';
        }

        return null;
    }

    private function generateEncryptedValue($backendType, $originalValue, $attribute = null)
    {
        if ($originalValue === null) {
            return null;
        }

        $salt = bin2hex(random_bytes(16));

        if ($attribute !== null
            && in_array($attribute->getFrontendInput(), ['select', 'multiselect'])
        ) {

            $options = $attribute->getSource()->getAllOptions();
            $validOptionIds = array_column($options, 'value');

            $validOptionIds = array_filter(
                $validOptionIds, function ($value) {
                    return $value !== false && $value !== '';
                }
            );

            if (!empty($validOptionIds)) {
                return $validOptionIds[random_int(0, count($validOptionIds) - 1)];
            }

            return null;
        }

        $baseEncrypt = function ($value) use ($salt) {
            return hash('sha256', $salt . $value);
        };

        $preserveType = function ($value, $type) use ($baseEncrypt) {
            $hash = $baseEncrypt($value);
            switch ($type) {
            case 'int':
                return (int)(hexdec(substr($hash, 0, 8)) % 1000000);

            case 'decimal':
                return (float)(hexdec(substr($hash, 0, 8)) / 1000000);

            case 'datetime':
                $timestamp = strtotime("-100 years");
                $days = hexdec(substr($hash, 0, 8)) % 36525;
                return date('Y-m-d H:i:s', $timestamp + ($days * 86400));

            case 'varchar':
                return substr($hash, 0, min(32, 255));

            case 'text':
                return substr($hash, 0, 32);

            case 'boolean':
                return (bool)(hexdec(substr($hash, 0, 2)) % 2);

            default:
                return substr($hash, 0, 32);
            }
        };

        switch ($backendType) {
        case 'int':
            return $preserveType($originalValue, 'int');
        case 'decimal':
            return $preserveType($originalValue, 'decimal');
        case 'datetime':
            return $preserveType($originalValue, 'datetime');
        case 'varchar':
            return $preserveType($originalValue, 'varchar');
        case 'text':
            return $preserveType($originalValue, 'text');
        case 'boolean':
            return $preserveType($originalValue, 'boolean');
        default:
            return $preserveType($originalValue, 'varchar');
        }
    }

    private function serverResponseSuccess($code, $success, $msg)
    {
        $response = [
            'rtnCode' => $code,
            'success' => $success,
            'msg' => $msg,
        ];

        return $this->response
            ->setHeader('Content-Type', 'application/json', true)
            ->setStatusCode(200)
            ->setBody(json_encode($response))
            ->sendResponse();
    }

    private function serverResponseError($title, $detail)
    {
        $response = [
            'title' => $title,
            'status' => 500,
            'detail' => $detail,
        ];

        return $this->response
            ->setHeader('Content-Type', 'application/json', true)
            ->setStatusCode(500)
            ->setBody(json_encode($response))
            ->sendResponse();
    }
}
