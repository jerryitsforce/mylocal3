<?php

namespace Branch8\MarketPlaceParentOrderFrontendUi\Block\ParentOrder;
use Branch8\MarketPlaceParentOrder\Model\ParentOrder;
use Magento\Catalog\Pricing\Price\FinalPrice;
use Magento\Framework\Pricing\Render;
use Magento\Framework\App\ActionInterface;
use Magento\Framework\Registry;

class ProductList extends \Magento\Catalog\Block\Product\AbstractProduct
{
    /**
     * @var \Magento\Framework\Url\Helper\Data
     */
    protected $urlHelper;

    /**
     * @var \Magento\Catalog\Model\ResourceModel\Product\CollectionFactory
     */
    protected $_productCollectionFactory;

    /**
     * @var \Magento\Quote\Model\QuoteFactory
     */
    protected $quoteFactory;
    /**
     * @var Registry
     */
    protected $registry;

    /**
     * @param  \Magento\Catalog\Block\Product\Context  $context
     * @param  \Magento\Framework\Url\Helper\Data  $urlHelper
     * @param  \Magento\Catalog\Model\ResourceModel\Product\CollectionFactory  $productCollectionFactory
     * @param  \Magento\Framework\Data\Form\FormKey  $formKey
     * @param  \Magento\Quote\Model\QuoteFactory  $quoteFactory
     * @param  array  $data
     */
    public function __construct(
        \Magento\Catalog\Block\Product\Context $context,
        \Magento\Framework\Url\Helper\Data $urlHelper,
        \Magento\Catalog\Model\ResourceModel\Product\CollectionFactory $productCollectionFactory,
        \Magento\Framework\Data\Form\FormKey $formKey,
        \Magento\Quote\Model\QuoteFactory $quoteFactory,
        Registry                       $registry,
        array $data = []
    ) {
        $this->quoteFactory = $quoteFactory;
        $this->urlHelper = $urlHelper;
        $this->_productCollectionFactory = $productCollectionFactory;
        $this->formKey = $formKey;
        $this->registry = $registry;
        parent::__construct($context, $data);
    }
    /**
     * Get form key
     *
     * @return string
     */
    public function getFormKey()
    {
        return $this->formKey->getFormKey();
    }

    /**
     * @return ParentOrder
     */
    public function getParentOrder()
    {
        return $this->registry->registry('current_parent_order');
    }

    /**
     * @return \Magento\Catalog\Model\ResourceModel\Product\Collection
     */
    public function getLoadProducts()
    {
        $productIds = $this->getCartData();
        return $this->_productCollectionFactory->create()
            ->addAttributeToSelect(["name", "image"])
            ->addAttributeToFilter("entity_id", ['in' => $productIds]);
    }

    /**
     * @return array
     */
    public function getCartData()
    {
        $productIds = [];
        $quote = $this->quoteFactory->create()->loadByCustomer($this->getParentOrder()->getDetail()->getCustomerId());
        foreach ($quote->getAllVisibleItems() as $item){
            $productIds [] = $item->getProductId();
        }
        return $productIds;
    }

    /**
     * @param $productId
     *
     * @return string
     */
    public function getImageUrl($product)
    {
        $url = false;
        $attribute = $product->getResource()->getAttribute('image');
        if (!$product->getImage()) {
            $url = $this->_assetRepo->getUrl('Magento_Catalog::images/product/placeholder/image.jpg');
        } elseif ($attribute) {
            $url = $attribute->getFrontend()->getUrl($product);
        }
        return $url;
    }
}
