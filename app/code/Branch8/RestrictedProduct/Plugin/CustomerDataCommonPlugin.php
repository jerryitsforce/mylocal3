<?php
declare(strict_types=1);

namespace Branch8\RestrictedProduct\Plugin;

use Branch8\RestrictedProduct\CustomerData\RestrictedProductIds;
use Branch8\Customer\CustomerData\Common as CustomerDataCommon;

class CustomerDataCommonPlugin
{
    /**
     * @var RestrictedProductIds
     */
    protected $restrictedProductIds;

    /**
     * @param RestrictedProductIds $restrictedProductIds
     */
    public function __construct(RestrictedProductIds $restrictedProductIds)
    {
        $this->restrictedProductIds = $restrictedProductIds;
    }

    /**
     * @param CustomerDataCommon $subject
     * @param array $result
     * @return array
     */
    public function afterGetSectionData(CustomerDataCommon $subject, array $result)
    {
        $result['restrictedproductids'] = $this->restrictedProductIds->getSectionData();
        return $result;
    }
}
