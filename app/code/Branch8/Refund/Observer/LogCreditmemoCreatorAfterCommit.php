<?php

declare(strict_types=1);

namespace Branch8\Refund\Observer;

use Branch8\Refund\Helper\ConfigurableRefundLogger;
use Magento\Authorization\Model\UserContextInterface;
use Magento\Backend\Model\Auth\Session as AuthSession;
use Magento\Framework\App\Area;
use Magento\Framework\App\State as AppState;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Registry;
use Magento\Sales\Api\Data\CreditmemoInterface;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\User\Model\UserFactory;
use Psr\Log\LoggerInterface;
use Webkul\Marketplace\Helper\Data as MarketplaceHelper;
use Webkul\SellerSubAccount\Helper\Data as SellerSubAccountHelper;

/**
 * Logs who created a new credit memo on the order status history (after DB commit).
 */
class LogCreditmemoCreatorAfterCommit implements ObserverInterface
{
    private const LOG_PREFIX = 'Branch8_Refund::LogCreditmemoCreatorAfterCommit';

    private const FILE_LOG_CLASS_KEY = 'LogCreditmemoCreatorAfterCommit';

    /**
     * @param UserContextInterface $userContext Web API / user context
     * @param AuthSession $authSession Backend admin session
     * @param MarketplaceHelper $marketplaceHelper Marketplace helper
     * @param SellerSubAccountHelper $sellerSubAccountHelper Sub-account helper
     * @param Registry $registry Core registry (cron name)
     * @param OrderRepositoryInterface $orderRepository Order repository
     * @param UserFactory $userFactory Admin user factory
     * @param AppState $appState Application area state
     * @param LoggerInterface $logger PSR logger for system log
     * @param ConfigurableRefundLogger $refundLogger Admin-gated refund file logger
     */
    public function __construct(
        private readonly UserContextInterface $userContext,
        private readonly AuthSession $authSession,
        private readonly MarketplaceHelper $marketplaceHelper,
        private readonly SellerSubAccountHelper $sellerSubAccountHelper,
        private readonly Registry $registry,
        private readonly OrderRepositoryInterface $orderRepository,
        private readonly UserFactory $userFactory,
        private readonly AppState $appState,
        private readonly LoggerInterface $logger,
        private readonly ConfigurableRefundLogger $refundLogger
    ) {
    }

    /**
     * @inheritDoc
     */
    public function execute(Observer $observer): void
    {
        try {
            $creditmemo = $this->resolveCreditmemo($observer);
            if (!$creditmemo instanceof CreditmemoInterface || !$creditmemo->getId()) {
                return;
            }

            if ($creditmemo->getCreatedAt() !== $creditmemo->getUpdatedAt()) {
                return;
            }

            $orderId = (int) $creditmemo->getOrderId();
            if ($orderId <= 0) {
                return;
            }

            $order = $this->orderRepository->get($orderId);
            $creatorLabel = $this->resolveCreatorLabel();
            $incrementId = $creditmemo->getIncrementId() ?: (string) $creditmemo->getId();
            $comment = $this->buildCreatorComment($incrementId, $creatorLabel);

            $order->addStatusHistoryComment($comment, $order->getStatus())
                ->setIsCustomerNotified(false);
            $this->orderRepository->save($order);
        } catch (\Throwable $e) {
            $this->logger->error(self::LOG_PREFIX, ['exception' => $e->getMessage()]);
            $this->refundLogger->logException(self::FILE_LOG_CLASS_KEY, $e, 'execute');
        }
    }

    private function resolveCreditmemo(Observer $observer): ?CreditmemoInterface
    {
        $event = $observer->getEvent();
        $object = $event->getDataObject();
        if ($object instanceof CreditmemoInterface) {
            return $object;
        }
        $creditmemo = $event->getData('creditmemo');
        return $creditmemo instanceof CreditmemoInterface ? $creditmemo : null;
    }

    private function buildCreatorComment(string $incrementId, string $creatorLabel): string
    {
        if ($this->isWebApiArea()) {
            return (string) __(
                'Credit memo #%1 was created by %2 (via API).',
                $incrementId,
                $creatorLabel
            );
        }

        return (string) __(
            'Credit memo #%1 was created by %2.',
            $incrementId,
            $creatorLabel
        );
    }

    /**
     * True when running in REST/SOAP Web API (e.g. admin Bearer token used by Hopes).
     */
    private function isWebApiArea(): bool
    {
        try {
            $area = $this->appState->getAreaCode();
            return $area === Area::AREA_WEBAPI_REST || $area === Area::AREA_WEBAPI_SOAP;
        } catch (\Magento\Framework\Exception\LocalizedException $e) {
            $this->refundLogger->logException(self::FILE_LOG_CLASS_KEY, $e, 'isWebApiArea');

            return false;
        }
    }

    private function resolveCreatorLabel(): string
    {
        if ($this->userContext->getUserType() === UserContextInterface::USER_TYPE_ADMIN) {
            $userId = (int) $this->userContext->getUserId();
            if ($userId > 0) {
                return $this->formatAdminLabel($userId);
            }
        }

        if ($this->userContext->getUserType() === UserContextInterface::USER_TYPE_CUSTOMER) {
            $customerId = (int) $this->userContext->getUserId();
            if ($customerId > 0) {
                return (string) __('Customer/Seller API context (customer ID %1)', $customerId);
            }
        }

        $user = $this->authSession->getUser();
        if ($user && $user->getId()) {
            return (string) __('Admin: %1 (ID %2)', $user->getName(), $user->getId());
        }

        if ((int) $this->marketplaceHelper->isSeller() === 1) {
            $sellerId = $this->sellerSubAccountHelper->getCustomerId()
                ?: $this->marketplaceHelper->getCustomerId();
            if ($sellerId) {
                return (string) __('Seller (customer ID %1)', $sellerId);
            }
        }

        $cronName = $this->registry->registry('current_cron_name');
        if ($cronName) {
            return (string) __('System (cron: %1)', $cronName);
        }

        return (string) __('System');
    }

    private function formatAdminLabel(int $userId): string
    {
        $sessionUser = $this->authSession->getUser();
        if ($sessionUser && (int) $sessionUser->getId() === $userId) {
            return (string) __('Admin: %1 (ID %2)', $sessionUser->getName(), $userId);
        }

        try {
            $adminUser = $this->userFactory->create()->load($userId);
            if ($adminUser->getId()) {
                return (string) __('Admin: %1 (ID %2)', $adminUser->getName(), $userId);
            }
        } catch (\Throwable $e) {
            $this->logger->warning(self::LOG_PREFIX, ['exception' => $e->getMessage()]);
            $this->refundLogger->logException(self::FILE_LOG_CLASS_KEY, $e, 'formatAdminLabel');
        }

        return (string) __('Admin user ID %1', $userId);
    }
}
