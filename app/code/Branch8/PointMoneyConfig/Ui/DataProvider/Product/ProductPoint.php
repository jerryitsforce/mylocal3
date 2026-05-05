<?php

namespace Branch8\PointMoneyConfig\Ui\DataProvider\Product;

use Branch8\PointMoneyConfig\Helper\Common as PointMoneyConfigHelper;
use Magento\Catalog\Ui\DataProvider\Product\Form\Modifier\AbstractModifier;
use Magento\Framework\Stdlib\ArrayManager;

class ProductPoint extends AbstractModifier
{
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
        $weightPathProductPoint                   = $this->arrayManager->findPath(PointMoneyConfigHelper::ATTRIBUTE_CODE_PRODUCT_POINT, $meta, null, 'children');
        $weightPathFreeRatioUpperRedeemLimitValue = $this->arrayManager->findPath(PointMoneyConfigHelper::ATTRIBUTE_CODE_FREE_RATIO_UPPER_REDEEM_LIMIT_VALUE, $meta, null, 'children');
        $weightPathFreeRatioLowerRedeemLimitValue = $this->arrayManager->findPath(PointMoneyConfigHelper::ATTRIBUTE_CODE_FREE_RATIO_LOWER_REDEEM_LIMIT_VALUE, $meta, null, 'children');

        if ($weightPathFreeRatioUpperRedeemLimitValue) {
            $meta = $this->arrayManager->merge(
                $weightPathFreeRatioUpperRedeemLimitValue . static::META_CONFIG_PATH,
                $meta,
                [
                    'dataScope'  => PointMoneyConfigHelper::ATTRIBUTE_CODE_FREE_RATIO_UPPER_REDEEM_LIMIT_VALUE,
                    'validation' => [
                        'validate-zero-or-greater' => true,
                        'free-ratio-upper-redeem-value-validation' => true,
                    ],
                ]
            );
        }

        if ($weightPathFreeRatioLowerRedeemLimitValue) {
            $meta = $this->arrayManager->merge(
                $weightPathFreeRatioLowerRedeemLimitValue . static::META_CONFIG_PATH,
                $meta,
                [
                    'dataScope'  => PointMoneyConfigHelper::ATTRIBUTE_CODE_FREE_RATIO_LOWER_REDEEM_LIMIT_VALUE,
                    'validation' => [
                        'validate-zero-or-greater' => true,
                        'free-ratio-lower-redeem-value-validation' => true,
                    ],
                ]
            );
        }

        return $meta;
    }
}
