<?php

namespace Branch8\Catalog\Plugin\Magento\CatalogUrlRewriteDataExporter\Model\Query;

use Magento\Framework\App\ResourceConnection;
use Magento\Framework\DB\Select;

class ProductUrlQuery
{
    /**
     * @var ResourceConnection
     */
    private $resourceConnection;

    /**
     * @var \Magento\Framework\App\Config\ScopeConfigInterface
     */
    protected $scopeConfig;

    /**
     * ProductUrlQuery constructor.
     *
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
     * Get resource table
     *
     * @param string $tableName
     * @return string
     */
    private function getTable(string $tableName) : string
    {
        return $this->resourceConnection->getTableName($tableName);
    }

    /**
     * Get query for provider
     *
     * @param array $arguments
     * @return Select
     */
    public function aroundGetQuery($subject, $process, array $arguments): Select
    {
        $productIds = isset($arguments['productId']) ? $arguments['productId'] : [];
        $storeViewCodes = isset($arguments['storeViewCode']) ? $arguments['storeViewCode'] : [];
        $connection = $this->resourceConnection->getConnection();

        $pdpPrefix = $this->scopeConfig->getValue(\Branch8\Catalog\Rewrite\Model\Product\Url::PDP_PREFIX);

        $select = $connection->select()
            ->from(
                ['ur' => $this->getTable('url_rewrite')],
                [
                    'productId' => 'ur.entity_id',
                    'url' => 'concat("'.$pdpPrefix.'",ur.request_path)'
                ]
            )
            ->join(
                ['s' => $this->getTable('store')],
                'ur.store_id = s.store_id',
                ['storeViewCode' => 's.code']
            )
            ->where('ur.entity_type = ?', 'product')
            ->where('ur.redirect_type = ?', 0)
            ->where('s.code IN (?)', $storeViewCodes)
            ->where('ur.entity_id IN (?)', $productIds)
            ->where('ur.metadata IS NULL');
        return $select;
    }
}
