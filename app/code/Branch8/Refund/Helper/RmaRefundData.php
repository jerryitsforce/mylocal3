<?php

namespace Branch8\Refund\Helper;

use Branch8\Rma\Helper\RmaActions;
use Branch8\Rma\Model\Rma\Status as RmaStatus;
use Magento\Framework\App\ResourceConnection;
use Webkul\MpRmaSystem\Model\ResourceModel\Details\CollectionFactory as DetailsCollection;
use \Branch8\Sales\Model\CreditMemo\CreditMemoStatus;
use Branch8\Sales\Helper\Order\UpdateOrderStatus;

class RmaRefundData
{

    /** @var \Branch8\Rma\Helper\RmaActions $rmaActions */
    protected $rmaActions;

    /**　@var \Webkul\MpRmaSystem\Model\ResourceModel\Details\CollectionFactory $detailsCollection */
    protected $detailsCollection;

    /** @var \Branch8\Sales\Helper\Order\UpdateOrderStatus $updateOrderStatus */
    protected $updateOrderStatus;

    private ResourceConnection $resourceConnection;

    public function __construct(
        RmaActions $rmaActions,
        DetailsCollection $detailsCollectionFactory,
        UpdateOrderStatus $updateOrderStatus,
        ResourceConnection $resourceConnection
    ) {
        $this->rmaActions = $rmaActions;
        $this->detailsCollection = $detailsCollectionFactory;
        $this->updateOrderStatus = $updateOrderStatus;
        $this->resourceConnection = $resourceConnection;
    }

    /**
     * setRmaRefund
     *
     * @param  int $memoId
     * @param  array $data
     * @return
     */
    public function setRmaRefund($memoId, $data)
    {
        $rmaDetail = $this->findRmaRecordByMemoId($memoId);

        if (is_null($rmaDetail->getOrderId())) {
            return;
        }

        $sellerUpdateStatus = RmaStatus::RETURNED;

        if ($data['creditmemo_status'] == CreditMemoStatus::REFUND_FAIL) {
            $sellerUpdateStatus = RmaStatus::RETURN_REFUND_FAIL;
        }

        $status = $this->rmaActions->changeStatusByRefundCron(
            $rmaDetail->getId(),
            $sellerUpdateStatus,
            $extraParams = []
        );

        $item = $this->rmaActions->getRmaItemCollection($rmaDetail->getId());

        foreach( $item as $singleItem) {
            $this->updateOrderStatus->updateItemStatusById(
                $singleItem->getItemId(), $status, $singleItem->getOrderId());
        }
    }

    /**
     * findRmaRecordByMemoId
     *
     * @param  int $memoId
     * @return \Webkul\MpRmaSystem\Model\ResourceModel\Details\CollectionFactory $detailsCollection
     */
    public function findRmaRecordByMemoId($memoId)
    {
        return $this->detailsCollection
            ->create()
            ->addFieldToFilter('memo_id', $memoId)
            ->getFirstItem();
    }


    /**
     * @param $memoId
     * @return string
     */
    public function getRefundedPointByMemoId($memoId)
    {
        $connection = $this->resourceConnection->getConnection();
        $salesCreditMemoItemTable = $connection->getTableName('sales_creditmemo_item');

        $select = $connection->select()
            ->from($salesCreditMemoItemTable, ['refunded_point'])
            ->where('parent_id = ?', $memoId);

        return $connection->fetchOne($select);
    }


    public function updateRmaCreditmemo($rmaId, $rmaData) {
        $this->rmaActions->updateRmaRecordData($rmaId, $rmaData);
    }

}
