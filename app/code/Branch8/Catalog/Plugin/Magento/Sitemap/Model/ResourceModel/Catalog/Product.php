<?php

namespace Branch8\Catalog\Plugin\Magento\Sitemap\Model\ResourceModel\Catalog;

class Product
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

    /**
     * @param $subject
     * @param $select
     * @return array
     */
    public function beforePrepareSelectStatement($subject, $select){
        $pdpPrefix = $this->scopeConfig->getValue(\Branch8\Catalog\Rewrite\Model\Product\Url::PDP_PREFIX);
        $select->columns(['url' => 'concat("'.$pdpPrefix.'",request_path)']);
        return [$select];
    }
}