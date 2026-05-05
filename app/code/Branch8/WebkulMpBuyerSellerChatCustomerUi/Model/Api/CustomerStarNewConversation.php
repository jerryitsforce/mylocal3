<?php declare(strict_types=1);

namespace Branch8\WebkulMpBuyerSellerChatCustomerUi\Model\Api;

use Branch8\WebkulMpBuyerSellerChat\Api\ChatConversationRepositoryInterface;
use Branch8\WebkulMpBuyerSellerChat\Model\Api\Data\ChatConversation;
use Branch8\WebkulMpBuyerSellerChat\Model\Api\Data\ChatConversationFactory;
use Branch8\WebkulMpBuyerSellerChat\Model\ChatProfileEntity;
use Branch8\WebkulMpBuyerSellerChat\Model\Actions\BuildBuyerSellerConversationData;
use Branch8\WebkulMpBuyerSellerChat\Model\Actions\GetOrCreateChatProfile;
use Branch8\WebkulMpBuyerSellerChat\Model\ChatRole;
use Branch8\WebkulMpBuyerSellerChat\Model\ChatStatus;
use Magento\Customer\Model\ResourceModel\CustomerRepository;
use Magento\Customer\Model\ResourceModel\Visitor\CollectionFactory as VisitorCollectionFactory;
use Magento\Customer\Model\Session;
use Magento\Customer\Model\Visitor;
use Webkul\MpBuyerSellerChat\Model\CustomerDataRepository;

class CustomerStarNewConversation implements \Branch8\WebkulMpBuyerSellerChatCustomerUi\Api\CustomerStarNewConversationInterface
{
    private GetOrCreateChatProfile $getOrCreateChatProfile;
    private CustomerRepository $customerRepository;
    private VisitorCollectionFactory $visitorCollectionFactory;
    private Visitor $visitorModel;
    private BuildBuyerSellerConversationData $buildBuyerSellerConversationData;
    private ChatConversationRepositoryInterface $chatConversationRepository;
    private Session $session;
    private ChatConversationFactory $chatConversationFactory;

    /**
     * @param Session $session
     * @param GetOrCreateChatProfile $getOrCreateChatProfile
     * @param CustomerRepository $customerRepository
     * @param Visitor $visitorModel
     * @param VisitorCollectionFactory $visitorCollectionFactory
     * @param BuildBuyerSellerConversationData $buildBuyerSellerConversationData
     * @param ChatConversationFactory $chatConversationFactory
     * @param ChatConversationRepositoryInterface $chatConversationRepository
     */
    public function __construct(
        Session                             $session,
        GetOrCreateChatProfile              $getOrCreateChatProfile,
        CustomerRepository                  $customerRepository,
        Visitor                             $visitorModel,
        VisitorCollectionFactory            $visitorCollectionFactory,
        BuildBuyerSellerConversationData    $buildBuyerSellerConversationData,
        ChatConversationFactory             $chatConversationFactory,
        ChatConversationRepositoryInterface $chatConversationRepository
    )
    {
        $this->session = $session;
        $this->getOrCreateChatProfile = $getOrCreateChatProfile;
        $this->customerRepository = $customerRepository;
        $this->visitorCollectionFactory = $visitorCollectionFactory;
        $this->visitorModel = $visitorModel;
        $this->buildBuyerSellerConversationData = $buildBuyerSellerConversationData;
        $this->chatConversationRepository = $chatConversationRepository;
        $this->chatConversationFactory = $chatConversationFactory;
    }

    /**
     * @param int $sellerId
     * @return \Branch8\WebkulMpBuyerSellerChat\Api\Data\ChatConversationInterface|mixed
     * @throws \Magento\Framework\Exception\CouldNotSaveException
     * @throws \Magento\Framework\Exception\LocalizedException
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function startWith(int $sellerId)
    {
        $customerId = $this->session->getCustomerId();
        $mCustomerData = $this->customerRepository->getById($customerId);
        $mSellerData = $this->customerRepository->getById($sellerId);
        $customerChatProfile = $this->getOrCreateChatProfile->execute(
            (int)$mCustomerData->getId(),
            ChatProfileEntity::CUSTOMER,
            ChatRole::CUSTOMER,
            ChatStatus::ONLINE
        );
        $sellerChatProfile = $this->getOrCreateChatProfile->execute(
            (int)$mSellerData->getId(),
            ChatProfileEntity::CUSTOMER,
            ChatRole::SELLER,
            $this->isSellerOnline($sellerId) ? ChatStatus::ONLINE : ChatStatus::OFFLINE
        );
        $chatConversation = $this->chatConversationRepository->findConverstation(
            $customerChatProfile,
            $sellerChatProfile
        );
        if (!$chatConversation->getId()) {
            $chatConversation = $this->chatConversationRepository->getOrCreate(
                (int)$customerChatProfile->getId(),
                (int)$sellerChatProfile->getId()
            );
        }
        return json_encode($this->buildBuyerSellerConversationData->build(
            $chatConversation,
            $customerChatProfile,
            $sellerChatProfile
        ));
    }

    /**
     * @param $id
     * @return bool
     */
    private function isSellerOnline($id)
    {
        $collection = $this->visitorCollectionFactory->create();
        $lastDate = gmdate('U') - $this->visitorModel->getOnlineInterval() * 60;
        $collection->addFieldToFilter('last_visit_at', [
            'from' => $collection->getConnection()->formatDate($lastDate),
        ]);
        $collection->addFieldToFilter('customer_id', $id);
        return $collection->getSize() > 0;
    }
}
