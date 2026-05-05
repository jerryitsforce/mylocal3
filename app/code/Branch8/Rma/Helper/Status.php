<?php

namespace Branch8\Rma\Helper;

use Branch8\Rma\Model\MarketplaceRmaStatusHistoryFactory;
use Branch8\Rma\Helper\Config\Flow;

class Status extends \Branch8\Rma\Model\Rma\Status
{
    /** @var \Branch8\Rma\Model\MarketplaceRmaStatusHistoryFactory $marketplaceRmaStatusHistory */
    protected $marketplaceRmaStatusHistory;

    /** @var \Branch8\Sales\Helper\Order\UpdateOrderStatus $updateOrderStatus */
    protected $updateOrderStatus;

    /** @var \Branch8\Rma\Helper\Config\Flow $flow */
    protected $flow;

    public function __construct(
        MarketplaceRmaStatusHistoryFactory $marketplaceRmaStatusHistory,
        Flow $flow
    ) {
        $this->marketplaceRmaStatusHistory = $marketplaceRmaStatusHistory;
        $this->flow = $flow;
    }

    /**
     * createStatusRecord
     *
     * @param  string $status
     * @param  \Webkul\MpRmaSystem\Model\Details $rmaDetail
     * @return void
     */
    public function createStatusRecord($status, $rmaDetail, $comment = null)
    {
        if (is_null($comment)) {
            $comment = __(
                "Update Rma Status To %1", $status
            );
        }
        $record = $this->marketplaceRmaStatusHistory->create();
        $record->setParentId($rmaDetail->getId());
        $record->setIsCustomerNotified(false);
        $record->setIsVisibleOnFront(false);
        $record->setComment($comment);
        $record->setStatus($status);
        $record->save();
    }
    
    /**
     * getNextStatus
     *
     * @param  string|int  $updateStatus
     * @param  bool $naturalPerson
     * @return string|int
     */
    public function getNextStatus($updateStatus, $naturalPerson){
        return $this->flow->getNextStatus($updateStatus, $naturalPerson);
    }

}
