<?php
declare(strict_types=1);

namespace Branch8\WebkulMpBuyerSellerChatUnreadMessageReminder\Plugin;

use Branch8\WebkulMpBuyerSellerChat\Model\Actions\SellerNickNameResolver;
use Branch8\WebkulMpBuyerSellerChat\Model\ChatConversationRepository;
use Branch8\WebkulMpBuyerSellerChatUnreadMessageReminder\Api\ChatNotificationServiceInterface;
use Branch8\WebkulMpBuyerSellerChat\Model\ChatProfileRepository;
use Branch8\WebkulMpBuyerSellerChat\Model\ChatRole;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Store\Model\StoreManagerInterface;

/**
 * Plugin to intercept message saving and trigger notifications.
 */
class SaveMessagePlugin
{
    /**
     * @var ChatNotificationServiceInterface
     */
    private ChatNotificationServiceInterface $notificationService;

    /**
     * @var ChatProfileRepository
     */
    private ChatProfileRepository $chatProfileRepository;

    private StoreManagerInterface $storeManager;
    private SellerNickNameResolver $sellerNickNameResolver;

    /**
     * @param ChatNotificationServiceInterface $notificationService
     * @param ChatProfileRepository $chatProfileRepository
     * @param StoreManagerInterface $storeManager
     * @param SellerNickNameResolver $sellerNickNameResolver
     */
    public function __construct(
        ChatNotificationServiceInterface $notificationService,
        ChatProfileRepository $chatProfileRepository,
        StoreManagerInterface $storeManager,
        SellerNickNameResolver $sellerNickNameResolver
    ) {
        $this->sellerNickNameResolver = $sellerNickNameResolver;
        $this->storeManager = $storeManager;
        $this->notificationService = $notificationService;
        $this->chatProfileRepository = $chatProfileRepository;
    }

    /**
     * @param \Branch8\WebkulMpBuyerSellerChat\Model\Api\SaveMessage $subject
     * @param mixed $result
     * @param int $conversationId
     * @param string $senderUniqueId
     * @param string $receiverUniqueId
     * @param string $message
     * @param string $dateTime
     * @param string $msgType
     * @param string|null $uniqueId
     * @param array|null $meta
     * @param int|null $productId
     * @return mixed
     */
    public function afterSaveChatMessage(
        \Branch8\WebkulMpBuyerSellerChat\Model\Api\SaveMessage $subject,
        $result,
        $conversationId,
        $senderUniqueId,
        $receiverUniqueId,
        $message,
        $dateTime,
        $msgType,
        $uniqueId = null,
        $meta = null,
        $productId = null
    ) {
        try {
            // Check sender role (should be seller) and receiver role (should be customer)
            $senderProfile = $this->chatProfileRepository->getByUniqueId($senderUniqueId);
            $receiverProfile = $this->chatProfileRepository->getByUniqueId($receiverUniqueId);
            if ($senderProfile->getRegisteredAs() === ChatRole::SELLER &&
                $receiverProfile->getRegisteredAs() === ChatRole::CUSTOMER
            ) {
                $customerId = (int)$receiverProfile->getObjectId();
                $sellerId = (int)$senderProfile->getObjectId();
                $this->notificationService->schedule(
                    $customerId,
                    $conversationId,
                    $message,
                    $this->sellerNickNameResolver->execute($sellerId),
                    $sellerId,
                    (int)$this->storeManager->getStore()->getId()
                );
            }
        } catch (NoSuchEntityException $e) {
            // Profile not found, ignore
        } catch (\Exception $e) {
            // Log as needed or let it fail gracefully
        }

        return $result;
    }
}
