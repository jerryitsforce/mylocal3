<?php
declare(strict_types=1);

namespace Branch8\HotaiOrderNumber\Plugin;

use Branch8\HotaiCore\Helper\Common as CommonHelper;

;

use Branch8\HotaiOrderNumber\Model\Actions\HotaiGenerateIncrementIdForOrders;
use Branch8\MarketPlaceParentOrder\Observer\PopulateParentOrderData;
use Magento\Framework\DB\Transaction;
use Magento\Framework\Event\Observer;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Sales\Api\OrderItemRepositoryInterface;
use Magento\Sales\Api\OrderRepositoryInterface;

class CreateHotailOrderNumberOrderItem
{
    const LOG_FOLDER_NAME = 'HotaiOrderNumber/Plugin/CreateHotaiOrderNumber';

    /** @var OrderRepositoryInterface */
    protected $orderRepository;

    /** @var OrderItemRepositoryInterface */
    protected $orderItemRepository;

    /** @var Transaction */
    protected $transaction;

    /** @var CommonHelper */
    protected $commonHelper;
    /**
     * @var HotaiGenerateIncrementIdForOrders
     */
    private HotaiGenerateIncrementIdForOrders $hotaiGenerateIncrementIdForOrders;

    /**
     * @param OrderRepositoryInterface $orderRepository
     * @param OrderItemRepositoryInterface $orderItemRepository
     * @param Transaction $transaction
     * @param CommonHelper $commonHelper
     * @param HotaiGenerateIncrementIdForOrders $hotaiGenerateIncrementIdForOrders
     */

    public function __construct(
        OrderRepositoryInterface          $orderRepository,
        OrderItemRepositoryInterface      $orderItemRepository,
        Transaction                       $transaction,
        CommonHelper                      $commonHelper,
        HotaiGenerateIncrementIdForOrders $hotaiGenerateIncrementIdForOrders
    )
    {
        $this->orderRepository = $orderRepository;
        $this->orderItemRepository = $orderItemRepository;
        $this->transaction = $transaction;
        $this->commonHelper = $commonHelper;
        $this->hotaiGenerateIncrementIdForOrders = $hotaiGenerateIncrementIdForOrders;
    }

    /**
     * @param PopulateParentOrderData $subject
     * @param $result
     * @param Observer $observer
     * @return mixed|void
     * @throws \Magento\Framework\Exception\FileSystemException
     * @throws \Zend_Log_Exception
     */
    public function afterExecute(PopulateParentOrderData $subject, $result, Observer $observer)
    {
        try {
            if (!$this->isMpSplitOrderNew($observer)) {
                return;
            }
            $object = $observer->getData('data_object');
            if (empty($object->getData('order_ids'))) {
                return;
            }
            $subOrderIds = explode(',', $object->getData('order_ids'));
            foreach ($subOrderIds as $orderId) {
                try {
                    $order = $this->orderRepository->get((int)($orderId));
                    /**
                     * @var $order \Magento\Sales\Model\Order
                     */
                    foreach ($order->getAllVisibleItems() as $item) {
                        $this->hotaiGenerateIncrementIdForOrders->generateForOrderItem($order, $item);
                        $orderItem = $this->orderItemRepository->get($item->getId());
                        $orderItem->setData(HotaiGenerateIncrementIdForOrders::FIELD_NAME_HOTAI_CHILD_ORDER_ITEM_NUMBER,
                            $this->hotaiGenerateIncrementIdForOrders->generateForOrderItem($order, $item)
                        );
                        $this->transaction->addObject($orderItem);
                    }
                } catch (NoSuchEntityException $exception) {
                    continue;
                }
            }
            $this->transaction->save();
            return $result;
        } catch (\Exception $e) {
            $this->commonHelper->writeLog("Exception message in createOrderItems afterExcute: " . $e->getMessage(), self::LOG_FOLDER_NAME);
        }
    }

    /**
     * 確認觸發observer的是不是MpSplitOrder的新紀錄
     *
     * @param Observer $observer
     * @return boolean
     */
    protected function isMpSplitOrderNew(Observer $observer): bool
    {
        $dataObject = $observer->getData('data_object');
        return ($dataObject && $dataObject->isObjectNew() && $dataObject->getId());
    }
}
