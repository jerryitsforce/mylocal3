<?php
namespace Branch8\Catalog\Ui\DataProvider\Product;

use Magento\Framework\Stdlib\ArrayManager;


class RemovePreorderAttrFromStg extends \Magento\Catalog\Ui\DataProvider\Product\Form\Modifier\AbstractModifier{

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
        unset($meta['product-details']['children']['container_wk_marketplace_preorder']);
        unset($meta['product-details']['children']['container_preorder_mode']);
        unset($meta['product-details']['children']['container_preorder_start_date']);
        unset($meta['product-details']['children']['container_preorder_end_date']);
        unset($meta['product-details']['children']['container_preorder_use_qty']);
        unset($meta['product-details']['children']['container_wk_mppreorder_qty']);
        unset($meta['product-details']['children']['container_preorder_x_days']);
        unset($meta['product-details']['children']['container_preorder_ship_date']);
        unset($meta['product-details']['children']['container_wk_marketplace_availability']);
        return $meta;
    }


}