<?php
declare(strict_types=1);

namespace Branch8\MarketPlaceParentOrderChildLink\Model;

use Branch8\MarketPlaceParentOrder\Api\Data\ParentOrderDetailInterface;
use Branch8\MarketPlaceParentOrder\Model\ParentOrderDetailFactory;
use Magento\Framework\DataObject\Copy;

class  AssignDataForParentOrder
{
    public Copy $objectCopyService;

    private \Magento\Framework\Api\DataObjectHelper $dataObjectHelper;

    public ParentOrderDetailFactory $detailFactory;

    /**
     * @param Copy $copy
     * @param \Magento\Framework\Api\DataObjectHelper $dataObjectHelper
     * @param ParentOrderDetailFactory $detailFactory
     */
    public function __construct(
        \Magento\Framework\DataObject\Copy      $copy,
        \Magento\Framework\Api\DataObjectHelper $dataObjectHelper,
        ParentOrderDetailFactory                $detailFactory
    )
    {
        $this->detailFactory = $detailFactory;
        $this->objectCopyService = $copy;
        $this->dataObjectHelper = $dataObjectHelper;
    }

    /**
     * @param \Magento\Sales\Api\Data\OrderInterface $order
     * @param \Branch8\MarketPlaceParentOrder\Model\ParentOrderDetail $detail
     * @return \Branch8\MarketPlaceParentOrder\Model\ParentOrderDetail
     */
    public function copy(
        \Magento\Sales\Api\Data\OrderInterface                  $order,
        \Branch8\MarketPlaceParentOrder\Model\ParentOrderDetail $detailObject
    )
    {
        //$detailObject = $this->detailFactory->create();
        $salesOrderData = $this->objectCopyService->getDataFromFieldset(
            'sales_order_detail_copy_parent_order',
            'to_parent_order',
            $order
        );
        $this->dataObjectHelper->populateWithArray(
            $detailObject,
            $salesOrderData,
            ParentOrderDetailInterface::class
        );
        return $detailObject;
    }
}
