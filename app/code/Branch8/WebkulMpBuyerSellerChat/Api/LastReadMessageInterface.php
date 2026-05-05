<?php declare(strict_types=1);

namespace Branch8\WebkulMpBuyerSellerChat\Api;

/**
 * Save last read message
 */
interface LastReadMessageInterface
{
    /**
     * @param string $conversationId
     * @param string $profileUniqueId
     * @param string $messageUniqueId
     * @return \Branch8\WebkulMpBuyerSellerChat\Api\Data\MessageMetaInformationInterface
     */
    public function save(
        string $conversationId,
        string $profileUniqueId,
        string $messageUniqueId
    );
}
