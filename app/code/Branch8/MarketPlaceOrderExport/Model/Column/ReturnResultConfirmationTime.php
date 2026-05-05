<?php

namespace Branch8\MarketPlaceOrderExport\Model\Column;

use Branch8\HotaiCore\Model\Order\Status;
use Branch8\MarketPlaceOrderExport\Model\ColumnInterface;
use Branch8\MarketPlaceOrderExport\Model\Services\DateFormat;
use Branch8\MarketPlaceOrderExport\Model\Services\GetAllRmaStatusItem;

class ReturnResultConfirmationTime implements ColumnInterface
{
    private $cached = [];

    private GetAllRmaStatusItem $getAllRmaStatusItem;

    /**
     * @param GetAllRmaStatusItem $getAllRmaStatusItem
     */
    public function __construct(
        GetAllRmaStatusItem                    $getAllRmaStatusItem,
    )
    {
        $this->getAllRmaStatusItem = $getAllRmaStatusItem;
    }

    /**
     * @return \Magento\Framework\Phrase|string
     */
    public function getHeader()
    {
        return __('Return Result Confirmation Time');
    }

    /**
     * @param array $row
     * @return int|string
     * @throws \Magento\Framework\Exception\InputException
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function processColumnData(array $row = [])
    {
        $isReturnFlow = in_array($row['flow_status'], Status::RETURN_FLOW);
        if (!$isReturnFlow) {
            return '';
        }
        $rmaStatus = $this->getAllRmaStatusItem->get($row['order_id'], $row['item_id'], $row['rma_options']);
        if (isset($rmaStatus[Status::STATUS_RETURNED])) {
            return DateFormat::getChangeDayTitle($rmaStatus[Status::STATUS_RETURNED], true);
        }
        return '';
    }
}
