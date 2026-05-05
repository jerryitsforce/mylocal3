<?php
/**
 * Copyright ©  All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Branch8\Catalog\Rewrite\Magento\Catalog\Block\Product\View;

class Options extends \Magento\Catalog\Block\Product\View\Options
{
    /**
     * Get json representation of
     *
     * @return string
     */
    public function getJsonConfig()
    {
        $config = [];
        foreach ($this->getOptions() as $option) {
            /* @var $option \Magento\Catalog\Model\Product\Option */
            if ($option->hasValues()) {
                $tmpPriceValues = [];
                foreach ($option->getValues() as $valueId => $value) {
                    $tmpPriceValues[$value->getOptionTypeId()] = $this->_getPriceConfiguration($value);
                }
                $priceValue = $tmpPriceValues;
            } else {
                $priceValue = $this->_getPriceConfiguration($option);
            }
            $config[$option->getId()] = $priceValue;
        }

        $configObj = new \Magento\Framework\DataObject(
            [
                'config' => $config,
            ]
        );

        //pass the return array encapsulated in an object for the other modules to be able to alter it eg: weee
        $this->_eventManager->dispatch('catalog_product_option_price_configuration_after', ['configObj' => $configObj]);

        $config = $configObj->getConfig();

        return $this->_jsonEncoder->encode($config);
    }

    /**
     * Get product options
     *
     * @return array
     */
    public function getOptions()
    {
        $options = $this->getProduct()->getOptions();
        // Adjust option value price: set price = 0 when price is empty
        // Fix for marketplacectrl/catalog/preview
        foreach ($options as $option) {
            if ($option->hasValues()) {
                foreach ($option->getValues() as $value) {
                    if ($value->getPrice() === null || $value->getPrice() === '' || !is_numeric($value->getPrice())) {
                        $value->setPrice(0);
                    }
                }
            } else {
                if ($option->getPrice() === null || $option->getPrice() === '' || !is_numeric($option->getPrice())) {
                    $option->setPrice(0);
                }
            }
        }
        return $options;
    }
}