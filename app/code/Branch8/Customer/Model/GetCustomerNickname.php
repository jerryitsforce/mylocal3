<?php
namespace Branch8\Customer\Model;

use Magento\Config\Model\Config\Source\Nooptreq;
use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Store\Model\ScopeInterface;

class GetCustomerNickname
{
    const DEFAULT_MASKED_CHAR = '○';

    private CustomerRepositoryInterface $customerRepository;
    private ScopeConfigInterface $scopeConfig;
    public function __construct(
        CustomerRepositoryInterface $customerRepository,
        ScopeConfigInterface $scopeConfig
    ) {
        $this->customerRepository = $customerRepository;
        $this->scopeConfig = $scopeConfig;
    }

    /**
     * @param $customerId
     * @param $fullNameAsDefault
     * @return string
     */
    public function getCustomerNicknameByCustomerId($customerId, $masked = true)
    {
        try {
            $customer = $this->customerRepository->getById($customerId);
            return $this->getCustomerNickname($customer, $masked);
        } catch (\Exception $e) {
            return '';
        }
    }


    /**
     * @param $customer
     * @param $fullNameAsDefault
     * @return string
     */
    public function getCustomerNickname($customer, $masked = true)
    {
        $nickname = $customer->getCustomAttribute('nickname')?->getValue();
        if ($nickname) {
            return $nickname;
        }

        return $masked ? $this->maskedInfo($customer->getFirstname()) : $customer->getFirstname();
    }

    public function maskedInfo($name)
    {
        $name = trim($name);
        $length = mb_strlen($name);

        if ($length <= 0) {
            return '';
        }

        if ($length <= 1) {
            return $name;
        }

        return str_repeat(self::DEFAULT_MASKED_CHAR, $length - 1) . mb_substr($name, -1);
    }
}
