<?php
namespace Branch8\Catalog\ViewModel\Product;

use Magento\Catalog\Helper\Data;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Escaper;
use Magento\Framework\Serialize\Serializer\JsonHexTag;
use Magento\Store\Model\StoreManagerInterface;

class Breadcrumbs extends \Magento\Catalog\ViewModel\Product\Breadcrumbs
{
    protected $_escaper;
    protected $_jsonSerializer;
    protected $_catalogData;
    private StoreManagerInterface $storeManager;


    public function __construct(
        Data $catalogData,
        ScopeConfigInterface $scopeConfig,
        Escaper $escaper,
        JsonHexTag $jsonSerializer,
        StoreManagerInterface $storeManager
    ) {
        $this->_escaper = $escaper;
        $this->_jsonSerializer = $jsonSerializer;
        $this->_catalogData = $catalogData;
        $this->storeManager = $storeManager;
        parent::__construct($catalogData, $scopeConfig, $escaper, $jsonSerializer);
    }

    /**
     * Returns breadcrumb json with html escaped names
     *
     * @return string
     */
    public function getJsonConfigurationHtmlEscaped(): string
    {
        return $this->_jsonSerializer->serialize(
            [
                'breadcrumbs' => [
                    'categoryUrlSuffix' => $this->_escaper->escapeHtml($this->getCategoryUrlSuffix()),
                    'useCategoryPathInUrl' => (int)$this->isCategoryUsedInProductUrl(),
                    'product' => $this->_escaper->escapeHtml($this->getProductName()),
                    'defaultCategoryCrumbs' => $this->getDefaultBreadcrumbs()
                ]
            ]
        );
    }

    /**
     * Returns product name.
     *
     * @return string
     */
    public function getProductName(): string
    {
        return $this->_catalogData->getProduct() !== null
            ? $this->_catalogData->getProduct()->getName() ?? ''
            : '';
    }

    public function getDefaultBreadcrumbs(): array
    {
        $crumbs = [];

        $product = $this->_catalogData->getProduct();
        $categoryCollection = clone $product->getCategoryCollection();
        if($product->getMainCategory()) {
            $categoryCollection->addAttributeToFilter('entity_id', $product->getMainCategory())
                ->setPageSize(1);
        } else {
            $categoryCollection->clear()
                ->addAttributeToSort('level', $categoryCollection::SORT_ORDER_DESC)
                ->addAttributeToFilter('path', ['like' => "1/" . $this->getRootCategoryId() . "/%"])
                ->setPageSize(1);
        }


        $breadcrumbCategories = $categoryCollection->getFirstItem()->getParentCategories();
        usort($breadcrumbCategories, function ($a, $b) {
            return strcmp($a->getLevel(), $b->getLevel());
        });


        foreach ($breadcrumbCategories as $category) {
            $crumbs[] = [
                'name' => 'category',
                'label' => $category->getName(),
                'title' => '',
                'link'  => $category->getUrl()
            ];
        }

        return $crumbs;
    }
}
