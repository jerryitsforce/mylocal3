<?php

declare(strict_types=1);

namespace Branch8\Catalog\Observer;

use Branch8\Catalog\Model\ResourceModel\ProductChangeHistory as HistoryResource;
use Magento\Authorization\Model\UserContextInterface;
use Magento\Catalog\Api\Data\ProductAttributeInterface;
use Magento\Backend\Model\Auth\Session as AuthSession;
use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Catalog\Model\Product;
use Magento\Customer\Model\CustomerFactory;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Psr\Log\LoggerInterface;
use Webkul\Marketplace\Helper\Data as MarketplaceHelper;
use Webkul\SellerSubAccount\Helper\Data as SellerSubAccountHelper;

class TrackingProductStatusChange implements ObserverInterface
{
    /**
     * @var string
     */
    private const LOG_PREFIX = 'Branch8_Catalog::TrackingProductStatusChange';

    /**
     * @var LoggerInterface
     */
    private LoggerInterface $logger;

    /**
     * @var UserContextInterface
     */
    private $userContext;

    /**
     * @var AuthSession
     */
    private AuthSession $authSession;

    /**
     * @var MarketplaceHelper
     */
    private MarketplaceHelper $marketplaceHelper;

    /**
     * @var SellerSubAccountHelper
     */
    private SellerSubAccountHelper $sellerSubAccountHelper;

    /**
     * @var HistoryResource
     */
    private HistoryResource $historyResource;

    /**
     * Constructor.
     *
     * @param LoggerInterface $logger
     * @param UserContextInterface $userContext
     * @param AuthSession $authSession
     * @param MarketplaceHelper $marketplaceHelper
     * @param SellerSubAccountHelper $sellerSubAccountHelper
     * @param HistoryResource $historyResource
     */
    public function __construct(
        LoggerInterface   $logger,
        UserContextInterface $userContext,
        AuthSession       $authSession,
        MarketplaceHelper $marketplaceHelper,
        SellerSubAccountHelper $sellerSubAccountHelper,
        HistoryResource   $historyResource
    ) {
        $this->logger = $logger;
        $this->userContext = $userContext;
        $this->authSession = $authSession;
        $this->historyResource = $historyResource;
        $this->marketplaceHelper = $marketplaceHelper;
        $this->sellerSubAccountHelper = $sellerSubAccountHelper;
    }

    /**
     * @inheritDoc
     */
    public function execute(Observer $observer): void
    {
        /** @var Product $product */
        $product = $observer->getEvent()->getData('product');
        if ($product->getOrigData('entity_id')) {
            $user = $this->getUpdatedByUser();
            $beforeStatus = (int)$product->getOrigData(ProductInterface::STATUS);
            $afterStatus = (int)$product->getStatus();
            if ($beforeStatus !== $afterStatus) {
                $data = [
                    'product_id' => $product->getId(),
                    'changed_field' => ProductAttributeInterface::CODE_STATUS,
                    'before_value' => $beforeStatus,
                    'after_value' => $afterStatus,
                    'changed_by' => key($user),
                    'changed_by_id' => reset($user),
                    'trace_log' => json_encode(debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS))
                ];

                try {
                    $this->historyResource->insert($data);
                } catch (\Exception $e) {
                    $this->logger->error(self::LOG_PREFIX, ['exception' => $e->getMessage()]);
                }
            }
        }
    }

    /**
     * Get the ID of the user who updated the data.
     *
     * @return array
     */
    private function getUpdatedByUser(): array
    {
        if ($this->userContext->getUserType() === UserContextInterface::USER_TYPE_ADMIN) {
            $userId = $this->userContext->getUserId();
            if ($userId) {
                return ['admin' => (int)$userId];
            }
        }
        if ($this->userContext->getUserType() === UserContextInterface::USER_TYPE_CUSTOMER) {
            $userId = $this->userContext->getUserId();
            if ($userId) {
                return ['seller' => (int)$userId];
            }
        }

        $user = $this->authSession->getUser();
        if (!empty($user)) {
            return ['admin' => (int)$user->getId()];
        }

        $isPartner = $this->marketplaceHelper->isSeller();
        if ($isPartner == 1) {
            $sellerId = $this->sellerSubAccountHelper->getCustomerId();
            if (!$sellerId) {
                $sellerId = $this->marketplaceHelper->getCustomerId();
            }
            return ['seller' => (int)$sellerId];
        }

        return ['system' => null];
    }
}
