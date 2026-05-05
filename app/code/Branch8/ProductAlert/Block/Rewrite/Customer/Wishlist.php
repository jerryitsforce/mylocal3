<?php

namespace Branch8\ProductAlert\Block\Rewrite\Customer;

class Wishlist extends \Webkul\MarketplacePreorder\Block\Rewrite\Customer\Wishlist
{

    /**
     * @var \Branch8\ProductAlert\Helper\Data
     */
    protected $data;

    public function __construct(
        \Magento\Wishlist\Helper\Data $wishlistHelper,
        \Magento\Wishlist\Block\Customer\Sidebar $block,
        \Magento\Catalog\Helper\ImageFactory $imageHelperFactory,
        \Magento\Framework\App\ViewInterface $view,
        \Webkul\MarketplacePreorder\Helper\Data $preorderHelper,
        \Branch8\ProductAlert\Helper\Data $data
    ) {
        $this->data = $data;
        parent::__construct($wishlistHelper, $block, $imageHelperFactory, $view, $preorderHelper);
    }

    /**
     * @inheritDoc
     */
    protected function getItems()
    {
        $this->view->loadLayout();

        $collection = $this->wishlistHelper->getWishlistItemCollection();
        // remove page size to load full wishlist items
        $collection->clear()->setInStockFilter(true)->setOrder('added_at');

        $items = [];
        foreach ($collection as $wishlistItem) {
            $items[] = $this->getItemData($wishlistItem);
        }
        return $items;
    }

    /**
     * Retrieve wishlist item data
     *
     * @param \Magento\Wishlist\Model\Item $wishlistItem
     * @return array
     */
    protected function getItemData(\Magento\Wishlist\Model\Item $wishlistItem)
    {
        $data = [];
        $product = $wishlistItem->getProduct();
        $result = parent::getItemData($wishlistItem);
        if ($this->data->getEighteenProducts($product->getId())) {
            $data= [
                'eighteen_product' => true,
                'image_src' => $this->data->getProductAlertImage()
            ];
        } else {
            $data['eighteen_product'] = false;
        }

        return \array_merge(
            $data,
            $result
        );
    }
}
