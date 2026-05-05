<?php
declare(strict_types=1);

namespace Branch8\ProductCertification\Model;

use Magento\Framework\Model\AbstractModel;
use Branch8\ProductCertification\Model\ResourceModel\ProductCertification as ResourceModel;

/**
 * ProductCertification Model — stores a single certification line per product
 */
class ProductCertification extends AbstractModel
{
    /**
     * @return void
     */
    protected function _construct(): void
    {
        $this->_init(ResourceModel::class);
    }
}
