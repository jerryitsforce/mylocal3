<?php
namespace Branch8\SplitCart\Block\Cart;

/**
 * Block on checkout/cart/index page to display a pager on the  cart items grid
 */
class Grid extends \Magento\Checkout\Block\Cart\Grid
{
    /**
     * @var \Webkul\MarketplacePreorder\Helper\Data
     */
    protected $wkPreorderHelper;

    /**
     * @var \Webkul\Marketplace\Helper\Data
     */
    protected $marketPlaceDataHelper;
    /**
     * @var \Branch8\SplitCart\Helper\Data
     */
    protected $splitCartHelper;

    protected $flagshipSalesHelper;

    protected $sellerIds = [];


    /**
     * Grid constructor.
     * @param \Magento\Framework\View\Element\Template\Context $context
     * @param \Magento\Customer\Model\Session $customerSession
     * @param \Magento\Checkout\Model\Session $checkoutSession
     * @param \Magento\Catalog\Model\ResourceModel\Url $catalogUrlBuilder
     * @param \Magento\Checkout\Helper\Cart $cartHelper
     * @param \Magento\Framework\App\Http\Context $httpContext
     * @param \Magento\Quote\Model\ResourceModel\Quote\Item\CollectionFactory $itemCollectionFactory
     * @param \Magento\Framework\Api\ExtensionAttribute\JoinProcessorInterface $joinAttributeProcessor
     * @param \Webkul\MarketplacePreorder\Helper\Data $wkPreorderHelper
     * @param \Webkul\Marketplace\Helper\Data $marketPlaceDataHelper
     * @param \Branch8\SplitCart\Helper\Data $splitCartHelper
     * @param array $data
     */
    public function __construct(
        \Magento\Framework\View\Element\Template\Context $context,
        \Magento\Customer\Model\Session $customerSession,
        \Magento\Checkout\Model\Session $checkoutSession,
        \Magento\Catalog\Model\ResourceModel\Url $catalogUrlBuilder,
        \Magento\Checkout\Helper\Cart $cartHelper,
        \Magento\Framework\App\Http\Context $httpContext,
        \Magento\Quote\Model\ResourceModel\Quote\Item\CollectionFactory $itemCollectionFactory,
        \Magento\Framework\Api\ExtensionAttribute\JoinProcessorInterface $joinAttributeProcessor,
        \Webkul\MarketplacePreorder\Helper\Data $wkPreorderHelper,
        \Webkul\Marketplace\Helper\Data $marketPlaceDataHelper,
        \Branch8\SplitCart\Helper\Data $splitCartHelper,
        \Branch8\FlagshipStore\Helper\Sales $flagshipSalesHelper,
        array $data = []
    ) {
        parent::__construct(
            $context,
            $customerSession,
            $checkoutSession,
            $catalogUrlBuilder,
            $cartHelper,
            $httpContext,
            $itemCollectionFactory,
            $joinAttributeProcessor,
            $data
        );
        $this->splitCartHelper = $splitCartHelper;
        $this->wkPreorderHelper = $wkPreorderHelper ?: \Magento\Framework\App\ObjectManager::getInstance()->create(\Webkul\MarketplacePreorder\Helper\Data::class);
        $this->marketPlaceDataHelper = $marketPlaceDataHelper?: \Magento\Framework\App\ObjectManager::getInstance()->create(\Webkul\Marketplace\Helper\Data::class);
        $this->flagshipSalesHelper = $flagshipSalesHelper;
    }

     /**
     * Get group cart items by seller
     *
     * @return array
     */
    public function getGroupItemsFollowSeller()
    {
        foreach($this->getItems() as $item) {
            $items[$item->getId()] = $item;

        }
        return $this->splitCartHelper->splitCartToSubCart($items);

    }
}
