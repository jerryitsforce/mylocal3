<?php

declare(strict_types=1);

namespace Branch8\MarketplaceProduct\Block\Product\ProductList;

use Branch8\MarketplaceProduct\Model\Product\BuildProductLinks;
use Magento\Catalog\Block\Product\Context;
use Magento\Catalog\Model\Product\Visibility as ProductVisibility;
use Magento\Catalog\Model\ProductLink\Link;
use Magento\Catalog\Model\ResourceModel\Product\CollectionFactory as ProductCollectionFactory;
use Magento\Checkout\Model\ResourceModel\Cart as CartResourceModel;
use Magento\Checkout\Model\Session as CheckoutSession;
use Magento\Framework\Module\Manager as ModuleManager;

/**
 * Catalog product related items block.
 *
 * @method $this setCacheKey(string $cacheKey)
 */
class Related extends \Magento\Catalog\Block\Product\ProductList\Related
{
    /**
     * @var ProductCollectionFactory
     */
    private ProductCollectionFactory $productCollectionFactory;

    /**
     * Related constructor.
     *
     * @param Context $context
     * @param CartResourceModel $checkoutCart
     * @param ProductVisibility $catalogProductVisibility
     * @param CheckoutSession $checkoutSession
     * @param ModuleManager $moduleManager
     * @param ProductCollectionFactory $productCollectionFactory
     * @param array $data
     */
    public function __construct(
        Context                  $context,
        CartResourceModel        $checkoutCart,
        ProductVisibility        $catalogProductVisibility,
        CheckoutSession          $checkoutSession,
        ModuleManager            $moduleManager,
        ProductCollectionFactory $productCollectionFactory,
        array                    $data = []
    ) {
        parent::__construct(
            $context,
            $checkoutCart,
            $catalogProductVisibility,
            $checkoutSession,
            $moduleManager,
            $data
        );
        $this->productCollectionFactory = $productCollectionFactory;
    }

    /**
     * @inheritdoc
     */
    protected function _construct(): void
    {
        $this->setCacheKey('branch8_marketplace_product_related' . $this->getProduct()->getId());
        parent::_construct();
    }

    /**
     * @inheritdoc
     */
    protected function _prepareData(): self
    {
        $product = $this->getProduct();
        $this->_itemCollection = $this->productCollectionFactory->create();

        $relatedSkus = $product->getData(BuildProductLinks::KEY_RELATED_TYPE);
        if (!empty($relatedSkus)) {
            $this->_itemCollection->addAttributeToFilter(Link::KEY_SKU, $relatedSkus);
        } else {
            $this->_itemCollection->addIdFilter(BuildProductLinks::KEY_RELATED_TYPE);
        }
        $this->_itemCollection->addStoreFilter();

        if ($this->moduleManager->isEnabled('Magento_Checkout')) {
            $this->_addProductAttributesAndPrices($this->_itemCollection);
        }
        $this->_itemCollection->setVisibility($this->_catalogProductVisibility->getVisibleInCatalogIds());

        $this->_itemCollection->load();

        foreach ($this->_itemCollection as $product) {
            $product->setDoNotUseCategoryId(true);
        }

        return $this;
    }
}
