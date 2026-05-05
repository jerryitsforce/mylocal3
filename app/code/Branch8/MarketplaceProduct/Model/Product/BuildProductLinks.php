<?php

declare(strict_types=1);

namespace Branch8\MarketplaceProduct\Model\Product;

use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Catalog\Api\Data\ProductLinkInterfaceFactory;

class BuildProductLinks
{
    /**#@+
     * Constants for keys of product link.
     */
    public const KEY_RELATED_TYPE = 'related';
    public const KEY_UP_SELL_TYPE = 'upsell';
    public const KEY_CROSS_SELL_TYPE = 'crosssell';
    public const KEY_GROUPED_TYPE = 'associated';
    /**#@-*/

    /**
     * @var ProductLinkInterfaceFactory
     */
    private ProductLinkInterfaceFactory $productLinkFactory;

    /**
     * BuildProductLinks constructor.
     *
     * @param ProductLinkInterfaceFactory $productLinkFactory
     */
    public function __construct(ProductLinkInterfaceFactory $productLinkFactory)
    {
        $this->productLinkFactory = $productLinkFactory;
    }

    /**
     * Returns product link keys.
     *
     * @return string[]
     */
    public static function getProductLinkTypes(): array
    {
        return [
            self::KEY_RELATED_TYPE,
            self::KEY_UP_SELL_TYPE,
            self::KEY_CROSS_SELL_TYPE
        ];
    }

    /**
     * Build product links to product.
     *
     * @param ProductInterface $product
     * @param array $linkData
     *
     * @return void
     */
    public function execute(ProductInterface $product, array $linkData): void
    {
        $productLinks = [];
        $sku = $product->getSku();
        foreach ($linkData as $linkType => $links) {
            foreach ($links as $position => $linkSku) {
                $productLink = $this->productLinkFactory->create();
                $productLink->setSku($sku);
                if (is_array($linkSku)) {
                    $linkSku = $linkSku['sku'];
                }
                $productLink->setLinkedProductSku($linkSku);
                $productLink->setPosition($position);
                $productLink->setLinkType($linkType);
                $productLinks[] = $productLink;
            }
        }

        $product->setProductLinks($productLinks);
    }
}
