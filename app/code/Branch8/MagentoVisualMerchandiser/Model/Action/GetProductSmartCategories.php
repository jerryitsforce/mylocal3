<?php

namespace Branch8\MagentoVisualMerchandiser\Model\Action;

use Magento\Catalog\Model\Product;
use Magento\Framework\App\ResourceConnection;
use Magento\Store\Api\Data\StoreInterface;
use Magento\Store\Model\StoreManagerInterface;
use Branch8\MagentoVisualMerchandiser\Helper\Logger as LoggerInterface;

/**
 *
 */
class GetProductSmartCategories
{
    /**
     * @var LoggerInterface
     */
    private LoggerInterface $logger;
    /**
     * @var ResourceConnection
     */
    private ResourceConnection $resourceConnection;

    /**
     * @param ResourceConnection $resourceConnection
     * @param LoggerInterface $logger
     */
    public function __construct(
        ResourceConnection $resourceConnection,
        LoggerInterface    $logger
    )
    {
        $this->resourceConnection = $resourceConnection;
        $this->logger = $logger;
    }

    /**
     * @param Product $product
     * @param StoreInterface|null $store
     * @return mixed
     */
    public function get(Product $product, StoreInterface $store = null)
    {
        $select = $this->resourceConnection->getConnection()->select();
        $select->from('visual_merchandiser_rule as vmr',
            ['category_id']
        )->join('catalog_category_product as cp', 'vmr.category_id = cp.category_id', [])
            ->where('cp.product_id = ?', $product->getId())->distinct();
        if ($store) {
            $select->where('vmr.store_id = ?', $store->getId());
        }
        return $this->resourceConnection->getConnection()->fetchCol($select);
    }
}
