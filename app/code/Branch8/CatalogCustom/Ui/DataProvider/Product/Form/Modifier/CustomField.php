<?php

namespace Branch8\CatalogCustom\Ui\DataProvider\Product\Form\Modifier;

use Magento\Framework\Stdlib\ArrayManager;
use Branch8\MarketplaceStaging\Helper\Data as HelperData;

class CustomField extends \Magento\Catalog\Ui\DataProvider\Product\Form\Modifier\AbstractModifier
{
    /**
     * @var Magento\Framework\Stdlib\ArrayManager
     */
    private $arrayManager;

    /**
     * @var HelperData
     */
    protected $helperData;

    /**
     * @param ArrayManager $arrayManager
     */
    public function __construct(
        ArrayManager $arrayManager,
        HelperData $helperData
    ) {
        $this->arrayManager = $arrayManager;
        $this->helperData = $helperData;
    }
    /**
     * modifyData
     *
     * @param array $data
     * @return array
     */
    public function modifyData(array $data)
    {
        return $data;
    }
    /**
     * modifyMeta
     *
     * @param array $data
     * @return array
     */
    public function modifyMeta(array $meta)
    {
        $attribute = 'hot_sell';
        $attributesHint = $this->helperData->getAttributesHint();
        $path = $this->arrayManager->findPath($attribute, $meta, null, 'children');
        $meta = $this->arrayManager->set(
            "{$path}/arguments/data/config/disabled",
            $meta,
            true
        );
        if (!empty($attributesHint)) {
            foreach ($attributesHint as $code => $hint) {
                if ($code == 'is_in_stock') {
                    $code = 'quantity_and_stock_status';
                } elseif ($code == 'stock') {
                    $code = 'qty';
                }
                if ($code == 'custom_option') {
                    $code = 'custom_options';
                    if (isset($meta[$code]['arguments']['data']['config'])) {
                        $meta[$code]['arguments']['data']['config']['template'] = 'Branch8_CatalogCustom/product/form/fieldset';
                        $meta[$code]['arguments']['data']['config']['tooltip'] = ['description'=>$hint];
                    }
                } elseif ($code == 'media_gallery') {
                    $code = 'gallery';
                    $meta[$code]['arguments']['data']['config']['tooltip'] = ['description'=>$hint];
                } else {
                    $path = $this->arrayManager->findPath($code, $meta, null, 'children');
                    $meta = $this->arrayManager->merge(
                        $path . static::META_CONFIG_PATH,
                        $meta,
                        [
                            'dataScope' => $code,
                            'tooltip' => ['description' => $hint],
                        ]
                    );
                }
            }
        }

        return $meta;
    }
}
