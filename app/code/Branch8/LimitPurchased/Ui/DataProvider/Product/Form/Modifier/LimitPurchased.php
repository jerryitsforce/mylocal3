<?php

namespace Branch8\LimitPurchased\Ui\DataProvider\Product\Form\Modifier;

use Branch8\LimitPurchased\Helper\Data as LimitPurchasedHelper;
use Magento\Framework\Stdlib\ArrayManager;
use Magento\Catalog\Model\Locator\LocatorInterface;

class LimitPurchased extends \Magento\Catalog\Ui\DataProvider\Product\Form\Modifier\AbstractModifier
{
    /**
     * @var ArrayManager
     * @since 101.0.0
     */
    protected $arrayManager;

    /**
     * @var LocatorInterface
     */
    private $locator;

    public function __construct(
        LocatorInterface $locator,
        ArrayManager $arrayManager
    ) {
        $this->locator = $locator;
        $this->arrayManager = $arrayManager;
    }

    public function modifyMeta(array $meta)
    {
        $meta = $this->customizeLimitPurchased($meta);

        return $meta;
    }

    public function modifyData(array $data)
    {
        return $data;
    }

    protected function customizeLimitPurchased(array $meta)
    {
        $limit_purchased_qty = $this->arrayManager->findPath(LimitPurchasedHelper::ATTRIBUTE_CODE_LIMIT_PURCHASED_QTY, $meta, null, 'children');
        if ($limit_purchased_qty) {
            $meta = $this->arrayManager->merge(
                $limit_purchased_qty . static::META_CONFIG_PATH,
                $meta,
                [
                    'dataScope' => LimitPurchasedHelper::ATTRIBUTE_CODE_LIMIT_PURCHASED_QTY,
                    'validation' => [
                        'validate-greater-than-zero' => true,
                    ]
                ]
            );
        }

        $mp_product_cart_limit = $this->arrayManager->findPath('mp_product_cart_limit', $meta, null, 'children');
        if ($mp_product_cart_limit) {
            $meta = $this->arrayManager->merge(
                $mp_product_cart_limit . static::META_CONFIG_PATH,
                $meta,
                [
                    'dataScope' => 'mp_product_cart_limit',
                    'visible' => false
                ]
            );
        }
        return $meta;
    }
}