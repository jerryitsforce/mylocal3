<?php
/**
 * Copyright ©  All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Branch8\RestrictedProduct\Plugin\Magento\Catalog\Model\ResourceModel;

use Branch8\RestrictedProduct\Helper\Data as DataHelper;

class Product
{
    /** @var DataHelper */
    protected $dataHelper;

    public function __construct(
        DataHelper $dataHelper
    ) {
        $this->dataHelper = $dataHelper;
    }

    public function afterSave(
        \Magento\Catalog\Model\ResourceModel\Product $subject,
        $result,
        \Magento\Framework\Model\AbstractModel $product
    ) {
        $this->dataHelper->processUpdateProduct([$product->getId()]);
        return $result;
    }
}