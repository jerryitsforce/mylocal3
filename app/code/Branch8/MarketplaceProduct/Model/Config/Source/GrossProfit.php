<?php

namespace Branch8\MarketplaceProduct\Model\Config\Source;

class GrossProfit implements \Magento\Framework\Option\ArrayInterface
{
    const GP_GTEQ_BGP = 1;

    const GP_LT_BGP = 2;

    const GP_GTEQ_GPL = 3;

    const GP_LT_GPL = 4;
    /**
     * Options getter
     *
     * @return array
     */
    public function toOptionArray()
    {
        return [
            ['value' => self::GP_GTEQ_BGP, 'label' => __('Gross Profit % >= Base Gross Profit')],
            ['value' => self::GP_LT_BGP, 'label' => __('Gross Profit % < Base Gross Profit')],
            ['value' => self::GP_GTEQ_GPL, 'label' => __('Gross Profit % >= Gross Profit Level')],
            ['value' => self::GP_LT_GPL, 'label' => __('Gross Profit % < Gross Profit Level')]
        ];
    }

    /**
     * Get options in "key-value" format
     *
     * @return array
     */
    public function toArray()
    {
        return [
            self::GP_GTEQ_BGP => __('Gross Profit % > Base Gross Profit'),
            self::GP_LT_BGP => __('Gross Profit % < Base Gross Profit'),
            self::GP_GTEQ_GPL => __('Gross Profit % > Gross Profit Level'),
            self::GP_LT_GPL => __('Gross Profit % < Gross Profit Level')
        ];
    }

}