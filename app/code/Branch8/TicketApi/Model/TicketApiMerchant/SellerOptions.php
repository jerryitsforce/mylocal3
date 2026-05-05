<?php

namespace Branch8\TicketApi\Model\TicketApiMerchant;

use Webkul\Marketplace\Model\ResourceModel\Seller\CollectionFactory as SellerCollectionFactory;

class SellerOptions implements \Magento\Framework\Data\OptionSourceInterface
{
    /** @var SellerCollectionFactory */
    protected $sellerCollectionFactory;

    private $options = null;

    public function __construct(
        SellerCollectionFactory $sellerCollectionFactory
    ) {
        $this->sellerCollectionFactory = $sellerCollectionFactory;
    }

    public function toOptionArray()
    {
        if ($this->options === null) {
            $sellerCollection = $this->sellerCollectionFactory->create();
            $sellerArray      = $sellerCollection->getItems();

            foreach ($sellerArray as $seller) {
                $this->options[] = [
                    'value' => $seller->getSellerId(),
                    'label' => $seller->getSellerId() . " - " . $seller->getCompanyName(),
                ];
            }
        }

        return $this->options;
    }
}