<?php
declare(strict_types=1);

namespace Branch8\MarketPlaceParentOrderFrontendUi\Block\ParentOrder;

use Webkul\Marketplace\Model\OrdersFactory as MpOrderModel;
use Magento\Catalog\Model\Product\OptionFactory;
use Magento\Framework\App\ObjectManager;
use Magento\Framework\Registry;
use Magento\Framework\Stdlib\StringUtils;
use Magento\Framework\View\Element\AbstractBlock;
use Magento\Framework\View\Element\Template\Context;
use Magento\Sales\Block\Items\AbstractItems;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\ResourceModel\Order\Item\Collection;
use Magento\Sales\Model\ResourceModel\Order\Item\CollectionFactory;
use Magento\Theme\Block\Html\Pager;
use \Magento\Sales\Model\OrderRepository;
use Magento\Catalog\Helper\ImageFactory;
use Magento\Catalog\Api\ProductRepositoryInterface;

class Items extends AbstractItems
{
    /**
     * @var Registry
     */
    protected $_coreRegistry = null;

    /**
     * @var int
     */
    protected $itemsPerPage;

    /**
     * @var CollectionFactory
     */
    protected $itemCollectionFactory;

    /**
     * @var Collection|null
     */
    protected $itemCollection;

    /** @var \Magento\Sales\Model\OrderRepository */
    protected $_orderRepository;

    protected $profile;

    /**
     * Small image value.
     *
     * @var string
     */
    const SMALL_IMAGE = 'small_image';

    /**
     * @var ImageFactory
     */
    public $imageFactory;
    public $productRepository;

    /**
     * @var \Webkul\Marketplace\Helper\Data
     */
    protected $marketPlaceDataHelper;
    /**
     * @var \Branch8\SplitCart\Helper\Data
     */
    protected $splitCartHelper;

    protected $_storeManager;

    /**
     * @var OptionFactory
     */
    protected $_productOptionFactory;

    /**
     * Magento string lib
     *
     * @var StringUtils
     */
    protected $string;
    /**
     * @var
     */
    protected $orderFactory;

    protected $cachedSubOrders;

    /**
     * @param Context $context
     * @param Registry $registry
     * @param CollectionFactory|null $itemCollectionFactory
     * @param OrderRepository $_orderRepository
     * @param \Webkul\Marketplace\ViewModel\Profile $profile
     * @param ProductRepositoryInterface $productRepository
     * @param ImageFactory $imageFactory
     * @param \Webkul\Marketplace\Helper\Data $marketPlaceDataHelper
     * @param \Branch8\SplitCart\Helper\Data $splitCartHelper
     * @param \Magento\Store\Model\StoreManagerInterface $storemanager
     * @param OptionFactory $productOptionFactory
     * @param StringUtils $string
     * @param MpOrderModel|null $orderFactory
     */
    public function __construct(
        Context                                    $context,
        Registry                                   $registry,
        CollectionFactory                          $itemCollectionFactory = null,
        OrderRepository                            $_orderRepository,
        \Webkul\Marketplace\ViewModel\Profile      $profile,
        ProductRepositoryInterface                 $productRepository,
        ImageFactory                               $imageFactory,
        \Webkul\Marketplace\Helper\Data            $marketPlaceDataHelper,
        \Branch8\SplitCart\Helper\Data             $splitCartHelper,
        \Magento\Store\Model\StoreManagerInterface $storemanager,
        OptionFactory                              $productOptionFactory,
        StringUtils                                $string,
        \Webkul\Marketplace\Model\OrdersFactory    $orderFactory = null
    )
    {
        $this->string = $string;
        $this->_coreRegistry = $registry;
        $this->profile = $profile;
        $this->splitCartHelper = $splitCartHelper;
        $this->imageFactory = $imageFactory;
        $this->marketPlaceDataHelper = $marketPlaceDataHelper ?: \Magento\Framework\App\ObjectManager::getInstance()->create(\Webkul\Marketplace\Helper\Data::class);
        $this->itemCollectionFactory = $itemCollectionFactory ?: ObjectManager::getInstance()
            ->get(CollectionFactory::class);
        $this->orderFactory = $orderFactory ?: \Magento\Framework\App\ObjectManager::getInstance()->create(\Webkul\Marketplace\Model\OrdersFactory::class);;
        $this->_orderRepository = $_orderRepository;
        $this->productRepository = $productRepository;
        $this->_storeManager = $storemanager;
        $this->_productOptionFactory = $productOptionFactory;
        parent::__construct($context, []);
    }

    /**
     * Get profile
     *
     * @return \Webkul\Marketplace\ViewModel\Profile
     */
    public function getProfile()
    {
        return $this->profile;
    }

    /**
     * Init pager block and item collection with page size and current page number
     *
     * @return $this
     * @since 100.1.7
     */
    protected function _prepareLayout()
    {
        $this->itemsPerPage = $this->_scopeConfig->getValue('sales/orders/items_per_page');
        $this->itemCollection = $this->createItemsCollection();

        /** @var Pager $pagerBlock */
        $pagerBlock = $this->getChildBlock('sales_order_item_pager');
        if ($pagerBlock) {
            $this->preparePager($pagerBlock);
        }

        return parent::_prepareLayout();
    }

    /**
     * Determine if the pager should be displayed for order items list.
     *
     * To be called from templates(after _prepareLayout()).
     *
     * @return bool
     * @since 100.1.7
     */
    public function isPagerDisplayed()
    {
        $pagerBlock = $this->getChildBlock('sales_order_item_pager');
        return $pagerBlock && ($this->itemCollection->getSize() > $this->itemsPerPage);
    }

    /**
     * Get visible items for current page.
     *
     * To be called from templates(after _prepareLayout()).
     *
     * @return \Magento\Framework\DataObject[]
     * @since 100.1.7
     */
    public function getItems()
    {
        return $this->itemCollection->getItems();
    }

    /**
     * @return string
     */
    public function getPlaceHoderImage()
    {
        $url = $this->_assetRepo->getUrl('Magento_Catalog::images/product/placeholder/image.jpg');
        return $url;
    }

    /**
     * Get pager HTML according to our requirements.
     *
     * To be called from templates(after _prepareLayout()).
     *
     * @return string HTML output
     * @since 100.1.7
     */
    public function getPagerHtml()
    {
        /** @var Pager $pagerBlock */
        $pagerBlock = $this->getChildBlock('sales_order_item_pager');
        return $pagerBlock ? $pagerBlock->toHtml() : '';
    }

    /**
     * Retrieve current order model instance
     *
     * @return Order
     */
    public function getOrder()
    {
        return $this->_coreRegistry->registry('current_parent_order');
    }

    /**
     * Prepare pager block
     *
     * @param AbstractBlock $pagerBlock
     */
    private function preparePager(AbstractBlock $pagerBlock): void
    {
        $collectionToPager = $this->itemCollection;
        $collectionToPager->addFieldToFilter('main_table.parent_item_id', ['null' => true]);
        $pagerBlock->setLimit($this->itemsPerPage);
        $pagerBlock->setAvailableLimit([$this->itemsPerPage]);
        $pagerBlock->setCollection($collectionToPager);
        $pagerBlock->setShowAmounts($this->isPagerDisplayed());
    }


    public function getType($item)
    {
        $product = $item->getProduct();
        if ($item->getIsVirtual()) {
            return $this->splitCartHelper->getSimpleCartType($product, true);
        }
        return $this->splitCartHelper->getSimpleCartType($product);
    }

    /**
     * Create items collection
     *
     * @return Collection
     */
    protected function createItemsCollection(): Collection
    {
        $collection = $this->itemCollectionFactory->create();
        $collection->getSelect()
            ->join(
                ['so' => 'sales_order'],
                'main_table.order_id = so.entity_id',
                ['so.hotai_child_order_number']
            );

        $collection->addFieldToFilter(
            'main_table.order_id', ['in' => $this->getOrder()->getSuborderIds()]);
        // echo $collection->load()->getSelect();
        return $collection;
    }

    /**
     * getSubOrderById
     *
     * @param int $orderId
     * @return
     */
    public function getSubOrderById($orderId)
    {
        if (!isset($this->cachedSubOrders[$orderId]))
            $this->cachedSubOrders[$orderId] = $this->_orderRepository->get($orderId);
        return $this->cachedSubOrders[$orderId];
    }

    /**
     * @param $options
     *
     * @return array
     */
    public function getItemOptions($options)
    {
        $result = [];
        if ($options) {
            if (isset($options['options'])) {
                $result[] = $options['options'];
            }
            if (isset($options['additional_options'])) {
                $result[] = $options['additional_options'];
            }
            if (isset($options['attributes_info'])) {
                $result[] = $options['attributes_info'];
            }
        }
        return array_merge([], ...$result);
    }

    /**
     * @param $optionValue
     *
     * @return array
     */
    public function getFormatedOptionValue($optionValue)
    {
        $optionInfo = [];

        // define input data format
        if (is_array($optionValue)) {
            if (isset($optionValue['option_id'])) {
                $optionInfo = $optionValue;
                if (isset($optionInfo['value'])) {
                    $optionValue = $optionInfo['value'];
                }
            } elseif (isset($optionValue['value'])) {
                $optionValue = $optionValue['value'];
            }
        }

        // render customized option view
        if (isset($optionInfo['custom_view']) && $optionInfo['custom_view']) {
            $_default = ['value' => $optionValue];
            if (isset($optionInfo['option_type'])) {
                try {
                    $group = $this->_productOptionFactory->create()->groupFactory($optionInfo['option_type']);
                    return ['value' => $group->getCustomizedView($optionInfo)];
                } catch (\Exception $e) {
                    return $_default;
                }
            }
            return $_default;
        }

        // truncate standard view
        $result = [];
        if (is_array($optionValue)) {
            $truncatedValue = implode("\n", $optionValue);
            $truncatedValue = nl2br($truncatedValue);
            return ['value' => $truncatedValue];
        } else {
            $truncatedValue = $this->filterManager->truncate($optionValue, ['length' => 55, 'etc' => '']);
            $truncatedValue = nl2br($truncatedValue);
        }

        $result = ['value' => $truncatedValue];

        if ($this->string->strlen($optionValue) > 55) {
            $result['value'] = $result['value']
                . ' ...';
            $optionValue = nl2br($optionValue);
            $result = array_merge($result, ['full_view' => $optionValue]);
        }

        return $result;
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


    public function isParentOrderAutoCancelled()
    {
        $order = $this->getOrder();
        return $order->getStatus() === Order::STATE_CANCELED && (bool)$order->getData('is_auto_cancelled');
    }

    /**
     * @param $orderId
     * @return int
     */
    public function getSellerIdByOrder($orderId)
    {
        /**
         * @var $collection \Webkul\Marketplace\Model\ResourceModel\Orders\Collection
         */
        $collection = $this->orderFactory->create()->getCollection()
            ->addFieldToSelect('seller_id')
            ->addFieldToFilter(
                'order_id',
                ['eq' => $orderId]
            );
        $item = $collection->getFirstItem();
        if ($item) {
            return (int)$item->getData('seller_id');
        }
        return 0;
    }
}
