<?php
namespace Branch8\Catalog\Ui\DataProvider\Product\Form\Modifier;

use Magento\Framework\Stdlib\ArrayManager;


class Weight extends \Magento\Catalog\Ui\DataProvider\Product\Form\Modifier\AbstractModifier{

    /** @var ArrayManager */
    protected ArrayManager $arrayManager;

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
        return $this->customizeFieldSub($meta);
    }

    protected function customizeFieldSub(array $meta)
    {
        $weight = $this->arrayManager->findPath('weight', $meta, null, 'children');

        if ($weight) {
            $meta = $this->arrayManager->merge(
                $weight. static::META_CONFIG_PATH,
                $meta,
                [
                    'dataScope'  => 'weight',
                    'validation' => [
                        'product-weight-validation' => true,
                    ],
                ]
            );
        }
        return $meta;
    }


}
