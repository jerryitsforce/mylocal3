<?php
/**
 * Copyright ©  All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types = 1);

namespace Branch8\HotaiShipping\Plugin\Magento\OfflineShipping\Model;

use Closure;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\OfflineShipping\Model\Carrier\Flatrate;
use Magento\Quote\Model\Quote;
use Magento\Quote\Model\Quote\Address\RateRequest;
use Magento\Quote\Model\Quote\Address\RateResult\MethodFactory;
use Magento\Shipping\Model\Rate\ResultFactory;
use Psr\Log\LoggerInterface;

/**
 * @property ResultFactory $_rateResultFactory
 * @property LoggerInterface $logger
 * @property MethodFactory $_rateMethodFactory
 */
class Carrier
{
    public const FLAT_RATE_FREE_SHIPPING = "carriers/flatrate/free_shipping_price";

    /**
     * @var ScopeConfigInterface
     */
    protected ScopeConfigInterface $_scopeConfig;
    private ResultFactory          $_rateResultFactory;
    private LoggerInterface        $logger;
    private MethodFactory          $_rateMethodFactory;

    public function __construct(
        LoggerInterface $logger,
        ResultFactory $rateResultFactory,
        MethodFactory $rateMethodFactory,
        ScopeConfigInterface $scopeConfig,
    ) {
        $this->_rateMethodFactory = $rateMethodFactory;
        $this->logger             = $logger;
        $this->_rateResultFactory = $rateResultFactory;
        $this->_scopeConfig       = $scopeConfig;
    }

    public function aroundCollectRates(Flatrate $subject, Closure $proceed, RateRequest $request)
    {
        $items = $request->getAllItems();
        if (empty($items)) {
            return $proceed($request);
        }

        /** @var \Magento\Quote\Model\Quote\Item $firstItem */
        $firstItem = reset($items);

        $quote = $firstItem->getQuote();
        if (!($quote instanceof Quote)) {
            return $proceed($request);
        }

        if ($this->getFreeShippingPrice() === 0) {
            return $proceed($request);
        }

        $subTotal = $quote->getSubTotal();

        if ($subTotal >= $this->getFreeShippingPrice()) {
            /**
             * @var $rateResult \Magento\Shipping\Model\Rate\Result
             * @var $rateMethod \Magento\Quote\Model\Quote\Address\RateResult\Method
             */
            $rateResult = $this->_rateResultFactory->create();
            $rateMethod = $this->_rateMethodFactory->create();
            $rateMethod->setCarrier('flatrate');
            $rateMethod->setCarrierTitle($this->getConfigData('title'));
            $shippingPrice = 0;
            $rateMethod->setMethod('flatrate');
            $rateMethod->setMethodTitle($subject->getConfigData('name'));
            $rateMethod->setPrice($shippingPrice);
            $rateMethod->setCost($shippingPrice);
            return $rateResult->append($rateMethod);
        }

        return $proceed($request);
    }

    /**
     * @return int
     */
    private function getFreeShippingPrice(): int
    {
        return (int) $this->_scopeConfig->getValue(self::FLAT_RATE_FREE_SHIPPING);
    }
}
