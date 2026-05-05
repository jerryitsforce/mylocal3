<?php
namespace Branch8\RewardSystem\Api;

interface ApiRequestInterface
{
    const API_KEY = 'api_key';

    const CUSTOMER_IDENTIFY = 'customer_identify';

    const PARTNER_IDENTIFY = 'partner_identify';

    /**
     * @param string $apiKey
     * @return $this
     */
    public function setApiKey(string $apiKey);
    
    /**
     * @return string
     */
    public function getApiKey();

    /**
     * @param string $customerIdentify
     * @return $this
     */
    public function setCustomerIdentify(string $customerIdentify);

    /**
     * @return string
     */
    public function getCustomerIdentify();

    /**
     * @return string
     */
    public function getPartnerIdentify();

    /**
     * @param string $partnerIdentify
     * @return $this
     */
    public function setPartnerIdentify(string $partnerIdentify);

}
