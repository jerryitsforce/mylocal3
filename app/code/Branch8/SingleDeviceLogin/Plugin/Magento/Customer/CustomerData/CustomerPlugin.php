<?php

namespace Branch8\SingleDeviceLogin\Plugin\Magento\Customer\CustomerData;

use Branch8\HotaiCore\Model\Detection\MobileDetect;
use Branch8\SingleDeviceLogin\Model\LoginSessionFactory;
use Branch8\SingleDeviceLogin\Model\TokenService;
use Magento\Customer\CustomerData\Customer;
use Magento\Customer\Model\Session;

class CustomerPlugin
{
    /**
     * @var TokenService
     */
    private TokenService $tokenService;
    /**
     * @var Session\Proxy
     */
    private Session\Proxy $customerSesion;
    private MobileDetect $mobileDetector;
    private LoginSessionFactory $loginSessionFactory;

    /**
     * @param TokenService $tokenService
     * @param MobileDetect $mobileDetection
     * @param Session\Proxy $sessionProxy
     * @param LoginSessionFactory $loginSessionFactory
     */
    public function __construct(
        TokenService        $tokenService,
        MobileDetect        $mobileDetection,
        Session\Proxy       $sessionProxy,
        LoginSessionFactory $loginSessionFactory
    )
    {
        $this->loginSessionFactory = $loginSessionFactory;
        $this->customerSesion = $sessionProxy;
        $this->tokenService = $tokenService;
        $this->mobileDetector = $mobileDetection;
    }

    /**
     * @param Customer $subject
     * @param array $result
     * @return array
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function afterGetSectionData(Customer $subject, array $result): array
    {
        if ($this->customerSesion->getCustomerId()) {
            $deviceType = $this->mobileDetector->isHotaiApp() ? 'app' : 'web';
            $loginSession = $this->loginSessionFactory->create()->loadByCustomerIdAndDeviceType($this->customerSesion->getCustomerId(), $deviceType);
            if (empty($loginSession)) {
                $newToken = $this->tokenService->generateNewToken();
                $loginSession->addData([
                    'token' => $newToken,
                    'device_type' => $deviceType,
                    'customer_id' => $this->customerSesion->getCustomerId()
                ])->save();
            }
            $result['login_token'] = $loginSession->getData('token');
            $result['device_type'] = $deviceType;
        }
        return $result;
    }
}
