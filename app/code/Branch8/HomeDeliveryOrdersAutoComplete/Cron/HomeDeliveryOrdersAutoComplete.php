<?php

namespace Branch8\HomeDeliveryOrdersAutoComplete\Cron;

use Branch8\HomeDeliveryOrdersAutoComplete\Helper\Common as CommonHelper;
use Branch8\MarketPlaceParentOrder\Model\ResourceModel\ParentOrderDetail\Collection as ParentOrderDetailCollection;
use Branch8\MarketPlaceParentOrder\Model\ResourceModel\ParentOrderDetail\CollectionFactory as ParentOrderDetailCollectionFactory;
use Magento\Framework\DB\Transaction;
use Magento\Sales\Api\OrderRepositoryInterface;
use Webkul\Mpsplitorder\Model\MpsplitorderFactory;
use Branch8\HotaiCore\Model\Order\State;
use Branch8\HotaiCore\Model\Order\Status;

class HomeDeliveryOrdersAutoComplete
{
    const DEFAULT_AFTER_MINUTES = 28800; // 20 days(60*24*20 = 28800)
    const LOG_FOLDER_NAME       = 'Cron';
    const COMPLETE_STATE        = 'complete';
    const COMPLETE_STATUS       = 'complete';

    /** @var ParentOrderDetailCollectionFactory */
    protected $parentOrderDetailCollectionFactory;

    /** @var CommonHelper */
    protected $commonHelper;

    /** @var OrderRepositoryInterface */
    protected $orderRepository;

    /** @var MpsplitorderFactory */
    protected $mpsplitorderFactory;

    /** @var Transaction */
    protected $transaction;

    protected $logFileName;
    protected $walkthroughLog;

    public function __construct(
        ParentOrderDetailCollectionFactory $parentOrderDetailCollectionFactory,
        CommonHelper $commonHelper,
        OrderRepositoryInterface $orderRepository,
        MpsplitorderFactory $mpsplitorderFactory,
        Transaction $transaction
    ) {
        $this->parentOrderDetailCollectionFactory = $parentOrderDetailCollectionFactory;
        $this->commonHelper                       = $commonHelper;
        $this->orderRepository                    = $orderRepository;
        $this->mpsplitorderFactory                = $mpsplitorderFactory;
        $this->transaction                        = $transaction;

        $this->logFileName    = "home_delivery_orders_auto_complete_" . date("Y_m") . ".log";
        $this->walkthroughLog = [];
    }

    public function execute()
    {
        $this->writeLog("HomeDeliveryOrdersAutoComplete cron start.");

        try {
            $shippingMethodsImplodeString = $this->commonHelper->getConfig(CommonHelper::CONFIG_PATH_TARGET_SHIPPING_METHODS);

            if (empty($shippingMethodsImplodeString)) {
                $this->writeLog("No shipping method selected, homeDeliveryOrdersAutoComplete cron end.");
                return;
            }

            $parentOrderDetailCollection = $this->getParentOrderDetailCollection($shippingMethodsImplodeString);

            $this->writeLog("HomeDeliveryOrdersAutoComplete sql: " . $parentOrderDetailCollection->getSelect()->__toString());

            foreach ($parentOrderDetailCollection as $parentOrderDetail) {
                $this->writeLog("Ready to handle parent ID: " . $parentOrderDetail->getParentId());
                $childOrderIdArray = $this->getChildOrderIdArrayByParentOrderId($parentOrderDetail->getParentId());
                $this->writeLog("Child order IDs: " . implode(",", $childOrderIdArray));

                foreach ($childOrderIdArray as $childOrderId) {
                    $this->writeLog("Ready to handle child order ID: " . $childOrderId);
                    /** @var \Magento\Sales\Model\Order $childOrder */
                    $childOrder = $this->orderRepository->get($childOrderId);
                    $childOrder->setState(State::STATE_COMPLETE);
                    $childOrder->setStatus(Status::STATUS_COMPLETE);

                    $this->transaction->addObject($childOrder);
                    $this->writeLog("Child order set to 'complete' added to transaction.");
                }

                $parentOrderDetail->setState(State::STATE_COMPLETE);
                $parentOrderDetail->setStatus(Status::STATUS_COMPLETE);
                $this->transaction->addObject($parentOrderDetail);
                $this->writeLog("Parent order set to 'complete' added to transaction.");

                $this->transaction->save();
                $this->writeLog("Transaction saved.");
            }
        } catch (\Exception $e) {
            $this->writeLog("HomeDeliveryOrdersAutoComplete exception message: " . $e->getMessage());
        }

        $this->writeLog("HomeDeliveryOrdersAutoComplete cron end.");
    }

    /**
     * 取得此次cron執行的目標parent order collection
     *
     * @param string $shippingMethodsImplodeString
     * @return ParentOrderDetailCollection
     */
    protected function getParentOrderDetailCollection(string $shippingMethodsImplodeString): ParentOrderDetailCollection
    {
        $afterMinutes = $this->commonHelper->getConfig(CommonHelper::CONFIG_PATH_CREATED_AFTER_MINUTES);
        $afterMinutes = ($afterMinutes) ? $afterMinutes : self::DEFAULT_AFTER_MINUTES;
        $filterDate   = date('Y-m-d H:i:s', strtotime("-" . $afterMinutes . " minutes"));

        $collection = $this->parentOrderDetailCollectionFactory->create();

        $collection->addFieldToFilter(
            'status',
            'shipping'
        )->addFieldToFilter(
            'shipping_method',
            ['in' => $shippingMethodsImplodeString]
        )->addFieldToFilter(
            'created_at',
            ['lt' => $filterDate]
        );

        return $collection;
    }

    /**
     * 以parent order ID取得底下所屬的Magento原生sales order ID array
     *
     * @param integer $parentOrderId
     * @return array
     */
    protected function getChildOrderIdArrayByParentOrderId(int $parentOrderId): array
    {
        $mainParentOrder = $this->mpsplitorderFactory->create()->load($parentOrderId, 'index_id');
        $childOrderIds   = explode(',', $mainParentOrder->getOrderIds());

        return $childOrderIds;
    }

    /**
     * 寫入log
     *
     * @param string $message
     * @return void
     */
    protected function writeLog($message): void
    {
        $this->commonHelper->writeLog($message, $this->logFileName, self::LOG_FOLDER_NAME);
    }
}
