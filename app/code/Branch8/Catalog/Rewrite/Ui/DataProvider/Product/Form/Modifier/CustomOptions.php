<?php

declare(strict_types=1);

namespace Branch8\Catalog\Rewrite\Ui\DataProvider\Product\Form\Modifier;

use Magento\Framework\Pricing\PriceCurrencyInterface;

class CustomOptions extends \Magento\Catalog\Ui\DataProvider\Product\Form\Modifier\CustomOptions
{
    /**
     * @inheritdoc
     */
    protected function getPriceFieldConfig($sortOrder): array
    {
        $config = parent::getPriceFieldConfig($sortOrder);
        $config['arguments']['data']['config']['validation']['validate-digits'] = true;
        return $config;
    }

    /**
     * @inheritdoc
     */
    protected function formatPrice($value): string
    {
        return $value !== null ? number_format(round((float)$value), 0, '.', '') : '';
    }
}
