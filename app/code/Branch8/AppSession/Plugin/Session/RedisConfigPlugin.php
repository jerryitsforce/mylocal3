<?php

namespace Branch8\AppSession\Plugin\Session;

use Magento\Framework\Session\SaveHandler\Redis\Config;

class RedisConfigPlugin
{
    public const XML_PATH_APP_COOKIE_LIFETIME = 'web/cookie/app_cookie_lifetime';

    private ?int $appCookieLifetime = null;


    public function __construct(
        private readonly \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
    )
    {
    }

    private function isHotaiApp() : bool {
        $isHotaiApp = false;
        if (isset($_SERVER['HTTP_USER_AGENT'])) {
            $userAgent = $_SERVER['HTTP_USER_AGENT'];
            if (str_contains($userAgent, 'HotaiApp')) {
                $isHotaiApp = true;
            }
        }
        return $isHotaiApp;
    }

    public function getAppCookieLifetime()
    {
        if (!$this->appCookieLifetime) {
            $this->appCookieLifetime = (int)$this->scopeConfig->getValue(self::XML_PATH_APP_COOKIE_LIFETIME, \Magento\Store\Model\ScopeInterface::SCOPE_STORE);
        }

        return $this->appCookieLifetime;
    }


    private function adjustLifetime(Config $subject, $result)
    {
        $uri = $_SERVER['REQUEST_URI'] ?? '';


        if (!$this->isHotaiApp()) {
            if ($uri == '/checkout/cart') {
                $this->log('Not HotaiApp, return original lifetime: ' . $_SERVER['HTTP_USER_AGENT']);
            }
            return $result;
        }

        $appSessionTTL = 34560000; //$this->getAppCookieLifetime()
        return $appSessionTTL;
    }

    /**
     * @param Config $subject
     * @param int $result
     * @return mixed
     */
    public function afterGetMinLifetime(Config $subject, $result)
    {
        return $this->adjustLifetime($subject, (int)$result);
    }

    /**
     * @param Config $subject
     * @param int $result
     * @return mixed
     */
    public function afterGetMaxLifetime(Config $subject, $result)
    {
        return $this->adjustLifetime($subject, (int)$result);
    }

    /**
     * @param Config $subject
     * @param int $result
     * @return mixed
     */
    public function afterGetLifetime(Config $subject, $result)
    {
        return $this->adjustLifetime($subject, (int)$result);
    }

    public function log($info, $file = 'debug-single-session')
    {
        $writer = new \Zend_Log_Writer_Stream(BP . "/var/log/$file.log");
        $logger = new \Zend_Log();
        $logger->addWriter($writer);

        if (is_array($info)) {
            $info = json_encode($info);
        }
        if (is_null($info)) {
            $info = 'null';
        }
        $logger->info($info);
    }
}
