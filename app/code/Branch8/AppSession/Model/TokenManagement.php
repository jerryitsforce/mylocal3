<?php

namespace Branch8\AppSession\Model;

use Magento\Framework\Exception\LocalizedException;
use Magento\Integration\Api\CustomerTokenServiceInterface;
use Magento\Integration\Api\Exception\UserTokenException;
use Magento\Integration\Api\UserTokenIssuerInterface;
use Magento\Integration\Model\CustomUserContext;
use Magento\Integration\Model\Oauth\TokenFactory;
use Magento\Integration\Model\UserToken\UserTokenParametersFactory;
use Magento\JwtUserToken\Api\Data\Revoked;
use Magento\JwtUserToken\Api\RevokedRepositoryInterface;

class TokenManagement
{
    public function __construct(
        protected TokenFactory $tokenModelFactory,
        protected CustomerTokenServiceInterface $customerTokenService,
        protected UserTokenParametersFactory $tokenParametersFactory,
        protected UserTokenIssuerInterface $tokenIssuer,
        protected RevokedRepositoryInterface $revokedRepo
    ) {
    }

    /**
     * @param $customer
     * @return string
     */
    public function generateCustomerToken($customer) {
        $customerId = $customer->getId();

        $context = new CustomUserContext(
            (int)$customerId,
            CustomUserContext::USER_TYPE_CUSTOMER
        );
        $params = $this->tokenParametersFactory->create();

        $token = $this->tokenIssuer->create($context, $params);

        return $token;
    }


    /**
     * @param $customerId
     * @return bool
     */
    public function revokeCustomerAccessToken($customerId): bool
    {
        try {
            $this->revokedRepo->saveRevoked(
                new Revoked(CustomUserContext::USER_TYPE_CUSTOMER, (int) $customerId, time() - 1)
            );
        } catch (UserTokenException $exception) {
            throw new LocalizedException(__('Failed to revoke customer\'s access tokens'), $exception);
        }
        return true;
    }
}
