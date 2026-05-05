<?php

namespace Branch8\MarketplaceProduct\Block;

use Magento\Catalog\Api\CategoryRepositoryInterface;
use Magento\Catalog\Model\Layer;
use Magento\Catalog\Model\ResourceModel\Product\CollectionFactory;
use Magento\Framework\App\ObjectManager;
use Magento\Framework\Exception\LocalizedException;
use Webkul\Marketplace\Helper\Data as MpHelper;
use Webkul\Marketplace\Model\ProductFactory as MpProductModel;

class  SellerProductCollection extends \Webkul\Marketplace\Block\Collection
{
    private $isChangeLayer = null;

    public function _getProductCollection()
    {
        if (!$this->_productlists) {
            $paramData = $this->getRequest()->getParams();
            $partner = $this->getProfileDetail();
            try {
                $sellerId = $partner->getSellerId();
            } catch (\Exception $e) {
                $sellerId = 0;
            }
            /**
             * 1. MUST sync seller ID to live search
             * 2. If empty seller , we will stop working
             * 2. use add addFieldToFilter let LiveSearch can build QueryBuilder
             */
            if (!$sellerId) {
                throw new LocalizedException(__('Can not find seller'));
            }
            $layer = $this->getLayer();
            $origCategory = null;
           // $paramData['cat'] = 26;
            if (isset($paramData['c']) || isset($paramData['cat'])) {
                try {
                    if (isset($paramData['c'])) {
                        $catId = $paramData['c'];
                    }
                    if (isset($paramData['cat'])) {
                        $catId = $paramData['cat'];
                    }
                    $category = $this->_categoryRepository->get($catId);
                } catch (\Exception $e) {
                    $category = null;
                }
                if ($category) {
                    $origCategory = $layer->getCurrentCategory();
                    $layer->setCurrentCategory($category);
                }
            }
            /**
             * @var $collection \Magento\CatalogStaging\Model\ResourceModel\Fulltext\Collection
             */
            $collection = $layer->getProductCollection();

            /**
             * This code help to build search filter with seller ID
             * \Magento\LiveSearchAdapter\Model\ResourceModel\Fulltext\Collection
             */
            $collection->addFieldToFilter('sellerId', $sellerId);
            $this->prepareSortableFieldsByCategory($layer->getCurrentCategory());
            $this->_productlists = $collection;
            if ($origCategory) {
                $layer->setCurrentCategory($origCategory);
            }
            $toolbar = $this->getToolbarBlock();
            $this->configureProductToolbar($toolbar, $collection);
            $this->_eventManager->dispatch(
                'catalog_block_product_list_collection',
                ['collection' => $collection]
            );
        }
        $this->_productlists->getSize();

        return $this->_productlists;
    }
}
