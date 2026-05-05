<?php

namespace Branch8\WebkulMpBuyerSellerChat\Model;

use Branch8\WebkulMpBuyerSellerChat\Api\ChatConversationRepositoryInterface;
use Branch8\WebkulMpBuyerSellerChat\Api\Data\ChatConversationInterface;
use Branch8\WebkulMpBuyerSellerChat\Model\Actions\SaveParticipants;
use Branch8\WebkulMpBuyerSellerChat\Model\Api\Data\ChatConversation;
use Branch8\WebkulMpBuyerSellerChat\Model\Api\Data\ChatConversationFactory;
use Branch8\WebkulMpBuyerSellerChat\Model\Api\Data\ChatParticipantFactory;
use Branch8\WebkulMpBuyerSellerChat\Model\Api\Data\ChatProfileInformation;
use Branch8\WebkulMpBuyerSellerChat\Model\ResourceModel\ChatConversation as ResourceModel;
use Branch8\WebkulMpBuyerSellerChat\Model\ResourceModel\ChatParticipant\CollectionFactory as ChatParticipantCollectionFactory;
use Magento\Framework\Exception\NoSuchEntityException;
use Branch8\WebkulMpBuyerSellerChat\Helper\Logger as CustomLogger;

class ChatConversationRepository implements ChatConversationRepositoryInterface
{
    private $converStationCached = [];
    private $converStationCodeCached = [];
    private ResourceModel $resource;
    private ChatConversationFactory $chatConversationFactory;
    private CustomLogger $logger;
    private ChatConversationFactory $conversationFactory;
    private SaveParticipants $saveParticipants;

    /**
     * @param ResourceModel $resource
     * @param ChatConversationFactory $chatConversationFactory
     * @param ChatConversationFactory $conversationFactory
     * @param SaveParticipants $saveParticipants
     * @param CustomLogger $logger
     */
    public function __construct(
        ResourceModel           $resource,
        ChatConversationFactory $chatConversationFactory,
        ChatConversationFactory $conversationFactory,
        SaveParticipants        $saveParticipants,
        CustomLogger            $logger
    )
    {
        $this->chatConversationFactory = $chatConversationFactory;
        $this->resource = $resource;
        $this->logger = $logger;
        $this->conversationFactory = $conversationFactory;
        $this->saveParticipants = $saveParticipants;
    }

    /**
     * @param int $customerProfileId
     * @param int $sellerUniqId
     * @return mixed
     * @throws \Magento\Framework\Exception\AlreadyExistsException
     */
    public function getOrCreate(int $customerProfileId, int $sellerUniqId)
    {
        try {
            $key = sprintf('%s_%s', $customerProfileId, $sellerUniqId);
            if (isset($this->converStationCached[$key])) {
                return $this->converStationCached[$key];
            }
            /**
             *
             */
            $chatConversation = $this->chatConversationFactory->create();
            $chatConversation->setCreatorProfileId($customerProfileId)->setType(
                ChatConversation::TYPE_SELLERCHAT
            );
            if (!$chatConversation->getId()) {
                $this->resource->save($chatConversation);
            }
            $participants = [
                ['conversation_id' => $chatConversation->getId(), 'profile_id' => $customerProfileId],
                ['conversation_id' => $chatConversation->getId(), 'profile_id' => $sellerUniqId],
            ];
            $this->saveParticipants->execute($participants);
            $this->converStationCached[$key] = $this->getById(
                $chatConversation->getConversationId()
            );
            return $this->converStationCached[$key];
        } catch (\Exception $exception) {
            $this->logger->critical($exception->getMessage());
            throw $exception;
        }
    }

    /**
     * @param ChatProfileInformation $customer
     * @param ChatProfileInformation $seller
     * @return ChatConversationInterface|null
     */
    public function findConverstation(
        ChatProfileInformation $customer,
        ChatProfileInformation $seller
    )
    {
        /**
         * @var ChatConversationInterface $chatConversation
         */
        $chatConversation = $this->chatConversationFactory->create();
        $chatConversation->getResource()->findBetweenSenderAndReceiver(
            $chatConversation,
            $customer->getId(), $seller->getId()
        );
        return $chatConversation;
    }

    /**
     * @param $conversationCode
     * @return ChatConversation
     * @throws NoSuchEntityException
     */
    public function loadConversationByCode($conversationCode)
    {
        if (isset($this->converStationCodeCached[$conversationCode])) {
            return $this->converStationCodeCached[$conversationCode];
        }

        $conversation = $this->conversationFactory->create()->load(
            $conversationCode, 'unique_id');
        if (!$conversation || !$conversation->getId()) {
            throw new NoSuchEntityException(__('Can not find the conversation'));
        }
        $this->converStationCodeCached[$conversationCode] = $conversation;
        return $this->converStationCodeCached[$conversationCode];
    }

    /**
     * @param int $conversationId
     * @return ChatConversation
     * @throws NoSuchEntityException
     */
    public function getById(int $conversationId)
    {
        $conversation = $this->conversationFactory->create()->load($conversationId);
        if (!$conversation || !$conversation->getId()) {
            throw new NoSuchEntityException(__('Can not find the conversation'));
        }
        return $conversation;
    }
}
