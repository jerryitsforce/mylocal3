<?php

declare(strict_types=1);

namespace Branch8\Report\Plugin;

use Branch8\Report\Api\Data\ProductChangeLogInterfaceFactory;
use Branch8\Report\Api\ProductChangeLogRepositoryInterface;
use Branch8\Report\Helper\Data as DataHelper;
use Branch8\Report\Model\Source\UserType;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\InventoryCatalog\Model\UpdateInventory;
use Magento\InventoryCatalog\Model\UpdateInventory\InventoryData;
use Magento\User\Model\User;
use Magento\User\Model\UserFactory;
use Psr\Log\LoggerInterface;

class LogStockStatusOnMassUpdate
{
    /**
     * @var string
     */
    private const LOG_PREFIX = 'Branch8_Report::LogStockStatusOnMassUpdate';

    /**
     * @var LoggerInterface
     */
    private LoggerInterface $logger;

    /**
     * @var DataHelper
     */
    private DataHelper $dataHelper;

    /**
     * @var UserFactory
     */
    private UserFactory $userFactory;

    /**
     * @var ProductRepositoryInterface
     */
    private ProductRepositoryInterface $productRepository;

    /**
     * @var ProductChangeLogInterfaceFactory
     */
    private ProductChangeLogInterfaceFactory $productChangeLogFactory;

    /**
     * @var ProductChangeLogRepositoryInterface
     */
    private ProductChangeLogRepositoryInterface $productChangeLogRepository;

    /**
     * Constructor.
     *
     * @param LoggerInterface $logger
     * @param DataHelper $dataHelper
     * @param UserFactory $userFactory
     * @param ProductRepositoryInterface $productRepository
     * @param ProductChangeLogInterfaceFactory $productChangeLogFactory
     * @param ProductChangeLogRepositoryInterface $productChangeLogRepository
     */
    public function __construct(
        LoggerInterface                     $logger,
        DataHelper                          $dataHelper,
        UserFactory                         $userFactory,
        ProductRepositoryInterface          $productRepository,
        ProductChangeLogInterfaceFactory    $productChangeLogFactory,
        ProductChangeLogRepositoryInterface $productChangeLogRepository
    ) {
        $this->logger = $logger;
        $this->dataHelper = $dataHelper;
        $this->userFactory = $userFactory;
        $this->productRepository = $productRepository;
        $this->productChangeLogFactory = $productChangeLogFactory;
        $this->productChangeLogRepository = $productChangeLogRepository;
    }

    /**
     * Log stock status for product.
     *
     * @param UpdateInventory $subject
     * @param InventoryData $data
     *
     * @return array
     */
    public function beforeExecute(UpdateInventory $subject, InventoryData $data): array
    {
        $skus = $data->getSkus();
        $inventoryData = $this->dataHelper->getSerializer()->unserialize($data->getData());
        $keysToKeep = ['qty', 'is_in_stock'];
        $stockStatusData = array_intersect_key($inventoryData, array_flip($keysToKeep));
        if (empty($stockStatusData)) {
            return [$data];
        }

        $user = $this->dataHelper->getUpdatedByUser();
        $userType = (int)key($user);
        $userId = (int)reset($user);
        if (is_array($inventoryData) && !empty($inventoryData['admin_user_updated'])) {
            if (count($inventoryData) === 1) {
                return [$data];
            }
            $admin = $this->getAdminUser($inventoryData['admin_user_updated']);
            if ($admin->getId()) {
                $userType = UserType::TYPE_ADMIN;
                $userId = (int)$admin->getId();
            }
        }

        foreach ($skus as $sku) {
            try {
                $product = $this->productRepository->get($sku);
                $oldStatus = $product->getQuantityAndStockStatus()['is_in_stock'] ?? null;
            } catch (\Exception $e) {
                $product = $oldStatus = null;
                if(\Branch8\HotaiCore\Helper\DebugLog::isEnable('Branch8_Report', 'exceptionlog')) {
                    $this->logger->error(self::LOG_PREFIX, ['exception' => $e->getMessage()]);
                }
            }
            $newStatus = $stockStatusData['is_in_stock'];
            if ($product && $oldStatus != $newStatus) {
                $productData = $afterProductData = $product->getData();
                $afterProductData['quantity_and_stock_status']['is_in_stock'] = $newStatus;
                if (isset($stockStatusData['qty'])) {
                    $afterProductData['quantity_and_stock_status']['qty'] = (int)$stockStatusData['qty'];
                }
                list($beforeData, $afterData) = $this->dataHelper->getCommonOrderedSubset($productData, $afterProductData);
                $beforeValues = $this->dataHelper->adjustProductData($beforeData);
                $afterValues = $this->dataHelper->adjustProductData($afterData);

                $productChangeLog = $this->productChangeLogFactory->create();
                $productChangeLog->setProductId((int)$product->getId())
                    ->setAction('massStockStatusUpdate')
                    ->setBeforeValues($beforeValues)
                    ->setAfterValues($afterValues)
                    ->setUserType($userType)
                    ->setUserId($userId);
                try {
                    $this->productChangeLogRepository->save($productChangeLog);
                } catch (\Exception $e) {
                    if(\Branch8\HotaiCore\Helper\DebugLog::isEnable('Branch8_Report', 'exceptionlog')) {
                        $this->logger->error(self::LOG_PREFIX, ['exception' => $e->getMessage()]);
                    }
                }
            }
        }

        return [$data];
    }

    /**
     * Returns admin user.
     *
     * @param string $username
     *
     * @return User
     */
    private function getAdminUser(string $username): User
    {
        return $this->userFactory->create()->loadByUsername($username);
    }
}
