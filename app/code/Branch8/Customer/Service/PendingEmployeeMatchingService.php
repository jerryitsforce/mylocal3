<?php

namespace Branch8\Customer\Service;

use Branch8\Customer\Model\ResourceModel\PendingEmployee\CollectionFactory as PendingEmployeeCollectionFactory;
use Branch8\Customer\Helper\OrganizationAPI;
use Branch8\Customer\Helper\Group as GroupHelper;
use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Customer\Model\CustomerRegistry;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\App\CacheInterface;
use Magento\Framework\App\PageCache\Version;
use Magento\Framework\Stdlib\CookieManagerInterface;
use Magento\Framework\Stdlib\Cookie\CookieMetadataFactory;
use Magento\Framework\App\Request\Http;
use Magento\Customer\Model\Session as CustomerSession;
use Psr\Log\LoggerInterface;
use Amasty\Groupcat\Model\ActiveRuleResolver;

class PendingEmployeeMatchingService
{
    /**
     * @var PendingEmployeeCollectionFactory
     */
    private $pendingEmployeeCollectionFactory;

    /**
     * @var CustomerRepositoryInterface
     */
    protected CustomerRepositoryInterface $_customerRepository;

    /**
     * @var OrganizationAPI
     */
    private $organizationAPIHelper;

    /**
     * @var GroupHelper
     */
    private $groupHelper;

    /**
     * @var ResourceConnection
     */
    private $resourceConnection;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var CustomerRegistry
     */
    protected $customerRegistry;

    /**
     * @var CacheInterface
     */
    private $cache;

    /**
     * @var Version
     */
    private $version;

    /**
     * @var CookieManagerInterface
     */
    private $cookieManager;

    /**
     * @var CookieMetadataFactory
     */
    private $cookieMetadataFactory;

    /**
     * @var Http
     */
    private $request;

    /**
     * @var CustomerSession
     */
    private $customerSession;

    /**
     * @var ActiveRuleResolver
     */
    private $activeRuleResolver;

    /**
     * @param PendingEmployeeCollectionFactory $pendingEmployeeCollectionFactory
     * @param OrganizationAPI $organizationAPIHelper
     * @param GroupHelper $groupHelper
     * @param ResourceConnection $resourceConnection
     * @param LoggerInterface $logger
     * @param CacheInterface $cache
     * @param Version $version
     * @param CookieManagerInterface $cookieManager
     * @param CookieMetadataFactory $cookieMetadataFactory
     * @param Http $request
     * @param CustomerRegistry $customerRegistry
     * @param CustomerRepositoryInterface $customerRepository
     * @param CustomerSession $customerSession
     * @param ActiveRuleResolver $activeRuleResolver
     */
    public function __construct(
        PendingEmployeeCollectionFactory $pendingEmployeeCollectionFactory,
        OrganizationAPI $organizationAPIHelper,
        GroupHelper $groupHelper,
        ResourceConnection $resourceConnection,
        LoggerInterface $logger,
        CacheInterface $cache,
        Version $version,
        CookieManagerInterface $cookieManager,
        CookieMetadataFactory $cookieMetadataFactory,
        Http $request,
        CustomerRegistry $customerRegistry,
        CustomerRepositoryInterface $customerRepository,
        CustomerSession $customerSession,
        ActiveRuleResolver $activeRuleResolver
    ) {
        $this->pendingEmployeeCollectionFactory = $pendingEmployeeCollectionFactory;
        $this->organizationAPIHelper = $organizationAPIHelper;
        $this->groupHelper = $groupHelper;
        $this->resourceConnection = $resourceConnection;
        $this->logger = $logger;
        $this->cache = $cache;
        $this->version = $version;
        $this->cookieManager = $cookieManager;
        $this->cookieMetadataFactory = $cookieMetadataFactory;
        $this->request = $request;
        $this->customerSession = $customerSession;
        $this->customerRegistry = $customerRegistry;
        $this->_customerRepository = $customerRepository;
        $this->activeRuleResolver = $activeRuleResolver;
    }

    /**
     * Process customer registration and match with pending employee records
     *
     * @param \Magento\Customer\Api\Data\CustomerInterface $customer
     * @return void
     */
    public function processCustomerRegistration($customer)
    {
        try {
            // 取得客戶電話號碼
            $phoneAttr = $customer->getCustomAttribute('phone_number');
            $phoneNumber = $phoneAttr ? $phoneAttr->getValue() : null;

            if (!$phoneNumber) {
                if(\Branch8\HotaiCore\Helper\DebugLog::isEnable('Branch8_Customer', 'systemlog')){
                    $this->logger->info('Customer phone number not found, skipping pending employee matching', [
                        'customer_id' => $customer->getId()
                    ]);
                }
                return;
            }

            // 查詢對應的 pending employee 記錄
            $pendingRecord = $this->findPendingEmployee($phoneNumber);

            if (!$pendingRecord) {
                return;
            }

            // 處理有 pending record 的情況
            if ($this->shouldProcessRecord($pendingRecord)) {
                // 符合 HotaiEMP 條件，更新客戶組織為 HotaiEMP
                $this->updateCustomerToHotaiEMP($customer->getId());

                // 同步更新 customer 物件的 group_id，確保自動登入時使用正確的值
                $customer->setGroupId($this->getUpdatedGroupId($customer->getId()));

                // 清空客戶相關快取
                $this->clearCustomerCache($customer->getId());

                if(\Branch8\HotaiCore\Helper\DebugLog::isEnable('Branch8_Customer', 'systemlog')){
                    $this->logger->info('Successfully processed pending employee matching - updated to HotaiEMP', [
                        'customer_id' => $customer->getId(),
                        'phone_number' => $phoneNumber,
                        'category_identity' => $pendingRecord->getCategoryIdentity()
                    ]);
                }
            } else {
                // 不符合 HotaiEMP 條件，如果目前是 HotaiEMP，則還原為 EC
                if ($this->isCustomerInHotaiEMP($customer->getId())) {
                    $this->updateCustomerToEC($customer->getId());

                    // 同步更新 customer 物件的 group_id
                    $customer->setGroupId($this->getUpdatedGroupId($customer->getId()));

                    // 清空客戶相關快取
                    $this->clearCustomerCache($customer->getId());

                    if(\Branch8\HotaiCore\Helper\DebugLog::isEnable('Branch8_Customer', 'systemlog')){
                        $this->logger->info('Successfully processed pending employee matching - reverted to EC', [
                            'customer_id' => $customer->getId(),
                            'phone_number' => $phoneNumber,
                            'category_identity' => $pendingRecord->getCategoryIdentity(),
                            'is_enabled' => $pendingRecord->getIsEnabled()
                        ]);
                    }
                }
            }

        } catch (\Exception $e) {
            if(\Branch8\HotaiCore\Helper\DebugLog::isEnable('Branch8_Customer', 'exceptionlog')){
                $this->logger->error('Error processing customer registration for pending employee', [
                    'customer_id' => $customer->getId(),
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString()
                ]);
            }
            throw $e;
        }
    }

    /**
     * Find pending employee by phone number
     *
     * @param string $phoneNumber
     * @return \Branch8\Customer\Model\PendingEmployee|null
     */
    private function findPendingEmployee($phoneNumber)
    {
        $collection = $this->pendingEmployeeCollectionFactory->create()
            ->addFieldToFilter('cellphone', $phoneNumber)
            ->setPageSize(1);

        return $collection->getFirstItem()->getId() ? $collection->getFirstItem() : null;
    }

    /**
     * Check if the pending record should be processed
     * 只處理 isEnabled=true 且 categoryIdentity=HotaiEMP 的記錄
     *
     * @param \Branch8\Customer\Model\PendingEmployee $pendingRecord
     * @return bool
     */
    private function shouldProcessRecord($pendingRecord)
    {
        return $pendingRecord->getIsEnabled()
            && $pendingRecord->getCategoryIdentity() === 'HotaiEMP';
    }

    /**
     * Update customer to HotaiEMP organization
     *
     * @param int $customerId
     * @return void
     * @throws \Exception
     */
    private function updateCustomerToHotaiEMP($customerId)
    {
        $empOrg = $this->organizationAPIHelper->getHotaiEMP();
        $action = 'Customer registration - matched with pending HotaiEMP employee';

        $result = $this->updateToOrg($customerId, $empOrg, $action);

        if (!$result['success']) {
            throw new \Exception('Failed to update customer to HotaiEMP organization: ' . $result['msg']);
        }
    }

    /**
     * Update customer organization (similar to CustomerRepository::updateToOrg)
     *
     * @param int $customerId
     * @param int $organizationId
     * @param string $action
     * @return array
     */
    private function updateToOrg($customerId, $organizationId, $action)
    {
        try {
            // 取得客戶目前的組織資訊
            $currentOrg = $this->getCurrentCustomerOrganization($customerId);

            // 如果新的組織與目前組織不同，則儲存原組織到 prev_org
            if ($currentOrg != $organizationId) {
                $this->savePreviousOrganization($customerId, $currentOrg);
            }

            $this->groupHelper->setLevel($customerId, $organizationId, $action);

            return [
                'success' => true,
                'msg' => 'Organization updated successfully.'
            ];

        } catch (\Exception $e) {
            if(\Branch8\HotaiCore\Helper\DebugLog::isEnable('Branch8_Customer', 'exceptionlog')){
                $this->logger->error('Error updating customer organization', [
                    'customer_id' => $customerId,
                    'organization_id' => $organizationId,
                    'error' => $e->getMessage()
                ]);
            }

            return [
                'success' => false,
                'msg' => 'Failed to update organization: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Check if customer is currently in HotaiEMP organization
     *
     * @param int $customerId
     * @return bool
     */
    private function isCustomerInHotaiEMP($customerId)
    {
        try {
            $connection = $this->resourceConnection->getConnection();
            $sql = 'SELECT 1 FROM customer_entity as ce
                    LEFT JOIN customer_group as cg ON ce.group_id = cg.customer_group_id
                    LEFT JOIN branch8_customer_organization bco ON cg.organization = bco.entity_id
                    WHERE ce.entity_id = ? AND bco.hotai1_name = "HotaiEMP" AND ce.platform <> "seller"';

            $result = $connection->fetchOne($sql, [$customerId]);
            return !empty($result);
        } catch (\Exception $e) {
            if(\Branch8\HotaiCore\Helper\DebugLog::isEnable('Branch8_Customer', 'exceptionlog')){
                $this->logger->error('Error checking if customer is in HotaiEMP', [
                    'customer_id' => $customerId,
                    'error' => $e->getMessage()
                ]);
            }
            return false;
        }
    }

    /**
     * Update customer to EC organization
     *
     * @param int $customerId
     * @return void
     * @throws \Exception
     */
    private function updateCustomerToEC($customerId)
    {
        $ecOrg = $this->organizationAPIHelper->getEcOrg();
        $action = 'Customer registration - reverted from HotaiEMP to EC';

        $result = $this->updateToOrg($customerId, $ecOrg, $action);

        if (!$result['success']) {
            throw new \Exception('Failed to update customer to EC organization: ' . $result['msg']);
        }
    }

    /**
     * Get current customer organization
     *
     * @param int $customerId
     * @return int|null
     */
    private function getCurrentCustomerOrganization($customerId)
    {
        try {
            $connection = $this->resourceConnection->getConnection();
            $sql = 'SELECT cg.organization FROM customer_entity as ce
                    LEFT JOIN customer_group as cg ON ce.group_id = cg.customer_group_id
                    WHERE ce.entity_id = ?';

            $result = $connection->fetchOne($sql, [$customerId]);
            return $result ? (int)$result : null;
        } catch (\Exception $e) {
            if(\Branch8\HotaiCore\Helper\DebugLog::isEnable('Branch8_Customer', 'exceptionlog')){
                $this->logger->error('Error getting current customer organization', [
                    'customer_id' => $customerId,
                    'error' => $e->getMessage()
                ]);
            }
            return null;
        }
    }

    /**
     * Save previous organization to customer_entity.prev_org
     *
     * @param int $customerId
     * @param int $previousOrgId
     * @return void
     */
    private function savePreviousOrganization($customerId, $previousOrgId)
    {
        try {
            $connection = $this->resourceConnection->getConnection();
            $connection->update(
                'customer_entity',
                ['prev_org' => $previousOrgId],
                'entity_id = ' . (int)$customerId
            );

        } catch (\Exception $e) {
            if(\Branch8\HotaiCore\Helper\DebugLog::isEnable('Branch8_Customer', 'exceptionlog')){
                $this->logger->error('Error saving previous organization', [
                    'customer_id' => $customerId,
                    'previous_org_id' => $previousOrgId,
                    'error' => $e->getMessage()
                ]);
            }
            throw $e;
        }
    }

    /**
     * Get updated group_id from database after organization update
     *
     * @param int $customerId
     * @return int
     */
    private function getUpdatedGroupId($customerId)
    {
        try {
            $connection = $this->resourceConnection->getConnection();
            $groupId = $connection->fetchOne(
                'SELECT group_id FROM customer_entity WHERE entity_id = ?',
                [$customerId]
            );
            return (int)$groupId;
        } catch (\Exception $e) {
            if(\Branch8\HotaiCore\Helper\DebugLog::isEnable('Branch8_Customer', 'exceptionlog')){
                $this->logger->error('Error getting updated group_id', [
                    'customer_id' => $customerId,
                    'error' => $e->getMessage()
                ]);
            }
            return 0;
        }
    }

    /**
     * Clear customer-related cache after organization update
     * This will force the browser to reload customer data from localStorage
     *
     * @param int $customerId
     * @return void
     */
    private function clearCustomerCache($customerId)
    {
        try {

            $this->customerRegistry->remove($customerId);
            $customerData = $this->_customerRepository->getById($customerId);

            $this->customerSession->unsCustomer();
            $this->customerSession->unsCustomerGroupId();

            $this->customerSession->setCustomerData($customerData);
            $this->customerSession->setCustomerGroupId($customerData->getGroupId());

            // 同步 session 的 Customer Model 的 group_id
            // 避免 CarOwner Plugin 透過 getCustomer()->getDataModel() 拿到舊的 group_id
            $customerModel = $this->customerSession->getCustomer();
            $customerModel->setGroupId($customerData->getGroupId());

            $this->updatePrivateContentVersion();

            // Remove validation rules cache using Amasty's cache key generator
            $cacheKey = 'validrules|' . $this->activeRuleResolver->getCacheKeyForActiveRules();
            $cacheRemoved = false;

            // Get cache data before removal using Magento's public API
            $beforeData = $this->cache->load($cacheKey);
            $beforeExists = ($beforeData !== false);

            // Only attempt to remove if cache exists
            if ($beforeExists) {
                try {
                    $cacheRemoved = $this->cache->remove($cacheKey);
                } catch (\Exception $e) {
                    // Log but don't fail if cache removal fails
                }
            }

            // Get cache data after removal using Magento's public API
            $afterData = $this->cache->load($cacheKey);
            $afterExists = ($afterData !== false);

            if(\Branch8\HotaiCore\Helper\DebugLog::isEnable('Branch8_Customer', 'systemlog')){
                $this->logger->info('Customer cache cleared and session updated after organization update', [
                    'customer_id' => $customerId,
                    'new_group_id' => $customerData->getGroupId(),
                    'validation_rules_cache_key' => $cacheKey,
                    'validation_rules_removed' => $cacheRemoved,
                    'validation_rules_before_exists' => $beforeExists,
                    'validation_rules_before_data' => $beforeExists ? $beforeData : null,
                    'validation_rules_after_exists' => $afterExists,
                    'validation_rules_after_data' => $afterExists ? $afterData : null
                ]);
            }

        } catch (\Exception $e) {
            if(\Branch8\HotaiCore\Helper\DebugLog::isEnable('Branch8_Customer', 'exceptionlog')){
                $this->logger->error('Error clearing customer cache', [
                    'customer_id' => $customerId,
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString()
                ]);
            }
        }
    }

    /**
     * Update private_content_version cookie to trigger frontend localStorage reload
     *
     * @return void
     */
    private function updatePrivateContentVersion()
    {
        try {
            $publicCookieMetadata = $this->cookieMetadataFactory->createPublicCookieMetadata()
                ->setDuration(86400)
                ->setPath('/')
                ->setSecure($this->request->isSecure())
                ->setHttpOnly(false);

            $newVersion = bin2hex(random_bytes(16));
            $this->cookieManager->setPublicCookie(
                Version::COOKIE_NAME,
                $newVersion,
                $publicCookieMetadata
            );
        } catch (\Exception $e) {
            if(\Branch8\HotaiCore\Helper\DebugLog::isEnable('Branch8_Customer', 'exceptionlog')){
                $this->logger->error('Error updating private_content_version cookie', [
                    'error' => $e->getMessage()
                ]);
            }
        }
    }
}
