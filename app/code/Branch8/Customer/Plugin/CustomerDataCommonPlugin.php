<?php
declare(strict_types=1);

namespace Branch8\Customer\Plugin;

use Branch8\Customer\CustomerData\BrowsingHistory;
use Branch8\Customer\CustomerData\Common as CustomerDataCommon;

class CustomerDataCommonPlugin
{
    /**
     * @var BrowsingHistory
     */
    protected $browsingHistory;

    /**
     * @param BrowsingHistory $browsingHistory
     */
    public function __construct(BrowsingHistory $browsingHistory)
    {
        $this->browsingHistory = $browsingHistory;
    }

    /**
     * @param CustomerDataCommon $subject
     * @param array $result
     * @return array
     */
    public function afterGetSectionData(CustomerDataCommon $subject, array $result)
    {
        $result['browsinghistory'] = $this->browsingHistory->getSectionData();
        return $result;
    }
}
