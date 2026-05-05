<?php
declare(strict_types=1);

namespace Branch8\Homepage\Plugin;

use Branch8\Homepage\CustomerData\MemberBar;
use Branch8\Customer\CustomerData\Common as CustomerDataCommon;

class CustomerDataCommonPlugin
{
    /**
     * @var MemberBar
     */
    protected $memberBar;

    /**
     * @param MemberBar $memberBar
     */
    public function __construct(MemberBar $memberBar)
    {
        $this->memberBar = $memberBar;
    }

    /**
     * @param CustomerDataCommon $subject
     * @param array $result
     * @return array
     */
    public function afterGetSectionData(CustomerDataCommon $subject, array $result)
    {
        $memberBarData = $this->memberBar->getSectionData();
        if (isset($memberBarData['member_bar'])) {
            $result['member_bar'] = $memberBarData['member_bar'];
        }
        return $result;
    }
}
