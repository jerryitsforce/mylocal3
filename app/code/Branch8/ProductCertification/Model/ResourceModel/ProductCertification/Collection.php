<?php
declare(strict_types=1);

namespace Branch8\ProductCertification\Model\ResourceModel\ProductCertification;

use Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;
use Branch8\ProductCertification\Model\ProductCertification;
use Branch8\ProductCertification\Model\ResourceModel\ProductCertification as ResourceModel;

/**
 * ProductCertification Collection
 */
class Collection extends AbstractCollection
{
    /**
     * @var string
     */
    protected $_idFieldName = 'entity_id';

    /**
     * @return void
     */
    protected function _construct(): void
    {
        $this->_init(ProductCertification::class, ResourceModel::class);
    }

    /**
     * Filter by product ID
     * @param int $productId
     * @return $this
     */
    public function addProductFilter(int $productId): self
    {
        $this->addFieldToFilter('product_id', $productId);
        return $this;
    }
}
