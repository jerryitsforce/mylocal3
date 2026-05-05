<?php

namespace Branch8\Marketplace\Plugin\Webkul\Marketplace\Helper;

use Magento\Catalog\Model\ResourceModel\Product\CollectionFactory as ProductCollection;
use Webkul\Marketplace\Helper\Data as HelperData;

class Data
{
    /**
     * @var ProductCollection
     */
    protected $_productCollectionFactory;

    /**
     * Constructor.
     * @param ProductCollection $productCollectionFactory
     */
    public function __construct(
        ProductCollection $productCollectionFactory
    ) {
        $this->_productCollectionFactory = $productCollectionFactory;
    }

    /**
     * Return the seller data by seller id.
     *
     * @param HelperData $subject
     * @param \Closure $proceed
     * @param int $sellerId
     * @return string[]
     */
    public function aroundGetSellerInfo(HelperData $subject, \Closure $proceed, $sellerId)
    {
        $sellerId = (int) $sellerId;
        $details = ['shop_url' => '', 'shop_title' => '', 'product_count' => ''];
        $sellerCollection = $subject->getSellerCollectionObj($sellerId);
        $sellerCollection = $subject->joinCustomer($sellerCollection);

        /*$collection = $this->_productCollectionFactory->create();
        $collection->joinSellerProducts();
        $collection->resetColumns();
        $collection->addFieldToCollection("count", "count(mp_product.seller_id)");
        $collection->getSelect()->where("mp_product.seller_id = $sellerId");
        $query = $collection->getSelectSql(true);*/
        $sellerCollection->resetColumns();
        $fields = ["entity_id", "shop_title", "shop_url", "company_locality", "logo_pic", "hot_tag", "allowed_categories"];
        $sellerCollection->addFieldsToCollection($fields);
        //$sellerCollection->addFieldToCollection("product_count", "($query)");
        $data = $sellerCollection->getData();
        foreach ($data as $key => $info) {
            $info['product_count'] = '';
            return $info;
        }

        return $details;
    }

    /**
     * Clean Cache
     */
    public function aroundClearCache(HelperData $subject, \Closure $proceed)
    {
        //remove clear cache code
    }
}
