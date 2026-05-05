<?php

namespace Branch8\WebkulMpBuyerSellerChat\Model;

use Branch8\WebkulMpBuyerSellerChat\Api\ChatProfileRepositoryInterface;
use Branch8\WebkulMpBuyerSellerChat\Api\Data\ChatConversationInterface;
use Branch8\WebkulMpBuyerSellerChat\Api\Data\ChatProfileInfoInterface;
use Branch8\WebkulMpBuyerSellerChat\Model\Api\Data\ChatProfileInformation;
use Branch8\WebkulMpBuyerSellerChat\Model\Api\Data\ChatProfileInformationFactory;
use Branch8\WebkulMpBuyerSellerChat\Model\ResourceModel\ChatProfileInformation as ResourceModel;
use Magento\Framework\Exception\NoSuchEntityException;
use Branch8\WebkulMpBuyerSellerChat\Helper\Logger as CustomLogger;

class ChatProfileRepository implements ChatProfileRepositoryInterface
{
    const CUSTOMER_ENTITY = 'customer';

    const ADMIN_ENTITY = 'administrator';
    private $chatProfileCached = [];

    private ResourceModel $resource;
    private CustomLogger $logger;
    private ChatProfileInformationFactory $chatProfileInformationFactory;

    /**
     * @param ResourceModel $resource
     * @param ChatProfileInformationFactory $chatProfileInformationFactory
     * @param CustomLogger $logger
     */
    public function __construct(
        ResourceModel                 $resource,
        ChatProfileInformationFactory $chatProfileInformationFactory,
        CustomLogger               $logger
    )
    {
        $this->chatProfileInformationFactory = $chatProfileInformationFactory;
        $this->resource = $resource;
        $this->logger = $logger;
    }

    /**
     * @param int $id
     * @return ChatProfileInformation
     * @throws NoSuchEntityException
     */
    public function getById(int $id)
    {
        $chatProfile = $this->chatProfileInformationFactory->create();
        $this->resource->load($chatProfile, $id, 'entity_id');
        if ($chatProfile->getId()) {
            return $chatProfile;
        }
        throw new NoSuchEntityException();
    }

    /**
     * @param $uniqueId
     * @return ChatProfileInformation
     * @throws NoSuchEntityException
     */
    public function getByUniqueId($uniqueId)
    {
        if (isset($this->chatProfileCached[$uniqueId])) {
            return $this->chatProfileCached[$uniqueId];
        }
        $chatProfile = $this->chatProfileInformationFactory->create();
        $this->resource->load(
            $chatProfile, $uniqueId,
            'unique_id'
        );
        if (!$chatProfile->getId()) {
            throw new NoSuchEntityException(__('No chat profile found'));
        }
        $this->chatProfileCached[$uniqueId] = $chatProfile;
        return $this->chatProfileCached[$uniqueId];
    }

    /**
     * @param $objectId
     * @param $entityType
     * @return ChatProfileInformation|null
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function getByObjectIdAndEntitytype($objectId, $entityType)
    {
        /**
         * @var $chatProfile ChatProfileInformation
         */
        $chatProfile = $this->chatProfileInformationFactory->create();
        $this->resource->loadByObjectIdAndEntitytype($chatProfile, $objectId, $entityType);
        if ($chatProfile->getId()) {
            return $chatProfile;
        }
        return null;
    }

    /**
     * @param $objectId
     * @param $entityType
     * @param $registerAs
     * @return ChatProfileInformation|null
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function getByObjectIdAndEntitytypeAndRegisterAs($objectId, $entityType, $registerAs)
    {
        /**
         * @var $chatProfile ChatProfileInformation
         */
        $chatProfile = $this->chatProfileInformationFactory->create();
        $this->resource->loadByObjectIdAndEntitytype($chatProfile, $objectId, $entityType,$registerAs);
        if ($chatProfile->getId()) {
            return $chatProfile;
        }
        return null;
    }

    /**
     * @param ChatProfileInfoInterface $chatProfileInfo
     * @return ChatProfileInfoInterface
     */
    public function save(ChatProfileInfoInterface $chatProfileInfo)
    {
        try {
            $this->resource->save($chatProfileInfo);
        } catch (\Exception $exception) {
            $this->logger->critical($exception->getMessage());
        }
        return $chatProfileInfo;
    }

    /**
     * @param $uniqueId
     * @param $status
     * @return true
     * @throws NoSuchEntityException
     * @throws \Magento\Framework\Exception\AlreadyExistsException
     */
    public function changeStatus($uniqueId, $status)
    {
        $chatProfile = $this->chatProfileInformationFactory->create();
        $this->resource->load($chatProfile, $uniqueId, 'unique_id');
        if (!$chatProfile->getId()) {
            throw new NoSuchEntityException(__('No chat profile found'));
        }
        $this->resource->save($chatProfile->setChatStatus((int)$status));
        return true;
    }
}
