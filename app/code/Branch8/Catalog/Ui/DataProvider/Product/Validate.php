<?php
namespace Branch8\Catalog\Ui\DataProvider\Product;

use Magento\Framework\Stdlib\ArrayManager;


class Validate extends \Magento\Catalog\Ui\DataProvider\Product\Form\Modifier\AbstractModifier{

    /** @var ArrayManager */
    protected $arrayManager;

    public function __construct(
        ArrayManager $arrayManager
    ) {
        $this->arrayManager = $arrayManager;
    }

    public function modifyData(array $data)
    {
        return $data;
    }

    public function modifyMeta(array $meta)
    {
        $meta = $this->customizeFieldSub($meta);
        return $meta;
    }

    protected function customizeFieldSub(array $meta)
    {
        $specialPrice = $this->arrayManager->findPath('special_price', $meta, null, 'children');
        
        if ($specialPrice) {
            $meta = $this->arrayManager->merge(
                $specialPrice. static::META_CONFIG_PATH,
                $meta,
                [
                    'dataScope'  => 'special_price',
                    'validation' => [
                        'product-special-price-validation' => true,
                    ],
                ]
            );
        }
        return $meta;
    }


}