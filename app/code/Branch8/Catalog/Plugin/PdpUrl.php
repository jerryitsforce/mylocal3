<?php

namespace Branch8\Catalog\Plugin;

class PdpUrl
{
    /**
     * @var \Magento\Framework\App\Config\ScopeConfigInterface
     */
    protected $scopeConfig;

    /**
     * @param \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
     */
    public function __construct(
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
    ){
        $this->scopeConfig = $scopeConfig;
    }

    public function beforeDispatch($subject, $request)
    {
        $pathInfor = $request->getPathInfo();
        $pdpPrefix = (string)$this->scopeConfig->getValue(\Branch8\Catalog\Rewrite\Model\Product\Url::PDP_PREFIX);
        if(substr($pathInfor, 1, strlen($pdpPrefix)) == $pdpPrefix){
            $pdpRealRequest = str_replace($pdpPrefix, '', $pathInfor);
            $request->setPathInfo($pdpRealRequest);
        }

        return [$request];
    }

}