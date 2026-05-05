<?php
declare(strict_types=1);
/**
 * @project    HotaiConnected
 * @package    Branch8
 * @author     cuongho803@gmail.com
 * @date       10/02/2026
 */

namespace Branch8\Preorder\Model;

use Magento\Framework\App\ResourceConnection;
use Psr\Log\LoggerInterface;

class IsPreorderProduct
{
    private $isPreorder = [];
    private ResourceConnection $resourceConnection;

    private LoggerInterface $logger;

    /**
     * @param ResourceConnection $resourceConnection
     * @param LoggerInterface $logger
     */
    public function __construct(
        ResourceConnection $resourceConnection,
        LoggerInterface $logger
    )
    {
        $this->logger = $logger;
        $this->resourceConnection = $resourceConnection;
    }

    /**
     * @param int $productId
     * @return bool
     */
    public function checkIsPreorder(int $productId)
    {
        if (isset($this->isPreorder[$productId])) {
            return (bool)$this->isPreorder[$productId];
        }
        try {
            $connection = $this->resourceConnection->getConnection();
            $tbProduct = $connection->getTableName('catalog_product_preorder');
            $bind = [
                'product_id' => $productId,
            ];
            $select = $connection->select()->from(
                ['e' => $tbProduct],
                ['product_id']
            )->where(
                'e.status = 1'
            )->where(
                'e.product_id = :product_id'
            );
            $isPreorder = $connection->fetchOne($select, $bind);
            $isPreorder ? $this->isPreorder[$productId] = true : $this->isPreorder[$productId] = false;
        } catch (\Exception $e) {
            if(\Branch8\HotaiCore\Helper\DebugLog::isEnable('Branch8_Preorder', 'exceptionlog')){
                $this->logger->critical($e->getMessage());
            }
            $this->isPreorder[$productId] = false;
        }
        return (bool)$this->isPreorder[$productId];
    }
}
