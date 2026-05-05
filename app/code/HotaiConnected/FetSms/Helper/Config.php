<?php
namespace HotaiConnected\FetSms\Helper;

use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Store\Model\ScopeInterface;

class Config extends AbstractHelper
{
    const XML_PATH_API_DOMAIN = 'hotaiconnected_fet_sms/general/api_domain';
    const XML_PATH_API_PATH = 'hotaiconnected_fet_sms/general/api_path';
    const XML_PATH_SYS_ID = 'hotaiconnected_fet_sms/general/sys_id';
    const XML_PATH_SRC_ADDRESS = 'hotaiconnected_fet_sms/general/src_address';
    const XML_PATH_ENABLED = 'hotaiconnected_fet_sms/general/enabled';

    /**
     * 判斷 FET 簡訊是否啟用
     */
    public function isEnabled()
    {
        return $this->scopeConfig->isSetFlag(self::XML_PATH_ENABLED, ScopeInterface::SCOPE_STORE);
    }

    /**
     * 取得 FET SMS API Domain
     */
    public function getApiDomain()
    {
        return $this->scopeConfig->getValue(self::XML_PATH_API_DOMAIN, ScopeInterface::SCOPE_STORE);
    }

    /**
     * 取得 SMS Submit Path
     */
    public function getApiPath()
    {
        return $this->scopeConfig->getValue(self::XML_PATH_API_PATH, ScopeInterface::SCOPE_STORE);
    }

    /**
     * 取得 System ID
     */
    public function getSysId()
    {
        return $this->scopeConfig->getValue(self::XML_PATH_SYS_ID, ScopeInterface::SCOPE_STORE);
    }

    /**
     * 取得 Source Address
     */
    public function getSrcAddress()
    {
        return $this->scopeConfig->getValue(self::XML_PATH_SRC_ADDRESS, ScopeInterface::SCOPE_STORE);
    }
}
