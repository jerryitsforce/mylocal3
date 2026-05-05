<?php
namespace Branch8\Brand\Plugin;

use Magento\Framework\Api\FilterBuilder;
use Magento\Framework\Api\Search\FilterGroupBuilder;
use Magento\Framework\Api\Search\SearchCriteriaBuilder;
use Magento\Framework\Module\Manager;
use Magento\LiveSearchAdapter\Model\QueryBuilder;
use Magento\LiveSearchAdapter\Model\SearchClient;

class ProductCount
{
    /**
     * @var null|array
     */
    private $productCount = null;

    /**
     * @var null|int
     */
    private $currentCategory = null;

    /**
     * @var \Magento\Store\Model\StoreManagerInterface
     */
    private $storeManager;

    /**
     * @var \Magento\Framework\Message\ManagerInterface
     */
    private $messageManager;

    /**
     * @var \Amasty\ShopbyBrand\Helper\Data
     */
    private $brandHelper;

    /**
     * @var \Magento\Catalog\Api\CategoryRepositoryInterface
     */
    private $categoryRepository;

    /**
     * @var \Magento\Catalog\Model\ResourceModel\Product\CollectionFactory
     */
    private $collectionFactory;

    /**
     * @var \Magento\Eav\Model\Config
     */
    private \Magento\Eav\Model\Config $eavConfig;

    /**
     * @var SearchCriteriaBuilder
     */
    private SearchCriteriaBuilder $searchCriteriaBuilder;

    /**
     * @var QueryBuilder
     */
    private QueryBuilder $queryBuilder;

    /**
     * @var SearchClient
     */
    private SearchClient $searchClient;

    /**
     * @var FilterBuilder
     */
    private FilterBuilder $filterBuilder;

    /**
     * @var FilterGroupBuilder
     */
    private FilterGroupBuilder $filterGroupBuilder;

    /**
     * @var Manager
     */
    private Manager $moduleManager;

    public function __construct(
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Magento\Catalog\Api\CategoryRepositoryInterface $categoryRepository,
        \Magento\Catalog\Model\ResourceModel\Product\CollectionFactory $collectionFactory,
        \Amasty\ShopbyBrand\Helper\Data $brandHelper,
        \Magento\Framework\Message\ManagerInterface $messageManager,
        \Magento\Eav\Model\Config $eavConfig,
        SearchCriteriaBuilder $searchCriteriaBuilder,
        FilterBuilder $filterBuilder,
        FilterGroupBuilder $filterGroupBuilder,
        QueryBuilder $queryBuilder,
        SearchClient $searchClient,
        Manager $moduleManager
    ) {
        $this->storeManager = $storeManager;
        $this->collectionFactory = $collectionFactory;
        $this->categoryRepository = $categoryRepository;
        $this->messageManager = $messageManager;
        $this->brandHelper = $brandHelper;
        $this->eavConfig = $eavConfig;
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;
        $this->filterBuilder = $filterBuilder;
        $this->filterGroupBuilder = $filterGroupBuilder;
        $this->queryBuilder = $queryBuilder;
        $this->searchClient = $searchClient;
        $this->moduleManager = $moduleManager;
    }

    /**
     * Get brand product count
     *
     * @param int $optionId
     * @return int
     */
    public function aroundGet($subject, \Closure $proceed, $optionId)
    {
        $rootCategoryId = $this->storeManager->getStore()->getRootCategoryId();
        if ($this->productCount === null || $rootCategoryId != $this->currentCategory) {
            $attrCode = $this->brandHelper->getBrandAttributeCode();
            $this->currentCategory = $rootCategoryId;

            try {
                $this->productCount = $this->loadProductCount($attrCode);
            } catch (\Magento\Framework\Exception\StateException $e) {
                if (!$this->messageManager->hasMessages()) {
                    $this->messageManager->addErrorMessage(
                        __('Make sure that the root category for current store is anchored')
                    )->addErrorMessage(
                        __('Make sure that "%1" attribute can be used in layered navigation', $attrCode)
                    );
                }
                $this->productCount = [];
            }
        }

        return isset($this->productCount[$optionId]) ? (int) $this->productCount[$optionId]['count'] : 0;
    }

    /**
     * @param string $attrCode
     *
     * @return array
     */
    private function loadProductCount($attrCode)
    {
        $result = [];
        try {
            $rootCategoryId = $this->storeManager->getStore()->getRootCategoryId();
            if ($this->moduleManager->isEnabled('Magento_LiveSearch')) {
                $attribute = $this->eavConfig->getAttribute('catalog_product', $attrCode);
                $options = $attribute->getSource()->getAllOptions();
                $dataOption = [];
                foreach ($options as $option) {
                    $dataOption[$option['value']] = $option['label'];
                }
                $attr1 = $this->filterBuilder->setField('category_id')
                    ->setValue($rootCategoryId)
                    ->setConditionType('in')
                    ->create();
                $filter1 = $this->filterGroupBuilder
                    ->addFilter($attr1)
                    ->create();
                $searchCriteria = $this->searchCriteriaBuilder->create();
                $searchCriteria->setFilterGroups([$filter1]);
                $searchCriteria->setRequestName('catalog_view_container');
                $searchCriteria->setPageSize(1);
                $searchCriteria->setCurrentPage(1);
                $body = $this->queryBuilder->build($searchCriteria);
                $dataReturn = $this->searchClient->request($body);
                if (!empty($dataReturn['data']['productSearch']['facets'])) {
                    foreach ($dataReturn['data']['productSearch']['facets'] as $facet) {
                        if ($facet['attribute'] == $attrCode) {
                            foreach ($facet['buckets'] as $value) {
                                if (in_array($value['title'], $dataOption)) {
                                    $key = array_search($value['title'], $dataOption);
                                    $result[$key]['count'] = $value['count'];
                                }
                            }
                        }
                    }
                }
            } else {
                $category = $this->categoryRepository->get($rootCategoryId);
                /** @var \Magento\CatalogSearch\Model\ResourceModel\Fulltext\Collection $collection**/
                $collection = $this->collectionFactory->create();

                $result = $collection->addAttributeToSelect($attrCode)
                    ->setVisibility([2,4])
                    ->addCategoryFilter($category)
                    ->getFacetedData($attrCode);
            }
        } catch (\Exception $e) {
            // do nothing
        }
        return $result;
    }
}
