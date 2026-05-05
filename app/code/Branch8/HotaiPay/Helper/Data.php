<?php 

namespace Branch8\HotaiPay\Helper;

use Branch8\HotaiPay\Helper\Config;
use Magento\Framework\App\Config\ScopeConfigInterface;
use \Magento\Framework\Session\SessionManager;

/**
 * Data
 */
class Data
{
    public $scopeConfig;
    public $config;
    protected $session;    
    /**
     * __construct
     *
     * @return void
     */
    public function __construct (
        ScopeConfigInterface $scopeConfig,
        Config $config,
        SessionManager $session
    )
    {
        $this->scopeConfig = $scopeConfig;
        $this->config = $config;
        $this->session = $session;
    }    
    /**
     * get
     *
     * @param  mixed $name
     * @return void | string
     */
    public function get($name)
    {
        return $this->scopeConfig->getValue($name, \Magento\Store\Model\ScopeInterface::SCOPE_STORE);
    }
    
    /**
     * getAppId
     *
     * @return void | string
     */
    public function getAppId()
    {
        return $this->scopeConfig->getValue($this->config::KEY_APPID, \Magento\Store\Model\ScopeInterface::SCOPE_STORE);
    }
    
    /**
     * getToken
     *
     * @return void | string
     */
    public function getToken()
    {
        return $this->session->getHotaiToken();
    }
    
    /**
     * getAesKey
     *
     * @return void | string
     */
    public function getAesKey()
    {
        return $this->scopeConfig->getValue($this->config::KEY_AES_KEY, \Magento\Store\Model\ScopeInterface::SCOPE_STORE);
    }
    
    /**
     * getAesIv
     *
     * @return void | string
     */
    public function getAesIv()
    {
        return $this->scopeConfig->getValue($this->config::KEY_AES_IV, \Magento\Store\Model\ScopeInterface::SCOPE_STORE);
    }
    
    /**
     * getPaymentUrl
     *
     * @return void | string
     */
    public function getPaymentUrl()
    {
        return $this->scopeConfig->getValue($this->config::HOST_PAYMENT, \Magento\Store\Model\ScopeInterface::SCOPE_STORE);
    }
    
    /**
     * getCreditCardManualUrl
     *
     * @return void | string
     */
    public function getCreditCardManualUrl()
    {
        return $this->scopeConfig->getValue($this->config::HOST_CREDIT_CARD_MANUAL, \Magento\Store\Model\ScopeInterface::SCOPE_STORE);
    }
    
    /**
     * getCreditCardFastUrl
     *
     * @return void | string
     */
    public function getCreditCardFastUrl()
    {
        return $this->scopeConfig->getValue($this->config::HOST_CREDIT_CARD_FAST, \Magento\Store\Model\ScopeInterface::SCOPE_STORE);
    }
    
    /**
     * getCheckoutUrl
     *
     * @return void | string
     */
    public function getCheckoutUrl()
    {
        return $this->scopeConfig->getValue($this->config::HOST_CHECKOUT, \Magento\Store\Model\ScopeInterface::SCOPE_STORE);
    }
    
    /**
     * getCheckoutMerchantId
     *
     * @return void
     */
    public function getCheckoutMerchantId()
    {
        return $this->scopeConfig->getValue($this->config::CHECKOUT_MERCHANT_ID, \Magento\Store\Model\ScopeInterface::SCOPE_STORE);
    }
    
    /**
     * getCheckoutMerId
     *
     * @return void
     */
    public function getCheckoutMerId()
    {
        return $this->scopeConfig->getValue($this->config::CHECKOUT_MER_ID, \Magento\Store\Model\ScopeInterface::SCOPE_STORE);
    }
    
    /**
     * getCheckoutTerminalId
     *
     * @return void
     */
    public function getCheckoutTerminalId()
    {
        return $this->scopeConfig->getValue($this->config::CHECKOUT_TERMINAL_ID, \Magento\Store\Model\ScopeInterface::SCOPE_STORE);
    }
    
    /**
     * getOrderNew
     *
     * @return void | string
     */
    public function getOrderNew()
    {
        return $this->scopeConfig->getValue($this->config::ORDER_NEW, \Magento\Store\Model\ScopeInterface::SCOPE_STORE);
    }
    
    /**
     * getOrderSuccess
     *
     * @return void | string
     */
    public function getOrderSuccess()
    {
        return $this->scopeConfig->getValue($this->config::ORDER_SUCCESS, \Magento\Store\Model\ScopeInterface::SCOPE_STORE);
    }
    
    /**
     * getOrderFail
     *
     * @return void | string
     */
    public function getOrderFail()
    {
        return $this->scopeConfig->getValue($this->config::ORDER_FAIL, \Magento\Store\Model\ScopeInterface::SCOPE_STORE);
    }

}
?>