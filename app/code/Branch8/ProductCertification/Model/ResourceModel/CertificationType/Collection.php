<?php
declare(strict_types=1);

namespace Branch8\ProductCertification\Model\ResourceModel\CertificationType;

use Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;
use Branch8\ProductCertification\Model\CertificationType;
use Branch8\ProductCertification\Model\ResourceModel\CertificationType as ResourceModel;

/**
 * CertificationType Collection
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
        $this->_init(CertificationType::class, ResourceModel::class);
    }

    /**
     * Filter active records only
     * @return $this
     */
    public function addActiveFilter(): self
    {
        $this->addFieldToFilter('status', CertificationType::STATUS_ENABLED);
        return $this;
    }
}
