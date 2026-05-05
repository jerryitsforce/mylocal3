<?php
declare(strict_types=1);

namespace Branch8\Customer\CustomerData;

use Magento\Customer\CustomerData\SectionSourceInterface;

class Common implements SectionSourceInterface
{
    /**
     * @inheritdoc
     */
    public function getSectionData()
    {
        return [];
    }
}
