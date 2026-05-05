<?php

namespace Branch8\RmaAdminUi\Model\Rma\Source;

use Branch8\Rma\Model\Rma\Status;

class RmaStatusForFinanceReviewGrid  extends \Branch8\Rma\Model\Rma\Source\RmaStatus
{
    /**
     * @return array
     */
    public function toOptionArray()
    {
        $availableOptions = [
            Status::RETURN_FINANCIAL_REVIEW_PROCESSING,
            Status::RETURN_FINANCIAL_REVIEW_AGREE,
            Status::RETURN_FINANCIAL_REVIEW_DECLINE
        ];
        $options = [];
        foreach ($availableOptions as $value) {
            $options[] = [
                'label' => __($this->statusLabel->getAdminAndSellerPanelRmaStatusTitle($value)),
                'value' => $value,
            ];
        }

        return $options;
    }
}
