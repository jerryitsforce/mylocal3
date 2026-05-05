<?php

namespace Branch8\InventoryLog\Helper;

use Magento\Framework\App\Helper\Context;

class Data extends \Magento\Framework\App\Helper\AbstractHelper
{
    /**
     * @var \Magento\Framework\Stdlib\DateTime\TimezoneInterface
     */
    protected $timezone;

    /**
     * @param Context $context
     * @param \Magento\Framework\Stdlib\DateTime\TimezoneInterface $timezone
     */
    public function __construct(
        Context $context,
        \Magento\Framework\Stdlib\DateTime\TimezoneInterface $timezone
    ){
        parent::__construct($context);
        $this->timezone = $timezone;
    }

    /**
     * @param $date
     * @param $format
     * @return string
     */
    public function getFormattedDate($date, $format = 'Y-m-d H:i:s'){
        return $this->timezone->date($date)->format($format);
    }

}