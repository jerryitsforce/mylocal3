<?php

namespace Branch8\WebkulMpBuyerSellerChat\Model;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\UrlInterface;

/**
 * Config Class
 */
class GeneralConfig
{
    const XML_PATH_HIDDEN_STATUS_ORDER = 'buyer_seller_chat/customer/contact_links/hide_for_order_statues';

    const XML_PATH_ENABLE_CHAT = 'buyer_seller_chat/general_settings/enable';

    const XML_PATH_AJAX_LOAD_SELLER_CONVERSATION = 'buyer_seller_chat/seller_setting/ajax_load_conversation';
    const XML_PATH_LOAD_CHAT_CONVERSATION_PAGE_SIZE = 'buyer_seller_chat/seller_setting/ajax_load_conversation_pagesize';
    const XML_PATH_AUTO_MARK_READ_MESSAGE_TYPE = 'buyer_seller_chat/messages/auto_mark_read';
    const XML_PATH_NOTIFY_WHEN_HAVE_NEW_MESSAGE = 'buyer_seller_chat/seller_setting/notify_when_have_new_message';
    const XML_PATH_NOTIFY_WHEN_HAVE_CLOSEAFTER = 'buyer_seller_chat/seller_setting/close_popup_after';

    private ScopeConfigInterface $scopeConfig;

    private UrlInterface $urlInterface;

    /**
     * @param UrlInterface $urlInterface
     * @param ScopeConfigInterface $scopeConfig
     */
    public function __construct(
        UrlInterface         $urlInterface,
        ScopeConfigInterface $scopeConfig
    )
    {
        $this->urlInterface = $urlInterface;
        $this->scopeConfig = $scopeConfig;
    }

    /**
     * Get Hidden Order Statuses
     * @return array|string[]
     */
    public function getHiddenOrderStatus()
    {
        $statues = (string)$this->scopeConfig->getValue(self::XML_PATH_HIDDEN_STATUS_ORDER);
        if ($statues) {
            return explode(',', $statues);
        }
        return [];
    }

    /**
     * @return bool
     */
    public function enableChat()
    {
        return (bool)$this->scopeConfig->getValue(self::XML_PATH_ENABLE_CHAT);
    }

    /**
     * @return array
     */
    public function getGeneralConfig()
    {
        $mediaUrl = $this->urlInterface->getUrl('mpchatsystem/index/viewfile?image=');
        $mediaUrl = str_replace("image=/", "image=", $mediaUrl);
        return [
            'ajaxLoadConversation' => $this->ajaxLoadSellerChatConversations(),
            'ajaxLoadConversationUrl' => $this->urlInterface->getUrl('mpchatsystem/ajax/LoadSellerChatConversation'),
            'ajaxLoadConversationPageSize' => $this->getLoadChatConversationPageSize(),
            'loginUrl' => $this->urlInterface->getUrl('customer/account/login'),
            'downLoadFileText' => __('Download File'),
            'mediaUrl' => $mediaUrl,
            'blockedCustomerText' => __('You have been blocked by this seller.'),
            'typingText' => __('Type your message here'),
            'chatUploadMediaPath' => $this->urlInterface->getUrl(
                'mpchatsystem/chat/PostMedia'
            ),
            'chatProfileMediaPath' => $this->urlInterface->getUrl(
                'media/mpchatsystem/chatProfile'
            ),
            'notifyWhenHaveMessage' => (bool)$this->scopeConfig->getValue(self::XML_PATH_NOTIFY_WHEN_HAVE_NEW_MESSAGE),
            'closeAfter' => (int)$this->scopeConfig->getValue(self::XML_PATH_NOTIFY_WHEN_HAVE_CLOSEAFTER),
            'endPoints' => [
                'loadHistory' => 'rest/V2/chat/load-history',
                'loadRecently' => 'rest/V2/chat/load-recently',
                'changeProfileStatus' => 'rest/V2/chat-profile/changeStatus',
                'sendMessage' => 'rest/V2/message/save-message',
                'totalUnreadMessages' => 'rest/V2/chat/total-unread-messages',
                'updateLastReadMessage' => 'rest/V2/chat/last-read-message',
                'useProfileImage' => 'mpchatsystem/chat/UseProfileImage'
            ]
        ];
    }

    /**
     * @return bool
     */
    public function ajaxLoadSellerChatConversations()
    {
        return (bool)$this->scopeConfig->getValue(self::XML_PATH_AJAX_LOAD_SELLER_CONVERSATION);
    }

    /**
     * @return bool
     */
    public function getLoadChatConversationPageSize()
    {
        return (bool)$this->scopeConfig->getValue(self::XML_PATH_LOAD_CHAT_CONVERSATION_PAGE_SIZE);
    }

    /**
     * @return string[]
     */
    public function getAutoMarkReadMessageType()
    {
        $types = (string)$this->scopeConfig->getValue(self::XML_PATH_AUTO_MARK_READ_MESSAGE_TYPE);
        if ($types) {
            return explode(',', $types);
        }
        return [];
    }

}
