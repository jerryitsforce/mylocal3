<?php

namespace Branch8\WebkulMpBuyerSellerChatCustomerUi\Model\Actions;

use Branch8\WebkulMpBuyerSellerChat\Model\Actions\GetOrCreateChatProfile;
use Branch8\WebkulMpBuyerSellerChat\Model\Actions\GetProfileTotalUnreadMessages;
use Branch8\WebkulMpBuyerSellerChat\Model\ChatProfileEntity;
use Branch8\WebkulMpBuyerSellerChat\Model\ChatRole;
use Magento\Store\Model\StoreManagerInterface;

class CustomerChatProfileData
{
    private GetOrCreateChatProfile $getOrCreateChatProfile;
    private StoreManagerInterface $storeManager;
    private \Magento\Framework\View\Asset\Repository $viewFileSystem;
    private GetProfileTotalUnreadMessages $getProfileTotalUnreadMessages;

    /**
     * @param GetOrCreateChatProfile $getOrCreateChatProfile
     * @param StoreManagerInterface $storeManager
     * @param \Magento\Framework\View\Asset\Repository $viewFileSystem
     * @param GetProfileTotalUnreadMessages $getProfileTotalUnreadMessages
     */
    public function __construct(
        GetOrCreateChatProfile                   $getOrCreateChatProfile,
        StoreManagerInterface                    $storeManager,
        \Magento\Framework\View\Asset\Repository $viewFileSystem,
        GetProfileTotalUnreadMessages $getProfileTotalUnreadMessages
    )
    {
        $this->storeManager = $storeManager;
        $this->getOrCreateChatProfile = $getOrCreateChatProfile;
        $this->viewFileSystem = $viewFileSystem;
        $this->getProfileTotalUnreadMessages = $getProfileTotalUnreadMessages;
    }

    /**
     * @param $customerId
     * @return array
     * @throws \Magento\Framework\Exception\CouldNotSaveException
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function getByCustomerId(int $customerId)
    {
        $profile = $this->getOrCreateChatProfile->execute($customerId,
            ChatProfileEntity::CUSTOMER,
            ChatRole::CUSTOMER
        );
        if ($profile->getImage() != null ||
            $profile->getImage() != '') {
            $defaultImageUrl = $this
                    ->storeManager
                    ->getStore()->getBaseUrl(\Magento\Framework\UrlInterface::URL_TYPE_MEDIA) .
                'mpchatsystem/chatProfile/' . $profile->getImage();
        } else {
            $defaultImageUrl = $this->viewFileSystem->getUrlWithParams(
                'Webkul_MpBuyerSellerChat::images/sellerimage.png',
                []
            );
        }
        return [
            'uniqueId' => $profile->getUniqueId(),
            'image' => $defaultImageUrl,
            'status' => $profile->getChatStatus(),
            'name' => $profile->getName(),
            'nickName' => $profile->getNickname(),
            'totalUnreadMessages' => $this->getProfileTotalUnreadMessages->execute($profile->getUniqueId()),
        ];
    }
}
