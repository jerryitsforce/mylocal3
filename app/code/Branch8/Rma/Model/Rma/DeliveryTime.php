<?php

namespace Branch8\Rma\Model\Rma;

use Magento\Framework\Data\OptionSourceInterface;
use Webkul\MpRmaSystem\Helper\Data;

class DeliveryTime implements OptionSourceInterface
{
    private $opions = null;
    const NOT_SPECIFIC = 0;
    const MORNING = 1;

    const AFTERNOON = 2;

    const EVENING = 3;

    const NOT_SPECIFIC_LABEL = 'Not Specific';
    const MORNING_LABEL = 'Morning（9:00-12:00）';

    const AFTERNOON_LABEL = 'Afternoon（12:00-16:00）';

    const EVENING_LABEL = 'Evening（17:00-19:00）';


    public function toOptionArray()
    {
        if ($this->opions === null) {
            $this->opions = [
                ['value' => self::NOT_SPECIFIC, 'label' => __(self::NOT_SPECIFIC_LABEL)],
                ['value' => self::MORNING, 'label' => __(self::MORNING_LABEL)],
                ['value' => self::AFTERNOON, 'label' => __(self::AFTERNOON_LABEL)],
                ['value' => self::EVENING, 'label' => __(self::EVENING_LABEL)]
            ];
        }
        return $this->opions;
    }

    /**
     * @param $id
     * @return string
     */
    public static function getOptionTextById($id)
    {
        switch ($id) {
            case self::NOT_SPECIFIC:
                return self::NOT_SPECIFIC_LABEL;
            case self::MORNING:
                return self::MORNING_LABEL;
            case self::AFTERNOON:
                return self::AFTERNOON_LABEL;
            case self::EVENING:
                return self::EVENING_LABEL;
            default:
                return '';
        }
    }
}
