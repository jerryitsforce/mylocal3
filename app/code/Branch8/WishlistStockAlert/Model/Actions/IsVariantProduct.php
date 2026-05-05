<?php
declare(strict_types=1);
/**
 * @project    HotaiConnected
 * @package    Branch8
 * @author     cuongho803@gmail.com
 * @date       05/04/2026
 */

namespace Branch8\WishlistStockAlert\Model\Actions;

use Magento\Framework\App\ResourceConnection;

class IsVariantProduct
{
    private $cache = [];

    /**
     * @param ResourceConnection $resourceConnection
     */
    public function __construct(
        readonly ResourceConnection $resourceConnection
    )
    {

    }

    /**
     * @param \Magento\Catalog\Model\Product $product
     * @return mixed|string
     */
    public function execute(\Magento\Catalog\Model\Product $product)
    {
        if (isset($this->cache[$product->getId()])) {
            return $this->cache[$product->getId()];
        }
        $select = $this->resourceConnection->getConnection()->select()->from('wk_osi_variations', new \Zend_Db_Expr('COUNT(*)'));
        $this->cache[$product->getId()] = $this->resourceConnection->getConnection()->fetchOne($select) > 0;
        return $this->cache[$product->getId()];
    }
}
