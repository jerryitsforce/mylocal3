<?php

namespace Branch8\Catalog\Plugin\Magento\CatalogUrlRewriteDataExporter\Model\Query;

use Magento\Framework\App\ResourceConnection;
use Magento\Framework\DB\Select;

class ProductUrlRewritesQuery
{
    /**
     * @var ResourceConnection
     */
    private $resourceConnection;

    protected $scopeConfig;

    /**
     * @param ResourceConnection $resourceConnection
     */
    public function __construct(
        ResourceConnection $resourceConnection,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
    ) {
        $this->resourceConnection = $resourceConnection;
        $this->scopeConfig = $scopeConfig;
    }

    /**
     * Return query that fetches a list of products' url rewrites.
     *
     * @param int[] $productIds
     * @param int $storeId
     * @return Select
     */
    public function aroundGetQuery($subject, $process, array $productIds, int $storeId): Select
    {
        $resourceConnection = $this->resourceConnection;
        $connection = $resourceConnection->getConnection();
        $urlRewritesTable = $resourceConnection->getTableName('url_rewrite');

        $pdpPrefix = $this->scopeConfig->getValue(\Branch8\Catalog\Rewrite\Model\Product\Url::PDP_PREFIX);

        return $connection->select()
            ->from(
                ['e' => $urlRewritesTable],
                [
                    \Magento\UrlRewrite\Service\V1\Data\UrlRewrite::ENTITY_ID,
//                    \Magento\UrlRewrite\Service\V1\Data\UrlRewrite::REQUEST_PATH,
                    \Magento\UrlRewrite\Service\V1\Data\UrlRewrite::TARGET_PATH,
                ]
            )
            ->columns([\Magento\UrlRewrite\Service\V1\Data\UrlRewrite::REQUEST_PATH => 'concat("'.$pdpPrefix.'", '.\Magento\UrlRewrite\Service\V1\Data\UrlRewrite::REQUEST_PATH.')'])
            ->where('entity_id IN (?)', $productIds)
            ->where('entity_type = ?', 'product')
            ->where('store_id = ?', $storeId);
    }
}