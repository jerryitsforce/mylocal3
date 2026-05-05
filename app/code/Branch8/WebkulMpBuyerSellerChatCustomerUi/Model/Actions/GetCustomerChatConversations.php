<?php

namespace Branch8\WebkulMpBuyerSellerChatCustomerUi\Model\Actions;

use Branch8\WebkulMpBuyerSellerChat\Api\ChatConversationRepositoryInterface;
use Branch8\WebkulMpBuyerSellerChat\Model\Actions\BuildBuyerSellerConversationData;
use Branch8\WebkulMpBuyerSellerChat\Model\Actions\GetOrCreateChatProfile;
use Branch8\WebkulMpBuyerSellerChat\Model\Api\Data\ChatConversation;
use Branch8\WebkulMpBuyerSellerChat\Model\Api\Data\ChatProfileInformation;
use Branch8\WebkulMpBuyerSellerChat\Model\ChatProfileRepository;
use Branch8\WebkulMpBuyerSellerChat\Model\ChatRole;
use Branch8\WebkulMpBuyerSellerChat\Model\ChatStatus;
use Magento\Customer\Model\ResourceModel\CustomerRepository;
use Magento\Framework\Exception\LocalizedException;
use Psr\Log\LoggerInterface;
use Webkul\MpBuyerSellerChat\Helper\Http as HttpDriver;
use \Webkul\MpBuyerSellerChat\Api\Data\CustomerDataInterfaceFactory;

/**
 * @property HttpDriver $httpDriver
 */
class GetCustomerChatConversations
{
    private $customerChatConverstions = [];
    /**
     * @var CustomerDataInterfaceFactory
     */
    private $customerDataFactory;

    private CustomerRepository $customerRepository;

    private CustomerDataInterfaceFactory $customerDataInterfaceFactory;

    private ChatConversationRepositoryInterface $chatConverStationRepository;

    private GetActiveConversations $getActiveConversations;

    private BuildBuyerSellerConversationData $buildSellerConversationData;

    private $getOrCreateChatProfile;

    private $chatProfileRepository;

    private $logger;
    /**
     * @param ChatProfileRepository $chatProfileRepository
     * @param \Webkul\MpBuyerSellerChat\Model\ResourceModel\CustomerData\CollectionFactory $customerDataFactory
     * @param CustomerRepository $customerRepository
     * @param CustomerDataInterfaceFactory $customerDataInterfaceFactory
     * @param ChatConversationRepositoryInterface $chatConversationRepository
     * @param GetActiveConversations $getActiveConversations
     * @param BuildBuyerSellerConversationData $buildBuyerSellerConversationData
     * @param GetOrCreateChatProfile $getOrCreateChatProfile
     * @param LoggerInterface $logger
     */
    public function __construct(
        ChatProfileRepository                                                        $chatProfileRepository,
        \Webkul\MpBuyerSellerChat\Model\ResourceModel\CustomerData\CollectionFactory $customerDataFactory,
        CustomerRepository                                                           $customerRepository,
        CustomerDataInterfaceFactory                                                 $customerDataInterfaceFactory,
        ChatConversationRepositoryInterface                                          $chatConversationRepository,
        GetActiveConversations                                                       $getActiveConversations,
        BuildBuyerSellerConversationData                                             $buildBuyerSellerConversationData,
        GetOrCreateChatProfile                                                       $getOrCreateChatProfile,
        LoggerInterface $logger
    )
    {
        $this->customerDataFactory = $customerDataFactory;
        $this->customerRepository = $customerRepository;
        $this->customerDataInterfaceFactory = $customerDataInterfaceFactory;
        $this->chatConverStationRepository = $chatConversationRepository;
        $this->getActiveConversations = $getActiveConversations;
        $this->chatProfileRepository = $chatProfileRepository;
        $this->buildSellerConversationData = $buildBuyerSellerConversationData;
        $this->getOrCreateChatProfile = $getOrCreateChatProfile;
    }

    /**
     * @param $customerId
     * @return \Magento\Customer\Api\Data\CustomerInterface|void
     */
    private function getCustomer($customerId)
    {
        try {
            return $this->customerRepository->getById($customerId);
        } catch (\Exception $exception) {
            return null;
        }
    }

    /**
     * @param $customerId
     * @return array
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function get($customerId)
    {
        if (isset($this->customerChatConverstions[$customerId])) {
            return $this->customerChatConverstions[$customerId];
        }
        $this->customerChatConverstions[$customerId] = [];
        $conversationData = [];
        $customerChatProfile = $this->getOrCreateChatProfile->execute(
            $customerId,
            ChatRole::CUSTOMER,
            ChatRole::CUSTOMER,
            ChatStatus::ONLINE
        );
        $magentoCustomer = $this->getCustomer($customerId);
        if (!$magentoCustomer) {
            return $this->customerChatConverstions[$customerId];
        }
        try {
            $customerActiveConversations = $this->getActiveConversations->get(
                $customerChatProfile,
                GetActiveConversations::DEFAULT
            );
        } catch (LocalizedException $exception) {
            return [];
        }

        /**
         * @var $conversation ChatConversation
         */
        foreach ($customerActiveConversations as $conversation) {
            /**
             * @var $sellerChatProfile ChatProfileInformation
             */
            $sellerChatProfile = $this->chatProfileRepository->getById(
                (int)$conversation->getSellerProfileId()
            );
            $conversationData[] = $this->buildSellerConversationData->build(
                $conversation,
                $customerChatProfile,
                $sellerChatProfile
            );
        }
        $this->customerChatConverstions[$customerId] = $conversationData;
        return $this->customerChatConverstions[$customerId];
    }
}
