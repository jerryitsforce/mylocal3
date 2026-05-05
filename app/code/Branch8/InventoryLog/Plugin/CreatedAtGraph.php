<?php

namespace Branch8\InventoryLog\Plugin;

class CreatedAtGraph
{
    protected $timezone;

    public function __construct(
        \Magento\Framework\Stdlib\DateTime\TimezoneInterface $timezone
    ){
        $this->timezone = $timezone;
    }

    public function aroundGetLabels($subject, $process){
        return array_map(function ($label) {
            $formattedTime = $this->timezone->date($label)->format('Y-m-d H:i:s');
            return "'" . $formattedTime . "'";
        }, $subject->getStockMovements()->getColumnValues('created_at'));
    }
}