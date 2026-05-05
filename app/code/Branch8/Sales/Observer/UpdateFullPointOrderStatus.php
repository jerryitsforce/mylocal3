<?php
declare (strict_types = 1);

namespace Branch8\Sales\Observer;

use Branch8\HotaiCore\Helper\Common as HotaiCoreCommonHelper;
use Branch8\HotaiCore\Model\Order\State;
use Branch8\HotaiCore\Model\Order\Status;
use Branch8\MarketPlaceParentOrder\Api\Data\ParentOrderInterface;
use Branch8\MarketPlaceParentOrder\Model\ParentOrderFactory;
use Branch8\Sales\Helper\Data as DataHelper;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Sales\Api\OrderRepositoryInterface;
use \Branch8\Sales\Helper\Order\UpdateOrderStatus;

class UpdateFullPointOrderStatus implements ObserverInterface
{
    const LOG_FOLDER_NAME = 'Sales/Observer/UpdateFullPointOrderStatus';
    const METHOD_FREE     = 'free';

    /** @var OrderRepositoryInterface */
    protected $orderRepository;

    /** @var ResourceConnection */
    protected $resourceConnection;

    /** @var HotaiCoreCommonHelper */
    protected $hotaiCoreCommonHelper;

    /** @var DataHelper */
    protected $dataHelper;

    /** @var \Branch8\MarketPlaceParentOrder\Model\ParentOrderFactory $parentOrderFactory */
    protected $parentOrderFactory;

    /** @var \Branch8\Sales\Helper\Order\UpdateOrderStatus $updateOrderStatus */
    protected $updateOrderStatus;

    public function __construct(
        OrderRepositoryInterface $orderRepository,
        ResourceConnection $resourceConnection,
        HotaiCoreCommonHelper $hotaiCoreCommonHelper,
        DataHelper $dataHelper,
        ParentOrderFactory $parentOrderFactory,
        UpdateOrderStatus $updateOrderStatus
    ) {
        $this->orderRepository       = $orderRepository;
        $this->resourceConnection    = $resourceConnection;
        $this->hotaiCoreCommonHelper = $hotaiCoreCommonHelper;
        $this->dataHelper            = $dataHelper;
        $this->parentOrderFactory    = $parentOrderFactory;
        $this->updateOrderStatus     = $updateOrderStatus;
    }

    public function execute(Observer $observer)
    {
        try {
            /** @var ParentOrderInterface $parentOrder */
            $parentOrder   = $observer->getData('data_object');
            $orderIdsArray = explode(',', $parentOrder->getOrderIds());
            $parentQuote   = $observer->getData('quote');

            if (! $parentQuote->getData('is_gift_order')) {
                return;
            }

            $b8ParentOrder = $this->parentOrderFactory->create()->load($parentOrder->getId());
            $parentDetail  = $b8ParentOrder->getDetail();

            $this->hotaiCoreCommonHelper->writeLog(
                json_encode([
                    'parent_order_id'          => $parentOrder->getId(),
                    'sales_presentative_infor' => $parentDetail->getData('sales_presentative_infor'),
                ]),
                self::LOG_FOLDER_NAME
            );

            if ($parentDetail->getPaymentMethod() !== self::METHOD_FREE) {
                return;
            }

            $parentDetail
                ->setState(State::STATE_PROCESSING)
                ->setStatus(Status::STATUS_GIFT_INFO_PENDING)
                ->save();

            foreach ($orderIdsArray as $orderId) {
                try {
                    /** @var \Magento\Sales\Model\Order $order */
                    $order = $this->orderRepository->get((int) $orderId);

                    $order->setState(State::STATE_PROCESSING)
                        ->setStatus(Status::STATUS_GIFT_INFO_PENDING)
                        ->addStatusHistoryComment(
                            __('Update Order Status to #%1.', Status::STATUS_GIFT_INFO_PENDING));

                    $this->orderRepository->save($order);

                    foreach ($order->getAllVisibleItems() as $item) {
                        $item->setFlowStatus(Status::STATUS_GIFT_INFO_PENDING);
                        $item->save();

                        $this->updateOrderStatus->addItemStatusRecord(
                            $order->getId(),
                            $item,
                            Status::STATUS_GIFT_INFO_PENDING
                        );
                    }

                } catch (\Exception $e) {
                    $this->hotaiCoreCommonHelper->writeLog(
                        json_encode([
                            'title'            => 'exception during order loop',
                            'orderId'          => $orderId,
                            'exceptionMessage' => $e->getMessage(),
                        ]),
                        self::LOG_FOLDER_NAME
                    );
                }
            }

        } catch (\Exception $e) {
            $this->hotaiCoreCommonHelper->writeLog(
                json_encode([
                    'exceptionMessage' => $e->getMessage(),
                ]),
                self::LOG_FOLDER_NAME
            );
        }
    }
}
