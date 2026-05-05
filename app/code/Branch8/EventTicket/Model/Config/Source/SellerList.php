<?php

namespace Branch8\EventTicket\Model\Config\Source;

use Webkul\Marketplace\Model\ResourceModel\Seller\CollectionFactory as SellerCollection;

class SellerList implements \Magento\Framework\Option\ArrayInterface
{
    /**
     * @var SellerCollection
     */
    protected $sellerCollection;

    /**
     * @param SellerCollection $sellerCollectionFactory
     */
    public function __construct(
        SellerCollection $sellerCollectionFactory
    ){
        $this->sellerCollection = $sellerCollectionFactory;
    }

    /**
     * @return array
     */
    public function toOptionArray()
    {
        $sellerData = [];
        $sellerColl = $this->sellerCollection->create()
            ->addFieldToFilter('is_seller', ['in' => [
                \Webkul\Marketplace\Model\Seller::STATUS_PENDING,
                \Webkul\Marketplace\Model\Seller::STATUS_ENABLED,
                \Webkul\Marketplace\Model\Seller::STATUS_PROCESSING,
            ]]);
        foreach($sellerColl as $_seller){
            if(!empty($_seller->getSellerCode())) {
                $sellerData[] = ['value' => $_seller->getSellerId(), 'label' => $_seller->getSellerCode()];
            }
        }
        return $sellerData;
    }
}