<?php

namespace Branch8\CatalogCustom\Block\Widget;

use Branch8\GA4\Helper\ProductHelper;
use Magento\Catalog\Block\Product\ImageBuilder;
use Magento\Catalog\Model\Product;
use Magento\Catalog\Model\Product\Attribute\Source\Status;
use Magento\Catalog\Pricing\Price\FinalPrice;
use Magento\Framework\App\ActionInterface;
use Magento\Framework\Url\EncoderInterface;
use Magento\Framework\View\Element\Template;
use Magento\Framework\View\LayoutFactory;
use Magento\Framework\View\LayoutInterface;
use Magento\Widget\Block\BlockInterface;
use Magento\Cms\Model\Template\FilterProvider;

class CategoryList extends Template implements BlockInterface
{
    /**
     * @var \Magento\Store\Model\StoreManagerInterface
     */
    protected $_storeManager;
    /**
     * @var \Magento\Catalog\Model\CategoryRepository
     */
    protected $categoryRepository;

    /**
     * @var ImageBuilder
     * @since 102.0.0
     */
    protected $imageBuilder;


    /**
     * @var LayoutFactory
     */
    private $layoutFactory;

    /**
     * @var RendererList
     */
    private $rendererListBlock;

    /**
     * @var \Magento\Catalog\Model\ResourceModel\Product\CollectionFactory
     */
    protected $_productCollectionFactory;

    /**
     * @var EncoderInterface|null
     */
    protected $urlEncoder;

    /**
     * @var \Magento\Checkout\Helper\Cart
     */
    protected $_cartHelper;

    /**
     * @var \Magento\Framework\Registry
     */
    protected $registry;

    /**
     * @var FilterProvider
     */
    private $filterProvider;
    private ProductHelper $productHelper;
    /**
     * @param  Template\Context  $context
     * @param  \Magento\Store\Model\StoreManagerInterface  $storeManager
     * @param  \Magento\Catalog\Model\CategoryRepository  $categoryRepository
     * @param  ImageBuilder  $imageBuilder
     * @param  LayoutFactory  $layoutFactory
     * @param  \Magento\Catalog\Model\ResourceModel\Product\CollectionFactory  $productCollectionFactory
     * @param  EncoderInterface  $urlEncoder
     * @param  \Magento\Checkout\Helper\Cart  $cartHelper
     * @param  \Magento\Framework\Registry  $registry
     * @param  FilterProvider  $filterProvider
     * @param  array  $data
     */
    public function __construct(
        \Magento\Framework\View\Element\Template\Context $context,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Magento\Catalog\Model\CategoryRepository $categoryRepository,
        \Magento\Catalog\Block\Product\ImageBuilder $imageBuilder,
        \Magento\Framework\View\LayoutFactory $layoutFactory,
        \Magento\Catalog\Model\ResourceModel\Product\CollectionFactory $productCollectionFactory,
        EncoderInterface $urlEncoder,
        \Magento\Checkout\Helper\Cart $cartHelper,
        \Magento\Framework\Registry $registry,
        FilterProvider $filterProvider,
        ProductHelper $productHelper,
        array $data = []
    ) {
        $this->_storeManager = $storeManager;
        $this->categoryRepository = $categoryRepository;
        $this->imageBuilder = $imageBuilder;
        $this->layoutFactory = $layoutFactory;
        $this->_productCollectionFactory = $productCollectionFactory;
        $this->urlEncoder = $urlEncoder;
        $this->_cartHelper = $cartHelper;
        $this->registry = $registry;
        $this->filterProvider = $filterProvider;
        $this->productHelper = $productHelper;
        parent::__construct($context, $data);
    }

    /**
     * @return void
     */
    protected function _construct()
    {
        parent::_construct();
        $this->setTemplate('Branch8_CatalogCustom::widget/category_list.phtml');
    }

    /**
     * @return array|false
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function getCategoryList()
    {
        $catData = $this->getSelectedCategory();
        if ($catData) {
            return $catData->getChildrenCategories();
        }
        return false;
    }

    /**
     * @param int $categoryId
     * @return array|false
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function getCategoryProductsData($categoryId)
    {
        $category = $this->getCategoryData($categoryId);
        if ($category) {
            $maxProducts = $this->getMaxProducts();
            $productCollection = $category->getProductCollection()
                ->addAttributeToSelect('*')
                ->addAttributeToFilter('status', Status::STATUS_ENABLED)
                ->addAttributeToFilter(
                    array(
                        array('attribute' => 'hide_product_on_search', 'null' => true),
                        array('attribute' => 'hide_product_on_search', 'eq' => 0),
                    ),
                    '',
                    'left'
                )
                ->setPageSize($maxProducts);

            return [
                'category' => $category,
                'productCollection' => $productCollection->getItems()
            ];
        }
        return false;
    }

    /**
     * @return string
     */
    public function getAjaxUrl()
    {
        return $this->getUrl('catalogcustom/ajax/categoryProducts');
    }

    /**
     * @return array|false
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function getChildCategory()
    {
        $catData = $this->getSelectedCategory();
        if ($catData) {
            $childrenCategories = $catData->getChildrenCategories();
            $categoryProductData = [];
            $maxProducts = $this->getMaxProducts();

            foreach ($childrenCategories as $category) {
                $productCollection = $category->getProductCollection()
                    ->addAttributeToSelect('*')
                    ->addAttributeToFilter('status', Status::STATUS_ENABLED)
                    ->addAttributeToFilter(
                        array(
                            array('attribute' => 'hide_product_on_search', 'null' => true),
                            array('attribute' => 'hide_product_on_search', 'eq' => 0),
                        ),
                        '',
                        'left'
                    )
                    ->setPageSize($maxProducts);
                $categoryProductData[] = [
                    'category' => $this->getCategoryData($category->getId()),
                    'productCollection' => $productCollection->getItems()
                ];
            }

            return $categoryProductData;
        }
        return false;
    }

    /**
     * Get maximum number of products to display
     *
     * @return int
     */
    public function getMaxProducts()
    {
        $maxProducts = $this->getData('max_product');
        return $maxProducts ? (int)$maxProducts : 20;
    }


    /**
     * @param $ids
     *
     * @return mixed
     */
    public function getProductCollectionByCategories($ids)
    {
        $collection = $this->_productCollectionFactory->create();
        $collection->addAttributeToSelect('*');
        $collection->addStoreFilter();
        $collection->addAttributeToFilter('status', Status::STATUS_ENABLED);
        $collection->addAttributeToFilter(
            array(
                array('attribute' => 'hide_product_on_search', 'null' => true),
                array('attribute' => 'hide_product_on_search', 'eq' => 0),
            ),
            '',
            'left'
        );
        $collection->addCategoriesFilter(['in' => $ids]);
        return $collection;
    }


    /**
     * @return false|\Magento\Catalog\Api\Data\CategoryInterface|mixed|null
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function getSelectedCategory()
    {
        if (!$this->getData('id_path')) {
            if ($this->getCurrentCategory()) {
                return $this->getCurrentCategory();
            }
            return false;
        }
        $rewriteData = $this->parseIdPath($this->getData('id_path'));

        if (isset($rewriteData[1])) {
            return $this->categoryRepository->get(
                $rewriteData[1],
                $this->_storeManager->getStore()->getId()
            );
        }
        return false;
    }

    /**
     * @return mixed|null
     */
    public function getCurrentCategory()
    {
        return $this->registry->registry('current_category');
    }

    /**
     * @param $id
     *
     * @return \Magento\Catalog\Api\Data\CategoryInterface|mixed|null
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function getCategoryData($id)
    {
        return $this->categoryRepository->get(
            $id,
            $this->_storeManager->getStore()->getId()
        );
    }

    /**
     * @param $idPath
     *
     * @return string[]
     */
    protected function parseIdPath($idPath)
    {
        $rewriteData = explode('/', $idPath);

        if (!isset($rewriteData[0]) || !isset($rewriteData[1])) {
            throw new \RuntimeException('Wrong id_path structure.');
        }
        return $rewriteData;
    }

    /**
     * @param  Product  $product
     * @param $priceType
     * @param $renderZone
     * @param  array  $arguments
     *
     * @return string
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function getProductPriceHtml(
        Product $product,
        $priceType = null,
        $renderZone = \Magento\Framework\Pricing\Render::ZONE_ITEM_LIST,
        array $arguments = []
    ) {
        if (!isset($arguments['zone'])) {
            $arguments['zone'] = $renderZone;
        }
        $arguments['price_id'] = isset($arguments['price_id'])
            ? $arguments['price_id']
            : 'old-price-' . $product->getId() . '-' . $priceType;
        $arguments['include_container'] = isset($arguments['include_container'])
            ? $arguments['include_container']
            : true;
        $arguments['display_minimal_price'] = isset($arguments['display_minimal_price'])
            ? $arguments['display_minimal_price']
            : true;

        /** @var \Magento\Framework\Pricing\Render $priceRender */
        $priceRender = $this->getLayout()->getBlock('product.price.render.default');
        if (!$priceRender) {
            $priceRender = $this->getLayout()->createBlock(
                \Magento\Framework\Pricing\Render::class,
                'product.price.render.default',
                ['data' => ['price_render_handle' => 'catalog_product_prices']]
            );
        }

        return $priceRender->render(
            FinalPrice::PRICE_CODE,
            $product,
            $arguments
        );
    }

    /**
     * @param  Product  $product
     *
     * @return string
     */
    public function getProductDetailsHtml(\Magento\Catalog\Model\Product $product)
    {
        $renderer = $this->getDetailsRenderer($product->getTypeId());
        if ($renderer) {
            $renderer->setProduct($product);
            return $renderer->toHtml();
        }
        return '';
    }

    /**
     * Get the renderer that will be used to render the details block
     *
     * @param string|null $type
     * @return bool|\Magento\Framework\View\Element\AbstractBlock
     */
    public function getDetailsRenderer($type = null)
    {
        if ($type === null) {
            $type = 'default';
        }
        $rendererList = $this->getDetailsRendererList();
        if ($rendererList) {
            return $rendererList->getRenderer($type, 'default');
        }
        return null;
    }

    /**
     * @return bool|RendererList|\Magento\Framework\View\Element\BlockInterface
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    protected function getDetailsRendererList()
    {
        if (empty($this->rendererListBlock)) {
            /** @var $layout LayoutInterface */
            $layout = $this->layoutFactory->create(['cacheable' => false]);
            $layout->getUpdate()->addHandle('catalog_widget_product_list')->load();
            $layout->generateXml();
            $layout->generateElements();

            $this->rendererListBlock = $layout->getBlock('category.product.type.widget.details.renderers');
        }
        return $this->rendererListBlock;
    }

    /**
     * Retrieve Product URL using UrlDataObject
     *
     * @param \Magento\Catalog\Model\Product $product
     * @param array $additional the route params
     * @return string
     */
    public function getProductUrl($product, $additional = [])
    {
        if ($this->hasProductUrl($product)) {
            if (!isset($additional['_escape'])) {
                $additional['_escape'] = true;
            }
            return $product->getUrlModel()->getUrl($product, $additional);
        }

        return '#';
    }

    /**
     * @param $product
     *
     * @return bool
     */
    public function hasProductUrl($product)
    {
        if ($product->getVisibleInSiteVisibilities()) {
            return true;
        }
        if ($product->hasUrlDataObject()) {
            if (in_array($product->hasUrlDataObject()->getVisibility(), $product->getVisibleInSiteVisibilities())) {
                return true;
            }
        }

        return false;
    }

    /**
     * Get post parameters.
     *
     * @param Product $product
     * @return array
     */
    public function getAddToCartPostParams(Product $product)
    {
        $url = $this->getAddToCartUrl($product);
        return [
            'action' => $url,
            'data' => [
                'product' => $product->getEntityId(),
                ActionInterface::PARAM_NAME_URL_ENCODED => $this->urlEncoder->encode($url),
            ]
        ];
    }

    /**
     * @inheritdoc
     */
    public function getAddToCartUrl($product, $additional = [])
    {
        $requestingPageUrl = $this->getRequest()->getParam('requesting_page_url');

        if (!empty($requestingPageUrl)) {
            $additional['useUencPlaceholder'] = true;
            $url = $this->getAddToCartUrlParent($product, $additional);
            return str_replace('%25uenc%25', $this->urlEncoder->encode($requestingPageUrl), $url);
        }

        return $this->getAddToCartUrlParent($product, $additional);
    }

    /**
     * Retrieve url for add product to cart
     *
     * Will return product view page URL if product has required options
     *
     * @param \Magento\Catalog\Model\Product $product
     * @param array $additional
     * @return string
     */
    public function getAddToCartUrlParent($product, $additional = [])
    {
        if (!$product->getTypeInstance()->isPossibleBuyFromList($product)) {
            if (!isset($additional['_escape'])) {
                $additional['_escape'] = true;
            }
            if (!isset($additional['_query'])) {
                $additional['_query'] = [];
            }
            $additional['_query']['options'] = 'cart';

            return $this->getProductUrl($product, $additional);
        }
        return $this->_cartHelper->getAddUrl($product, $additional);
    }

    /**
     * @param $product
     * @param $imageId
     * @param $attributes
     *
     * @return \Magento\Catalog\Block\Product\Image
     */
    public function getImage($product, $imageId, $attributes = [])
    {
        return $this->imageBuilder->create($product, $imageId, $attributes);
    }

    public function getIsShowInformation()
    {
        return $this->getData('show_full_info');
    }

    /**
     * @param $description
     *
     * @return string
     * @throws \Exception
     */
    public function getDescription($description)
    {
        return $this->filterProvider->getBlockFilter()->filter($description);
    }


    public function getGA4ItemJson($_item, $index = 1)
    {
        return $this->productHelper->getGA4ItemJson($_item, $index);
    }
}
