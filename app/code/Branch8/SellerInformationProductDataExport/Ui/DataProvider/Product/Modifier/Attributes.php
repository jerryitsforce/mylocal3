<?php

declare(strict_types=1);

namespace Branch8\SellerInformationProductDataExport\Ui\DataProvider\Product\Modifier;

use Magento\Framework\Escaper;
use Magento\Framework\Stdlib\ArrayManager;
use Magento\Ui\DataProvider\Modifier\ModifierInterface;

/**
 * Modify product listing attributes
 */
class Attributes implements ModifierInterface
{
    const INDEX_SELLER_ATTRIBUTE = 'index_seller_id';
    const INDEX_STOCK_ATTRIBUTE = 'index_stock_status';
    const LIVESEARCH_CATEGORIES = 'livesearch_categories';
    const LIVESEARCH_INSTOCK = 'livesearch_instock';
    const SELLER_SHOP_NAME = 'seller_shop_name';
    const VIP_PRICE_PRIORITY = 'vip_price_priority';
    const NEED_TO_REFILL = 'need_to_refill';
    const MIN_VARIATION_PRICE = 'min_variation_price';
    /**
     * @var ArrayManager
     * @since 101.0.0
     */
    protected $arrayManager;
    /**
     * @var Escaper
     */
    private $escaper;

    /**
     * @var array
     */
    private $escapeAttributes;

    /**
     * @param Escaper $escaper
     * @param array $escapeAttributes
     */
    public function __construct(
        ArrayManager $arrayManager,
        Escaper      $escaper,
        array        $escapeAttributes = []
    )
    {
        $this->arrayManager = $arrayManager;
        $this->escaper = $escaper;
        $this->escapeAttributes = $escapeAttributes;
    }

    /**
     * @inheritdoc
     */
    public function modifyData(array $data)
    {
        return $data;
    }

    /**
     * @inheritdoc
     */
    public function modifyMeta(array $meta)
    {
        $elementPath = $this->arrayManager->findPath(
            self::INDEX_SELLER_ATTRIBUTE,
            $meta, null, 'children'
        );
        if (!$elementPath) {
            return $meta;
        }
        $value = [
            'arguments' => [
                'data' => [
                    'config' => [
                        'disabled' => true,
                        'notice' => __('This value need for Live Search and automatic update by seller id')
                    ],
                ],
            ]
        ];
        $meta = $this->arrayManager->merge(
            $elementPath,
            $meta,
            $value
        );

        $elementPath = $this->arrayManager->findPath(
            self::INDEX_STOCK_ATTRIBUTE,
            $meta, null, 'children'
        );
        if (!$elementPath) {
            return $meta;
        }
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
            $elementPath,
            $meta,
            $value
        );

        $elementPath = $this->arrayManager->findPath(
            self::LIVESEARCH_CATEGORIES,
            $meta, null, 'children'
        );
        if (!$elementPath) {
            return $meta;
        }
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
            $elementPath,
            $meta,
            $value
        );

        $elementPath = $this->arrayManager->findPath(
            self::SELLER_SHOP_NAME,
            $meta, null, 'children'
        );
        if (!$elementPath) {
            return $meta;
        }
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
            $elementPath,
            $meta,
            $value
        );

        $elementPath = $this->arrayManager->findPath(
            self::LIVESEARCH_INSTOCK,
            $meta, null, 'children'
        );
        if (!$elementPath) {
            return $meta;
        }
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
            $elementPath,
            $meta,
            $value
        );

        $elementPath = $this->arrayManager->findPath(
            self::MIN_VARIATION_PRICE,
            $meta, null, 'children'
        );
        if (!$elementPath) {
            return $meta;
        }
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
            $elementPath,
            $meta,
            $value
        );

        $elementPath = $this->arrayManager->findPath(
            self::NEED_TO_REFILL,
            $meta, null, 'children'
        );
        if (!$elementPath) {
            return $meta;
        }
        $value = [
            'arguments' => [
                'data' => [
                    'config' => [
                        'disabled' => true,
                        'notice' => __('This value need for filter product need to refill and automatic update')
                    ],
                ],
            ]
        ];
        $meta = $this->arrayManager->merge(
            $elementPath,
            $meta,
            $value
        );

        $elementPath = $this->arrayManager->findPath(
            self::VIP_PRICE_PRIORITY,
            $meta, null, 'children'
        );
        if (!$elementPath) {
            return $meta;
        }
        $value = [
            'arguments' => [
                'data' => [
                    'config' => [
                        'disabled' => true,
                        'notice' => __('This value need for Sort and automatic update')
                    ],
                ],
            ]
        ];
        $meta = $this->arrayManager->merge(
            $elementPath,
            $meta,
            $value
        );

        return $meta;
    }
}
