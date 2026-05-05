<?php

declare(strict_types=1);

namespace Branch8\WebkulMpBuyerSellerChatSellerUi\Model\Actions;

use Branch8\WebkulMpBuyerSellerChat\Model\Actions\BuildBuyerSellerConversationData;
use Branch8\WebkulMpBuyerSellerChat\Model\Actions\GetOrCreateChatProfile;
use Branch8\WebkulMpBuyerSellerChat\Model\Api\Data\ChatConversation;
use Branch8\WebkulMpBuyerSellerChat\Model\ChatProfileRepository;
use Branch8\WebkulMpBuyerSellerChat\Model\ChatRole;
use Branch8\WebkulMpBuyerSellerChat\Model\ChatStatus;
use Magento\Customer\Api\Data\CustomerInterface;
use Magento\Customer\Model\ResourceModel\CustomerRepository;

class GetSellerChatConversations
{
    /**
     * @var array
     */
    private array $sellerChatConversations = [];

    /**
     * @var CustomerRepository
     */
    private CustomerRepository $customerRepository;

    /**
     * @var GetActiveConversations
     */
    private GetActiveConversations $getActiveConversations;

    /**
     * @var BuildBuyerSellerConversationData
     */
    private BuildBuyerSellerConversationData $buildSellerConversationData;

    /**
     * @var GetOrCreateChatProfile
     */
    private GetOrCreateChatProfile $getOrCreateChatProfile;

    /**
     * @var ChatProfileRepository
     */
    private ChatProfileRepository $chatProfileRepository;

    /**
     * @param ChatProfileRepository $chatProfileRepository
     * @param CustomerRepository $customerRepository
     * @param GetActiveConversations $getActiveConversations
     * @param BuildBuyerSellerConversationData $buildBuyerSellerConversationData
     * @param GetOrCreateChatProfile $getOrCreateChatProfile
     */
    public function __construct(
        ChatProfileRepository            $chatProfileRepository,
        CustomerRepository               $customerRepository,
        GetActiveConversations           $getActiveConversations,
        BuildBuyerSellerConversationData $buildBuyerSellerConversationData,
        GetOrCreateChatProfile           $getOrCreateChatProfile
    )
    {
        $this->chatProfileRepository = $chatProfileRepository;
        $this->customerRepository = $customerRepository;
        $this->getActiveConversations = $getActiveConversations;
        $this->buildSellerConversationData = $buildBuyerSellerConversationData;
        $this->getOrCreateChatProfile = $getOrCreateChatProfile;

    }

    /**
     * Returns seller chat conversations.
     *
     * @param int $customerId
     * @return array|mixed
     *
     * @throws \Exception
     */
    public function get(int $customerId, $page = 1)
    {
        if (isset($this->sellerChatConversations[$customerId])) {
            return $this->sellerChatConversations[$customerId];
        }
        $this->sellerChatConversations[$customerId] = [];
        $conversationData = [];
        $sellerChatProfile = $this->getOrCreateChatProfile->execute(
            (int)$customerId,
            ChatRole::CUSTOMER,
            ChatRole::SELLER,
            ChatStatus::ONLINE
        );
        $magentoCustomer = $this->getCustomer($customerId);
        if (!$magentoCustomer) {
            return $this->sellerChatConversations[$customerId];
        }
        $sellerChatConversations = $this->getActiveConversations->get($sellerChatProfile);
        /** @var ChatConversation $conversation */
        foreach ($sellerChatConversations as $conversation) {
            $customerChatProfile = $this->chatProfileRepository->getById((int)$conversation->getCustomerProfileId());
            $data = $this->buildSellerConversationData->build(
                $conversation,
                $customerChatProfile,
                $sellerChatProfile
            );
            $conversationData[] = $data;
        }
        $this->sellerChatConversations[$customerId] = $conversationData;
        return $this->sellerChatConversations[$customerId];
    }

    /**
     * Get customer by ID.
     *
     * @param int $customerId
     *
     * @return CustomerInterface|null
     */
    private function getCustomer(int $customerId): ?CustomerInterface
    {
        try {
            return $this->customerRepository->getById($customerId);
        } catch (\Exception $e) {
            return null;
        }
    }
}
