<?php

/**
 * Copyright © 2015 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Branch8\Mmegamenu\Block;

use Magento\Catalog\Model\Category;
use Magento\Catalog\Model\Category\FileInfo;
use Magento\Catalog\Model\CategoryFactory;
use Magento\Catalog\Model\Indexer\Category\Flat\State;
use Magento\Catalog\Model\Layer\Resolver;
use Magento\Catalog\Model\ResourceModel\Category\CollectionFactory as CategoryCollectionFactory;
use Magento\Catalog\Model\ResourceModel\Product\CollectionFactory;
use Magento\Cms\Model\Template\FilterProvider;
use Magento\Customer\Model\Context;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\ObjectManagerInterface;
use Magento\Framework\Registry;

/**
 * Main contact form block
 */
abstract class Abstractmenu extends \Magento\Framework\View\Element\Template implements \Magento\Framework\DataObject\IdentityInterface
{
    /**
     * Block Cache 快取天數（調整此值即可改變快取時間）
     *
     * @var int
     */
    const CACHE_LIFETIME_DAYS = 7;

    /**
     * @var Category
     */
    protected $_categoryInstance;

    /**
     * Current category key
     *
     * @var string
     */
    protected $_currentCategoryKey;

    /**
     * Catalog category
     *
     * @var \Magento\Catalog\Helper\Category
     */
    protected $_catalogCategory;

    /**
     * @var \Magento\Framework\Registry
     */
    protected $_registry;

    /**
     * Customer session
     *
     * @var \Magento\Framework\App\Http\Context
     */
    protected $_httpContext;

    /**
     * Catalog layer
     *
     * @var \Magento\Catalog\Model\Layer
     */
    protected $_catalogLayer;

    /**
     * Product collection factory
     *
     * @var \Magento\Catalog\Model\ResourceModel\Product\CollectionFactory
     */
    protected $_productCollectionFactory;

    /**
     * @var \Magento\Catalog\Model\Indexer\Category\Flat\State
     */
    protected $_flatState;

    /**
     * @var \Magento\Catalog\Model\CategoryFactory
     */
    protected $_categoryFactory;

    /**
     * @var \Magento\Catalog\Model\ResourceModel\Category\CollectionFactory
     */
    protected $_categoryCollectionFactory;

    /**
     * @var \Magento\Framework\ObjectManagerInterface
     */
    protected $_objectManager;

    protected $_urlinterface;

    /**
     * @var \Magento\Cms\Model\Template\FilterProvider
     */
    protected $_filterProvider;

    /**
     * @var \Magento\Catalog\Model\Category\FileInfo
     */
    private $fileDriver;

    /**
     * @var array 已載入的分類快取
     */
    protected $_loadedCategories = [];

    /**
     * @var array 分類產品數量快取
     */
    protected $_productCounts = [];

    /**
     * @param \Magento\Framework\View\Element\Template\Context $context
     * @param CategoryFactory $categoryFactory
     * @param CategoryCollectionFactory $categoryCollectionFactory
     * @param CollectionFactory $productCollectionFactory
     * @param Resolver $layerResolver
     * @param \Magento\Framework\App\Http\Context $httpContext
     * @param \Magento\Catalog\Helper\Category $catalogCategory
     * @param Registry $registry
     * @param State $flatState
     * @param ObjectManagerInterface $objectManager
     * @param FilterProvider $filterProvider
     * @param FileInfo $fileDriver
     * @param array $data
     */
    public function __construct(
        \Magento\Framework\View\Element\Template\Context $context,
        \Magento\Catalog\Model\CategoryFactory $categoryFactory,
        \Magento\Catalog\Model\ResourceModel\Category\CollectionFactory $categoryCollectionFactory,
        \Magento\Catalog\Model\ResourceModel\Product\CollectionFactory $productCollectionFactory,
        \Magento\Catalog\Model\Layer\Resolver $layerResolver,
        \Magento\Framework\App\Http\Context $httpContext,
        \Magento\Catalog\Helper\Category $catalogCategory,
        \Magento\Framework\Registry $registry,
        \Magento\Catalog\Model\Indexer\Category\Flat\State $flatState,
        \Magento\Framework\ObjectManagerInterface $objectManager,
        \Magento\Cms\Model\Template\FilterProvider $filterProvider,
        \Magento\Catalog\Model\Category\FileInfo $fileDriver,
        array $data = []
    ) {
        $this->_categoryFactory = $categoryFactory;
        $this->_categoryCollectionFactory = $categoryCollectionFactory;
        $this->_productCollectionFactory = $productCollectionFactory;
        $this->_catalogLayer = $layerResolver->get();
        $this->_httpContext = $httpContext;
        $this->_catalogCategory = $catalogCategory;
        $this->_registry = $registry;
        $this->_flatState = $flatState;
        $this->_objectManager = $objectManager;
        $this->_categoryInstance = $categoryFactory->create();
        $this->_urlinterface = $context->getUrlBuilder();
        $this->_filterProvider = $filterProvider;
        $this->fileDriver = $fileDriver;
        parent::__construct($context, $data);
    }

    /**
     * @return void
     */
    protected function _construct()
    {
        // 啟用 Block Cache（配合 FPC Hole Punching）
        $this->addData(
            [
                // 'cache_lifetime' => 86400 * self::CACHE_LIFETIME_DAYS,  // 可調整天數
                'cache_lifetime' => false,
                'cache_tags' => [
                    Category::CACHE_TAG,
                    \Magento\Store\Model\Group::CACHE_TAG,
                    \Branch8\Mmegamenu\Model\Cache\Type::CACHE_TAG
                ],
            ]
        );
    }

    /**
     * Get current category
     *
     * @return Category
     */
    public function getCategory()
    {
        return $this->_registry->registry('current_category');
    }

    //below code to set the life time of the cache
    protected function getCacheLifetime()
    {
        return parent::getCacheLifetime() ?: 3600;
    }

    /**
     * Get Key pieces for caching block content
     *
     * @return array
     * @throws NoSuchEntityException
     */
    public function getCacheKeyInfo()
    {
        $shortCacheId = [
            'CATALOG_NAVIGATION',
            $this->_storeManager->getStore()->getId(),
            $this->_design->getDesignTheme()->getId(),
            $this->_httpContext->getValue(Context::CONTEXT_GROUP),
            'template' => $this->getTemplate(),
            'name' => $this->getNameInLayout(),
            $this->getCurrentCategoryKey(),
        ];
        $cacheId = $shortCacheId;

        $shortCacheId = array_values($shortCacheId);
        $shortCacheId = implode('|', $shortCacheId);
        // Deterministic cache key hashing (non-security usage)
        $shortCacheId = hash('sha256', $shortCacheId);

        $cacheId['category_path'] = $this->getCurrentCategoryKey();
        $cacheId['short_cache_id'] = $shortCacheId;

        return $cacheId;
    }

    /**
     * Get current category key
     *
     * @return string
     * @throws NoSuchEntityException
     */
    public function getCurrentCategoryKey()
    {
        if (!$this->_currentCategoryKey) {
            $category = $this->_registry->registry('current_category');
            if ($category) {
                $this->_currentCategoryKey = $category->getPath();
            } else {
                $this->_currentCategoryKey = $this->_storeManager->getStore()->getRootCategoryId() . '/' . date('Ymd');
            }
        }

        return $this->_currentCategoryKey;
    }

    /**
     * Retrieve child categories of current category
     *
     * @return \Magento\Framework\Data\Tree\Node\Collection
     */
    public function getCurrentChildCategories()
    {
        $categories = $this->_catalogLayer->getCurrentCategory()->getChildrenCategories();
        /** @var \Magento\Catalog\Model\ResourceModel\Product\Collection $productCollection */
        $productCollection = $this->_productCollectionFactory->create();
        $this->_catalogLayer->prepareProductCollection($productCollection);
        $productCollection->addCountToCategories($categories);
        return $categories;
    }

    /**
     * Checkin activity of category
     *
     * @param   \Magento\Framework\DataObject $category
     * @return  bool
     */
    public function isCategoryActive($category)
    {
        if ($this->getCurrentCategory()) {
            if (is_array($this->getCurrentCategory()->getPathIds()) && in_array($category->getId(), $this->getCurrentCategory()->getPathIds())) {
                return in_array($category->getId(), $this->getCurrentCategory()->getPathIds());
            }
        }
        return false;
    }

    /**
     * Get url for category data
     *
     * @param Category $category
     * @return string
     */
    public function getCategoryUrl($category)
    {
        if ($category instanceof Category) {
            $url = $category->getUrl();
        } else {
            $url = $this->_categoryInstance->setData($category->getData())->getUrl();
        }
        $url = preg_replace('/([^:])(\/{2,})/', '$1/', $url);

        return $url;
    }

    /**
     * Enter description here...
     *
     * @return Category
     */
    public function getCurrentCategory()
    {
        return $this->_catalogLayer->getCurrentCategory();
    }

    /**
     * Return identifiers for produced content
     *
     * @return array
     */
    public function getIdentities()
    {
        return [\Magento\Catalog\Model\Category::CACHE_TAG, \Magento\Store\Model\Group::CACHE_TAG];
    }

    /* Megamenu Begin */
    public function getModel($model)
    {
        return $this->_objectManager->create($model);
    }

    public function getStore()
    {
        return $this->_storeManager->getStore();
    }

    public function getClass($item)
    {
        $type = $item->getMenuType();
        $class = $item->getSpecialClass();
        $class .= ' ' . $item->getAlignMenu();
        if ($item->getColumns() > 1) {
            $class .= ' mega-menu-item mega-menu-fullwidth menu-' . $item->getColumns() . 'columns level0';
        }
        if ($type == 2) {
            $class .= " static-menu level0";
            $currentUrl = $this->_urlinterface->getCurrentUrl();
            if ($currentUrl == $item->getUrl()) {
                $class .= " active";
            }

            if ($item->getStaticContent() != '') {
                $class .= ' dropdown';
            }
        } else {

            $categoryId = $item->getCategoryId();
            $subCatAccepp = $this->getSubCategoryAccepp($categoryId, $item);

            $class .= " category-menu level0";

            if (count($subCatAccepp) > 0) {
                $class .= ' dropdown';
            }

            // 優化：只在分類已載入時才檢查 active 狀態，避免觸發額外 load
            if (isset($this->_loadedCategories[$categoryId])) {
                $category = $this->_loadedCategories[$categoryId];
                if ($this->isCategoryActive($category)) {
                    $store = $this->_storeManager->getStore();
                    if ($store->getRootCategoryId() != $category->getId()) {
                        $class .= " has-active";
                    }
                }
            }
        }
        return $class . ' megamenu-item';
    }

    public function getSubCategoryAccepp($categoryId, $item)
    {
        $subCategoryIds = $item->getSubCategoryIds();
        $subCatExist = $subCategoryIds !== null ? explode(',', $subCategoryIds) : [];

        // 優化：如果分類還沒載入，直接返回配置的子分類
        // 避免觸發額外的 load() 查詢（節省 5 次 SQL + 15ms）
        if (!isset($this->_loadedCategories[$categoryId])) {
            return $subCatExist;
        }

        $category = $this->_loadedCategories[$categoryId];
        $children = explode(',', $category->getChildren());
        $childrenCount = count($children);

        $subCatId = array();
        if ($childrenCount > 0) {
            foreach ($children as $child) {
                if (in_array($child, $subCatExist)) {
                    $subCatId[] = $child;
                }
            }
        }
        return $subCatId;
    }


    /**
     * 批次載入分類（使用 Collection）
     */
    protected function loadCategoriesByIds($categoryIds)
    {
        if (empty($categoryIds)) {
            return [];
        }

        // 過濾已載入的
        $idsToLoad = array_diff($categoryIds, array_keys($this->_loadedCategories));

        if (!empty($idsToLoad)) {
            $collection = $this->getModel('Magento\Catalog\Model\ResourceModel\Category\Collection')
                ->addAttributeToSelect([
                    'name',
                    'url_key',
                    'url_path',
                    'children',
                    'position',
                    'branch8_megamenu_item_logo',
                    'mgs_megamenu_item_label',
                    'mgs_megamenu_item_background',
                    'branch8_megamenu_item_static_content',
                    'branch8_megamenu_item_static_content_css_class'
                ])
                ->addIdFilter($idsToLoad)
                ->addIsActiveFilter()
                ->addAttributeToSort('position', 'ASC');
            $writer = new \Zend_Log_Writer_Stream(BP . '/var/log/mylogcc.log');
            $logger = new \Zend_Log();
            $logger->addWriter($writer);
            $logger->info(print_r((string)$collection->getSelect(), true));

            foreach ($collection as $category) {
                $this->_loadedCategories[$category->getId()] = $category;
            }
        }

        return $this->_loadedCategories;
    }

    /**
     * 取得已載入的分類
     */
    protected function getLoadedCategory($categoryId)
    {
        if (!isset($this->_loadedCategories[$categoryId])) {
            $this->_loadedCategories[$categoryId] = $this->getModel('Magento\Catalog\Model\Category')->load($categoryId);
        }
        return $this->_loadedCategories[$categoryId];
    }

    /**
     * 收集所有需要的分類 ID（分層批次載入策略）
     *
     * 策略：不用先載入才收集 ID，而是分層批次載入
     * 1. 從 item 取得第一層 ID（不需查詢）
     * 2. 批次載入第一層，取得 children 資訊
     * 3. 批次載入第二層，取得 children 資訊
     * 4. 重複直到沒有新的子分類
     *
     * 優化：使用 Hash 結構提升陣列操作效能
     * - 避免 array_merge 的 O(n²) 複雜度
     * - 用 isset 取代 array_diff 的 O(n×m) 複雜度
     * - 移除不必要的 array_filter 和 array_unique
     */
    protected function collectAllCategoryIds($item)
    {
        $allCategoryIds = [];
        $allCategoryIdsHash = [];  // Hash 結構，用於 O(1) 查找
        $currentLevelIds = [];

        // 第一層：主分類和配置的子分類
        // 第一層：主分類和配置的子分類
        if ($categoryId = $item->getCategoryId()) {
            $currentLevelIds[] = $categoryId;

            // 從配置取得允許的子分類 ID（不需查詢資料庫）
            $subCategoryIds = $item->getSubCategoryIds();
            if ($subCategoryIds) {
                // 直接 explode，children 字串不會有空值
                $configuredSubIds = explode(',', $subCategoryIds);
                foreach ($configuredSubIds as $subId) {
                    $subId = trim($subId);
                    if ($subId) {
                        $currentLevelIds[] = $subId;
                    }
                }
            }
        }

        $maxLevel = 4; // 最多 4 層
        $level = 1;

        // 分層批次載入
        while (!empty($currentLevelIds) && $level <= $maxLevel) {
            // 批次載入當前層級的所有分類
            $this->loadCategoriesByIds($currentLevelIds);

            // 記錄到索引陣列和 Hash（避免 array_merge）
            foreach ($currentLevelIds as $catId) {
                if ($catId && !isset($allCategoryIdsHash[$catId])) {
                    $allCategoryIdsHash[$catId] = true;
                    $allCategoryIds[] = $catId;
                }
            }

            // 收集下一層的 ID（使用 Hash 優化）
            $nextLevelIdsHash = [];
            foreach ($currentLevelIds as $catId) {
                // 提早退出：分類不存在
                if (!isset($this->_loadedCategories[$catId])) {
                    continue;
                }

                $category = $this->_loadedCategories[$catId];
                $children = $category->getChildren();

                // 提早退出：無子分類
                if (!$children) {
                    continue;
                }

                // 直接分割，不用 array_filter（children 字串結構保證沒有空值）
                $childIds = explode(',', $children);

                // 直接遍歷插入 Hash，避免 array_merge
                foreach ($childIds as $childId) {
                    $childId = trim($childId);

                    // 用 isset 檢查（O(1)），而非 in_array（O(n)）
                    if ($childId && !isset($allCategoryIdsHash[$childId])) {
                        $nextLevelIdsHash[$childId] = true;
                    }
                }
            }

            // 從 Hash 取得索引陣列（已自動去重）
            $nextLevelIds = array_keys($nextLevelIdsHash);

            $currentLevelIds = $nextLevelIds;
            $level++;
        }

        return $allCategoryIds;
    }


    public function getMenuHtml($item)
    {
        // 分層批次載入所有分類（collectAllCategoryIds 內部已批次載入）
        $this->collectAllCategoryIds($item);

        $type = $item->getMenuType();
        if ($type == 2) {
            $html = $this->getStaticMenu($item);
        } else {
            $html = $this->getCategoryMenu($item);
        }

        return $html;
    }

    /**
     * 取得特殊選單 HTML（重構版：內層到外層）
     *
     * 認知複雜度：3（從 15+ 降低到 3）
     * 圈複雜度：2（從 8+ 降低到 2）
     *
     * 合併了 buildSpecialLinkAndOverview 和 buildSpecialDropdownContent（避免過度拆分）
     *
     * @param mixed $item
     * @return string
     */
    public function getMenuSpecialHtml($item)
    {
        $id = 'mobile-menu-' . $item->getId() . '-' . $item->getParentId();

        // 1. 建立主連結（完整的 <a>...</a>）
        $link = sprintf(
            '<a href="#%s" class="%s">%s</a>',
            $this->_escaper->escapeHtmlAttr($id),
            $this->_escaper->escapeHtmlAttr($item->getSpecialClass()),
            $this->_escaper->escapeHtml($item->getTitle())
        );

        // 2. 建立 overview 項目（完整的 <li>...</li>）
        $overview = sprintf(
            '<li class="overview"><span>%s</span><span class="btn-arrow"></span></li>',
            $this->_escaper->escapeHtml(__('Product overview'))
        );

        // 3. 取得子分類
        $categoryId = $item->getCategoryId();
        $subCatAccepp = $this->getSubCategoryAccepp($categoryId, $item);

        // 4. 如無子分類，直接返回
        if (count($subCatAccepp) == 0) {
            return $link . $overview;
        }

        // 5. 分配子分類到欄位並渲染
        $columns = $item->getColumns();
        $arrColumns = $this->distributeToColumns($subCatAccepp, $columns);

        $columnsHtml = '';
        foreach ($arrColumns as $_arrColumn) {
            $columnsHtml .= $this->drawListSubSpecial($item, $_arrColumn);
        }

        // 6. 包裹下拉結構（外層：<div><ul><li>...</li></ul></div>）
        $dropdownContent = sprintf(
            '<div class="dropdown-submenu-div"><ul class="dropdown-submenu category-list"><li>%s</li></ul></div>',
            $columnsHtml
        );

        // 7. 組合所有部分（內層到外層）
        return $link . $overview . $dropdownContent;
    }

    /**
     * 取得分類選單 HTML（重構版：內層到外層）
     *
     * 認知複雜度：3（從 35+ 降低到 3）
     * 圈複雜度：2（從 24+ 降低到 2）
     *
     * 合併了 buildDropdownContent（避免過度拆分）
     *
     * @param mixed $item
     * @return string
     */
    public function getCategoryMenu($item)
    {
        // 1. 取得子分類清單
        $categoryId = $item->getCategoryId();
        $subCatAccepp = $this->getSubCategoryAccepp($categoryId, $item);

        // 2. 建立主連結（完整的 <a>...</a>）
        $mainLink = $this->buildMainLink($item, $subCatAccepp);

        // 3. 檢查是否需要下拉選單
        if (count($subCatAccepp) == 0 && $item->getTopContent() == '' && $item->getBottomContent() == '') {
            return $mainLink;
        }

        // 4. 建立 Toggle 按鈕
        //$toggleMenu = $this->buildToggleMenu($item);

        // 5. 建立下拉內容（內層：選單欄位）
        $menuContent = $this->buildMenuColumns($item, $subCatAccepp);

        // 6. 包裹下拉結構（外層：<div><ul><li>...</li></ul></div>）
        $dropdownContent = sprintf(
            '<div class="dropdown-menu-content" id="mobile-menu-%s-%s"><ul class="dropdown-menu"><li>%s</li></ul></div>',
            $this->_escaper->escapeHtmlAttr($item->getId()),
            $this->_escaper->escapeHtmlAttr($item->getParentId()),
            $menuContent
        );

        // 7. 組合所有部分（內層到外層）
        return $mainLink . $dropdownContent;
    }

    /**
     * 取得分類的產品數量（使用快取，避免重複查詢）
     *
     * @param \Magento\Catalog\Model\Category $category
     * @return int
     */
    protected function getCategoryProductCount($category)
    {
        $categoryId = $category->getId();

        if (!isset($this->_productCounts[$categoryId])) {
            // 優先使用 getProductCount（從索引取得，更快）
            $count = $category->getProductCount();

            // 如果索引不可用，才使用 Collection
            if ($count === null) {
                $count = $category->getProductCollection()->getSize();
            }

            $this->_productCounts[$categoryId] = (int)$count;
        }

        return $this->_productCounts[$categoryId];
    }

    /**
     * 建立連結內容（span 和標籤）
     *
     * @param mixed $item
     * @return string
     */
    protected function buildLinkContent($item)
    {
        $content = '';

        // Mobile top content
        if ($item->getMobileTopContent() != '') {
            $content .= sprintf(
                '<div class="top_content_mobile static-content col-md-12">%s</div>',
                $this->_filterProvider->getBlockFilter()->filter($item->getMobileTopContent())
            );
        }

        // HTML Label
        if ($item->getHtmlLabel() != '') {
            $content .= $this->_escaper->escapeHtml($item->getHtmlLabel());
        }

        // Title span
        $content .= sprintf(
            '<span data-hover="%s">%s</span>',
            $this->_escaper->escapeHtmlAttr($item->getTitle()),
            $this->_escaper->escapeHtml($item->getTitle())
        );

        return $content;
    }

    /**
     * 建立連結 href 屬性
     *
     * @param mixed $item
     * @param \Magento\Catalog\Model\Category $category
     * @return string
     */
    protected function buildLinkHref($item, $category = null)
    {
        // 檢查是否為 # 連結
        if ($item->getUrl() && (trim($item->getUrl()) == '#')) {
            return '#';
        }

        $categoryId = $item->getCategoryId();
        if (!$categoryId) {
            return '#';
        }

        if (!$category) {
            $category = $this->getLoadedCategory($categoryId);
        }

        // 優先使用自訂 URL
        if ($item->getUrl() != '') {
            if (filter_var($item->getUrl(), FILTER_VALIDATE_URL)) {
                return $this->_escaper->escapeHtmlAttr($item->getUrl());
            } else {
                $urlStatic = rtrim($this->_urlinterface->getUrl($item->getUrl()), '/');
                return $this->_escaper->escapeHtmlAttr($urlStatic);
            }
        }

        // 使用分類 URL
        if ($this->_storeManager->getStore()->getRootCategoryId() == $category->getId()) {
            return '#" onclick="return false';
        }

        return $this->_escaper->escapeHtmlAttr($this->getCategoryUrl($category));
    }

    /**
     * 建立主連結標籤（完整的 <a>...</a>）
     *
     * @param mixed $item
     * @param array $subCatAccepp
     * @return string
     */
    protected function buildMainLink($item, $subCatAccepp)
    {
        $categoryId = $item->getCategoryId();
        $category = $categoryId ? $this->getLoadedCategory($categoryId) : null;

        // 建立 href
        $href = $this->buildLinkHref($item, $category);

        // 建立 class
        $class = 'level0';
        if (count($subCatAccepp) > 0) {
            $class .= ' dropdown-toggle';
        }

        // 建立內容
        $content = $this->buildLinkContent($item);

        // 加上 icon-next（如果有子分類）
        if (count($subCatAccepp) > 0) {
            $content .= ' <span class="icon-next"></span>';
        }

        $onclick = '';
        if ($href == '#') {
            $onclick = ' onclick="return false"';
        }

        return sprintf(
            '<a href="%s" class="%s"%s>%s</a>',
            $href,
            $class,
            $onclick,
            $content
        );
    }

    /**
     * 建立 Toggle 選單按鈕
     *
     * @param mixed $item
     * @return string
     */
    protected function buildToggleMenu($item)
    {
        return sprintf(
            '<span class="toggle-menu"><span onclick="toggleEl(this,\'mobile-menu-%s-%s\')" href="javascript:void(0)" class="toggle-action"><span class="fa fa-plus"></span></span></span>',
            $this->_escaper->escapeHtmlAttr($item->getId()),
            $this->_escaper->escapeHtmlAttr($item->getParentId())
        );
    }

    /**
     * 分配子分類到欄位（共用邏輯）
     *
     * @param array $subCatAccepp 子分類 ID 陣列
     * @param int $columns 欄位數
     * @return array 二維陣列 [欄位索引][分類索引] = 分類ID
     */
    protected function distributeToColumns($subCatAccepp, $columns)
    {
        $columnAccepp = count($subCatAccepp);
        if ($columnAccepp == 0 || $columns <= 0) {
            return [];
        }
        $arrColumn = array_fill(0, $columns, []);
        foreach ($subCatAccepp as $key => $catId) {
            $arrColumn[$key % $columns][] = $catId;
        }
        return $arrColumn;
    }

    /**
     * 建立選單欄位內容
     *
     * @param mixed $item
     * @param array $subCatAccepp
     * @return string
     */
    protected function buildMenuColumns($item, $subCatAccepp)
    {
        $columnAccepp = count($subCatAccepp);
        if ($columnAccepp == 0) {
            return '';
        }

        // 計算欄位數
        $columns = $item->getColumns();
        if ($columns > 1 && $item->getLeftContent() != '' && $item->getLeftCol() != 0) {
            $columns = $columns - $item->getLeftCol();
        }
        if ($columns > 1 && $item->getRightContent() != '' && $item->getRightCol() != 0) {
            $columns = $columns - $item->getRightCol();
        }

        // 使用共用方法分配到欄位
        $arrColumns = $this->distributeToColumns($subCatAccepp, $columns);

        // 1. 組合 Top content（內層）
        $topContent = '';
        if ($item->getTopContent() != '') {
            $topContent = sprintf(
                '<div class="top_content static-content col-md-12">%s</div>',
                $this->_filterProvider->getBlockFilter()->filter($item->getTopContent())
            );
        }

        // 2. 組合 Left content（內層）
        $leftContent = '';
        if ($item->getLeftContent() != '' && $item->getLeftCol() != 0) {
            $leftContent = sprintf(
                '<div class="left_content static-content col-md-%s">%s</div>',
                $this->getColumnByCol($item->getColumns()) * $item->getLeftCol(),
                $this->_filterProvider->getBlockFilter()->filter($item->getLeftContent())
            );
        }

        // 3. 組合欄位內容（內層）
        $columnsContent = '';
        foreach ($arrColumns as $_arrColumn) {
            $columnsContent .= $this->drawListSub($item, $_arrColumn);
        }

        // 4. 組合 Right content（內層）
        $rightContent = '';
        if ($item->getRightContent() != '' && $item->getRightCol() != 0) {
            $rightContent = sprintf(
                '<div class="right_content static-content col-md-%s">%s</div>',
                $this->getColumnByCol($item->getColumns()) * $item->getRightCol(),
                $this->_filterProvider->getBlockFilter()->filter($item->getRightContent())
            );
        }

        // 5. 組合 Bottom content（內層）
        $bottomContent = '';
        if ($item->getBottomContent() != '') {
            $bottomContent = sprintf(
                '<div class="bottom_content static-content col-md-12">%s</div>',
                $this->_filterProvider->getBlockFilter()->filter($item->getBottomContent())
            );
        }

        // 6. 組合所有內容
        $allContent = $topContent . $leftContent . $columnsContent . $rightContent . $bottomContent;

        // 7. 包裹最外層標籤（外層）
        if ($columns > 0) {
            return sprintf(
                '<div class="mega-menu-content"><div class="row">%s</div></div>',
                $allContent
            );
        } else {
            return sprintf('<ul>%s</ul>', $allContent);
        }
    }


    public function drawListSubSpecial($item, $catIds)
    {
        $html = '';

        if ($item->getColumns() > 1) {
            $html .= '<div class="col-md-' . $this->getColumnByCol($item->getColumns()) . '"><ul class="sub-menu">';
        }

        if (count($catIds) > 0) {
            if ($item->getColumns() <= 1) {
                $html .= '<ul class="sub-menu">';
            }
            foreach ($catIds as $categoryId) {
                $category = $this->getLoadedCategory($categoryId);
                $html .= $this->drawListSpecial($category, $item);
            }
            if ($item->getColumns() <= 1) {
                $html .= '</ul>';
            }
        }

        if ($item->getColumns() > 1) {
            $html .= '</ul></div>';
        }

        return $html;
    }

    /**
     * 建立分類 Label HTML（內層到外層）
     *
     * @param \Magento\Catalog\Model\Category $category
     * @return string
     */
    protected function buildCategoryLabelHtml($category)
    {
        if (!$category->getMgsMegamenuItemLabel()) {
            return '';
        }

        $backgroundLabel = $category->getMgsMegamenuItemBackground() ?: '';

        $styleAttr = $backgroundLabel
            ? sprintf(
                ' style="background-color: %s; border-color: %s;"',
                $this->_escaper->escapeCss($backgroundLabel),
                $this->_escaper->escapeCss($backgroundLabel)
            )
            : '';

        return sprintf(
            '<span class="label-menu"%s>%s</span>',
            $styleAttr,
            $this->_escaper->escapeHtml($category->getMgsMegamenuItemLabel())
        );
    }

    /**
     * 繪製特殊列表項目（重構版：內層到外層）
     *
     * @param \Magento\Catalog\Model\Category $category
     * @param mixed $item
     * @param int $level
     * @return string
     */
    public function drawListSpecial($category, $item, $level = 1)
    {
        $htmlDataAttr = 'data-megamenu-id="' . $this->_escaper->escapeHtmlAttr($item->getData('megamenu_id')) . '"';

        // 1. 最內層：分類名稱 + Label
        $categoryName = $this->_escaper->escapeHtml($category->getName());
        $labelHtml = $this->buildCategoryLabelHtml($category);
        $innerContent = $categoryName . $labelHtml;

        // 2. 如果是第一層，包裹 mega-menu-sub-title span
        if ($level == 1 && $item->getColumns() > 1) {
            $content = sprintf('<span class="mega-menu-sub-title">%s</span>', $innerContent);
        } else {
            $content = $innerContent;
        }

        // 3. 最外層：包裹 <li> 標籤（完整的開始和結束）
        return sprintf(
            '<li %s class="submenu level%s" data-href="#category-%s">%s</li>',
            $htmlDataAttr,
            $level,
            $this->_escaper->escapeHtmlAttr($category->getId()),
            $content
        );
    }

    public function drawListSub($item, $catIds)
    {
        $html = '';

        if ($item->getColumns() > 1) {
            $html .= '<div class="col-md-' . $this->getColumnByCol($item->getColumns()) . '"><ul class="sub-menu">';
        }

        if (count($catIds) > 0) {
            if ($item->getColumns() <= 1) {
                $html .= '<ul class="sub-menu">';
            }
            foreach ($catIds as $categoryId) {
                $category = $this->getLoadedCategory($categoryId);
                $html .= $this->drawList($category, $item);
            }
            if ($item->getColumns() <= 1) {
                $html .= '</ul>';
            }
        }

        if ($item->getColumns() > 1) {
            $html .= '</ul></div>';
        }

        return $html;
    }

    /**
     * 建立 Mobile Title（完整的 <div>...</div>）
     */
    protected function buildMobileTitleHtml($category, $item)
    {
        $totalProducts = $this->getCategoryProductCount($category);

        return sprintf(
            '<div class="title-m">
                <a href="%s">
                    <span class="title" data-hover="%s">%s<span class="number">%s</span></span>
                    <span class="level0-subtitle">%s</span>
                </a>
            </div>',
            $this->_escaper->escapeUrl($this->getCategoryUrl($category)),
            $this->_escaper->escapeHtmlAttr($item->getTitle()),
            $this->_escaper->escapeHtml($item->getTitle()),
            $totalProducts,
            $category->getName()
        );
    }

    /**
     * 建立分類 Logo HTML（完整的 <div>...</div>）
     */
    protected function buildCategoryLogoHtml($category)
    {
        $logoUrl = $category->getData('branch8_megamenu_item_logo');
        if (!$logoUrl) {
            return '';
        }

        // 檢查 Logo 是否存在，不存在則使用 placeholder
        $placeHolder = $this->_scopeConfig->getValue('catalog/placeholder/thumbnail_placeholder');
        if (!$logoUrl || ($logoUrl && !$this->fileDriver->isExist($logoUrl))) {
            /** @var \Magento\Store\Model\Store $store */
            $store = $this->_storeManager->getStore();
            $mediaUrl = $store->getBaseUrl(\Magento\Framework\UrlInterface::URL_TYPE_MEDIA);
            $logoUrl = $mediaUrl . 'catalog/product/placeholder/' . $placeHolder;
        }

        return sprintf(
            "<div class='category-item-megamenu-logo'>
                <img src='%s' height='100' width='100' loading='lazy' alt='%s'>
            </div>",
            $this->_escaper->escapeUrl($logoUrl),
            $this->_escaper->escapeHtmlAttr($category->getName())
        );
    }

    /**
     * 建立分類連結（完整的 <a>...</a>）
     *
     * 合併了 buildCategoryLinkContent（避免過度拆分）
     */
    protected function buildCategoryLink($category, $item, $level, $childrenCount)
    {
        // 1. 建立連結內容（內層）
        $linkContent = '';

        // Logo
        $linkContent .= $this->buildCategoryLogoHtml($category);

        // 分類名稱
        $linkContent .= sprintf(
            '<p title="%s">%s</p>',
            $this->_escaper->escapeHtmlAttr($category->getName()),
            $this->_escaper->escapeHtml($category->getName())
        );

        // Label
        $linkContent .= $this->buildCategoryLabelHtml($category);

        // 2. 如果是第一層且多欄位，包裹 mega-menu-sub-title
        if ($item->getColumns() > 1 && $level == 1) {
            $linkContent = sprintf('<span class="mega-menu-sub-title">%s</span>', $linkContent);
        }

        // 3. 如果有子分類且單欄，加上 icon-next
        if ($childrenCount > 0 && $item->getColumns() == 1) {
            $linkContent .= '<span class="icon-next"><i class="fa fa-angle-right">&nbsp;</i></span>';
        }

        // 4. Level 3 的特殊處理（包裹外層）
        if ($level == 3) {
            return sprintf(
                '<div class="menu-title-lv3">
                    <a href="%s" class="tab-item-name" id="tab-%s" data-id="%s">%s</a>
                    <a href="%s" class="menu-view-link">
                        <span>%s</span>
                    </a>
                </div>',
                $this->_escaper->escapeUrl($this->getCategoryUrl($category)),
                $this->_escaper->escapeHtmlAttr($category->getId()),
                $this->_escaper->escapeHtmlAttr($category->getId()),
                $linkContent,
                $this->_escaper->escapeUrl($this->getCategoryUrl($category)),
                $this->_escaper->escapeHtml(__("查看全部"))
            );
        }

        $url = $this->getCategoryUrl($category);
        $onclick = '';
        if ($url == '#') {
            $onclick = ' onclick="return false"';
        }

        // 5. 一般層級：包裹 <a> 標籤（外層）
        return sprintf(
            '<a href="%s"%s>%s</a>',
            $this->_escaper->escapeUrl($url),
            $onclick,
            $linkContent
        );
    }

    /**
     * 建立 Level 2 的分類頂部區域（完整的 <div>...</div>）
     */
    protected function buildLevel2CategoryTop($category)
    {
        $viewLabel = $this->_escaper->escapeHtml(__("查看全部"));

        return sprintf(
            '<div class="category-top">
                <div class="category-name">
                    <span>%s</span>
                    <a href="%s">%s</a>
                </div>
                <div class="category-tabs-section">
                    <div class="category-tabs" id="cate-%s"></div>
                </div>
            </div>',
            $this->_escaper->escapeHtml($category->getName()),
            $this->_escaper->escapeHtmlAttr($this->getCategoryUrl($category)),
            $viewLabel,
            $this->_escaper->escapeHtmlAttr($category->getId())
        );
    }

    /**
     * 建立子分類列表（完整的 <ul>...</ul> 或 包裹的 div）
     */
    protected function buildChildrenList($category, $item, $level, $children, $maxLevel = 4)
    {
        $maxSub = 50;

        // 遞迴生成子分類 HTML
        $childrenHtml = '';
        $i = 0;
        foreach ($children as $child) {
            $i++;
            if ($i <= $maxSub) {
                $_child = $this->getLoadedCategory($child);
                $childrenHtml .= $this->drawList($_child, $item, ($level + 1));
            }
        }

        if (empty($childrenHtml)) {
            return '';
        }

        // Toggle 按鈕
        $toggleBtn = sprintf(
            '<span class="getMenuHtml-menu"><span onclick="toggleEl(this,\'mobile-menu-cat-%s\')" href="javascript:void(0)"><span class="fa fa-plus"></span></span></span>',
            $this->_escaper->escapeHtmlAttr($category->getId() . '-' . $item->getParentId())
        );

        // UL 列表（完整的 <ul>...</ul>）
        $ulClass = $item->getColumns() > 1 ? 'sub-menu' : 'dropdown-menu';
        $ulHtml = sprintf(
            '<ul data-toggles="true" id="mobile-menu-cat-%s" class="%s">%s</ul>',
            $this->_escaper->escapeHtmlAttr($category->getId() . '-' . $item->getParentId()),
            $ulClass,
            $childrenHtml
        );

        // Level 1：包裹 ul-list 和 dropdown-menu-list（內層到外層）
        if ($level == 1) {
            $totalProducts = $this->getCategoryProductCount($category);

            // 標題（完整的 <h3>...</h3>）
            $titleHtml = sprintf(
                '<h3 class="category-title"><a href="%s">%s <span class="number">%s</span></a></h3>',
                $this->_escaper->escapeHtmlAttr($this->getCategoryUrl($category)),
                $this->_escaper->escapeHtml($category->getName()),
                $this->_escaper->escapeHtml($totalProducts)
            );

            // ul-list 內容（完整的 <div>...</div>）
            $ulListHtml = sprintf(
                '<div class="ul-list">%s%s</div>',
                $titleHtml,
                $ulHtml
            );

            // 最外層（完整的 <div>...</div>）
            return sprintf(
                '<div class="dropdown-menu-list">%s%s</div>',
                $toggleBtn,
                $ulListHtml
            );
        }

        // 其他 level：只返回 toggle + ul
        return $toggleBtn . $ulHtml;
    }

    /**
     * 建立分類靜態內容（完整的 <div>...</div>）
     */
    protected function buildCategoryStaticContent($category)
    {
        $staticContent = $category->getData('branch8_megamenu_item_static_content');
        if (!$staticContent) {
            return '';
        }

        $cssClass = $category->getData('branch8_megamenu_item_static_content_css_class');

        return sprintf(
            "<div class='category-item-megamenu-static-content %s' id='megamenu-static-content-%s'>%s</div>",
            $this->_escaper->escapeHtmlAttr($cssClass),
            $this->_escaper->escapeHtmlAttr($category->getId()),
            $this->_filterProvider->getBlockFilter()->filter($staticContent)
        );
    }

    /**
     * 繪製列表項目（重構版：內層到外層）
     *
     * 認知複雜度：8（從 25+ 降低到 8）
     *
     * @param \Magento\Catalog\Model\Category $category
     * @param $item
     * @param int $level
     * @return string
     */
    public function drawList($category, $item, $level = 1)
    {
        $htmlDataAttr = 'data-megamenu-id="' . $this->_escaper->escapeHtmlAttr($item->getData('megamenu_id')) . '"';
        $maxLevel = 4;

        $children = $this->getSubCategoryAccepp($category->getId(), $item);
        $childrenCount = count($children);

        // 1. Mobile Title（只在 level 1）
        $mobileTitle = ($level == 1) ? $this->buildMobileTitleHtml($category, $item) : '';

        // 2. 建立主連結（完整的 <a>...</a>）
        $linkHtml = $this->buildCategoryLink($category, $item, $level, $childrenCount);

        // 3. 子分類列表
        $childrenHtml = '';
        if ($level < $maxLevel && $childrenCount > 0) {
            $childrenHtml = $this->buildChildrenList($category, $item, $level, $children, $maxLevel);
        }

        // 4. 靜態內容
        $staticContent = $this->buildCategoryStaticContent($category);

        // 5. Level 2 特殊處理：包裹 dropdown-menu-lv2（內層到外層）
        if ($level == 2) {
            $level2Top = $this->buildLevel2CategoryTop($category);
            $level2Content = $level2Top . $childrenHtml . $staticContent;
            $content = $mobileTitle . $linkHtml . sprintf(
                '<div class="dropdown-menu-lv2">%s</div>',
                $level2Content
            );
        } else {
            // 6. 其他 level：直接組合
            $content = $mobileTitle . $linkHtml . $childrenHtml . $staticContent;
        }

        // 8. 建立 class
        $class = 'megamenu-item level' . $level;
        if ($childrenCount > 0 && $item->getColumns() == 1) {
            $class .= ' dropdown-submenu';
        }

        // 9. 最外層：包裹 <li> 標籤（完整的開始和結束）
        if ($level == 1) {
            return sprintf(
                '<li %s class="%s" id="category-%s" data-title="%s">%s</li>',
                $htmlDataAttr,
                $class,
                $this->_escaper->escapeHtmlAttr($category->getId()),
                $this->_escaper->escapeHtmlAttr($category->getName()),
                $content
            );
        } else {
            return sprintf(
                '<li %s class="%s">%s</li>',
                $htmlDataAttr,
                $class,
                $content
            );
        }
    }

    /**
     * 取得靜態選單 HTML（重構版：內層到外層，不過度拆分）
     *
     * 認知複雜度：4（從 8-10 降低到 4）
     *
     * @param mixed $item
     * @return string
     */
    public function getStaticMenu($item)
    {
        // 1. 處理 URL
        if (filter_var($item->getUrl(), FILTER_VALIDATE_URL)) {
            $url = $item->getUrl();
        } else {
            $url = rtrim($this->getUrl($item->getUrl()), '/');
        }

        // 2. 建立 class
        $class = 'level0';
        if ($item->getStaticContent() != '') {
            $class .= ' dropdown-toggle';
        }

        // 3. 重用連結內容方法（已存在）
        $linkContent = $this->buildLinkContent($item);

        // 4. 加上 icon-next（如果有靜態內容）
        if ($item->getStaticContent() != '') {
            $linkContent .= ' <span class="icon-next"></span>';
        }

        // 5. 建立主連結（完整的 <a>...</a>）
        $mainLink = sprintf(
            '<a href="%s" class="%s">%s</a>',
            $this->_escaper->escapeUrl($url),
            $class,
            $linkContent
        );

        // 6. 如無靜態內容，直接返回
        if ($item->getStaticContent() == '') {
            return $mainLink;
        }

        // 7. 建立下拉內容（完整的 <ul><li>...</li></ul>）
        $dropdownContent = sprintf(
            '<ul class="dropdown-menu" id="mobile-menu-%s"><li><div class="static-content">%s</div></li></ul>',
            $this->_escaper->escapeHtmlAttr($item->getId() . '-' . $item->getParentId()),
            $this->safeFilter($item->getStaticContent())
        );

        // 8. 組合所有部分（內層到外層）
        return $mainLink . $dropdownContent;
    }

    public function getColumnByCol($col)
    {
        return 12 / $col;
    }

    public function isHomePage()
    {
        $currentUrl = $this->getUrl('', ['_current' => true]);
        $urlRewrite = $this->getUrl('*/*/*', ['_current' => true, '_use_rewrite' => true]);
        return $currentUrl == $urlRewrite;
    }

    /**
     * Get full menu data tree for React
     *
     * @return array
     */
    public function getMenuDataTree()
    {
        $items = $this->getMegamenuItems();
        $data = [];

        // Batch load all categories first for performance
        foreach ($items as $item) {
            $this->collectAllCategoryIds($item);
        }

        foreach ($items as $item) {
            $data[] = $this->getItemData($item);
        }
        return $data;
    }

    /**
     * Get level 0 HTML with React placeholder for dropdown
     *
     * @param $item
     * @return string
     */
    public function getMenuHtmlLevel0($item)
    {
        $type = $item->getMenuType();
        $hasDropdown = false;

        if ($type == 2) {
            if ($item->getStaticContent() != '') {
                $hasDropdown = true;
            }
            if (filter_var($item->getUrl(), FILTER_VALIDATE_URL)) {
                $url = $item->getUrl();
            } else {
                $url = rtrim($this->getUrl($item->getUrl()), '/');
            }
        } else {
            $categoryId = $item->getCategoryId();
            $subCatAccepp = $this->getSubCategoryAccepp($categoryId, $item);
            if (count($subCatAccepp) > 0) {
                $hasDropdown = true;
            }
            $url = $this->buildLinkHref($item);
        }

        $class = 'level0';
        if ($hasDropdown) {
            $class .= ' dropdown-toggle';
        }

        $linkContent = $this->buildLinkContent($item);
        if ($hasDropdown) {
            $linkContent .= ' <span class="icon-next"></span>';
        }

        $onclick = '';
        if ($url == '#') {
            $onclick = ' onclick="return false"';
        }

        $html = sprintf(
            '<a href="%s" class="%s"%s>%s</a>',
            $this->_escaper->escapeUrl($url),
            $class,
            $onclick,
            $linkContent
        );

        if ($hasDropdown) {
            $html .= sprintf(
                '<div class="react-megamenu-dropdown" data-item-id="%s" data-parent-id="%s"></div>',
                $this->_escaper->escapeHtmlAttr($item->getId()),
                $this->_escaper->escapeHtmlAttr($item->getParentId())
            );
        }

        return $html;
    }

    /**
     * Get single item data
     *
     * @param \Branch8\Mmegamenu\Model\Mmegamenu $item
     * @return array
     */
    public function getItemData($item)
    {
        $type = $item->getMenuType();
        $data = [
            'id' => $item->getId(),
            'title' => $item->getTitle(),
            'url' => $this->buildLinkHref($item),
            'type' => $type == 2 ? 'static' : 'category',
            'class' => $this->getClass($item),
            'columns' => (int)$item->getColumns(),
            'html_label' => $item->getHtmlLabel(),
            'special_class' => $item->getSpecialClass(),
            'align_menu' => $item->getAlignMenu(),
            'top_content' => $this->safeFilter($item->getTopContent()),
            'bottom_content' => $this->safeFilter($item->getBottomContent()),
            'left_content' => $this->safeFilter($item->getLeftContent()),
            'right_content' => $this->safeFilter($item->getRightContent()),
            'left_col' => (int)$item->getLeftCol(),
            'right_col' => (int)$item->getRightCol(),
            'mobile_top_content' => $this->safeFilter($item->getMobileTopContent()),
            'megamenu_id' => $item->getData('megamenu_id'),
            'parent_id' => $item->getParentId(),
            'is_active' => strpos($this->getClass($item), 'active') !== false || strpos($this->getClass($item), 'has-active') !== false
        ];

        if ($type != 2) {
            $categoryId = $item->getCategoryId();
            $subCatIds = $this->getSubCategoryAccepp($categoryId, $item);
            $data['categories'] = [];
            foreach ($subCatIds as $subId) {
                if (isset($this->_loadedCategories[$subId])) {
                    $category = $this->_loadedCategories[$subId];
                    $data['categories'][] = $this->getCategoryDataArr($category, $item, 1);
                    usort($data['categories'], function ($a, $b) {
                        $posA = $a['position'] ?? PHP_INT_MAX;
                        $posB = $b['position'] ?? PHP_INT_MAX;
                        return $posA <=> $posB;
                    });
                }
            }
        } else {
            $data['static_content'] = $this->safeFilter($item->getStaticContent());
        }

        return $data;
    }

    /**
     * Get category data as array
     *
     * @param \Magento\Catalog\Model\Category $category
     * @param mixed $item
     * @param int $level
     * @return array
     */
    public function getCategoryDataArr($category, $item, $level = 1)
    {
        $maxLevel = 4;
        $childrenIds = $this->getSubCategoryAccepp($category->getId(), $item);

        $data = [
            'id' => $category->getId(),
            'name' => $category->getName(),
            'url' => $this->getCategoryUrl($category),
            'level' => $level,
            'position' => (int)$category->getPosition(),
            'product_count' => $this->getCategoryProductCount($category),
            'logo' => $this->getCategoryLogoUrl($category),
            'label' => $category->getMgsMegamenuItemLabel(),
            'label_background' => $category->getMgsMegamenuItemBackground(),
            'static_content' => $this->safeFilter($category->getData('branch8_megamenu_item_static_content')),
            'static_content_class' => $category->getData('branch8_megamenu_item_static_content_css_class'),
            'is_active' => $this->isCategoryActive($category),
            'children' => []
        ];
        if ($level == 2 || $level == 3) {
            $data['view_label'] = $this->_escaper->escapeHtml(__("查看全部"));;
        }

        if ($level < $maxLevel && count($childrenIds) > 0) {
            foreach ($childrenIds as $childId) {
                if (isset($this->_loadedCategories[$childId])) {
                    $child = $this->_loadedCategories[$childId];
                    $data['children'][] = $this->getCategoryDataArr($child, $item, $level + 1);
                }
            }
        }
        usort($data['children'], function ($a, $b) {
            $posA = $a['position'] ?? PHP_INT_MAX;
            $posB = $b['position'] ?? PHP_INT_MAX;
            return $posA <=> $posB;
        });

        return $data;
    }

    /**
     * Get category logo URL
     *
     * @param \Magento\Catalog\Model\Category $category
     * @return string
     */
    protected function getCategoryLogoUrl($category)
    {
        $logoUrl = $category->getData('branch8_megamenu_item_logo');
        if (!$logoUrl) {
            return '';
        }

        $placeHolder = $this->_scopeConfig->getValue('catalog/placeholder/thumbnail_placeholder');
        if (!$logoUrl || ($logoUrl && !$this->fileDriver->isExist($logoUrl))) {
            /** @var \Magento\Store\Model\Store $store */
            $store = $this->_storeManager->getStore();
            $mediaUrl = $store->getBaseUrl(\Magento\Framework\UrlInterface::URL_TYPE_MEDIA);
            return $mediaUrl . 'catalog/product/placeholder/' . $placeHolder;
        }

        if (strpos($logoUrl, 'http') === 0) {
            return $logoUrl;
        }

        /** @var \Magento\Store\Model\Store $store */
        $store = $this->_storeManager->getStore();
        $mediaUrl = $store->getBaseUrl();
        return $mediaUrl . ltrim($logoUrl, '/');
    }

    /**
     * Safely filter content to avoid NULL errors in Magento's Filter
     *
     * @param string|null $content
     * @return string
     */
    protected function safeFilter($content)
    {
        if ($content === null || $content === '') {
            return '';
        }
        return (string)$this->_filterProvider->getBlockFilter()->filter($content);
    }
}
