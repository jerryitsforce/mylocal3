<?php

namespace Branch8\WebkulMpBuyerSellerChat\Model\Actions;

use Branch8\WebkulMpBuyerSellerChat\Model\Api\Data\ChatProfileInformation;
use Branch8\WebkulMpBuyerSellerChat\Model\ChatProfileEntity;
use Branch8\WebkulMpBuyerSellerChat\Model\ChatProfileRepository;
use Branch8\WebkulMpBuyerSellerChat\Model\ChatRole;
use Branch8\WebkulMpBuyerSellerChat\Model\ChatStatus;
use Branch8\WebkulMpBuyerSellerChat\Model\Until;
use Magento\Customer\Model\ResourceModel\CustomerRepository;
use Magento\Customer\Model\ResourceModel\Visitor\CollectionFactory as VisitorCollectionFactory;
use Magento\Customer\Model\Visitor;
use Webkul\MpBuyerSellerChat\Api\Data\CustomerDataInterface;
use Webkul\MpBuyerSellerChat\Model\CustomerDataRepository;

class GetOrCreateChatProfile
{
    private $chatRolePrefix = [
        ChatRole::CUSTOMER => 'cu_',
        ChatRole::SELLER => 'se_',
        ChatRole::DEALER => 'de_',
    ];
    private $customerRepository;

    private \Branch8\WebkulMpBuyerSellerChat\Api\Data\ChatProfileInfoInterfaceFactory $chatProfileInfoInterfaceFactory;

    private chatProfileRepository $chatProfileRepository;

    private VisitorCollectionFactory $visitorCollectionFactory;

    private Visitor $visitorModel;
    private \Magento\User\Model\UserFactory $userFactory;
    private SellerNickNameResolver $sellerNickNameResolver;

    /**
     * @param ChatProfileRepository $chatProfileRepository
     * @param CustomerRepository $customerRepository
     * @param Visitor $visitorModel
     * @param VisitorCollectionFactory $visitorCollectionFactory
     * @param SellerNickNameResolver $sellerNickNameResolver
     * @param \Branch8\WebkulMpBuyerSellerChat\Api\Data\ChatProfileInfoInterfaceFactory $chatProfileFactory
     * @param \Magento\User\Model\UserFactory $userFactory
     */
    public function __construct(
        ChatProfileRepository                                                     $chatProfileRepository,
        CustomerRepository                                                        $customerRepository,
        Visitor                                                                   $visitorModel,
        VisitorCollectionFactory                                                  $visitorCollectionFactory,
        SellerNickNameResolver                                                    $sellerNickNameResolver,
        \Branch8\WebkulMpBuyerSellerChat\Api\Data\ChatProfileInfoInterfaceFactory $chatProfileFactory,
        \Magento\User\Model\UserFactory                                           $userFactory
    )
    {
        $this->chatProfileInfoInterfaceFactory = $chatProfileFactory;
        $this->chatProfileRepository = $chatProfileRepository;
        $this->customerRepository = $customerRepository;
        $this->visitorCollectionFactory = $visitorCollectionFactory;
        $this->visitorModel = $visitorModel;
        $this->userFactory = $userFactory;
        $this->sellerNickNameResolver = $sellerNickNameResolver;
    }

    /**
     * @param int $objectId
     * @param string $entityType
     * @param string $registerAs
     * @param int|null $status
     * @return \Branch8\WebkulMpBuyerSellerChat\Model\Api\Data\ChatProfileInformation|CustomerDataInterface|\Webkul\MpBuyerSellerChat\Model\PreorderComplete|null
     * @throws \Magento\Framework\Exception\CouldNotSaveException
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function execute(
        int    $objectId,
        string $entityType,
        string $registerAs,
        int    $status = null
    )
    {
        $chatProfile = $this->chatProfileRepository->getByObjectIdAndEntitytypeAndRegisterAs(
            $objectId,
            $entityType,
            $registerAs
        );

        if (!$status) {
            $status = $this->isProfileOnline($objectId) ?
                ChatStatus::ONLINE : ChatStatus::OFFLINE;
        }
        if (!$chatProfile || !$chatProfile->getId()) {
            $uniqueCode = Until::generateUnique($this->getPrefixByChatRole($registerAs));
            /**
             * @var $chatProfile ChatProfileInformation
             */
            $chatProfile = $this->chatProfileInfoInterfaceFactory->create();
            list($email, $nickName, $name) = $this->getNameAndEmail($registerAs, $objectId);
            $chatProfile->setObjectId($objectId)
                ->setEntityType($entityType)
                ->setChatStatus($status)
                ->setEmail($email)
                ->setNickName($nickName)
                ->setName($name)
                ->setRegisteredAs($registerAs)
                ->setUniqueId($uniqueCode);
            $chatProfile = $this->chatProfileRepository->save($chatProfile);
        }
        return $chatProfile;
    }

    /**
     * @param $id
     * @return bool
     */
    private function isProfileOnline($id)
    {
        $collection = $this->visitorCollectionFactory->create();
        $lastDate = gmdate('U') - $this->visitorModel->getOnlineInterval() * 60;
        $collection->addFieldToFilter('last_visit_at', [
            'from' => $collection->getConnection()->formatDate($lastDate),
        ]);
        $collection->addFieldToFilter('customer_id', $id);
        return $collection->getSize() > 0;
    }

    /**
     * @param $chatRole
     * @return string
     */
    private function getPrefixByChatRole($chatRole)
    {
        return $this->chatRolePrefix[$chatRole];
    }

    /**
     * Will be refactor by interface later
     * @param $objectType
     * @param $objectId
     * @return array
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    private function getNameAndEmail($objectType, $objectId)
    {
        $nickname = $email = $name = '';
        switch ($objectType) {
            case ChatProfileEntity::CUSTOMER:
                try {
                    $object = $this->customerRepository->getById($objectId);
                    $name = ChatProfileEntity::getProfileName(
                        ChatProfileEntity::CUSTOMER, $object
                    );
                    $nickname = $name;
                    if (($attribute = $object->getCustomAttribute('nickname'))
                        && $attribute->getValue()
                    ) {
                        $nickname = $attribute->getValue();
                    }
                    $email = $object->getEmail();
                } catch (\Exception $e) {
                }
                break;
            case ChatProfileEntity::SELLER:
                $nickname = $this->sellerNickNameResolver->execute($objectId);
                try {
                    $object = $this->customerRepository->getById($objectId);
                    $name = ChatProfileEntity::getProfileName(
                        ChatProfileEntity::CUSTOMER, $object
                    );
                    $email = $object->getEmail();
                } catch (\Exception $e) {

                }
                break;
            case ChatProfileEntity::DEALER:
                $object = $this->userFactory->create()->load($objectId);
                $name = ChatProfileEntity::getProfileName(
                    ChatProfileEntity::DEALER, $object
                );
                $nickname = $name;
                $email = $object->getEmail();
                break;
        }
        return [$email, $nickname, $name];
    }
}
