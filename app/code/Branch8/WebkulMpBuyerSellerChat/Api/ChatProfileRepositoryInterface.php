<?php

namespace Branch8\WebkulMpBuyerSellerChat\Api;

use Branch8\WebkulMpBuyerSellerChat\Model\Api\Data\ChatProfileInformation;

interface ChatProfileRepositoryInterface
{
    /**
     * @param $entityType
     * @param $entityId
     * @return ChatProfileInformation
     */
    public function getByObjectIdAndEntitytype($entityType, $entityId);

    /**
     * @param $entityType
     * @param $entityId
     * @param $registerAs
     * @return mixed
     */
    public function getByObjectIdAndEntitytypeAndRegisterAs($entityType, $entityId, $registerAs);

    /**
     * @param string $uniqueId
     * @param int $status
     * @return mixed
     */
    public function changeStatus(string $uniqueId, int $status);
}
