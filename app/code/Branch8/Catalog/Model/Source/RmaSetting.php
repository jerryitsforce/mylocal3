<?php

namespace Branch8\Catalog\Model\Source;

use Magento\Framework\App\Config\ScopeConfigInterface;

class RmaSetting extends \Magento\Rma\Model\Product\Source
{
    private ScopeConfigInterface $scopeConfig;

    /**
     * @param ScopeConfigInterface $scopeConfig
     */
    public function __construct(
        ScopeConfigInterface $scopeConfig,
    )
    {
        $this->scopeConfig = $scopeConfig;
    }
    /**
     * Retrieve all attribute options
     *
     * @return array
     */
    public function getAllOptions()
    {
        $currentSetting = (bool)$this->scopeConfig->getValue(self::XML_PATH_PRODUCTS_ALLOWED) ? __('Yes')->render() : __('No')->render();
        if (!$this->_options) {
            $this->_options = [
                ['label' => __('Non-returnable'), 'value' => self::ATTRIBUTE_ENABLE_RMA_NO],
                ['label' => __('Yes'), 'value' => self::ATTRIBUTE_ENABLE_RMA_YES],
                ['label' => __('Using Default Rma Config (%1)',$currentSetting), 'value' => self::ATTRIBUTE_ENABLE_RMA_USE_CONFIG],
            ];
        }
        return $this->_options;
    }
}
