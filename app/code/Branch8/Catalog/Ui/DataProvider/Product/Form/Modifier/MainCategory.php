<?php

namespace Branch8\Catalog\Ui\DataProvider\Product\Form\Modifier;

use Magento\Catalog\Api\Data\ProductAttributeInterface;
use Magento\Catalog\Model\Category;
use Magento\Catalog\Model\Locator\LocatorInterface;
use Magento\Catalog\Ui\DataProvider\Product\Form\Modifier\AbstractModifier;
use Magento\Catalog\Model\ResourceModel\Category\CollectionFactory as CategoryCollectionFactory;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\UrlInterface;
use Magento\Store\Model\ScopeInterface;
use Magento\Ui\Component\Form\Field;
use Webkul\Marketplace\Helper\Data;
use Magento\Framework\App\Config\ScopeConfigInterface;

class MainCategory extends AbstractModifier
{
    const MAIN_CATEGORY_FIELD_ORDER = 31;
    const FLAGSTORE_CATEGORY_FIELD_ORDER = 32;

    /**
     * @var UrlInterface
     */
    protected $urlBuilder;

    /**
     * @var LocatorInterface
     */
    protected $locator;

    /**
     * @var Data
     */
    protected $marketplaceHelper;

    /**
     * @var CategoryCollectionFactory
     */
    protected $categoryCollectionFactory;

    /**
     * @var ScopeConfigInterface
     */
    protected ScopeConfigInterface $scopeConfig;
    protected array $allowedCategoryIds;
    protected array $flagshipCategory;
    protected array $shownCategoriesIds;
    protected int $minLevel = 2;
    /**
     * @var \Branch8\FlagshipStore\Helper\Sales
     */
    protected $flagshipStoreSalesHelper;

    protected $flagshipSoreId = null;

    /**
     * @param LocatorInterface $locator
     * @param UrlInterface $urlBuilder
     * @param Data $marketplaceHelper
     * @param CategoryCollectionFactory $categoryCollectionFactory
     * @param ScopeConfigInterface $scopeConfig
     * @param \Branch8\FlagshipStore\Helper\Sales $flagshipStoreSalesHelper
     */
    public function __construct(
        LocatorInterface $locator,
        UrlInterface $urlBuilder,
        Data $marketplaceHelper,
        CategoryCollectionFactory $categoryCollectionFactory,
        ScopeConfigInterface $scopeConfig,
        \Branch8\FlagshipStore\Helper\Sales $flagshipStoreSalesHelper
    ) {
        $this->locator = $locator;
        $this->urlBuilder = $urlBuilder;
        $this->marketplaceHelper = $marketplaceHelper;
        $this->categoryCollectionFactory = $categoryCollectionFactory;
        $this->scopeConfig = $scopeConfig;
        $this->flagshipStoreSalesHelper = $flagshipStoreSalesHelper;
    }

    public function modifyMeta(array $meta){
        if ($name = $this->getGeneralPanelName($meta)) {
            $meta[$name]['children']['main_category']['arguments']['data']['config']  = [
                'component' => 'Branch8_Catalog/js/components/new-main-category',
                'disableLabel' => true,
                'filterOptions' => true,
                'chipsEnabled' => true,
                'elementTmpl' => 'Branch8_Catalog/grid/filters/elements/ui-select',
                'formElement' => 'multiselect',
                'componentType' => Field::NAME,
                'options' => $this->getOptions(),
                'visible' => 1,
                'required' => 1,
                'allowSelectLevel' => 4,
                'label' => __('Main Category'),
                'source' => $name,
                'sortOrder' => $this->getNextAttributeSortOrder(
                    $meta,
                    [ProductAttributeInterface::CODE_WEIGHT],
                    self::MAIN_CATEGORY_FIELD_ORDER
                ),
                'disabled' => $this->locator->getProduct()->isLockedAttribute('main_category'),
                'validation' => ['required-entry' => 1],
            ];
            $isInFlagShipStore = $this->isInFlagshipStore();
            $isFlagstoreVisible = !empty($this->getFlagshipCategoryIds()) && $isInFlagShipStore;
            $meta[$name]['children']['flagstore_category']['arguments']['data']['config']  = [
                'component' => 'Branch8_Catalog/js/components/new-main-category',
                'disableLabel' => true,
                'filterOptions' => true,
                'chipsEnabled' => true,
                'elementTmpl' => 'Branch8_Catalog/grid/filters/elements/ui-select',
                'formElement' => 'multiselect',
                'componentType' => Field::NAME,
                'visible' => $isFlagstoreVisible ? 1 : 0,
                'options' => $this->getOptions(true),
                'required' => $isFlagstoreVisible ? 1 : 0,
                'allowSelectLevel' => $this->minLevel,
                'label' => __('Flagstore Category'),
                'source' => $name,
                'sortOrder' => $this->getNextAttributeSortOrder(
                    $meta,
                    [ProductAttributeInterface::CODE_WEIGHT],
                    self::FLAGSTORE_CATEGORY_FIELD_ORDER
                ),
                'disabled' => $this->locator->getProduct()->isLockedAttribute('flagstore_category'),
                'validation' => ['required-entry' => $isFlagstoreVisible ? 1 : 0],
            ];
            if ($this->locator->getProduct()->getTypeId() == 'virtual' && isset($meta[$name]['children']['container_shipping_method'])) {
                $meta[$name]['children']['container_shipping_method']['children']['shipping_method']['arguments']['data']['config']['visible'] = 0;
            }

            /**
             * Hide the attribute that detect the seller <> virtual fake product for processing shipping flagship order
             */
            $meta[$name]['children']['container_flagship_store_process_seller_id']['children']['flagship_store_process_seller_id']['arguments']['data']['config']['visible'] = 0;


        }

        return $meta;
    }

    protected function isInFlagshipStore()
    {
        $result = false;
        $productId = $this->locator->getProduct()->getId();
        $sellerId = (int)$this->marketplaceHelper->getSellerIdByProductId($productId);
        $flshipStoreId = $this->flagshipStoreSalesHelper->getFlagshipStoreFromSellerId($sellerId);
        if((int)$flshipStoreId){
            $this->flagshipSoreId = (int)$flshipStoreId;
            $result = true;
        }

        return $result;
    }

    /**
     * @inheritdoc
     * @since 101.0.0
     */
    public function modifyData(array $data){
        return array_replace_recursive(
            $data,
            [
                $this->locator->getProduct()->getId() => [
                    self::DATA_SOURCE_DEFAULT => [
                        'main_category' => $this->locator->getProduct()->getMainCategory()
                    ],
                ]
            ]
        );
    }

    /**
     * Get Options
     *
     * @return array
     * @throws LocalizedException
     */
    public function getOptions($flagship = false)
    {
        $flagshipCategoryConfig = $this->getFlagshipCategory();
        $shownCategoriesIds = $this->getShownCategoryIds();
        $shownCategoriesIds = array_keys($shownCategoriesIds);
        if (!$flagship) {
            $shownCategoriesIds = array_diff($shownCategoriesIds, $flagshipCategoryConfig);
        } else {
            $shownCategoriesIds = array_intersect($shownCategoriesIds, $flagshipCategoryConfig);
            $shownCategoriesIds = $this->getShownFlagshipCategoryIds($shownCategoriesIds);
        }



        if(!count($shownCategoriesIds)){
            return [];
        }
        $catCollection = $this->categoryCollectionFactory->create()
            ->addAttributeToSelect(['name', 'is_active', 'parent_id', 'level'])
            ->addAttributeToFilter('entity_id', ['in' => $shownCategoriesIds]);

        $sellerCategory = [
            Category::TREE_ROOT_ID => [
                'value' => Category::TREE_ROOT_ID,
                'optgroup' => null,
            ],
        ];
        $flagshipStoreCate = null;
        if($flagship && $this->flagshipSoreId){
            $flagshipStoreInfor = $this->flagshipStoreSalesHelper->getFlagshipStoreInfor($this->flagshipSoreId);
            $flagshipStoreCate = $flagshipStoreInfor['category_id'];
        }

        foreach ($catCollection as $category) {
            $catId = $category->getId();
            $catParentId = $category->getParentId();

            /**
             * Specify flatship
             */
            if($flagshipStoreCate){
                if($category->getLevel() == 3 && $flagshipStoreCate != $catId){
                    continue;
                }
            }

            foreach ([$catId, $catParentId] as $categoryId) {
                if (!isset($sellerCategory[$categoryId])) {
                    $sellerCategory[$categoryId] = ['value' => $categoryId];
                }
            }

            $sellerCategory[$catId]['is_active'] = $category->getIsActive();
            $sellerCategory[$catId]['label'] = $category->getName();
            $sellerCategory[$catParentId]['optgroup'][] = &$sellerCategory[$catId];
        }
//var_dump($sellerCategory);die;
        return $sellerCategory[Category::TREE_ROOT_ID]['optgroup'];
    }

    /**
     * Get Shown Category Ids
     *
     * @return array
     * @throws LocalizedException
     */
    public function getShownCategoryIds()
    {
        if (isset($this->shownCategoriesIds)) {
            return $this->shownCategoriesIds;
        }
        $categoryCollection = $this->categoryCollectionFactory->create();
        $allowedCategoryIds = $this->getAllowedCategoryIds();

        if (!empty($allowedCategoryIds)) {
            $categoryCollection->addAttributeToSelect('path')
                ->addAttributeToFilter('entity_id', ['in' => $allowedCategoryIds]);
        } else {
            $categoryCollection->addAttributeToSelect('path')
                ->addAttributeToFilter('entity_id', ['neq' => Category::TREE_ROOT_ID]);
        }

        $shownCategoriesIds = [];

        /** @var \Magento\Catalog\Model\Category $category */
        foreach ($categoryCollection as $category) {
            foreach (explode('/', $category['path']) as $parentId) {
                $shownCategoriesIds[$parentId] = 1;
            }
        }

        $this->shownCategoriesIds = $shownCategoriesIds;

        return $this->shownCategoriesIds;
    }

    /**
     * Get Shown Category Ids
     *
     * @return array
     * @throws LocalizedException
     */
    public function getShownFlagshipCategoryIds($arrayId)
    {
        if (empty($arrayId)) {
            return [];
        }
        $categoryCollection = $this->categoryCollectionFactory->create();
        $categoryCollection->addAttributeToSelect('level')->addAttributeToSelect('path')
            ->addAttributeToFilter('entity_id', ['in' => $arrayId]);

        $shownCategoriesIds = [];

        /** @var \Magento\Catalog\Model\Category $category */
        $level = 0;
        foreach ($categoryCollection as $category) {
            if (!$level) {
                $level = $category['level'];
            } elseif ($level > $category['level']) {
                $level = $category['level'];
            }
            foreach (explode('/', $category['path']) as $parentId) {
                $shownCategoriesIds[$parentId] = 1;
            }
        }
        $this->minLevel = $level;

        return array_keys($shownCategoriesIds);
    }

    /**
     * Get Allowed Category Ids
     *
     * @return array
     */
    public function getAllowedCategoryIds()
    {
        if (isset($this->allowedCategoryIds)) {
            return $this->allowedCategoryIds;
        }
        $allowedCategories = '';
        $sellerId = $this->marketplaceHelper->getSellerIdByProductId($this->locator->getProduct()->getId());
        $model = $this->marketplaceHelper->getSellerCollection()
            ->addFieldToFilter('seller_id', $sellerId)
            ->addFieldToFilter('store_id', 0);
        foreach ($model as $key => $value) {
            $allowedCategories = $value['allowed_categories'];
        }

        if ($allowedCategories) {
            $this->allowedCategoryIds = explode(',', $allowedCategories);
        } else {
            $this->allowedCategoryIds = [];
        }
        return $this->allowedCategoryIds;
    }

    /**
     * Get Flagship Category
     *
     * @return array
     */
    public function getFlagshipCategory()
    {
        if (!isset($this->flagshipCategory)) {
            $this->flagshipCategory = [];
            $flagshipCategory = $this->scopeConfig->getValue(
                'seller_flagship/general/categories',
                ScopeInterface::SCOPE_STORE
            );
            if ($flagshipCategory) {
                $this->flagshipCategory = explode(',', $flagshipCategory);
            }
        }
        return $this->flagshipCategory;
    }

    /**
     * Get Flagship Category Ids
     *
     * @return array
     * @throws LocalizedException
     */
    public function getFlagshipCategoryIds()
    {
        if (!empty($this->getAllowedCategoryIds())) {
            $flagshipCategory = $this->getFlagshipCategory();
            $shownCategoriesIds = $this->getShownCategoryIds();
            $shownCategoriesIds = array_keys($shownCategoriesIds);
            return array_intersect($shownCategoriesIds, $flagshipCategory);
        }
        return [];
    }
}
