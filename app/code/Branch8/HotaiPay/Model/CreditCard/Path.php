<?php

namespace Branch8\HotaiPay\Model\CreditCard;

use Magento\Store\Model\StoreManagerInterface;

class Path
{
    protected $storeManager;

    /**
     * __construct
     *
     * @return void
     */
    public function __construct(
        StoreManagerInterface $storeManager
    ) {
        $this->storeManager = $storeManager;
    }

    /**
     * getReturnPath
     *
     * @param  mixed $redirectUrl
     * @return void | string
     */
    public function getReturnPath(string $redirectUrl)
    {
        $baseUrl = $this->storeManager->getStore()->getBaseUrl();
        $returnPath = str_replace($baseUrl, "", $redirectUrl);
        return $returnPath;
    }
}
