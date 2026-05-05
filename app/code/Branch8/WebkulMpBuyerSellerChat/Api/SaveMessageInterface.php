<?php declare(strict_types=1);

namespace Branch8\WebkulMpBuyerSellerChat\Api;

use Branch8\WebkulMpBuyerSellerChat\Api\Data\MessageMetaInformationInterface;

interface SaveMessageInterface
{
    /**
     * @param string $conversationId
     * @param string $senderUniqueId
     * @param string $receiverUniqueId
     * @param string $message
     * @param string $dateTime
     * @param string $msgType
     * @param string|null $uniqueId
     * @param MessageMetaInformationInterface[]|null $meta
     * @param int|null $productId
     * @return mixed
     */
    public function saveChatMessage(
        string $conversationId,
        string $senderUniqueId,
        string $receiverUniqueId,
        string $message,
        string $dateTime,
        string $msgType,
        string $uniqueId = null,
        array  $meta = null,
        int    $productId = null
    );
}
