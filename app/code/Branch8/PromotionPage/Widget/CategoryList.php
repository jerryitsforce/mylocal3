<?php

namespace Branch8\PromotionPage\Widget;

use Magento\Catalog\Block\Product\Widget\Html\Pager;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\View\Element\Template;
use Magento\Widget\Block\BlockInterface;

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
     * @var \Magento\Cms\Model\Template\FilterProvider
     */
    protected $filterProvider;
    /**
     * @var ScopeConfigInterface
     */
    protected $scopeConfig;
    /**
     * @var
     */
    protected $pager;
    /**
     * @var \Magento\Catalog\Model\ResourceModel\Category\CollectionFactory
     */
    protected $categoryCollectionFactory;

    protected $totalPage = 0;

    protected $collection = null;

    const DEFAULT_PAGE_SIZE = 9;
    const PAGE_VAR_NAME = 'event-page';

    /**
     * @param  Template\Context  $context
     * @param  \Magento\Store\Model\StoreManagerInterface  $storeManager
     * @param  \Magento\Catalog\Model\CategoryRepository  $categoryRepository
     * @param  \Magento\Cms\Model\Template\FilterProvider  $filterProvider
     * @param  ScopeConfigInterface  $scopeConfig
     * @param  \Magento\Catalog\Model\ResourceModel\Category\CollectionFactory  $categoryCollectionFactory
     * @param  array  $data
     */
    public function __construct(
        \Magento\Framework\View\Element\Template\Context $context,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Magento\Catalog\Model\CategoryRepository $categoryRepository,
        \Magento\Cms\Model\Template\FilterProvider $filterProvider,
        ScopeConfigInterface $scopeConfig,
        \Magento\Catalog\Model\ResourceModel\Category\CollectionFactory $categoryCollectionFactory,
        array $data = []
    ) {
        $this->_storeManager = $storeManager;
        $this->categoryRepository = $categoryRepository;
        $this->filterProvider = $filterProvider;
        $this->scopeConfig = $scopeConfig;
        $this->categoryCollectionFactory = $categoryCollectionFactory;
        parent::__construct($context, $data);
    }

    /**
     * @return void
     */
    protected function _construct()
    {
        parent::_construct();
        $this->setTemplate('Branch8_PromotionPage::widget/category_list.phtml');
    }

    /**
     * @return mixed|null
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function getSubcategories()
    {
        if ($this->collection === null) {
            $this->initializeCollection();
        }
        return $this->collection;
    }

    /**
     * @return \Magento\Catalog\Model\ResourceModel\Category\Collection|mixed|null
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function initializeCollection()
    {
        $rootCategoryId = $this->scopeConfig->getValue(
            'promotion_page/promotion_categories/root_category',
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE,
            $this->_storeManager->getStore()->getId()
        );
        if (!$rootCategoryId) {
            return null;
        }

        if($this->collection == null){
            try {
                $category = $this->categoryRepository->get($rootCategoryId, $this->_storeManager->getStore()->getId());
                $childrenCategories = $category->getChildrenCategories();
                $childrenIds = [];
                foreach ($childrenCategories as $category) {
                    $this->totalPage++;
                    $childrenIds[] = $category->getId();
                }
                if (!empty($childrenIds)) {
                    $this->collection = $this->categoryCollectionFactory->create()
                        ->addAttributeToSelect('*')
                        ->addAttributeToFilter('entity_id', ['in' => [$childrenIds]])
                        ->setPageSize($this->getPageSize())
                        ->setCurPage($this->getCurrentPage());
                }
                return $this->collection;
            } catch (\Exception $e) {
                return $this->collection;
            }
        }
        return $this->collection;
    }

    /**
     * @return int|mixed
     */
    public function getSize(){
        return $this->totalPage;
    }

    /**
     * @return string
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function getPagerHtml()
    {
        if ($this->showPager()) {
            if (!$this->pager) {
                $this->pager = $this->getLayout()->createBlock(
                    Pager::class,
                    $this->getWidgetPagerBlockName()
                )->setTemplate('Branch8_PromotionPage::widget/pager.phtml');

                $this->pager->setUseContainer(true)
                    ->setShowAmounts(true)
                    ->setShowPerPage(false)
                    ->setPageVarName(self::PAGE_VAR_NAME)
                    ->setLimit($this->getPageSize())
                    ->setTotalLimit($this->totalPage)
                    ->setCollection($this->collection);
            }
            if ($this->pager instanceof \Magento\Framework\View\Element\AbstractBlock) {
                return $this->pager->toHtml();
            }
        }
        return '';
    }

    /**
     * @return array|mixed|null
     */
    public function getPageSize()
    {
        if (!$this->hasData('page_size')) {
            $this->setData('page_size', self::DEFAULT_PAGE_SIZE);
        }
        return $this->getData('page_size');
    }

    /**
     * @return int
     */
    public function getCurrentPage()
    {
        return (int) $this->getRequest()->getParam(self::PAGE_VAR_NAME, 1);
    }

    public function showPager()
    {
        return true;
    }

    /**
     * Get widget pager block name
     *
     * @return string
     */
    protected function getWidgetPagerBlockName(): string
    {
        $pagerBlockName = 'widget.category.list.pager';
        return $pagerBlockName . '.' . self::PAGE_VAR_NAME;
    }

    /**
     * @param $content
     *
     * @return string
     * @throws \Exception
     */
    public function getFilteredContent($content)
    {
        return $this->filterProvider->getBlockFilter()->filter($content);
    }
}
