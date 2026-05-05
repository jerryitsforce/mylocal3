<?php
declare(strict_types=1);

namespace Branch8\Rma\Model\Rma;

use Magento\Framework\Data\OptionSourceInterface;
use Webkul\MpRmaSystem\Helper\Data;

class Resolution implements OptionSourceInterface
{
    private $opions = null;
    const RESOLUTION_TYPE_RETURN_REFUND = 1;
    const RESOLUTION_TYPE_EXCHANGE = 2;

    /**
     * @return array[]
     */
    public function toOptionArray()
    {
        if ($this->opions === null) {
            $this->opions = [
                ['value' => Data::RESOLUTION_REFUND, 'label' => __('Return & Refund')],
                ['value' => Data::RESOLUTION_REPLACE, 'label' => __('Exchange')]
            ];
        }
        return $this->opions;
    }
}
