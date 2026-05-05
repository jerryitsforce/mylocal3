<?php
/**
 * Copyright © HotaiConnected. All rights reserved.
 */

namespace HotaiConnected\Logistics\Model\Config\Source;

use Magento\Framework\Data\OptionSourceInterface;
use Webkul\Marketplace\Model\ResourceModel\Seller\CollectionFactory;

/**
 * Seller Options Source
 */
class Seller implements OptionSourceInterface
{
    /**
     * @var CollectionFactory
     */
    protected $sellerCollectionFactory;

    /**
     * @param CollectionFactory $sellerCollectionFactory
     */
    public function __construct(
        CollectionFactory $sellerCollectionFactory
    ) {
        $this->sellerCollectionFactory = $sellerCollectionFactory;
    }

    /**
     * Get options
     *
     * @return array
     */
    public function toOptionArray()
    {
        $options = [];

        // Get seller collection
        $collection = $this->sellerCollectionFactory->create();
        $collection->addFieldToFilter('is_seller', ['eq' => 1]); // All sellers

        // Add empty option
        $options[] = [
            'value' => '',
            'label' => __('-- 請選擇特約商 --')
        ];

        foreach ($collection as $seller) {
            // Use shop_title or company_name as label
            $label = $seller->getShopTitle() ?: $seller->getCompanyName();

            // If still no label, use seller_id
            if (!$label) {
                $label = __('Seller #%1', $seller->getSellerId());
            }

            // Add seller code if available
            if ($seller->getSellerCode()) {
                $label .= ' (' . $seller->getSellerCode() . ')';
            }

            $options[] = [
                'value' => $seller->getSellerId(),
                'label' => $label
            ];
        }

        return $options;
    }
}
