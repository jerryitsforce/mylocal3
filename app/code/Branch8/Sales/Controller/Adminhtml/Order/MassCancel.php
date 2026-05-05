<?php
declare(strict_types=1);

namespace Branch8\Sales\Controller\Adminhtml\Order;

use Branch8\HotaiCore\Model\Order\Status;
use Branch8\Marketplace\Model\Actions\CancelOrderAction;
use Branch8\Marketplace\Model\Actions\GetSellerByOrder;
use Magento\Framework\App\Action\HttpPostActionInterface as HttpPostActionInterface;
use Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;
use Magento\Backend\App\Action\Context;
use Magento\Ui\Component\MassAction\Filter;
use Magento\Sales\Model\ResourceModel\Order\CollectionFactory;
use Magento\Sales\Api\OrderManagementInterface;
use Psr\Log\LoggerInterface;

class MassCancel extends \Magento\Sales\Controller\Adminhtml\Order\AbstractMassAction implements HttpPostActionInterface
{
    /**
     * Authorization level of a basic admin session
     */
    const ADMIN_RESOURCE = 'Magento_Sales::cancel';

    private CancelOrderAction $cancelOrderAction;
    private LoggerInterface $logger;
    private GetSellerByOrder $getSellerByOrder;

    /**
     * @param Context $context
     * @param Filter $filter
     * @param CollectionFactory $collectionFactory
     * @param CancelOrderAction $cancelOrderAction
     * @param GetSellerByOrder $getSellerByOrder
     * @param LoggerInterface $logger
     */
    public function __construct(
        Context           $context,
        Filter            $filter,
        CollectionFactory $collectionFactory,
        CancelOrderAction $cancelOrderAction,
        GetSellerByOrder $getSellerByOrder,
        LoggerInterface   $logger
    )
    {
        parent::__construct($context, $filter);
        $this->logger = $logger;
        $this->getSellerByOrder = $getSellerByOrder;
        $this->cancelOrderAction = $cancelOrderAction;
        $this->collectionFactory = $collectionFactory;
    }

    /**
     * Cancel selected orders
     *
     * @param AbstractCollection $collection
     * @return \Magento\Framework\Controller\Result\Redirect
     */
    protected function massAction(AbstractCollection $collection)
    {
        $countCancelOrder = 0;
        foreach ($collection->getItems() as $order) {
            try {
                $isCanceled = true;
                $seller = $this->getSellerByOrder->execute($order);
                $sellerId = $seller ? $seller->getId() : null;
                $this->cancelOrderAction
                    ->execute($order, $sellerId)
                    ->saveCancelReason($order)
                    ->updateTrackingCollection($order, $sellerId)
                    ->updateOrderItemFlowStatus($order, $status = Status::STATUS_CANCELED)
                    ->updateSellerOrderStatus($order, $sellerId);
            } catch (\Exception $exception) {
                $this->logger->critical($exception->getMessage());
                $isCanceled = false;
            }
            if ($isCanceled === false) {
                continue;
            }
            $countCancelOrder++;
        }
        $countNonCancelOrder = $collection->count() - $countCancelOrder;
        if ($countNonCancelOrder && $countCancelOrder) {
            $this->messageManager->addErrorMessage(__('%1 order(s) cannot be canceled.', $countNonCancelOrder));
        } elseif ($countNonCancelOrder) {
            $this->messageManager->addErrorMessage(__('You cannot cancel the order(s).'));
        }

        if ($countCancelOrder) {
            $this->messageManager->addSuccessMessage(__('We canceled %1 order(s).', $countCancelOrder));
        }
        $resultRedirect = $this->resultRedirectFactory->create();
        $resultRedirect->setPath($this->getComponentRefererUrl());
        return $resultRedirect;
    }
}
