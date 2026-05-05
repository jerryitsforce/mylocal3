<?php

namespace Branch8\MarketplaceProduct\Block;

class Collection extends \Webkul\Marketplace\Block\Collection
{
    /**
     * Get product collection
     * Custom show peding status
     *
     * @return bool|\Magento\Catalog\Model\ResourceModel\Product\Collection
     */
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

            $productname = $this->getRequest()->getParam('name');
            $querydata = $this->mpProductModel->create()
                ->getCollection()
                ->addFieldToFilter(
                    'seller_id',
                    ['eq' => $sellerId]
                )
                ->addFieldToFilter(
                    'status',
                    ['in' => [1,0]]
                )
                ->addFieldToSelect('mageproduct_id')
                ->setOrder('mageproduct_id');

            $layer = $this->getLayer();

            $origCategory = null;
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
            $collection = $layer->getProductCollection();
            $collection->addAttributeToSelect('*');
            $collection->addAttributeToFilter(
                'entity_id',
                ['in' => $querydata->getData()]
            );
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
