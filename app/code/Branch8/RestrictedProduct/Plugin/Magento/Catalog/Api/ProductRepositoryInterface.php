<?php
/**
 * Copyright ©  All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Branch8\RestrictedProduct\Plugin\Magento\Catalog\Api;

use Magento\Catalog\Api\Data\ProductInterface;
use Branch8\RestrictedProduct\Helper\Data as DataHelper;

class ProductRepositoryInterface
{
    /** @var DataHelper */
    protected $dataHelper;

    public function __construct(
        DataHelper $dataHelper
    ) {
        $this->dataHelper = $dataHelper;
    }

    public function afterSave(
        \Magento\Catalog\Api\ProductRepositoryInterface $subject,
        ProductInterface $result, /** result from the save call **/
        ProductInterface $entity  /** original parameter to the call **/
        /** other parameter not required **/
    ) {
        $this->dataHelper->processUpdateProduct([$result->getId()]);
        return $result;
    }
}
