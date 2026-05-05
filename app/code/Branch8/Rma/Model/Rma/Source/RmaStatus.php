<?php
namespace Branch8\Rma\Model\Rma\Source;

use Magento\Framework\Data\OptionSourceInterface;
use Branch8\Rma\Helper\Config\StatusLabel;
use Branch8\Rma\Model\Rma\Status;

/**
 * Class RmaStatus Grid
 */
class RmaStatus implements OptionSourceInterface
{

    protected $statusLabel;

    public function __construct(
        StatusLabel $statusLabel
    )
    {
        $this->statusLabel = $statusLabel;
    }
    /**
     * Get options.
     *
     * @return array
     */
    public function toOptionArray()
    {
        $availableOptions = [
            Status::RETURN_APPLY_PROCESSING,
            Status::RETURN_APPLY_CANCEL,
            Status::RETURN_APPLY_AGREE,
            Status::RETURN_APPLY_DECLINE,
            Status::RETURN_SHIPPING,
            Status::RETURN_REVIEW_PROCESSING,
            Status::RETURN_REVIEW_AGREE,
            Status::RETURN_REVIEW_DECLINE,
            Status::RETURN_CANCEL_BEFORE_SHIPPING,
            Status::RETURN_GOODS_AND_REFUND,
            Status::NOT_RETURN_GOODS_BUT_REFUND,
            Status::RETURN_FINANCIAL_REVIEW_PROCESSING,
            Status::RETURN_FINANCIAL_REVIEW_AGREE,
            Status::RETURN_FINANCIAL_REVIEW_DECLINE,
            Status::RETURN_REFUND_PROCESSING,
            Status::RETURNED,
            Status::REPLACE_APPLY_PROCESSING,
            Status::REPLACE_APPLY_CANCEL,
            Status::REPLACE_APPLY_AGREE,
            Status::REPLACE_APPLY_DECLINE,
            Status::REPLACE_SHIPPING_TO_SUPPLIER,
            Status::REPLACE_REVIEW_PROCESSING,
            Status::REPLACE_REVIEW_AGREE,
            Status::REPLACE_REVIEW_DECLINE,
            Status::REPLACE_SHIPPING_TO_CUSTOMER,
            Status::REPLACE_SHIPPING_ARRIVED,
            Status::REPLACE_COMPLETE,
            STATUS::RETURN_FINANCIAL_STATUS,
            STATUS::RETURN_FINANCIAL_MANUAL_REFUND_SUCCESS,
            STATUS::RETURN_REFUND_FAIL,
            STATUS::REPLACE_FAIL,
            STATUS::REPLACE_READY_SHIPPING_TO_CUSTOMER,
            STATUS::REPLACE_AGAIN,
            STATUS::REJECT_REPLACE_AGAIN
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
