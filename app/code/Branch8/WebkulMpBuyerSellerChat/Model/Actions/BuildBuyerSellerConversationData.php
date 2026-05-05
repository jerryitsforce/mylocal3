<?php
declare(strict_types=1);

namespace Branch8\WebkulMpBuyerSellerChat\Model\Actions;

use Branch8\WebkulMpBuyerSellerChat\Model\Api\Data\ChatProfileInformation;
use Branch8\WebkulMpBuyerSellerChat\Model\ChatConversationRepository;
use Magento\Customer\Model\ResourceModel\CustomerRepository;
use Magento\Store\Model\StoreManagerInterface;
use Webkul\MpBuyerSellerChat\Model\CustomerData;
use Webkul\MpBuyerSellerChat\Model\ResourceModel\CustomerBlock\CollectionFactory as CustomerBlockCollectionFactory;

class BuildBuyerSellerConversationData
{
    private CustomerRepository $customerRepository;
    private \Webkul\MpBuyerSellerChat\Api\CustomerDataRepositoryInterface $chatCustomerRepository;
    private \Magento\Framework\View\Asset\Repository $viewFileSystem;
    private StoreManagerInterface $storeManager;
    private CustomerBlockCollectionFactory $blockCustomerCollectionFactory;
    private ChatConversationRepository $chatConversationRepository;
    private GetCustomerMetaInformation $getCustomerMetaInformation;

    private ReadTotalUnreadMessages $totalUnreadMessages;
    private \Magento\Store\Model\App\Emulation $emulation;

    /**
     * @param CustomerRepository $customerRepository
     * @param \Webkul\MpBuyerSellerChat\Api\CustomerDataRepositoryInterface $chatCustomerRepository
     * @param \Magento\Framework\View\Asset\Repository $viewFileSystem
     * @param StoreManagerInterface $storeManager
     * @param CustomerBlockCollectionFactory $blockCustomerCollectionFactory
     * @param ChatConversationRepository $chatConversationRepository
     * @param GetCustomerMetaInformation $getCustomerMetaInformation
     * @param ReadTotalUnreadMessages $totalUnreadMessages
     * @param \Magento\Store\Model\App\Emulation $emulation
     */
    public function __construct(
        CustomerRepository                                            $customerRepository,
        \Webkul\MpBuyerSellerChat\Api\CustomerDataRepositoryInterface $chatCustomerRepository,
        \Magento\Framework\View\Asset\Repository                      $viewFileSystem,
        StoreManagerInterface                                         $storeManager,
        CustomerBlockCollectionFactory                                $blockCustomerCollectionFactory,
        ChatConversationRepository                                    $chatConversationRepository,
        GetCustomerMetaInformation                                    $getCustomerMetaInformation,
        ReadTotalUnreadMessages                                       $totalUnreadMessages,
        \Magento\Store\Model\App\Emulation                            $emulation
    )
    {
        $this->viewFileSystem = $viewFileSystem;
        $this->chatCustomerRepository = $chatCustomerRepository;
        $this->customerRepository = $customerRepository;
        $this->storeManager = $storeManager;
        $this->blockCustomerCollectionFactory = $blockCustomerCollectionFactory;
        $this->chatConversationRepository = $chatConversationRepository;
        $this->getCustomerMetaInformation = $getCustomerMetaInformation;
        $this->totalUnreadMessages = $totalUnreadMessages;
        $this->emulation = $emulation;
    }

    /**
     * @param \Branch8\WebkulMpBuyerSellerChat\Model\Api\Data\ChatConversation $conversation
     * @param ChatProfileInformation $customerChatProfile
     * @param ChatProfileInformation $sellerChatProfile
     * @return array
     * @throws \Magento\Framework\Exception\LocalizedException
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function build(
        \Branch8\WebkulMpBuyerSellerChat\Model\Api\Data\ChatConversation $conversation,
        ChatProfileInformation                                           $customerChatProfile,
        ChatProfileInformation                                           $sellerChatProfile
    )
    {
        /**
         * @var CustomerData $sellerChatProfile
         */
        $mcustomer = $this->customerRepository->getById(
            $customerChatProfile->getObjectId()
        );
        $mseller = $this->customerRepository->getById(
            $sellerChatProfile->getObjectId()
        );
        $sellerName = $sellerChatProfile->getName() ?: $mseller->getFirstname() . ' ' . $mseller->getLastname();
        $customerName = $customerChatProfile->getName() ?: $mcustomer->getFirstname() . ' ' . $mcustomer->getLastname();
        return [
            'conversationId' => $conversation->getId(),
            'totalMessages' => $conversation->getTotalMessages(),
            'conversationUniqueId' => $conversation->getConversationUniqueId(),
            'customerUniqueId' => $customerChatProfile->getUniqueId(),
            'customerNickName' => $customerChatProfile->getNickName(),
            'customerName' => $customerName,
            'customerEmail' => $mcustomer->getEmail(),
            'customerImage' => $this->getChatProfileImage($customerChatProfile),
            'customerTotalUnreadMessages' => $this->totalUnreadMessages->execute(
                $customerChatProfile,
                $conversation
            ),
            'sellerId' => $mseller->getId(),
            'sellerName' => $sellerName,
            'sellerNickName' => $sellerChatProfile->getNickName(),
            'sellerEmail' => $mseller->getEmail(),
            'sellerTotalUnreadMessages' => $this->totalUnreadMessages->execute(
                $sellerChatProfile,
                $conversation
            ),
            'sellerUniqueId' => $sellerChatProfile->getUniqueId(),
            'sellerImage' => $this->getChatProfileImage($sellerChatProfile),
            'customerChatStatus' => $customerChatProfile->getChatStatus(),
            'sellerChatStatus' => $sellerChatProfile->getChatStatus(),
            'blockedCustomerList' => $this->getBlockedCustomerList(
                $sellerChatProfile->getUniqueId()
            ),
            'customerProfileData' => $this->getCustomerMetaInformation->execute(
                (int)$mseller->getId(),
                (int)$mcustomer->getId()
            ),
            'last_update' => $conversation->getId() ? $this->toIsoDate($conversation->getLastUpdate()) : '',
            'lastUpdateUCTimestamp' => $conversation->getData('lastUpdateUCTimestamp') ? $conversation->getData('lastUpdateUCTimestamp') :
                (new \DateTime('now', new \DateTimeZone('UTC')))->getTimestamp(),
        ];
    }

    /**
     * @param $date
     * @return string
     */
    private function toIsoDate($date)
    {
        return date('c', strtotime($date));
    }

    /**
     * @param string $uniqueId
     * @return array
     */
    private function getBlockedCustomerList(string $uniqueId)
    {
        $list = [];
        $blockedCustomerList = $this->blockCustomerCollectionFactory->create()
            ->addFieldToFilter('seller_unique_id', ['eq' => $uniqueId]);
        foreach ($blockedCustomerList as $blockCustomer) {
            $list[] = $blockCustomer->getCustomerUniqueId();
        }
        return $list;
    }

    /**
     * @param ChatProfileInformation $chatProfile
     * @return string
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    private function getChatProfileImage(ChatProfileInformation $chatProfile)
    {
        if ($chatProfile->getImage() != null ||
            $chatProfile->getImage() != '') {
            $image = $this
                    ->storeManager
                    ->getStore()->getBaseUrl(
                        \Magento\Framework\UrlInterface::URL_TYPE_MEDIA) .
                'mpchatsystem/chatProfile/' . $chatProfile->getImage();
        } else {
            $this->emulation->startEnvironmentEmulation($this->storeManager->getStore()->getId(), \Magento\Framework\App\Area::AREA_FRONTEND, true);
            $image = $this->viewFileSystem->getUrlWithParams(
                'Webkul_MpBuyerSellerChat::images/sellerimage.png',
                []
            );
            $this->emulation->stopEnvironmentEmulation();
        }
        return $image;
    }
}
