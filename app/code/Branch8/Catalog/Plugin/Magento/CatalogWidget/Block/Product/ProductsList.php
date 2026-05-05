<?php
namespace Branch8\Catalog\Plugin\Magento\CatalogWidget\Block\Product;

use Magento\CatalogWidget\Block\Product\ProductsList as CoreProductsList;
use Magento\Framework\App\Http\Context as HttpContext;
use Magento\Framework\App\ObjectManager;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Pricing\PriceCurrencyInterface;
use Magento\Framework\Serialize\Serializer\Json;
use Magento\Framework\View\DesignInterface;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Catalog\Model\ResourceModel\Product\Collection;

class ProductsList
{
    /**
     * Store manager
     *
     * @var StoreManagerInterface
     */
    protected $_storeManager;

    /**
     * @var DesignInterface
     */
    protected $_design;

    /**
     * @var HttpContext
     */
    protected $httpContext;

    /**
     * @var PriceCurrencyInterface
     */
    private $priceCurrency;

    /**
     * Json Serializer Instance
     *
     * @var Json
     */
    private $json;

    /**
     * ProductsListPlugin constructor.
     *
     * @param StoreManagerInterface $storeManager
     * @param DesignInterface $design
     * @param HttpContext $httpContext
     * @param Json|null $json
     */
    public function __construct(
        StoreManagerInterface $storeManager,
        DesignInterface $design,
        HttpContext $httpContext,
        Json $json = null
    ){
        $this->_storeManager = $storeManager;
        $this->_design = $design;
        $this->httpContext = $httpContext;
        $this->json = $json ?: ObjectManager::getInstance()->get(Json::class);
    }

    /**
     * @param CoreProductsList $subject
     * @param \Closure $proceed
     * @return array
     * @throws NoSuchEntityException
     */
    public function aroundGetCacheKeyInfo(CoreProductsList $subject, \Closure $proceed): array
    {
        $conditions = $subject->getData('conditions')
            ? $subject->getData('conditions')
            : $subject->getData('conditions_encoded');
        $requestUri = $subject->getRequest()->getRequestUri();
        $isAdmin = false;
        if (str_contains($requestUri, 'admin/pagebuilder/stage/preview') || str_contains($requestUri, 'htposcms/pagebuilder/stage/preview')) {
            $isAdmin = true;
        }

        return [
            'CATALOG_PRODUCTS_LIST_WIDGET',
            $this->getPriceCurrency()->getCurrency()->getCode(),
            $this->_storeManager->getStore()->getId(),
            $this->_design->getDesignTheme()->getId(),
            $this->httpContext->getValue(\Magento\Customer\Model\Context::CONTEXT_GROUP),
            $this->json->serialize($this->httpContext->getValue('tax_rates')),
            (int)$subject->getRequest()->getParam($subject->getData('page_var_name'), 1),
            $subject->getProductsPerPage(),
            $subject->getProductsCount(),
            $conditions,
            (int)$subject->getRequest()->getParam('isAjax', 0),
            $isAdmin,
            $subject->getTemplate(),
            $subject->getTitle()
        ];
    }

    /**
     * Get currency of product
     *
     * @return PriceCurrencyInterface
     * @deprecated
     * @see Constructor injection
     */
    private function getPriceCurrency()
    {
        if ($this->priceCurrency === null) {
            $this->priceCurrency = ObjectManager::getInstance()
                ->get(PriceCurrencyInterface::class);
        }
        return $this->priceCurrency;
    }

    /**
     * Add custom filters to the product collection
     *
     * @param CoreProductsList $subject
     * @param Collection $result
     * @return Collection
     */
    public function afterCreateCollection(CoreProductsList $subject, Collection $result)
    {
        $result->addAttributeToFilter(
            array(
                array('attribute' => 'hide_product_on_search', 'null' => true),
                array('attribute' => 'hide_product_on_search', 'eq' => 0),
            ),
            '',
            'left'
        );
        $result->addAttributeToSelect(['livesearch_instock', 'index_stock_status', 'limit_purchased_enable', 'limit_purchased_customer_group', 'limit_purchased_qty', 'limit_purchased_start_time', 'limit_purchased_end_time']);
        return $result;
    }
}
