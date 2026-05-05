<?php
declare(strict_types=1);
/**
 * @project    HotaiConnected
 * @package    Branch8
 * @author     cuongho803@gmail.com
 * @date       01/04/2026
 */

namespace Branch8\WishlistStockAlert\CustomerData;

use Magento\Framework\App\ObjectManager;

class Wishlist extends \Magento\Wishlist\CustomerData\Wishlist
{
    /**
     * @var \Magento\Catalog\Model\Product\Configuration\Item\ItemResolverInterface|mixed
     */
    private mixed $itemResolver;

    /**
     * @param \Magento\Wishlist\Helper\Data $wishlistHelper
     * @param \Magento\Wishlist\Block\Customer\Sidebar $block
     * @param \Magento\Catalog\Helper\ImageFactory $imageHelperFactory
     * @param \Magento\Framework\App\ViewInterface $view
     * @param \Magento\Catalog\Model\Product\Configuration\Item\ItemResolverInterface|null $itemResolver
     */
    public function __construct(
        \Magento\Wishlist\Helper\Data                                           $wishlistHelper,
        \Magento\Wishlist\Block\Customer\Sidebar                                $block,
        \Magento\Catalog\Helper\ImageFactory                                    $imageHelperFactory,
        \Magento\Framework\App\ViewInterface                                    $view,
        \Magento\Catalog\Model\Product\Configuration\Item\ItemResolverInterface $itemResolver = null
    )
    {
        $this->itemResolver = $itemResolver ?: ObjectManager::getInstance()->get(
            \Magento\Catalog\Model\Product\Configuration\Item\ItemResolverInterface::class
        );
        parent::__construct($wishlistHelper, $block, $imageHelperFactory, $view, $this->itemResolver);
    }

    /**
     * @param \Magento\Wishlist\Model\Item $wishlistItem
     * @return array
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    protected function getItemData(\Magento\Wishlist\Model\Item $wishlistItem)
    {
        $product = $wishlistItem->getProduct();
        return [
            'item_id' => $wishlistItem->getId(),
            'image' => $this->getImageData($this->itemResolver->getFinalProduct($wishlistItem)),
            'product_sku' => $product->getSku(),
            'product_id' => $product->getId(),
            'product_url' => $this->wishlistHelper->getProductUrl($wishlistItem),
            'product_name' => $product->getName(),
            'product_price' => $this->block->getProductPriceHtml(
                $product,
                'wishlist_configured_price',
                \Magento\Framework\Pricing\Render::ZONE_ITEM_LIST,
                ['item' => $wishlistItem]
            ),
            'product_is_saleable_and_visible' => $product->isSaleable() && $product->isVisibleInSiteVisibility(),
            'product_has_required_options' => $product->getTypeInstance()->hasRequiredOptions($product),
            'add_to_cart_params' => $this->wishlistHelper->getAddToCartParams($wishlistItem),
            'delete_item_params' => $this->wishlistHelper->getRemoveParams($wishlistItem),
            'combo' => $wishlistItem->getData('combo') ? $wishlistItem->getData('combo') : 'NONE',
        ];
    }
}
