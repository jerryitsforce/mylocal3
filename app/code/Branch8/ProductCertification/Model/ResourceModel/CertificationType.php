<?php
declare(strict_types=1);

namespace Branch8\ProductCertification\Model\ResourceModel;

use Magento\Framework\Model\ResourceModel\Db\AbstractDb;

/**
 * CertificationType ResourceModel
 */
class CertificationType extends AbstractDb
{
    /**
     * @return void
     */
    protected function _construct(): void
    {
        $this->_init('branch8_certification_type', 'entity_id');
    }
}
