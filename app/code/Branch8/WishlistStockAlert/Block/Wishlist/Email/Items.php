<?php
declare(strict_types=1);

namespace Branch8\WishlistStockAlert\Block\Wishlist\Email;

use Magento\Catalog\Model\Product\Configuration\Item\ItemResolverInterface;
use Magento\Catalog\Model\Product\Image\UrlBuilder;
use Magento\Framework\App\ObjectManager;
use Magento\Framework\View\ConfigInterface;
use Magento\InventorySalesApi\Api\Data\SalesChannelInterface;
use Magento\InventorySalesApi\Api\StockResolverInterface;

class Items extends \Magento\Wishlist\Block\Share\Email\Items
{
    /**
     * @var ItemResolverInterface
     */
    private $itemResolver;
    /**
     * @var string
     */
    protected $_template = 'Branch8_WishlistStockAlert::email/items.phtml';
    /**
     * @var \Magento\Catalog\Model\ProductFactory
     */
    private $wishlistItemCollectionFactory;

    /**
     * @param \Magento\Catalog\Block\Product\Context $context
     * @param \Magento\Framework\App\Http\Context $httpContext
     * @param \Magento\Wishlist\Model\ResourceModel\Item\CollectionFactory $wishlistItemCollectionFactory
     * @param StockResolverInterface $stockResolver
     * @param array $data
     * @param ConfigInterface|null $config
     * @param UrlBuilder|null $urlBuilder
     * @param ItemResolverInterface|null $itemResolver
     */
    public function __construct(
        \Magento\Catalog\Block\Product\Context                       $context,
        \Magento\Framework\App\Http\Context                          $httpContext,
        \Magento\Wishlist\Model\ResourceModel\Item\CollectionFactory $wishlistItemCollectionFactory,
        StockResolverInterface                                       $stockResolver,
        array                                                        $data = [],
        ConfigInterface                                              $config = null,
        UrlBuilder                                                   $urlBuilder = null,
        ItemResolverInterface                                        $itemResolver = null
    )
    {
        parent::__construct($context, $httpContext, $data, $config, $urlBuilder, $itemResolver);
        $this->itemResolver = $itemResolver ?? ObjectManager::getInstance()->get(ItemResolverInterface::class);
        $this->wishlistItemCollectionFactory = $wishlistItemCollectionFactory;
        $this->stockResolver = $stockResolver;
    }

    /**
     * @return int|null
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function getWishlistItemsCount()
    {
        if (empty($this->getData('item_ids'))) {
            return 0;
        }
        return count($this->getWishlistItems());
    }

    /**
     * @return \Magento\Catalog\Model\Product|\Magento\Wishlist\Model\ResourceModel\Item\Collection
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function getWishlistItems()
    {
        if ($this->_collection === null) {
            $this->_collection = $this->wishlistItemCollectionFactory->create();
            $this->_collection->addStoreFilter([$this->getData('store_id')]);
            $store = $this->_storeManager->getStore($this->getData('store_id'));
            $stock = $this->stockResolver->execute(SalesChannelInterface::TYPE_WEBSITE, $store->getWebsite()->getCode());
            if ($this->getData('item_ids')) {
                $this->_collection->addFieldToFilter('main_table.wishlist_item_id', ['in' => $this->getData('item_ids')]);
                $from = $this->_collection->getSelect()->getPart(\Zend_Db_Select::FROM);
                if (!isset($from['alert'])) {
                    $this->_collection->getSelect()->join(
                        ['alert' => 'branch8_wishlist_stock_alert'],
                        'alert.wishlist_item_id = main_table.wishlist_item_id'
                    );
                }
                $this->_collection->getSelect()->join(
                    ['stock' => 'branch8_options_stock_index'],
                    'alert.product_id=stock.product_id AND alert.combo=stock.combo',
                    []
                )->where('stock.stock_id = ?', $stock->getId());
            } else {
                $this->_collection->addFieldToFilter('main_table.wishlist_item_id', ['in' => 0]);
            }
        }
        return $this->_collection;
    }

    /**
     * @param $store
     * @return $this
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function setStore($store)
    {
        if ($store instanceof \Magento\Store\Model\Website) {
            $store = $store->getDefaultStore();
        }
        if (!$store instanceof \Magento\Store\Model\Store) {
            $store = $this->_storeManager->getStore($store);
        }

        $this->_store = $store;

        return $this;
    }

    /**
     * @return void
     */
    public function reset()
    {
        $this->_collection = null;
        $this->setData('store_id', null);
        $this->setData('item_ids', []);
        return;
    }
}
