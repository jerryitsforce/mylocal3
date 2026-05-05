<?php

declare(strict_types=1);

namespace Branch8\Report\Observer;

use Branch8\Report\Api\Data\ProductChangeLogInterfaceFactory;
use Branch8\Report\Api\ProductChangeLogRepositoryInterface;
use Branch8\Report\Helper\Data as DataHelper;
use Branch8\Report\Model\ProductLogContext;
use Branch8\Report\Model\Source\UserType;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\User\Model\User;
use Magento\User\Model\UserFactory;
use Psr\Log\LoggerInterface;

class ProductAttributeUpdateBeforeObserver implements ObserverInterface
{
    /**
     * @var string
     */
    private const LOG_PREFIX = 'Branch8_Report::ProductAttributeUpdateBeforeObserver';

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
     * @var ProductLogContext
     */
    private ProductLogContext $productLogContext;

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
     * @param ProductLogContext $productLogContext
     * @param ProductRepositoryInterface $productRepository
     * @param ProductChangeLogInterfaceFactory $productChangeLogFactory
     * @param ProductChangeLogRepositoryInterface $productChangeLogRepository
     */
    public function __construct(
        LoggerInterface                     $logger,
        DataHelper                          $dataHelper,
        UserFactory                         $userFactory,
        ProductLogContext                   $productLogContext,
        ProductRepositoryInterface          $productRepository,
        ProductChangeLogInterfaceFactory    $productChangeLogFactory,
        ProductChangeLogRepositoryInterface $productChangeLogRepository
    ) {
        $this->logger = $logger;
        $this->dataHelper = $dataHelper;
        $this->userFactory = $userFactory;
        $this->productLogContext = $productLogContext;
        $this->productRepository = $productRepository;
        $this->productChangeLogFactory = $productChangeLogFactory;
        $this->productChangeLogRepository = $productChangeLogRepository;
    }

    /**
     * @inheritDoc
     */
    public function execute(Observer $observer): void
    {
        $event = $observer->getEvent();
        $attrData = $event->getAttributesData();
        $productIds = $event->getProductIds();

        if (empty($productIds) || empty($attrData)) {
            return;
        }

        foreach ($productIds as $productId) {
            $productId = (int)$productId;
            $user = $this->dataHelper->getUpdatedByUser();
            $userType = (int)key($user);
            $userId = (int)reset($user);
            $serializer = $this->dataHelper->getSerializer();

            if (is_array($attrData) && !empty($attrData['admin_user_updated'])) {
                if (count($attrData) === 1) {
                    continue;
                }
                $admin = $this->getAdminUser($attrData['admin_user_updated']);
                if ($admin->getId()) {
                    $userType = UserType::TYPE_ADMIN;
                    $userId = (int)$admin->getId();
                }
            }

            $postData = $serializer->serialize($attrData);
            try {
                $product = $this->productRepository->getById($productId);
            } catch (\Exception $e) {
                if(\Branch8\HotaiCore\Helper\DebugLog::isEnable('Branch8_Report', 'branch8reportlog')) {
                    $this->logger->error(self::LOG_PREFIX, ['exception' => $e->getMessage()]);
                }
                continue;
            }

            $links = $product->getProductLinks();
            if (empty($links)) {
                $productLinks = ['related_skus' => [], 'upsell_skus' => [], 'crosssell_skus' => []];
            } else {
                $productLinks = $this->dataHelper->prepareProductLinks($product, $links);
            }
            $productBeforeData = $product->getData();
            $productBeforeData += $productLinks;
            $beforeValues = $this->dataHelper->adjustProductData($productBeforeData);

            $productChangeLog = $this->productChangeLogFactory->create();
            $productChangeLog->setProductId((int)$productId)
                ->setAction('massUpdateAttributes')
                ->setPostData($postData)
                ->setBeforeValues($beforeValues)
                ->setUserType($userType)
                ->setUserId($userId);

            try {
                $log = $this->productChangeLogRepository->save($productChangeLog);
                $this->productLogContext->add($productId, $log);
            } catch (\Exception $e) {
                if(\Branch8\HotaiCore\Helper\DebugLog::isEnable('Branch8_Report', 'exceptionlog')) {
                    $this->logger->error(self::LOG_PREFIX, [
                        'product_id' => $productId,
                        'exception' => $e->getMessage()
                    ]);
                }
            }
        }
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
