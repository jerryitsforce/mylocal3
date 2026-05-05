<?php

namespace Branch8\RestrictedProduct\Ui\DataProvider\Product\Form\Modifier;

use Magento\Framework\Stdlib\ArrayManager;
use Magento\Catalog\Model\Locator\LocatorInterface;

class RestrictedProduct extends \Magento\Catalog\Ui\DataProvider\Product\Form\Modifier\AbstractModifier
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
        $restricted_customer_groups = $this->arrayManager->findPath('allow_customer_groups', $meta, null, 'children');
        if ($restricted_customer_groups) {
            $value = [
                'arguments' => [
                    'data' => [
                        'config' => [
                            'disabled' => true,
                            'notice' => __('This value need for Live Search and automatic update')
                        ],
                    ],
                ]
            ];
            $meta = $this->arrayManager->merge(
                $restricted_customer_groups,
                $meta,
                $value
            );
        }
        return $meta;
    }
}