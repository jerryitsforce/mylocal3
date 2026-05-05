<?php

/**
 * Copyright © 2016 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Branch8\HotaiPay\Observer;

use Magento\Framework\Event\Observer;
use Magento\Payment\Observer\AbstractDataAssignObserver;
use Branch8\HotaiPay\Model\ParentOrderPaymentFactory;
use Magento\Framework\Session\SessionManager;
use \Magento\Framework\Stdlib\DateTime\DateTime;
use Branch8\HotaiPay\Helper\Order\OrderManagement;
use Branch8\HotaiPay\Helper\Log as HotaiPayLogHelper;

class ParentOrderPaymentData extends AbstractDataAssignObserver
{

    private $parentOrderPaymentFactory;
    protected $coreSession;
    protected $date;
    private HotaiPayLogHelper $hotaiPayLogHelper;
    protected $orderManagement;
    /**
     * __construct
     *
     * @return void
     */
    public function __construct(
        ParentOrderPaymentFactory $parentOrderPaymentFactory,
        SessionManager $coreSession,
        DateTime $date,
        HotaiPayLogHelper $hotaiPayLogHelper,
        OrderManagement $orderManagement
    ) {
        $this->parentOrderPaymentFactory = $parentOrderPaymentFactory;
        $this->coreSession = $coreSession;
        $this->date = $date;
        $this->hotaiPayLogHelper = $hotaiPayLogHelper;
        $this->orderManagement = $orderManagement;
    }
    public function execute(Observer $observer)
    {
        $dataObject = $observer->getData('data_object');
        try {
            if ($dataObject && $dataObject->isObjectNew() && $dataObject->getId()) {
                $parentOrderId = $dataObject->getId();
                $grandTotal = $dataObject->getData('grand_total');
                //$grandTotal = $this->orderManagement->getGrandTotalByParentId($parentOrderId);

                //Save to Parent Order Payment table
                $collection = $this->parentOrderPaymentFactory->create();
                $collection->setParentId($parentOrderId);
                $collection->setBaseAmountOrdered($grandTotal);
                $collection->setCreatedAt($this->date->gmtDate());
                $collection->setUpdatedAt($this->date->gmtDate());
                $collection->save();
            }
        } catch (\Exception $exception) {
            $this->hotaiPayLogHelper->writeLog('[Exception] ' . $exception->getMessage(), __CLASS__);
        }
    }
}
