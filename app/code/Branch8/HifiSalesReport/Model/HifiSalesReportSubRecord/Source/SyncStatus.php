<?php

namespace Branch8\HifiSalesReport\Model\HifiSalesReportSubRecord\Source;

use Branch8\HifiSalesReport\Model\HifiSalesReportSubRecord;

class SyncStatus extends \Magento\Eav\Model\Entity\Attribute\Source\AbstractSource
{
    public function getAllOptions()
    {
        if ($this->_options !== null) {
            return $this->_options;
        }

        return [
            ['label' => __('Yet synced'), 'value' => HifiSalesReportSubRecord::SYNC_STATUS_YET],
            ['label' => __('Synced'), 'value' => HifiSalesReportSubRecord::SYNC_STATUS_SYNCED],
        ];
    }

    public static function getOptionArray()
    {
        return [
            HifiSalesReportSubRecord::SYNC_STATUS_YET    => __('Yet synced'),
            HifiSalesReportSubRecord::SYNC_STATUS_SYNCED => __('Synced'),
        ];
    }
}