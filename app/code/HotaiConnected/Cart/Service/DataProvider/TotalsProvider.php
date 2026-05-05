<?php
/**
 * Copyright © HotaiConnected. All rights reserved.
 */

namespace HotaiConnected\Cart\Service\DataProvider;

use Magento\Quote\Model\Quote;
use Magento\Quote\Api\CartTotalRepositoryInterface;
use Magento\Framework\Pricing\Helper\Data as PriceHelper;
use Psr\Log\LoggerInterface;

class TotalsProvider
{
    /**
     * @var CartTotalRepositoryInterface
     */
    private $cartTotalRepository;

    /**
     * @var PriceHelper
     */
    private $priceHelper;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @param CartTotalRepositoryInterface $cartTotalRepository
     * @param PriceHelper $priceHelper
     * @param LoggerInterface $logger
     */
    public function __construct(
        CartTotalRepositoryInterface $cartTotalRepository,
        PriceHelper $priceHelper,
        LoggerInterface $logger
    ) {
        $this->cartTotalRepository = $cartTotalRepository;
        $this->priceHelper = $priceHelper;
        $this->logger = $logger;
    }

    /**
     * Get cart totals with formatted prices
     *
     * @param Quote $quote
     * @return array
     */
    public function getTotals(Quote $quote): array
    {
        try {
            $totals = $this->cartTotalRepository->get($quote->getId());

            $subtotalAmount = $totals->getSubtotal();
            $subtotalInclTaxAmount = $totals->getSubtotalInclTax();
            $grandTotalAmount = $totals->getGrandTotal();

            return [
                // 格式化的價格字串（含 HTML）
                // 注意：subtotal 和 subtotalAmount 應該使用含稅價格（與 section/load 一致）
                'subtotal' => $this->formatPriceHtml($subtotalInclTaxAmount),
                'subtotalExclTax' => $this->formatPriceHtml($subtotalAmount),
                'subtotalInclTax' => $this->formatPriceHtml($subtotalInclTaxAmount),

                // 數字金額（使用含稅金額與 section/load 一致）
                'subtotalAmount' => $subtotalInclTaxAmount,

                // 其他欄位
                'subtotal_with_discount' => $totals->getSubtotalWithDiscount(),
                'subtotal_with_discount_incl_tax' => $this->calculateSubtotalWithDiscountInclTax($totals),
                'discount_amount' => abs($totals->getDiscountAmount()),
                'grand_total' => $grandTotalAmount,
                'summaryCount' => (int)$totals->getItemsQty()
            ];
        } catch (\Exception $e) {
            $this->logger->warning('Failed to get cart totals', [
                'quote_id' => $quote->getId(),
                'exception' => $e->getMessage()
            ]);
            return [
                'subtotal' => $this->formatPriceHtml(0),
                'subtotalExclTax' => $this->formatPriceHtml(0),
                'subtotalInclTax' => $this->formatPriceHtml(0),
                'subtotalAmount' => 0,
                'subtotal_with_discount' => 0,
                'subtotal_with_discount_incl_tax' => 0,
                'discount_amount' => 0,
                'grand_total' => 0,
                'summaryCount' => 0
            ];
        }
    }

    /**
     * Format price with HTML span tag
     *
     * @param float $amount
     * @return string
     */
    private function formatPriceHtml($amount): string
    {
        $formattedPrice = $this->priceHelper->currency($amount, true, false);
        return '<span class="price">' . $formattedPrice . '</span>';
    }

    /**
     * Calculate subtotal with discount including tax
     *
     * @param \Magento\Quote\Api\Data\TotalsInterface $totals
     * @return float
     */
    private function calculateSubtotalWithDiscountInclTax($totals): float
    {
        $subtotalInclTax = $totals->getSubtotalInclTax();
        $discountAmount = abs($totals->getDiscountAmount());

        return $subtotalInclTax - $discountAmount;
    }
}
