<?php
/**
 * Copyright © HotaiConnected. All rights reserved.
 */

namespace HotaiConnected\Cart\Service\DataProvider;

use Magento\Quote\Model\Quote;
use Branch8\SplitCart\Helper\Data as SplitCartHelper;
use Branch8\FlagshipStore\Helper\Sales as FlagshipHelper;
use HotaiConnected\Cart\Service\DataProvider\ItemDetailsProvider;
use HotaiConnected\Cart\Service\DataProvider\FreeShippingProvider;

class SellerItemsProvider
{
    /**
     * @var SplitCartHelper
     */
    private $splitCartHelper;

    /**
     * @var FlagshipHelper
     */
    private $flagshipHelper;

    /**
     * @var ItemDetailsProvider
     */
    private $itemDetailsProvider;

    /**
     * @var FreeShippingProvider
     */
    private $freeShippingProvider;

    /**
     * @param SplitCartHelper $splitCartHelper
     * @param FlagshipHelper $flagshipHelper
     * @param ItemDetailsProvider $itemDetailsProvider
     * @param FreeShippingProvider $freeShippingProvider
     */
    public function __construct(
        SplitCartHelper $splitCartHelper,
        FlagshipHelper $flagshipHelper,
        ItemDetailsProvider $itemDetailsProvider,
        FreeShippingProvider $freeShippingProvider
    ) {
        $this->splitCartHelper = $splitCartHelper;
        $this->flagshipHelper = $flagshipHelper;
        $this->itemDetailsProvider = $itemDetailsProvider;
        $this->freeShippingProvider = $freeShippingProvider;
    }

    /**
     * Get seller items grouped by seller with full details
     *
     * @param Quote $quote
     * @return array
     */
    public function getSellerItems(Quote $quote): array
    {
        $sellerItems = [];
        $quoteItems = $quote->getAllVisibleItems();

        // Create item map
        $itemsMap = [];
        foreach ($quoteItems as $item) {
            $itemsMap[$item->getId()] = $item;
        }

        // Split cart by seller
        $splitCarts = $this->splitCartHelper->splitCartToSubCart($itemsMap);

        foreach ($splitCarts as $sellerId => $typeGroups) {
            foreach ($typeGroups as $type => $items) {
                $sellerData = $this->getSellerData($sellerId);
                $sellerShippingMethods = $this->getSellerShippingMethods($sellerId, $sellerData);

                $itemDetails = [];
                foreach ($items as $item) {
                    $itemDetails[] = $this->itemDetailsProvider->getItemDetails($item);
                }

                $freeShippingStatus = $this->freeShippingProvider->getFreeShippingStatus(
                    $type,
                    $sellerShippingMethods,
                    $items
                );

                $isAllChecked = $this->splitCartHelper->isAvailableCheckoutAllCartType($items);

                $sellerItems[] = [
                    'sellerId' => $sellerId,
                    'sellerInfo' => [
                        'link' => $sellerData['url'] ?? '',
                        'title' => $sellerData['title'] ?? ''
                    ],
                    'sellerType' => $type,
                    'freeShippingStatus' => $freeShippingStatus,
                    'isAllChecked' => $isAllChecked,
                    'items' => $itemDetails
                ];
            }
        }

        return $sellerItems;
    }

    /**
     * Get seller data (URL and title)
     *
     * @param string $sellerId
     * @return array
     */
    private function getSellerData(string $sellerId): array
    {
        // Check if flagship store
        if (strpos($sellerId, \Branch8\FlagshipStore\Helper\Sales::CART_FLAGSHIP_STORE_PREFIX) === 0) {
            $flagshipSellerData = $this->flagshipHelper->getFlagshipStoreData($sellerId);
            return [
                'url' => $flagshipSellerData['url'] ?? '',
                'title' => $flagshipSellerData['name'] ?? ''
            ];
        }

        // Regular seller
        $seller = $this->splitCartHelper->getSellerBySellerId($sellerId);
        if ($seller) {
            return [
                'url' => $seller['shop_url'] ? $this->splitCartHelper->getSellerShopUrl($seller['shop_url']) : '',
                'title' => $seller['shop_title'] ?: ($seller['shop_url'] ?: '')
            ];
        }

        return ['url' => '', 'title' => ''];
    }

    /**
     * Get seller shipping methods
     *
     * @param string $sellerId
     * @param array $sellerData
     * @return array
     */
    private function getSellerShippingMethods(string $sellerId, array $sellerData): array
    {
        // Check if flagship store
        if (strpos($sellerId, \Branch8\FlagshipStore\Helper\Sales::CART_FLAGSHIP_STORE_PREFIX) === 0) {
            $methods = $this->flagshipHelper->getIntersectionSellerShippingMethod($sellerId);
            return is_array($methods) ? $methods : [];
        }

        // Regular seller
        $seller = $this->splitCartHelper->getSellerBySellerId($sellerId);
        if ($seller && isset($seller['shipping_methods'])) {
            $methods = $seller['shipping_methods'];
            return is_string($methods) ? explode(',', $methods) : [];
        }

        return [];
    }
}
