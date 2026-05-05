<?php
declare(strict_types=1);

namespace Branch8\HelpDeskNotification\Plugin\Magento\Backend\Model\Auth;

use Magento\Framework\Session\Config\ConfigInterface;
use Magento\Framework\Stdlib\CookieManagerInterface;

class SessionPlugin
{
    private CookieManagerInterface $cookieManager;
    private \Magento\Framework\Stdlib\Cookie\CookieMetadataFactory $cookieMetadataFactory;
    private ConfigInterface $sessionConfig;

    /**
     * @param CookieManagerInterface $cookieManager
     * @param ConfigInterface $sessionConfig
     * @param \Magento\Framework\Stdlib\Cookie\CookieMetadataFactory $cookieMetadataFactory
     */
    public function __construct(
        CookieManagerInterface                                 $cookieManager,
        ConfigInterface                                        $sessionConfig,
        \Magento\Framework\Stdlib\Cookie\CookieMetadataFactory $cookieMetadataFactory
    )
    {
        $this->sessionConfig = $sessionConfig;
        $this->cookieMetadataFactory = $cookieMetadataFactory;
        $this->cookieManager = $cookieManager;
    }

    /**
     * @param $subject
     * @param $result
     * @return mixed
     * @throws \Magento\Framework\Exception\InputException
     * @throws \Magento\Framework\Stdlib\Cookie\FailureToSendException
     */
    public function afterProcessLogout($subject, $result)
    {
        $metadata = $this->cookieMetadataFactory->createCookieMetadata();
        $metadata->setPath('/');
        $this->cookieManager->deleteCookie('shown-ticket-popup', $metadata);

        return $result;
    }
}
