<?php

namespace Branch8\HotaiAuth\Observer;

use Branch8\HotaiAuth\Helper\HotaiLogin;
use Branch8\HotaiAuth\Logger\Logger;
use Branch8\HotaiAuth\Service\HotaiAuthService;
use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Customer\Model\CustomerFactory;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Exception\LocalizedException;

/**
 * Observes the `hotai_auth_save_latest_token` event.
 */
class SaveLatestHotaiTokenObserver implements ObserverInterface
{
    public function __construct(
        private CustomerRepositoryInterface $customerRepository,
        private HotaiLogin $hotaiLoginHelper,
        private HotaiAuthService $hotaiAuthService,
        private \Magento\Framework\Session\SessionManager $sessionManager
    )
    {
    }

    /**
     * Observer for hotai_auth_save_latest_token.
     *
     * @param Observer $observer
     *
     * @return void
     */
    public function execute(Observer $observer)
    {

        $event = $observer->getEvent();
        $hotaiToken = $event->getData('hotai_token');
        $memberSeq = $this->getMemberSeqFromSession();
        $memberSeq = $memberSeq ?? $this->getMemberSeqFromToken($hotaiToken);

        $this->hotaiAuthService->writeLog('[SaveLatestHotaiTokenObserver] for memberSeq: ' . $memberSeq);

        if (!$memberSeq) {
            return;
        }

        $hotaiToken = !empty($hotaiToken) ? $hotaiToken : [];

        try {
            $customer = $this->hotaiLoginHelper->getCustomerByMemberSeq($memberSeq);
            if (!$customer->getId()) {
                return;
            }
            $customer = $this->customerRepository->getById($customer->getId());
            $customer->setCustomAttribute('latest_hotai_token', json_encode($hotaiToken));

            $this->customerRepository->save($customer);
        } catch (\Exception $e) {
            $this->hotaiAuthService->writeLog('[SaveLatestHotaiTokenObserver] Error saving latest Hotai token: ' . $e->getMessage());
            // Handle exception if needed, e.g., log it
        }
    }

    protected function getMemberSeqFromSession()
    {
        $memberSeq = $this->sessionManager->getHotaiMemberSeq();
        if(!$memberSeq) {
            $hotaiProfile = $this->sessionManager->getHotaiProfile();
            $memberSeq = $hotaiProfile['memberSeq'] ?? null;
        }
        return $memberSeq;
    }

    private function getMemberSeqFromToken(array $hotaiToken)
    {
        if (empty($hotaiToken['accessToken'])) {
            return null;
        }

        $payload = $this->getJwtTokenPayload($hotaiToken['accessToken']);
        return $payload['sub'] ?? null;
    }


    private function getJwtTokenPayload($token)
    {
        $parts = explode('.', $token);
        if (count($parts) !== 3) {
            return [];
        }

        $payload = $parts[1];
        $decodedPayload = base64_decode(strtr($payload, '-_', '+/'));
        if ($decodedPayload === false) {
            return [];
        }

        $payloadData = json_decode($decodedPayload, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            return [];
        }

        return $payloadData;
    }

}
