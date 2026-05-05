<?php

declare(strict_types=1);

namespace Branch8\MarketplaceProduct\Model\Cart;

use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Quote\Model\Quote;

/**
 * Fetch Product models corresponding to a cart's items.
 */
class GetCartProducts
{
    /**
     * @var SearchCriteriaBuilder
     */
    private SearchCriteriaBuilder $searchCriteriaBuilder;

    /**
     * @var ProductRepositoryInterface
     */
    private ProductRepositoryInterface $productRepository;

    /**
     * GetCartProducts constructor.
     *
     * @param ProductRepositoryInterface $productRepository
     * @param SearchCriteriaBuilder $searchCriteriaBuilder
     */
    public function __construct(
        SearchCriteriaBuilder      $searchCriteriaBuilder,
        ProductRepositoryInterface $productRepository
    ) {
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;
        $this->productRepository = $productRepository;
    }

    /**
     * Get product models based on items in cart.
     *
     * @param Quote $cart
     *
     * @return array
     */
    public function execute(Quote $cart): array
    {
        $cartItems = $cart->getAllItems();
        if (empty($cartItems)) {
            return [];
        }
        $cartItemIds = array_map(fn($item) => $item->getProduct()->getId(), $cartItems);
        $searchCriteria = $this->searchCriteriaBuilder->addFilter('entity_id', $cartItemIds, 'in')->create();
        return $this->productRepository->getList($searchCriteria)->getItems();
    }
}
