<?php
declare(strict_types=1);

namespace Branch8\MarketPlaceParentOrder\Model\ParentOrder\Status\History;

use Branch8\MarketPlaceParentOrder\Model\History;

/**
 * Class Validator
 * @package Magento\Sales\Model\Order\Status\History
 */
class Validator
{
    /**
     * @var array
     */
    protected $requiredFields = [
        'parent_id' => 'Parent Order Id',
        'comment' => 'Comment'
    ];

    /**
     * @param History $history
     * @return array
     */
    public function validate(History $history)
    {
        $warnings = [];
        foreach ($this->requiredFields as $code => $label) {
            if (!$history->hasData($code) || empty($history->getData($code))) {
                $warnings[] = sprintf('"%s" is required. Enter and try again.', $label);
            }
        }
        return $warnings;
    }
}
