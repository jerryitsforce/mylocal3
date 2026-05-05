<?php
/**
 * Copyright © HotaiConnected. All rights reserved.
 */

namespace HotaiConnected\Cart\Service\DataProvider;

use Branch8\HotaiShipping\Helper\Data as HotaiShippingHelper;
use Magento\Framework\Pricing\Helper\Data as PriceHelper;

class FreeShippingProvider
{
    /**
     * @var HotaiShippingHelper
     */
    private $hotaiShippingHelper;

    /**
     * @var PriceHelper
     */
    private $priceHelper;

    /**
     * @param HotaiShippingHelper $hotaiShippingHelper
     * @param PriceHelper $priceHelper
     */
    public function __construct(
        HotaiShippingHelper $hotaiShippingHelper,
        PriceHelper $priceHelper
    ) {
        $this->hotaiShippingHelper = $hotaiShippingHelper;
        $this->priceHelper = $priceHelper;
    }

    /**
     * Get free shipping status for sub cart
     *
     * @param string $subCartType
     * @param array $sellerShippingMethods
     * @param array $items
     * @return array
     */
    public function getFreeShippingStatus(string $subCartType, array $sellerShippingMethods, array $items): array
    {
        $freeShippingData = $this->hotaiShippingHelper->getSubCartFreeShippingData(
            $subCartType,
            $sellerShippingMethods,
            $items
        );

        if (empty($freeShippingData)) {
            return [
                'sellerSubtotal' => $this->priceHelper->currency(0, true, false),
                'shippingMethods' => [],
                'statusList' => []
            ];
        }

        $shippingMethods = [];
        $statusList = [];
        $subCartTotal = $freeShippingData['sub_cart_total'] ?? 0;

        // Process each shipping method
        foreach ($freeShippingData['carries_shipping_fee'] as $methodCode => $methodData) {
            $threshold = $methodData['threshold'] ?? 0;
            $reached = $subCartTotal >= $threshold;

            $message = $reached
                ? (string)__('已達免運門檻')
                : (string)__('差<span>%1</span>達免運門檻', $this->priceHelper->currency($threshold - $subCartTotal, true, false));

            $shippingMethods[] = [
                'message' => $message,
                'methodName' => $methodData['title'] ?? '',
                'reached' => $reached,
                'shippingFee' => $methodData['shipping_fee_formated'] ?? $this->priceHelper->currency(0, true, false)
            ];

            // Add to statusList (summary of all methods)
            $statusList[] = [
                'message' => $message,
                'reached' => $reached
            ];
        }

        return [
            'sellerSubtotal' => $this->priceHelper->currency($subCartTotal, true, false),
            'shippingMethods' => $shippingMethods,
            'statusList' => $statusList
        ];
    }
}
