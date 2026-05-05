<?php
declare(strict_types=1);

namespace Branch8\WebkulMpBuyerSellerChatSellerUi\ViewModel;

use Branch8\WebkulMpBuyerSellerChatSellerUi\Model\Actions\SellerChatProfileData;
use Branch8\WebkulMpBuyerSellerChatSellerUi\Model\Actions\GetSellerChatConversations;
use Branch8\WebkulMpBuyerSellerChat\Model\GeneralConfig;
use Magento\Framework\UrlInterface;
use Magento\Framework\View\Element\Block\ArgumentInterface;
use Webkul\SellerSubAccount\Helper\Data as HelperData;

/**
 * SellerConfig
 */
class SellerConfig implements ArgumentInterface
{
    private GeneralConfig $generalConfig;
    private UrlInterface $urlBuilder;
    private \Magento\Customer\Model\Session $session;
    private GetSellerChatConversations $getSellerChatConversations;
    private SellerChatProfileData $sellerChatProfileData;
    /**
     * @var HelperData
     */
    protected $subAccountHelper;
    private UrlInterface $urlInterface;

    /**
     * @param \Magento\Customer\Model\Session\Proxy $session
     * @param UrlInterface $urlBuilder
     * @param GeneralConfig $generalConfig
     * @param GetSellerChatConversations $getSellerChatConversations
     * @param SellerChatProfileData $sellerChatProfileData
     * @param HelperData $subAccountHelper
     * @param UrlInterface $urlInterface
     */
    public function __construct(
        \Magento\Customer\Model\Session\Proxy $session,
        UrlInterface                          $urlBuilder,
        GeneralConfig                         $generalConfig,
        GetSellerChatConversations            $getSellerChatConversations,
        SellerChatProfileData                 $sellerChatProfileData,
        HelperData                            $subAccountHelper,
        UrlInterface                          $urlInterface
    )
    {
        $this->urlBuilder = $urlBuilder;
        $this->session = $session;
        $this->generalConfig = $generalConfig;
        $this->getSellerChatConversations = $getSellerChatConversations;
        $this->sellerChatProfileData = $sellerChatProfileData;
        $this->subAccountHelper = $subAccountHelper;
        $this->urlInterface = $urlInterface;
    }

    /**
     * @return array
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function getCustomerSellerChatData()
    {
        if ($this->generalConfig->ajaxLoadSellerChatConversations()) {
            return [];
        }
        $customerId = (int)$this->session->getCustomerId();
        $subAccount = $this->subAccountHelper->getCurrentSubAccount();
        if ($subAccount->getId()) {
            $customerId = (int)$subAccount->getSellerId();
        }
        return $this->getSellerChatConversations->get($customerId);
    }

    /**
     * @return array
     * @throws \Magento\Framework\Exception\CouldNotSaveException
     * @throws \Magento\Framework\Exception\LocalizedException
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function getSellerChatProfile()
    {
        $customerId = (int)$this->session->getCustomerId();
        $subAccount = $this->subAccountHelper->getCurrentSubAccount();
        if ($subAccount->getId()) {
            $customerId = (int)$subAccount->getSellerId();
        }
        return $this->sellerChatProfileData->getByCustomerId(
            $customerId
        );
    }


    /**
     * @return array
     */
    public function getGeneralConfig()
    {
        $defaultConfig = $this->generalConfig->getGeneralConfig();
        return array_merge($defaultConfig, [
            'ajaxLoadConversationUrl' => $this->urlInterface->getUrl('mpchatsystem/seller_ajax/LoadSellerChatConversation'),
            'endPoints' => [
                'loadHistory' => 'rest/V2/seller/chat/load-history',
                'loadRecently' => 'rest/V2/seller/chat/load-recently',
                'changeProfileStatus' => 'rest/V2/seller/chat-profile/changeStatus',
                'sendMessage' => 'rest/V2/seller/message/save-message',
                'totalUnreadMessages' => 'rest/V2/seller/chat/total-unread-messages',
                'updateLastReadMessage' => 'rest/V2/seller/chat/last-read-message',
                'useProfileImage' => 'mpchatsystem/chat/UseProfileImage'
            ]
        ]);
    }

    /**
     * @return bool
     */
    public function ajaxLoadSellerConversation()
    {
        return $this->generalConfig->ajaxLoadSellerChatConversations();
    }
}
