<?php
/**
 * Copyright © HotaiConnected. All rights reserved.
 */

namespace HotaiConnected\Cart\Service;

use Magento\Checkout\Model\Session as CheckoutSession;
use HotaiConnected\Cart\Service\DataProvider\SellerItemsProvider;
use HotaiConnected\Cart\Service\DataProvider\TotalsProvider;
use HotaiConnected\Cart\Service\DataProvider\CouponProvider;

class UnifiedCartDataService
{
    /**
     * @var CheckoutSession
     */
    private $checkoutSession;

    /**
     * @var SellerItemsProvider
     */
    private $sellerItemsProvider;

    /**
     * @var TotalsProvider
     */
    private $totalsProvider;

    /**
     * @var CouponProvider
     */
    private $couponProvider;

    /**
     * @param CheckoutSession $checkoutSession
     * @param SellerItemsProvider $sellerItemsProvider
     * @param TotalsProvider $totalsProvider
     * @param CouponProvider $couponProvider
     */
    public function __construct(
        CheckoutSession $checkoutSession,
        SellerItemsProvider $sellerItemsProvider,
        TotalsProvider $totalsProvider,
        CouponProvider $couponProvider
    ) {
        $this->checkoutSession = $checkoutSession;
        $this->sellerItemsProvider = $sellerItemsProvider;
        $this->totalsProvider = $totalsProvider;
        $this->couponProvider = $couponProvider;
    }

    /**
     * Get unified cart data
     *
     * @return array
     * @throws \Exception
     */
    public function getUnifiedCartData(): array
    {
        $quote = $this->checkoutSession->getQuote();

        if (!$quote || !$quote->getId()) {
            return $this->getEmptyCartData();
        }

        $sellerItems = $this->sellerItemsProvider->getSellerItems($quote);
        $totals = $this->totalsProvider->getTotals($quote);
        $coupons = $this->couponProvider->getCoupons($quote);

        return [
            'sellerItems' => $sellerItems,
            'applied_coupons' => $coupons['applied_coupons'] ?? null,
            'prices_coupons' => $coupons['prices_coupons'] ?? ['discounts' => [['label' => '', 'amount' => ['value' => 0]]]],
            'subtotal' => $totals['subtotal'] ?? '<span class="price">NT$0</span>',
            'subtotalAmount' => $totals['subtotalAmount'] ?? 0,
            'subtotalExclTax' => $totals['subtotalExclTax'] ?? '<span class="price">NT$0</span>',
            'subtotalInclTax' => $totals['subtotalInclTax'] ?? '<span class="price">NT$0</span>',
            'summaryCount' => $totals['summaryCount'] ?? 0,
        ];
    }

    /**
     * Get empty cart data
     *
     * @return array
     */
    private function getEmptyCartData(): array
    {
        return [
            'sellerItems' => [],
            'applied_coupons' => null,
            'prices_coupons' => [
                'discounts' => [
                    [
                        'label' => '',
                        'amount' => ['value' => 0]
                    ]
                ]
            ],
            'subtotal' => '<span class="price">NT$0</span>',
            'subtotalAmount' => 0,
            'subtotalExclTax' => '<span class="price">NT$0</span>',
            'subtotalInclTax' => '<span class="price">NT$0</span>',
            'summaryCount' => 0
        ];
    }
}
