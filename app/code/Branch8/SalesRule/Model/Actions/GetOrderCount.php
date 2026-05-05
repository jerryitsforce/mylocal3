<?php

namespace Branch8\SalesRule\Model\Actions;

use Amasty\RulesPro\Model\Cache;
use Magento\Framework\App\CacheInterface;
use Magento\Framework\App\ObjectManager;
use Magento\Framework\App\ResourceConnection;

class GetOrderCount
{
    /**
     * @var CacheInterface
     */
    private $cache;

    private ResourceConnection $resourceConnection;

    public function __construct(
        \Magento\Framework\Model\ResourceModel\Db\Context $context,
        ResourceConnection                                $resourceConnection,
        CacheInterface                                    $cache = null // TODO: move to not optional argument and remove OM
    )
    {
        $this->cache = $cache ?? ObjectManager::getInstance()->get(CacheInterface::class);
        $this->resourceConnection = $resourceConnection;
    }

    /**
     * @param $customerId
     * @param $attribute
     * @return float
     */
    public function get($customerId, $attribute)
    {
        $cacheKey = hash('sha256', $customerId . '_' . $attribute);
        $cacheData = $this->cache->load($cacheKey);
        if ($cacheData === false) {
            $connection = $this->resourceConnection->getConnection();
            $columns = ['total_orders' => new \Zend_Db_Expr('COUNT(*)')];
            $select = $connection->select()
                ->from(['i' => $connection->getTableName('sales_order')], $columns)
                ->where('i.customer_id = ?', $customerId);
            $result = (float)$connection->fetchOne($select);
            $this->cache->save($result, $cacheKey, ['RULE_CUSTOMER_' . $customerId], '1200');
            return $result;
        }
        return (float)$cacheData;
    }
}
