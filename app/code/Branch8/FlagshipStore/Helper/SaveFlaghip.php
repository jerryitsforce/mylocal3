<?php

namespace Branch8\FlagshipStore\Helper;

use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\App\Helper\Context;
use Magento\Store\Model\ScopeInterface;

class SaveFlaghip extends AbstractHelper
{
    const FLAGSHIP_STORE_IDENTIFY = 'flagship-store-process-vproduct';
    /**
     * @var \Magento\Catalog\Model\ResourceModel\Category\CollectionFactory
     */
    protected $categoryCollectionFactory;
    /**
     * @var \Magento\Catalog\Model\ProductFactory
     */
    protected $_product;
    /**
     * @var ProductRepositoryInterface
     */
    protected $productRepository;
    /**
     * @var \Magento\Catalog\Model\ResourceModel\Product\CollectionFactory
     */
    protected $productCollectionFactory;
    /**
     * @var \Magento\CatalogInventory\Api\StockRegistryInterface
     */
    protected $_stockRegistry;
    /**
     * @var \Magento\Eav\Model\ResourceModel\Entity\Attribute
     */
    protected $eavAttribute;

    protected $eavConfig;

    public function __construct(
        Context $context,
        \Magento\Catalog\Model\ResourceModel\Category\CollectionFactory $categoryCollectionFactory,
        \Magento\Catalog\Model\ResourceModel\Product\CollectionFactory $productCollectionFactory,
        \Magento\Catalog\Model\ProductFactory $productFactory,
        ProductRepositoryInterface $productRepository,
        \Magento\CatalogInventory\Api\StockRegistryInterface $stockRegistry,
        \Magento\Eav\Model\ResourceModel\Entity\Attribute $eavAttribute,
        \Magento\Eav\Model\Config $eavConfig
    )
    {
        $this->categoryCollectionFactory = $categoryCollectionFactory;
        $this->_product = $productFactory;
        $this->productRepository = $productRepository;
        $this->productCollectionFactory = $productCollectionFactory;
        $this->_stockRegistry = $stockRegistry;
        $this->eavAttribute = $eavAttribute;
        parent::__construct($context);
        $this->eavConfig = $eavConfig;
    }
    public function autoAssignFlagshipCategoryToSeller($categoryId, $sellerIds, $conn){
        $allowedCates = [$categoryId];
        /**
         * Load category and childs
         */
        $category = $this->categoryCollectionFactory->create()
            ->addAttributeToFilter('entity_id', $categoryId)
            ->getFirstItem();
        $childs = $category->getAllChildren(true);
        /**
         * Load flagship category config
         */
        $flagshipCategory = $this->scopeConfig->getValue(
            \Branch8\FlagshipStore\Helper\Data::FLAGSHIP_CATEGORY_CONFIG,
            ScopeInterface::SCOPE_STORE
        );
        $flagshipCates = explode(',', (string)$flagshipCategory);
        /**
         * Find the intersect category
         */
        $missedCates = array_intersect($childs, $flagshipCates);
        $allowedCates = array_unique(array_merge($allowedCates, $missedCates));

        /**
         * Load Seller category, update flagship category
         */
        foreach($sellerIds as $_sid){
            $sellerCatesQuery = $conn->select()
                ->from(['mp_userdata' => 'marketplace_userdata'], ['allowed_categories'])
                ->where('seller_id = ?', (int)$_sid);
            $sellerCates = explode(',', (string)$conn->fetchOne($sellerCatesQuery));
            $sellerMissedCate = array_diff($allowedCates, $sellerCates);
            $newCatesArr = array_merge($sellerCates, $sellerMissedCate);
            $newCateStr = implode(',', $newCatesArr);
            $sqlUpdateAssign = 'update marketplace_userdata set allowed_categories="'.$newCateStr.'" where seller_id='.(int)$_sid;
            $conn->query($sqlUpdateAssign);
        }

    }

    public function assignVirtualFakeProduct($mainSellerId, $conn)
    {
        $querySku = $this->getSellerIdentity($mainSellerId);
        $sqlCheck = $this->productCollectionFactory->create()
            ->addAttributeToFilter('sku', ['like' => '%'.$querySku.'%']);
        $sqlCheck->getSelect()
            ->joinLeft(['mp_product' => 'marketplace_product'], 'mp_product.mageproduct_id = e.entity_id and mp_product.mage_pro_row_id = e.row_id', [])
            ->columns(['cnt' => 'count(*)'])
            ->where('seller_id = ?', $mainSellerId)
            ->limit(1);
        $res = $conn->fetchAll($sqlCheck->getSelect());
        if(!$res[0]['cnt']){
            $this->createVirtualFakeProduct($mainSellerId);
        }
    }

    public function getSellerIdentity($sellerId)
    {
        return self::FLAGSHIP_STORE_IDENTIFY.'-' . $sellerId;
    }

    public function createVirtualFakeProduct($sellerId)
    {

        $_product = $this->_product->create();
        $_product->setName('旗艦館商品運費');
        $_product->setTypeId('virtual');
        $_product->setAttributeSetId(4);
        $sku = 'HOTAI' . time() . '-'. $this->getSellerIdentity($sellerId);
        $_product->setSku($sku);
        $_product->setWebsiteIds(array(1));
        $_product->setVisibility(1);
        $_product->setPrice(0);
        $_product->setSpecialPrice(0);
        $_product->setCost(0);
        $_product->setCommissionPercent(0);
        $_product->setShippingMethod(['electronic']);
        $_product->setFlagshipStoreProcessSellerId($sellerId);
        $_product->setAssignSeller(['seller_id' => $sellerId]);

        $brandCode = 'brand';
        $attribute = $this->eavConfig->getAttribute('catalog_product', $brandCode);
        $brandOptions = $attribute->getSource()->getAllOptions();
        $firstOption = '';
        foreach($brandOptions as $_brandOption){
            if($_brandOption['value'] != ''){
                $firstOption = $_brandOption['value'];
            }
        }
        $_product->setBrand($firstOption);

        $product = $this->productRepository->save($_product);

        $productId = $product->getId();
        $stockItem=$this->_stockRegistry->getStockItem($productId); // load stock of that product
        $stockItem->setData('is_in_stock', 1); //set updated data as your requirement
        $stockItem->setData('qty', 10000); //set updated quantity
        $stockItem->setData('use_config_manage_stock', 0);
        $stockItem->setData('manage_stock', 0);
        $stockItem->setData('min_sale_qty', 1);
        $stockItem->setData('max_sale_qty', 1);



        $stockItem->save();

        $this->_eventManager->dispatch('create_flagship_store_process_product', [
            'product' => $product
        ]);
    }

    public function removeFlagshipCategoryFromSeller($sellerIds, $conn)
    {
        /**
         * Load flagship category config
         */
        $flagshipCategory = $this->scopeConfig->getValue(
            \Branch8\FlagshipStore\Helper\Data::FLAGSHIP_CATEGORY_CONFIG,
            ScopeInterface::SCOPE_STORE
        );
        $flagshipCates = explode(',', (string)$flagshipCategory);
        foreach($sellerIds as $_sid) {
            $sellerCatesQuery = $conn->select()
                ->from(['mp_userdata' => 'marketplace_userdata'], ['allowed_categories'])
                ->where('seller_id = ?', (int)$_sid);
            $sellerCategories = $conn->fetchOne($sellerCatesQuery);
            $sellerCategoriesArr = explode(',', (string)$sellerCategories);
            $newSellerCategories = array_diff($sellerCategoriesArr, $flagshipCates);
            $newSellerCategoriesStr = implode(',', $newSellerCategories);

            $sqlUpdateCategories = 'update marketplace_userdata set allowed_categories="'.$newSellerCategoriesStr.'" where seller_id='.$_sid;
            $conn->query($sqlUpdateCategories);

        }
    }


    public function removeFlagshipCategoryFromSellerProduct($sellerIds, $conn)
    {
        if(empty($sellerIds)){
            return ;
        }
        $sellerProductIdsQuery = $conn->select()
            ->from(['mp_product' => 'marketplace_product'], ['mage_pro_row_id'])
            ->where('seller_id in (?)', implode(',', $sellerIds));
        $allProductRowIds = $conn->fetchCol($sellerProductIdsQuery);
        $productRowIds = implode(',', $allProductRowIds);

        $flagstoreCategoryAttrId = $this->eavAttribute->getIdByCode(\Magento\Catalog\Model\Product::ENTITY, 'flagstore_category');

        if(!empty($productRowIds)){
            $sqlRemoveValue = 'delete from catalog_product_entity_int where row_id in('.$productRowIds.') and attribute_id='.$flagstoreCategoryAttrId;
            $conn->query($sqlRemoveValue);
        }

    }
}