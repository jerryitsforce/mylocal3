<?php
/**
 * @author Magenest Team
 * @copyright Copyright (c) 2018 Magenest (https://www.magenest.com)
 * @package Magenest_Core
 */
namespace Magenest\Core\Observer;

use Magenest\Core\Helper\Data;
use Magento\Framework\Event\ObserverInterface;

class PreDispatch implements ObserverInterface
{
    /**
     * @var \Magento\Backend\Model\Auth\Session
     */
    private $backendSession;

    /**
     * @var Data
     */
    private $helper;

    /**
     * @var \Magento\Store\Model\StoreManagerInterface
     */
    protected $_storeManager;

    /**
     * @var \Psr\Log\LoggerInterface $logger
     */
    public $logger;

    /**
     * PreDispatch constructor.
     * @param \Magento\Backend\Model\Auth\Session $backendAuthSession
     * @param \Magento\Store\Model\StoreManagerInterface $storeManager
     * @param Data $helper
     * @param \Psr\Log\LoggerInterface $logger
     */
    public function __construct(
        \Magento\Backend\Model\Auth\Session $backendAuthSession,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        Data $helper,
        \Psr\Log\LoggerInterface $logger
    ) {
        $this->backendSession = $backendAuthSession;
        $this->helper = $helper;
        $this->_storeManager = $storeManager;
        $this->logger = $logger;
    }

    /**
     * @param \Magento\Framework\Event\Observer $observer
     * @return bool|void
     */
    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        if ($this->backendSession->isLoggedIn()) {
            if($this->helper->checkUpdate()){
                try{
                    $curlClient = $this->helper->getCurlClient();
                    $modules = $this->helper->getModules();
                    $curlClient->setOption(CURLOPT_REFERER, $this->_storeManager->getStore()->getBaseUrl());
                    $curlClient->post(Data::getUpdateUrl(),$modules);
                } catch (\Exception $e){
                    $this->logger->critical($e);
                }

                try {
                    $this->helper->checkNotificationUpdate();
                } catch (\Exception $e) {
                    $this->logger->critical($e);
                }
            }
        }
    }

}
