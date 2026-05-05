<?php

namespace Branch8\WebkulMpBuyerSellerChatUnreadMessageReminder\Model;

use Branch8\WebkulMpBuyerSellerChat\Model\Api\Data\ChatParticipant;

interface MailInterface
{
    /**
     * @param ChatParticipant $chatParticipant
     * @return mixed
     */
    public function send(ChatParticipant $chatParticipant);
}
