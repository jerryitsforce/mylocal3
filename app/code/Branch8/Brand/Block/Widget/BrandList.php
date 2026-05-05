<?php
/**
 * @author Amasty Team
 * @copyright Copyright (c) Amasty (https://www.amasty.com)
 * @package Shop by Brand for Magento 2
 */

namespace Branch8\Brand\Block\Widget;

use Amasty\ShopbyBase\Api\UrlBuilderInterface;
use Amasty\ShopbyBrand\Helper\Data as DataHelper;
use Amasty\ShopbyBrand\Model\Attribute;
use Amasty\ShopbyBrand\Model\Brand\BrandDataInterface;
use Amasty\ShopbyBrand\Model\Brand\BrandListDataProvider;
use Branch8\Brand\Model\Brand\BrandListDataProvider as BrandListDataProviderNew;
use Amasty\ShopbyBrand\Model\Brand\ListDataProvider\FilterItems;
use Amasty\ShopbyBrand\Model\Source\SliderSort;
use Amasty\ShopbyBrand\Model\Source\Tooltip;
use Magento\Catalog\Model\CategoryRepository;
use Magento\Eav\Model\Entity\Attribute\Option;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\Http\Context as HttpContext;
use Magento\Framework\App\Request\DataPersistorInterface;
use Magento\Framework\View\Element\Template;
use Magento\Framework\View\Element\Template\Context;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Widget\Block\BlockInterface;
use Amasty\ShopbyBrand\Block\Widget\BrandListAbstract;

class BrandList extends BrandListAbstract implements BlockInterface
{
    /**
     * deprecated. leave for back compatibility.
     */
    public const CONFIG_VALUES_PATH = 'amshopby_brand/brands_landing';

    /**
     * @var  array|null
     */
    protected $items;

    /**
     * @var DataHelper
     */
    private DataHelper $helper;

    /**
     * @var BrandListDataProviderNew
     */
    protected $brandListDataProviderNew;

    /**
     * Scope config
     *
     * @var ScopeConfigInterface
     */
    protected ScopeConfigInterface $scopeConfig;

    /**
     * @var StoreManagerInterface
     */
    protected StoreManagerInterface $storeManager;

    /**
     * @var CategoryRepository
     */
    protected CategoryRepository $categoryRepository;

    /**
     * @param Context $context
     * @param DataPersistorInterface $dataPersistor
     * @param DataHelper $helper
     * @param UrlBuilderInterface $amUrlBuilder
     * @param BrandListDataProvider $brandListDataProvider
     * @param BrandListDataProviderNew $brandListDataProviderNew
     * @param Attribute $brandAttribute
     * @param StoreManagerInterface $storeManager
     * @param CategoryRepository $categoryRepository
     * @param ScopeConfigInterface $scopeConfig
     * @param HttpContext $httpContext
     * @param array $data
     */
    public function __construct(
        DataPersistorInterface $dataPersistor,
        DataHelper $helper,
        UrlBuilderInterface $amUrlBuilder,
        BrandListDataProvider $brandListDataProvider,
        Attribute $brandAttribute,
        HttpContext $httpContext,
        Context $context,
        BrandListDataProviderNew $brandListDataProviderNew,
        StoreManagerInterface $storeManager,
        CategoryRepository $categoryRepository,
        ScopeConfigInterface $scopeConfig,
        array $data = []
    ) {
        $this->helper = $helper;
        $this->brandListDataProviderNew = $brandListDataProviderNew;
        $this->storeManager = $storeManager;
        $this->categoryRepository = $categoryRepository;
        $this->scopeConfig = $scopeConfig;
        parent::__construct($dataPersistor, $helper, $amUrlBuilder, $brandListDataProvider, $brandAttribute, $httpContext, $context, $data);
    }

    public function getCacheKeyInfo()
    {
        $parts = parent::getCacheKeyInfo();
        $parts[] = 'brand_list_by_brand';
        $parts['category'] = $this->getCategory();
        $parts['brands'] = $this->getFilterBrand();

        return $parts;
    }

    /**
     * @return array
     */
    public function getIndex()
    {
        $items = $this->getItems();

        if (!$items) {
            return [];
        }

        // $letters = $this->sortByLetters($items);
        // $index = $this->breakByColumns($letters);

        return $items;
    }

    /**
     * @param array $items
     *
     * @return array
     */
    private function sortByLetters($items)
    {
        $letters = $this->items2letters($items);

        return $letters;
    }

    /**
     * @param array $letters
     *
     * @return array
     */
    private function breakByColumns($letters)
    {
        $columnCount = abs((int)$this->getData('columns'));
        if (!$columnCount) {
            $columnCount = 1;
        }

        $row = 0; // current row
        $num = 0; // current number of items in row
        $index = [];
        foreach ($letters as $letter => $items) {
            $index[$row][$letter] = $items['items'];
            $num++;
            if ($num >= $columnCount) {
                $num = 0;
                $row++;
            }
        }

        return $index;
    }

    /**
     * @return BrandDataInterface[]
     */
    public function getItems()
    {
        if ($this->items === null) {
            $storeId = (int) $this->_storeManager->getStore()->getId();
            $listBrands = $this->getFilterBrand() ? explode(',', $this->getFilterBrand()) : [];
            $items = $this->brandListDataProviderNew->getList($storeId, $this->getItemsFilter(), SliderSort::NAME, $this->getData('category'), $listBrands);
            foreach ($items as $key => $item) {
                $selectedBrands = $this->getFilterBrand();
                if (!$selectedBrands) {
                    continue;
                }
                $selectedBrands = explode(',', $selectedBrands);
                if (!in_array($item->getBrandId(), $selectedBrands)) {
                    unset($items[$key]);
                }
            }
            $this->items = $items;
        }

        return $this->items;
    }

    private function getItemsFilter(): array
    {
        $filters = [
            FilterItems::FOR_WIDGET => true
        ];

        if (!$this->isDisplayZero()) {
            $filters[FilterItems::NOT_EMPTY] = true;
        }

        return $filters;
    }

    /**
     * @param array $items
     * @return array
     */
    protected function items2letters($items)
    {
        $letters = [];
        foreach ($items as $item) {
            $letter = $this->getLetter($item['label']);
            if (!isset($letters[$letter]['items'])) {
                $letters[$letter]['items'] = [];
            }

            $letters[$letter]['items'][] = $item;
            if (!isset($letters[$letter]['count'])) {
                $letters[$letter]['count'] = 0;
            }

            $letters[$letter]['count']++;
        }

        return $letters;
    }

    /**
     * @param $item
     * @return false|mixed|string|string[]|null
     */
    public function getLetter($label)
    {
        if (function_exists('mb_strtoupper')) {
            $letter = mb_strtoupper(mb_substr($label, 0, 1, 'UTF-8'));
        } else {
            $letter = strtoupper(substr($label, 0, 1));
        }

        if (is_numeric($letter)) {
            $letter = '#';
        }

        return $letter;
    }

    /**
     * @return array
     */
    public function getAllLetters()
    {
        $brandLetters = [];
        /** @codingStandardsIgnoreStart */
        foreach ($this->getIndex() as $letters) {
            $brandLetters = array_merge($brandLetters, array_keys($letters));
        }
        /** @codingStandardsIgnoreEnd */

        return $brandLetters;
    }

    /**
     * @return string
     */
    public function getSearchHtml()
    {
        $html = '';
        if (!$this->isShowSearch() || !$this->getItems()) {
            return $html;
        }

        $searchCollection = [];
        foreach ($this->getItems() as $item) {
            $searchCollection[$item['url']] = $item['label'];
        }

        /** @var Template $block */
        $block = $this->getSearchBrandBlock();
        if ($block) {
            // phpcs:ignore Magento2.Functions.DiscouragedFunction
            $searchCollection = json_encode($searchCollection, JSON_HEX_APOS);
            $block->setBrands($searchCollection);
            $html = $block->toHtml();
        }

        return $html;
    }

    /**
     * @return bool|\Magento\Framework\View\Element\BlockInterface
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    protected function getSearchBrandBlock()
    {
        $block = $this->getLayout()->getBlock('ambrands.search');
        if (!$block) {
            $block = $this->getLayout()->createBlock(Template::class, 'ambrands.search')
                ->setTemplate('Amasty_ShopbyBrand::brand_search.phtml');
        }

        return $block;
    }

    public function isTooltipEnabled(): bool
    {
        $setting = $this->helper->getModuleConfig('general/tooltip_enabled');

        return in_array(Tooltip::ALL_BRAND_PAGE, explode(',', $setting));
    }

    public function getTooltipAttribute(BrandDataInterface $item): string
    {
        if ($this->isTooltipEnabled()) {
            $result = $this->helper->generateToolTipContent($item);
        }

        return $result ?? '';
    }

    public function getImageWidth(): int
    {
        return abs((int) $this->getData('image_width')) ?: 100;
    }

    public function getImageHeight(): int
    {
        return abs((int) $this->getData('image_height'));
    }

    public function isShowBrandLogo(): bool
    {
        return (bool) $this->getData('show_images');
    }

    public function getTitle(): string
    {
        return $this->getData('title') ?: '';
    }


    public function getConfigValuesPath(): string
    {
        return self::CONFIG_VALUES_PATH;
    }

    /**
     * @param \Magento\Eav\Model\Entity\Attribute\Option $option
     * @return string
     */
    public function getBrandUrl(Option $option)
    {
        try {
            $categoryId = $this->scopeConfig->getValue('b8brand/general/product_category');
            if ($this->getData('category')) {
                $categoryId = $this->getData('category');
            }
            $category = $this->categoryRepository->get($categoryId, $this->storeManager->getStore()->getId());

            return $category->getUrl().'?brand='.$option->getLabel();
        } catch (\Exception $e) {
            return parent::getBrandUrl($option);
        }

    }
}
