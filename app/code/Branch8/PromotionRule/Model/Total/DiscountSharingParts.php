<?php

namespace Branch8\PromotionRule\Model\Total;

use Branch8\PromotionRule\Model\Actions\GetRulePrices;
use Branch8\PromotionRule\Model\Actions\GetSellerByProductId;
use Magento\Catalog\Model\Product;
use Branch8\PromotionRule\Model\Actions\GetSellerBorneCatalogRuleConfig;
use Branch8\PromotionRule\Model\Actions\GetSellerBorneSaleRuleConfig;
use Magento\Framework\Pricing\PriceCurrencyInterface;
use Magento\Framework\Serialize\Serializer\Json;
use Magento\Framework\Stdlib\DateTime\TimezoneInterface;
use Magento\Quote\Api\Data\ShippingAssignmentInterface;
use Magento\Quote\Model\Quote;
use Magento\Quote\Model\Quote\Address\Total;
use Magento\Store\Model\Store;
use Webkul\Marketplace\Helper\Data as MarketplaceHelper;

class DiscountSharingParts extends \Magento\Quote\Model\Quote\Address\Total\AbstractTotal
{
    const CODE = 'discountSharingParts';

    private $allCatalogRuleSellerBone = [];

    private $allSaleRuleSellerBone = [];
    /**
     * @var array
     */
    private $quoteFields = [
        'seller_borne_catalogrule_amount',
        'seller_borne_salesrule_amount',
        'platform_borne_catalogrule_amount',
        'platform_borne_salesrule_amount',
        'seller_borne_total_amount',
        'platform_borne_total_amount',
        'applied_catalog_rule_ids',
    ];

    private $quoteItemFields = [
        'seller_borne_catalogrule_amount',
        'seller_borne_salesrule_amount',
        'platform_borne_catalogrule_amount',
        'platform_borne_salesrule_amount',
        'seller_borne_total_amount',
        'platform_borne_total_amount',
    ];
    private GetSellerBorneCatalogRuleConfig $getSellerBorneCatalogRuleConfig;

    private PriceCurrencyInterface $priceCurrency;

    private GetRulePrices $getRulePrices;
    private TimezoneInterface $dateTime;
    private GetSellerBorneSaleRuleConfig $getSellerBorneSaleRuleConfig;
    private GetSellerByProductId $getSellerByProductId;

    private Json $json;

    /**
     * @param PriceCurrencyInterface $priceCurrency
     * @param GetRulePrices $getRulePrices
     * @param TimezoneInterface $dateTime
     * @param GetSellerBorneCatalogRuleConfig $getSellerBorneCatalogRuleConfig
     * @param GetSellerBorneSaleRuleConfig $getsellerBorneSaleRuleConfig
     * @param GetSellerByProductId $getSellerByProductId
     * @param Json $json
     */
    public function __construct(
        PriceCurrencyInterface          $priceCurrency,
        GetRulePrices                   $getRulePrices,
        TimezoneInterface               $dateTime,
        GetSellerBorneCatalogRuleConfig $getSellerBorneCatalogRuleConfig,
        GetSellerBorneSaleRuleConfig    $getsellerBorneSaleRuleConfig,
        GetSellerByProductId            $getSellerByProductId,
        Json $json
    )
    {
        $this->dateTime = $dateTime;
        $this->getRulePrices = $getRulePrices;
        $this->priceCurrency = $priceCurrency;
        $this->getSellerBorneSaleRuleConfig = $getsellerBorneSaleRuleConfig;
        $this->getSellerByProductId = $getSellerByProductId;
        $this->getSellerBorneCatalogRuleConfig = $getSellerBorneCatalogRuleConfig;
        $this->json = $json;
    }

    /**
     * @inheritDoc
     */
    public function _resetState(): void
    {
        parent::_resetState();
        $this->setCode(self::CODE);
    }

    /**
     * @param \Magento\Quote\Model\Quote $quote
     * @param ShippingAssignmentInterface $shippingAssignment
     * @param Total $total
     * @return $this|SellerBoneDiscount
     */
    public function collect(
        \Magento\Quote\Model\Quote                          $quote,
        \Magento\Quote\Api\Data\ShippingAssignmentInterface $shippingAssignment,
        \Magento\Quote\Model\Quote\Address\Total            $total
    )
    {
        $itemsAggregate = [];
        foreach ($shippingAssignment->getItems() as $item) {
            $itemId = $item->getId();
            $itemsAggregate[$itemId] = $item;
        }
        $items = [];
        foreach ($quote->getAllAddresses() as $quoteAddress) {
            foreach ($quoteAddress->getAllItems() as $item) {
                $items[] = $item;
            }
        }
        if (!$items || !$itemsAggregate) {
            return $this;
        }

        if ($quote->getSellerBoneDiscountCollect()) {
            return $this;
        }
        $this->reset($quote, $items)->init($quote, $items);
        $this->calculateCatalogDiscountSharingParts($quote);
        $this->calculateSaleRuleDiscountSharingParts($quote);
        $quote->setData(
            'seller_borne_total_amount',
            $quote->getData('seller_borne_catalogrule_amount') + $quote->getData('seller_borne_salesrule_amount')
        );
        $quote->setData(
            'platform_borne_total_amount',
            $quote->getData('platform_borne_catalogrule_amount') + $quote->getData('platform_borne_salesrule_amount')
        );
        $quote->setData('catalog_rules_borne_info', $this->json->serialize($this->allCatalogRuleSellerBone));
        $quote->setData('cart_rules_borne_info',$this->json->serialize($this->allSaleRuleSellerBone) );

        $quote->setSellerBoneDiscountCollect(true);
        return $this;
    }

    /**
     * @param Quote $quote
     * @return Quote
     */
    private function calculateCatalogDiscountSharingParts(Quote $quote)
    {
        /**
         * @var $item \Magento\Quote\Model\Quote\Item
         */
        $platformBorneCatalogRuleTotalAmount = $sellerBorneCatalogRuleTotalAmount = 0;
        $catalogRules = [];
        $store = $quote->getStore();
        $customerGroupid = (int)$quote->getCustomerGroupId();
        if ($this->allCatalogRuleSellerBone) {
            foreach ($quote->getAllVisibleItems() as $item) {
                $product = $item->getProduct();
                if (!$product || !isset($this->allCatalogRuleSellerBone[$item->getId()])) {
                    continue;
                }
                $qty = $item->getQty();
                $sellerShareItemTotal = $platformShareItemTotal = 0;
                $sellerId = $this->getSellerIdByProductId($product->getId());
                $discountAmount = $this->resolveDiscountAmount($product, $store, $qty, $customerGroupid);
                foreach ($this->allCatalogRuleSellerBone[$item->getId()] as $sellerBoneRuleConfig) {
                    $sellerIds = (string)$sellerBoneRuleConfig['seller_ids'] ? explode(',', $sellerBoneRuleConfig['seller_ids']) : [];
                    $enableSellerBorne = (bool)filter_var($sellerBoneRuleConfig['seller_borne_discount'], FILTER_VALIDATE_BOOLEAN);
                    $bornePercentage = (int)($sellerBoneRuleConfig['borne_discount_percentage']);
                    if ($discountAmount > 0) {
                        list($sellerShare, $platformShare) = $this->calDiscountShare(
                            $discountAmount, $enableSellerBorne, $bornePercentage, $sellerId, $sellerIds
                        );
                        $sellerShareItemTotal += ($sellerShare);
                        $platformShareItemTotal += ($platformShare);
                        $catalogRules[$sellerBoneRuleConfig['rule_id']] = $sellerBoneRuleConfig['rule_id'];
                    }
                }
                $item->setData('seller_borne_catalogrule_amount', $sellerShareItemTotal);
                $item->setData('platform_borne_catalogrule_amount', $platformShareItemTotal);
                $item->setData('platform_borne_total_amount',
                    $item->getData('platform_borne_catalogrule_amount')
                    + $item->getData('platform_borne_salesrule_amount')
                );
                $item->setData('seller_borne_total_amount',
                    $item->getData('seller_borne_catalogrule_amount')
                    + $item->getData('seller_borne_salesrule_amount')
                );
                $item->setData('catalog_rules_borne_info',
                    isset($this->allCatalogRuleSellerBone[$item->getId()]) ?
                        $this->json->serialize($this->allCatalogRuleSellerBone[$item->getId()]) : '[]'
                );
                $sellerBorneCatalogRuleTotalAmount += $sellerShareItemTotal;
                $platformBorneCatalogRuleTotalAmount += $platformShareItemTotal;
            }
        }
        $quote->setData('seller_borne_catalogrule_amount', $sellerBorneCatalogRuleTotalAmount);
        $quote->setData('platform_borne_catalogrule_amount', $platformBorneCatalogRuleTotalAmount);
        $quote->setData('applied_catalog_rule_ids', join(',', $catalogRules));
        return $quote;
    }

    /**
     * @param Quote $quote
     * @return Quote
     */
    private function calculateSaleRuleDiscountSharingParts(Quote $quote)
    {
        $platformBorneSaleRuleTotalAmount = $sellerBorneSaleTotalAmount = 0;
        if ($this->allSaleRuleSellerBone) {
            foreach ($quote->getAllVisibleItems() as $item) {
                $product = $item->getProduct();
                if (!$product || !isset($this->allSaleRuleSellerBone[$item->getId()])) {
                    continue;
                }
                $sellerShareItemTotal = $platformShareItemTotal = 0;
                $sellerId = $this->getSellerIdByProductId($product->getId());
                $discountBreakDownSerialize = (string)$item->getData('sale_rule_discount_breakdown');
                if (!$discountBreakDownSerialize
                    || !($discountBreakDown = json_decode($discountBreakDownSerialize, true))
                ) {
                    continue;
                }
                foreach ($this->allSaleRuleSellerBone[$item->getId()] as $sellerBoneRuleConfig) {
                    $ruleId = $sellerBoneRuleConfig['rule_id'];
                    $discountAmount = $discountBreakDown[$ruleId] ?? 0;
                    $sellerIds = (string)$sellerBoneRuleConfig['seller_ids'] ? explode(',', $sellerBoneRuleConfig['seller_ids']) : [];
                    $enableSellerBorne = (bool)filter_var($sellerBoneRuleConfig['seller_borne_discount'], FILTER_VALIDATE_BOOLEAN);
                    $bornePercentage = ($sellerBoneRuleConfig['borne_discount_percentage']) ? (int)($sellerBoneRuleConfig['borne_discount_percentage']) : 100;
                    if ($discountAmount > 0) {
                        list($sellerShare, $platformShare) = $this->calDiscountShare(
                            $this->priceCurrency->roundPrice($discountAmount), $enableSellerBorne, $bornePercentage, $sellerId, $sellerIds
                        );
                        $sellerShareItemTotal += ($sellerShare);
                        $platformShareItemTotal += ($platformShare);
                    }
                }
                $item->setData('seller_borne_salesrule_amount', $sellerShareItemTotal);
                $item->setData('platform_borne_salesrule_amount', $platformShareItemTotal);
                $item->setData('seller_borne_total_amount',
                    $item->getData('seller_borne_catalogrule_amount')
                    + $item->getData('seller_borne_salesrule_amount')
                );
                $item->setData('platform_borne_total_amount',
                    $item->getData('platform_borne_catalogrule_amount')
                    + $item->getData('platform_borne_salesrule_amount')
                );
                $item->setData('cart_rules_borne_info',
                    isset($this->allSaleRuleSellerBone[$item->getId()]) ?
                        $this->json->serialize($this->allSaleRuleSellerBone[$item->getId()]) : '[]'
                );
                $sellerBorneSaleTotalAmount += $sellerShareItemTotal;
                $platformBorneSaleRuleTotalAmount += $platformShareItemTotal;
            }
            $quote->setData('seller_borne_salesrule_amount', $sellerBorneSaleTotalAmount);
            $quote->setData('platform_borne_salesrule_amount', $platformBorneSaleRuleTotalAmount);
        }
        return $quote;
    }

    /**
     * @param $discountAmount
     * @param $enableSellerBorne
     * @param $bornePercentage
     * @param $sellerId
     * @param $sellerIds
     * @return array
     */
    private function calDiscountShare($discountAmount, $enableSellerBorne, $bornePercentage, $sellerId, $sellerIds)
    {
        /*
         * 1.if not enable seller borne ,Hotai will cover all the expenses.
         * 2. if does not have sellers statisfy ,Hotai will cover all the expenses.
        */
        if (!in_array($sellerId, $sellerIds) || !$enableSellerBorne) {
            return [0, $discountAmount];
        }
        $bornePercentage = abs((int)$bornePercentage);
        $sellerShare = $this->priceCurrency->roundPrice(($discountAmount * $bornePercentage / 100), 0);
        $platformShare = $discountAmount - $sellerShare;
        return [$sellerShare, $platformShare];
    }

    /**
     * @param Product $product
     * @param Store $store
     * @param $qty
     * @param $customerGroupId
     * @return float|int
     */
    private function resolveDiscountAmount(Product $product, Store $store, $qty, $customerGroupId)
    {
        $basePrice = $product->getPrice();
        $specialPrice = $product->getSpecialPrice();
        $discountAmount = 0;
        $finalPrice = $product->getFinalPrice($qty);
        $rulePrice = $this->getRulePrices->getRulePricesForProduct(
            $this->dateTime->scopeDate($store->getId()),
            $store->getWebsiteId(),
            $customerGroupId,
            $product->getId()
        );
        /*
         We have customize on this project,discount from catalogrule is applied in special price
        @see Branch8/CatalogRule/Plugin/Indexer/RuleProductsSelectBuilder.php
        */
        if ($rulePrice <= 0) {
            return $discountAmount;
        }
        if ($specialPrice > 0 && $specialPrice > $rulePrice && $rulePrice === $finalPrice) {
            $discountAmount = ($specialPrice - $rulePrice) * $qty;
        } else {
            $discountAmount = ($basePrice - $rulePrice) * $qty;
        }
        return $discountAmount;
    }

    /**
     * @param $productId
     * @return int|mixed
     */
    private function getSellerIdByProductId($productId)
    {
        return $this->getSellerByProductId->get((int)$productId);
    }

    /**
     * @param Quote $quote
     * @param array $items
     * @return $this
     */
    private function reset(Quote $quote, array $items = [])
    {
        $this->resetQuoteFields($quote);
        $items = count($items) ? $items : $quote->getAllVisibleItems();
        foreach ($items as $item) {
            $this->resetQuoteItemFields($item);
        }
        return $this;
    }

    /**
     * @param Quote $quote
     * @return $this
     */
    private function init(Quote $quote, array $items = [])
    {
        $items = count($items) ? $items : $quote->getAllVisibleItems();
        foreach ($items as $item) {
            $product = $item->getProduct();
            $availableToCheckout = (bool)$item->getData('available_to_checkout');
            $catalogRuleIds = (string)$item->getData('applied_catalog_rule_ids');
            $saleRuleIds = (string)$item->getData('applied_rule_ids');

            if (!$product || !$availableToCheckout) {
                continue;
            }
            if ($catalogRuleIds) {
                $this->allCatalogRuleSellerBone[$item->getId()] =
                    $this->getSellerBorneCatalogRuleConfig->execute(explode(',', $catalogRuleIds));
            }
            if ($saleRuleIds) {
                $this->allSaleRuleSellerBone[$item->getId()] =
                    $this->getSellerBorneSaleRuleConfig->execute(explode(',', $saleRuleIds));
            }
        }
        return $this;
    }

    /**
     * @param Quote $quote
     * @return $this
     */
    private function resetQuoteFields(Quote $quote)
    {
        foreach ($this->quoteFields as $field) {
            if ($field === 'applied_catalog_rule_ids') {
                $quote->setData($field);
            } else {
                $quote->setData($field, 0);
            }
        }
        return $this;
    }

    /**
     * @param Quote\Item $item
     * @param $fields
     * @return $this
     */
    private function resetQuoteItemFields(\Magento\Quote\Model\Quote\Item $item, $fields = [])
    {
        foreach ($this->quoteItemFields as $field) {
            $item->setData($field, 0);
        }
        return $this;
    }

    /**
     * @param \Magento\Quote\Model\Quote $quote
     * @param Total $total
     * @return null
     */
    public function fetch(\Magento\Quote\Model\Quote $quote, \Magento\Quote\Model\Quote\Address\Total $total)
    {
        return null;
    }
}
