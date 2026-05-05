<?php
declare(strict_types=1);

namespace Branch8\WebkulMpBuyerSellerChat\Model\Actions;

use Branch8\WebkulMpBuyerSellerChat\Model\Api\Data\ChatConversation;
use Branch8\WebkulMpBuyerSellerChat\Model\Api\Data\ChatProfileInformation;
class ConversationVerification
{
    /**
     * @param ChatProfileInformation $chatProfileEntity
     * @param ChatConversation $conversation
     * @return true
     */
    public function verify(ChatProfileInformation $chatProfileEntity, ChatConversation $conversation)
    {
        return true;
    }
}
