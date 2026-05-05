<?php
declare(strict_types=1);

namespace Branch8\GA4\Plugin;

use Branch8\GA4\CustomerData\Ga4;
use Branch8\Customer\CustomerData\Common as CustomerDataCommon;

class CustomerDataCommonPlugin
{
    /**
     * @var Ga4
     */
    protected $ga4;

    /**
     * @param Ga4 $ga4
     */
    public function __construct(Ga4 $ga4)
    {
        $this->ga4 = $ga4;
    }

    /**
     * @param CustomerDataCommon $subject
     * @param array $result
     * @return array
     */
    public function afterGetSectionData(CustomerDataCommon $subject, array $result)
    {
        $result['branch8_ga4'] = $this->ga4->getSectionData();
        return $result;
    }
}
