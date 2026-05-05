<?php
/**
 * Copyright ©  All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Branch8\OptionsWithStockAndImages\Plugin\Magento\Catalog\Model\ProductOptions;

class Config
{
    const XML_PATH_ONLY_ENABLE_DROPDOWN = 'optionsWithStockAndImages/setting/only_enable_dropdown';

    /**
    * @var \Magento\Framework\App\Config\ScopeConfigInterface
    */
    protected $scopeConfig;

    public function __construct(\Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig)
    {
        $this->scopeConfig = $scopeConfig;
    }

    public function afterGetAll(
        \Magento\Catalog\Model\ProductOptions\Config $subject,
        $result
    ) {
        if($this->scopeConfig->getValue(self::XML_PATH_ONLY_ENABLE_DROPDOWN)){
            unset($result['text']);
            unset($result['file']);
            unset($result['date']);
            foreach($result['select']['types'] as $key => $type){
                if($key != 'drop_down'){
                    $result['select']['types'][$key]['disabled'] = true;
                }
            }
        }
        return $result;
    }
}