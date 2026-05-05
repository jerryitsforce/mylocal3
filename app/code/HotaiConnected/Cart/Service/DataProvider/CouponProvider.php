<?php
/**
 * Copyright © HotaiConnected. All rights reserved.
 */

namespace HotaiConnected\Cart\Service\DataProvider;

use Magento\Quote\Model\Quote;
use Magento\Quote\Api\CartTotalRepositoryInterface;
use Psr\Log\LoggerInterface;

class CouponProvider
{
    /**
     * @var CartTotalRepositoryInterface
     */
    private $cartTotalRepository;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @param CartTotalRepositoryInterface $cartTotalRepository
     * @param LoggerInterface $logger
     */
    public function __construct(
        CartTotalRepositoryInterface $cartTotalRepository,
        LoggerInterface $logger
    ) {
        $this->cartTotalRepository = $cartTotalRepository;
        $this->logger = $logger;
    }

    /**
     * Get coupon data (GraphQL compatible format)
     *
     * @param Quote $quote
     * @return array
     */
    public function getCoupons(Quote $quote): array
    {
        $couponCode = $quote->getCouponCode();

        if (!$couponCode) {
            return [
                'applied_coupons' => null,
                'prices_coupons' => [
                    'discounts' => [
                        [
                            'label' => '',
                            'amount' => ['value' => 0]
                        ]
                    ]
                ]
            ];
        }

        // applied_coupons - 只返回 code (與 GraphQL 格式一致)
        $appliedCoupons = $couponCode;

        // prices_coupons - 折扣詳細資訊
        $pricesCoupons = [
            'discounts' => []
        ];

        try {
            $totals = $this->cartTotalRepository->get($quote->getId());
            $discountAmount = abs($totals->getDiscountAmount());

            if ($discountAmount > 0) {
                $pricesCoupons['discounts'][] = [
                    'label' => $this->getDiscountLabel($quote, $couponCode),
                    'amount' => [
                        'value' => $discountAmount
                    ]
                ];
            } else {
                $pricesCoupons['discounts'][] = [
                    'label' => '',
                    'amount' => ['value' => 0]
                ];
            }
        } catch (\Exception $e) {
            $this->logger->warning('Failed to get coupon discount amount', [
                'quote_id' => $quote->getId(),
                'coupon_code' => $couponCode,
                'exception' => $e->getMessage()
            ]);
            $pricesCoupons['discounts'][] = [
                'label' => '',
                'amount' => ['value' => 0]
            ];
        }

        return [
            'applied_coupons' => $appliedCoupons,
            'prices_coupons' => $pricesCoupons
        ];
    }

    /**
     * Get discount label
     *
     * @param Quote $quote
     * @param string $couponCode
     * @return string
     */
    private function getDiscountLabel(Quote $quote, string $couponCode): string
    {
        // Check if there's a specific discount description from applied rules
        $address = $quote->isVirtual() ? $quote->getBillingAddress() : $quote->getShippingAddress();

        if ($address && $address->getDiscountDescription()) {
            return $address->getDiscountDescription();
        }

        return 'Discount (' . $couponCode . ')';
    }
}
