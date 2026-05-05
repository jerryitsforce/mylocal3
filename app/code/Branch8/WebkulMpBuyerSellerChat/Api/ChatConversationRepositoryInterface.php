<?php

namespace Branch8\WebkulMpBuyerSellerChat\Api;

use Branch8\WebkulMpBuyerSellerChat\Model\Api\Data\ChatProfileInformation;

interface ChatConversationRepositoryInterface
{
    /**
     * @param int $customerProfileId
     * @param int $sellerUniqId
     * @return mixed
     */
    public function getOrCreate(int $customerProfileId, int $sellerUniqId);

    /**
     * @param ChatProfileInformation $customer
     * @param ChatProfileInformation $seller
     * @return mixed
     */
    public function findConverstation(ChatProfileInformation $customer,
                                      ChatProfileInformation $seller
    );

}
