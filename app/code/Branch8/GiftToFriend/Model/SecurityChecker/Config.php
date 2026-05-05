<?php
namespace Branch8\GiftToFriend\Model\SecurityChecker;

use Magento\Store\Model\ScopeInterface as StoreScopeInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Config\ScopeInterface;

class Config extends \Magento\Security\Model\Config{

    const XML_PATH_SMS_PROTECTION_TYPE = 'protection_type';

    const XML_PATH_FRONTEND_AREA = 'gift_order/security/';

    const XML_PATH_MAX_NUMBER_SMS_REQUESTS = 'max_request';

    const XML_PATH_MAX_NUMBER_SMS_REQUESTS_IN = 'max_request_in';

    const XML_PATH_MIN_TIME_BETWEEN_SMS_REQUESTS = 'min_time';
    /**
     * @var ScopeConfigInterface
     */
    private $scopeConfig;

    /**
     * @var ScopeInterface
     */
    private $scope;

    public function __construct(
        ScopeConfigInterface $scopeConfig,
        ScopeInterface $scope
    ) {
        parent::__construct($scopeConfig, $scope);
        $this->scopeConfig = $scopeConfig;
        $this->scope = $scope;
    }

    public function getMaxNumberPasswordResetRequests()
    {
        return (int) $this->scopeConfig->getValue(
            $this->getXmlPathPrefix() . self::XML_PATH_MAX_NUMBER_SMS_REQUESTS,
            StoreScopeInterface::SCOPE_STORE
        );
    }

    protected function getXmlPathPrefix()
    {
        if ($this->scope->getCurrentScope() == \Magento\Framework\App\Area::AREA_ADMINHTML) {
            return self::XML_PATH_ADMIN_AREA;
        }
        return self::XML_PATH_FRONTEND_AREA;
    }
    /**
     * {@inheritDoc}
     *
     * @return int
     */
    public function getLimitationTimePeriod(){
        return (int) $this->scopeConfig->getValue(
            $this->getXmlPathPrefix() . self::XML_PATH_MAX_NUMBER_SMS_REQUESTS_IN,
            StoreScopeInterface::SCOPE_STORE
        );
    }

    public function getSmsProtectionType(){
        return (int) $this->scopeConfig->getValue(
            $this->getXmlPathPrefix() . self::XML_PATH_SMS_PROTECTION_TYPE,
            StoreScopeInterface::SCOPE_STORE
        );
    }
    public function getMaxNumberSmsRequests()
    {
        return (int) $this->scopeConfig->getValue(
            $this->getXmlPathPrefix() . self::XML_PATH_MAX_NUMBER_SMS_REQUESTS,
            StoreScopeInterface::SCOPE_STORE
        );
    }

    public function getMinTimeBetweenSmsRequests(){
        $timeInMin = $this->scopeConfig->getValue(
            $this->getXmlPathPrefix() . self::XML_PATH_MIN_TIME_BETWEEN_SMS_REQUESTS,
            StoreScopeInterface::SCOPE_STORE
        );
        return $timeInMin * 60;
    }


}