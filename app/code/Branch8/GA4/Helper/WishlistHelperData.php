<?php
namespace Branch8\GA4\Helper;

use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Customer\Helper\View;
use Magento\Customer\Model\Session;
use Magento\Framework\App\Helper\Context;
use Magento\Framework\App\ObjectManager;
use Magento\Framework\Data\Helper\PostHelper;
use Magento\Framework\DataObject;
use Magento\Framework\Escaper;
use Magento\Framework\Registry;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Wishlist\Controller\WishlistProviderInterface;
use Magento\Wishlist\Model\ResourceModel\Item\CollectionFactory as WishlistItemCollection;
use Magento\Wishlist\Model\ResourceModel\Wishlist\CollectionFactory;
use Magento\Wishlist\Model\WishlistFactory;

class WishlistHelperData extends \Magento\MultipleWishlist\Helper\Data
{
    /**
     * @var Escaper|mixed
     */
    private mixed $_escaper;

    public function __construct(
        Context $context,
        Registry $coreRegistry,
        Session $customerSession,
        WishlistFactory $wishlistFactory,
        StoreManagerInterface $storeManager,
        PostHelper $postDataHelper,
        View $customerViewHelper,
        WishlistProviderInterface $wishlistProvider,
        ProductRepositoryInterface $productRepository,
        WishlistItemCollection $itemCollectionFactory,
        CollectionFactory $wishlistCollectionFactory,
        $escaper = null
    ) {
        parent::__construct(
            $context,
            $coreRegistry,
            $customerSession,
            $wishlistFactory,
            $storeManager,
            $postDataHelper,
            $customerViewHelper,
            $wishlistProvider,
            $productRepository,
            $itemCollectionFactory,
            $wishlistCollectionFactory
        );
        $this->_escaper = $escaper ?? ObjectManager::getInstance()->get(Escaper::class);

    }

    public function getAddParams($item, array $params = [])
    {
        parent::getAddParams($item, $params);
        $productId = null;
        if ($item instanceof \Magento\Catalog\Model\Product) {
            $productId = (int) $item->getEntityId();
        }
        if ($item instanceof \Magento\Wishlist\Model\Item) {
            $productId = (int) $item->getProductId();
        }

        $url = $this->_getUrlStore($item)->getUrl('wishlist/index/add');
        if ($productId) {
            $params['product'] = $productId;
        }

        if ($item->getData('ga4_settings')) {
            /** @var DataObject $ga4Settings */
            $ga4Settings = $item->getData('ga4_settings');
            if($ga4Settings->getData('item_list_id')) {
                $params['item_list_id'] = $ga4Settings->getData('item_list_id');
            }
            if($ga4Settings->getData('item_list_name')) {
                $params['item_list_name'] = $ga4Settings->getData('item_list_name');
            }

            if($ga4Settings->getData('promotion_id')) {
                $params['promotion_id'] = $ga4Settings->getData('promotion_id');
            }
            if($ga4Settings->getData('promotion_name')) {
                $params['promotion_id'] = $ga4Settings->getData('promotion_name');
            }
        }

        return $this->_postDataHelper->getPostData(
            $this->_escaper->escapeUrl($url),
            $params
        );
    }
}
