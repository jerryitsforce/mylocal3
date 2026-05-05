<?php
namespace Branch8\Preorder\Ui\DataProvider\Product;

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
    $preorder_start_date = $this->arrayManager->findPath('preorder_start_date', $meta, null, 'children');
    $preorder_end_date = $this->arrayManager->findPath('preorder_end_date', $meta, null, 'children');
    $wk_marketplace_availability = $this->arrayManager->findPath('wk_marketplace_availability', $meta, null, 'children');
    $preorder_x_days = $this->arrayManager->findPath('preorder_x_days', $meta, null, 'children');
    $preorder_ship_date = $this->arrayManager->findPath('preorder_ship_date', $meta, null, 'children');
    $wk_mppreorder_qty = $this->arrayManager->findPath('wk_mppreorder_qty', $meta, null, 'children');

    if ($preorder_start_date) {
        $meta = $this->arrayManager->merge(
            $preorder_start_date. static::META_CONFIG_PATH,
            $meta,
            [
                'dataScope'  => 'preorder_start_date',
                'validation' => [
                    'preorder-mode-startenddate-start-validation' => true,
                ],
            ]
        );
    }

    if ($preorder_end_date) {
        $meta = $this->arrayManager->merge(
            $preorder_end_date . static::META_CONFIG_PATH,
            $meta,
            [
                'dataScope'  => 'preorder_end_date',
                'validation' => [
                    'preorder-mode-startenddate-end-validation' => true,
                ],
            ]
        );
    }
    if ($wk_marketplace_availability) {
        $meta = $this->arrayManager->merge(
            $wk_marketplace_availability . static::META_CONFIG_PATH,
            $meta,
            [
                'dataScope'  => 'wk_marketplace_availability',
                'validation' => [
                    'preorder-mode-startenddate-availability-validation' => true,
                ],
            ]
        );
    }
    if ($preorder_x_days) {
        $meta = $this->arrayManager->merge(
            $preorder_x_days . static::META_CONFIG_PATH,
            $meta,
            [
                'dataScope'  => 'preorder_x_days',
                'validation' => [
                    'preorder-mode-startenddate-xday-validation' => true
                ],
            ]
        );
    }
    
    if ($preorder_ship_date) {
        $meta = $this->arrayManager->merge(
            $preorder_ship_date . static::META_CONFIG_PATH,
            $meta,
            [
                'dataScope'  => 'preorder_ship_date',
                'validation' => [
                    'preorder-mode-shipdate-ship-date-validation' => true,
                ],
            ]
        );
    }
    if ($wk_mppreorder_qty) {
        $meta = $this->arrayManager->merge(
            $wk_mppreorder_qty . static::META_CONFIG_PATH,
            $meta,
            [
                'dataScope'  => 'wk_mppreorder_qty',
                'validation' => [
                    'preorder-mode-startenddate-max-qty-validation' => true,
                ],
            ]
        );
    }

    return $meta;
}


}