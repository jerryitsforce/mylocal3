<?php
namespace Branch8\OptionsWithStockAndImages\Model\Resolver;

use Magento\Framework\App\ResourceConnection;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Customer\Model\Session as CustomerSession;

class IndexedVariationsPriceResolver
{
    private $resource;
    private $storeManager;
    private $customerSession;
    private $priceCache = [];

    public function __construct(
        ResourceConnection $resource,
        StoreManagerInterface $storeManager,
        CustomerSession $customerSession
    ) {
        $this->resource = $resource;
        $this->storeManager = $storeManager;
        $this->customerSession = $customerSession;
    }

    /**
     * @param int $productId
     * @param string $variationComb
     * @return float|null
     */
    public function resolve(int $productId, string $variationComb): ?float
    {
        $websiteId = $this->storeManager->getStore()->getWebsiteId();
        $customerGroupId = $this->customerSession->getCustomerGroupId();

        $cacheKey = "variation-{$productId}-{$variationComb}-{$websiteId}-{$customerGroupId}";
        if (isset($this->priceCache[$cacheKey])) {
            return $this->priceCache[$cacheKey];
        }

        $connection = $this->resource->getConnection();
        $indexTable = $this->resource->getTableName('branch8_variations_price_index');

        $select = $connection->select()
            ->from($indexTable, 'final_price')
            ->where('product_id = ?', $productId)
            ->where('variation_comb = ?', $variationComb)
            ->where('website_id = ?', $websiteId)
            ->where('customer_group_id = ?', $customerGroupId);

        $indexedPrice = $connection->fetchOne($select);

        if ($indexedPrice !== false && $indexedPrice !== null) {
            $this->priceCache[$cacheKey] = (float)$indexedPrice;
            return (float)$indexedPrice;
        }

        return null;
    }

    /**
     * @param int $productId
     * @return array
     */
    public function getFinalPrice(int $productId): array
    {
        $websiteId = $this->storeManager->getStore()->getWebsiteId();
        $customerGroupId = $this->customerSession->getCustomerGroupId();

        $cacheKey = "variation-list-{$productId}-{$websiteId}-{$customerGroupId}";
        if (isset($this->priceCache[$cacheKey])) {
            return $this->priceCache[$cacheKey];
        }

        $connection = $this->resource->getConnection();
        $indexTable = $this->resource->getTableName('branch8_variations_price_index');

        $select = $connection->select()
            ->from($indexTable, ['variation_comb', 'final_price'])
            ->where('product_id = ?', $productId)
            ->where('website_id = ?', $websiteId)
            ->where('customer_group_id = ?', $customerGroupId);

        $results = $connection->fetchAll($select);

        $response = [];
        foreach ($results as $row) {
            $response[] = [
                'variation_comb' => $row['variation_comb'],
                'final_price' => (float)$row['final_price']
            ];
        }

        $this->priceCache[$cacheKey] = $response;
        return $response;
    }

    public function getMinimumPriceForProduct(int $productId): ?float
    {
        $websiteId = $this->storeManager->getStore()->getWebsiteId();
        $customerGroupId = $this->customerSession->getCustomerGroupId();

        $cacheKey = "variation-min-price-{$productId}-{$websiteId}-{$customerGroupId}";
        if (isset($this->priceCache[$cacheKey])) {
            return $this->priceCache[$cacheKey];
        }

        $connection = $this->resource->getConnection();
        $indexTable = $this->resource->getTableName('branch8_variations_price_index');

        $select = $connection->select()
            ->from($indexTable, ['min_price' => new \Magento\Framework\DB\Sql\Expression('MIN(final_price)')])
            ->where('product_id = ?', $productId)
            ->where('website_id = ?', $websiteId)
            ->where('customer_group_id = ?', $customerGroupId);

        $minPrice = $connection->fetchOne($select);

        if ($minPrice !== null) {
            $this->priceCache[$cacheKey] = (float)$minPrice;
            return (float)$minPrice;
        }

        return null;
    }
}
