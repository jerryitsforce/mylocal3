<?php
namespace Branch8\AppNotification\Model;

use Branch8\AppNotification\Api\CustomerFcmTokenManagementInterface;
use Branch8\AppNotification\Api\Data\CustomerFcmTokenInterface;
use Branch8\AppNotification\Model\ResourceModel\CustomerFcmToken\CollectionFactory;
use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Customer\Model\Session;
use Magento\Framework\Reflection\DataObjectProcessor;
use Branch8\AppNotification\Model\CustomerFcmTokenFactory;
use Branch8\AppNotification\Model\ResourceModel\CustomerFcmToken as CustomerFcmTokenResource;
use Magento\Store\Model\StoreManagerInterface;

class CustomerFcmTokenManagement implements CustomerFcmTokenManagementInterface
{
    protected $customerRepository;
    protected $customerSession;
    private CollectionFactory $collectionFactory;
    private DataObjectProcessor $dataObjectProcessor;
    private \Branch8\AppNotification\Model\CustomerFcmTokenFactory $tokenFactory;
    private CustomerFcmTokenResource $tokenResource;
    private StoreManagerInterface $storeManager;

    public function __construct(
        CustomerRepositoryInterface $customerRepository,
        Session $customerSession,
        CollectionFactory $collectionFactory,
        DataObjectProcessor $dataObjectProcessor,
        CustomerFcmTokenFactory $tokenFactory,
        CustomerFcmTokenResource $tokenResource,
        StoreManagerInterface $storeManager
    ) {
        $this->customerRepository = $customerRepository;
        $this->customerSession = $customerSession;
        $this->collectionFactory = $collectionFactory;
        $this->dataObjectProcessor = $dataObjectProcessor;
        $this->tokenFactory = $tokenFactory;
        $this->tokenResource = $tokenResource;
        $this->storeManager = $storeManager;
    }


    /**
     * @param int $customerId
     * @param string $fcmToken
     * @return bool
     * @throws \Magento\Framework\Exception\CouldNotSaveException
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function saveToken($customerId, $fcmToken): bool
    {
        $storeId = (int) $this->storeManager->getStore()->getId();

        $data = [
            'customer_id'   => $customerId,
            'store_id'      => $storeId,
            'token'         => $fcmToken,
            'is_subscribed' => 1,
        ];

        try {
            $this->tokenResource->getConnection()->insertOnDuplicate(
                $this->tokenResource->getMainTable(),
                $data,
                ['token', 'is_subscribed']
            );
        } catch (\Exception $e) {
            throw new \Magento\Framework\Exception\CouldNotSaveException(
                __('Could not save token: %1', $e->getMessage())
            );
        }

        return true;
    }


    /**
     * @param string $fcmToken
     * @return bool
     * @throws \Magento\Framework\Exception\CouldNotDeleteException
     */
    public function deleteToken($customerId, $fcmToken): bool
    {
        $collection = $this->collectionFactory->create()
            ->addFieldToFilter('token', $fcmToken)
            ->addFieldToFilter('customer_id', $customerId)
            ->setPageSize(1);

        $existing = $collection->getFirstItem();

        if ($existing->getId()) {
            try {
                $this->tokenResource->delete($existing);
            } catch (\Exception $e) {
                throw new \Magento\Framework\Exception\CouldNotDeleteException(__('Unable to delete token.'));
            }
        }

        return true;
    }


    public function getCustomerTokens(): array
    {
        if (!$this->customerSession->isLoggedIn()) {
            throw new \Magento\Framework\Exception\LocalizedException(__('Customer not logged in'));
        }

        $customerId = (int) $this->customerSession->getCustomerId();

        $collection = $this->collectionFactory->create()
            ->addFieldToFilter('customer_id', $customerId)
            ->addFieldToFilter('is_subscribed', 1);

        $result = [];
        foreach ($collection as $tokenModel) {
            /** @var CustomerFcmTokenInterface $tokenModel */
            $result[] = $this->dataObjectProcessor->buildOutputDataArray(
                $tokenModel,
                CustomerFcmTokenInterface::class
            );
        }

        return $result;
    }
}
