<?php
namespace Branch8\Rma\Observer;

use Branch8\Rma\Helper\Data;
use Branch8\Rma\Helper\Email;
use Branch8\Rma\Helper\RmaActions;
use Branch8\Rma\Model\Rma\Status;
use Branch8\Sales\Model\Actions\ResyncOrdersToGrid;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\Event\Observer;
use Magento\Sales\Model\OrderFactory;
use Psr\Log\LoggerInterface;

class RmaStatusChangeAfterObserver implements \Magento\Framework\Event\ObserverInterface
{
    /**
     * Log option value for this observer.
     */
    private const LOG_OPTION = 'RmaStatusChangeAfterObserver';

    private Data $mpRmaHelper;

    private LoggerInterface $logger;

    private RmaActions $rmaActions;

    protected OrderFactory $orderFactory;

    protected Email $emailHelper;

    /**
     * @var array|mixed|null
     */
    private mixed $rmaId;
    /**
     * @var array|mixed|null
     */
    private mixed $newStatus;
    /**
     * @var array|mixed|null
     */
    private mixed $extraParams;
    private ResyncOrdersToGrid $resyncOrdersToGrid;

    private ResourceConnection $resourceConnection;


    public function __construct(
        Data $helper,
        RmaActions $rmaActions,
        LoggerInterface $logger,
        OrderFactory $orderFactory,
        Email $emailHelper,
        ResourceConnection $resourceModel,
        ResyncOrdersToGrid $resyncOrdersToGrid,

    ) {
        $this->rmaActions = $rmaActions;
        $this->mpRmaHelper = $helper;
        $this->logger = $logger;
        $this->orderFactory = $orderFactory;
        $this->emailHelper = $emailHelper;
        $this->resyncOrdersToGrid = $resyncOrdersToGrid;
        $this->resourceConnection = $resourceModel;
    }

    public function execute(Observer $observer)
    {
        $this->rmaId = $observer->getData('rma_id');
        $this->newStatus = $observer->getData('new_status');
        $this->extraParams = $observer->getData('extra_params');

        if(!$this->rmaId) {
            return;
        }

        if(in_array($this->newStatus, Status::declinedStatus())) {
            $this->sendDeclinedEmail();
        }

    }

    /**
     * @param $id
     * @return void
     */
    private function resyncOrdersToGrid($id)
    {
        $select = $this->resourceConnection->getConnection()->select();
        $select->from('marketplace_rma_details',['order_id'])
            ->where('id = ?', $id);
        $row = $this->resourceConnection->getConnection()->fetchRow($select);
        if (!empty($row['order_id'])) {
            $this->resyncOrdersToGrid->execute([$row['order_id']]);
        }
    }

    public function sendDeclinedEmail()
    {
        try {
            // Get the collection of items associated with the RMA
            $items = $this->rmaActions->getRmaItemCollection($this->rmaId);

            // Retrieve the first order associated with the RMA items (assuming all items are part of the same order)
            if (count($items) >= 1) {
                $firstItem = reset($items); // Gets the first item in the array

                // Assuming each item contains an order ID
                $orderId = $firstItem->getOrderId();
                $order = $this->orderFactory->create()->load($orderId);

                // Ensure order exists
                if ($order->getId()) {
                    // Send declined email with order information
                    $this->emailHelper->sendDeclinedEmail(
                        $items,
                        $this->rmaId,
                        $this->extraParams['decline_reason_id'],    // Decline reason ID
                        $this->extraParams['decline_reason_detail'], // Decline reason details
                        $order, // Pass the order to the email helper
                        $this->newStatus
                    );
                } else {
                    \Magento\Framework\App\ObjectManager::getInstance()
                        ->get(\Branch8\Rma\Helper\Log::class)
                        ->info("Order not found for RMA ID: {$this->rmaId}", self::LOG_OPTION);
                }
            } else {
                \Magento\Framework\App\ObjectManager::getInstance()
                    ->get(\Branch8\Rma\Helper\Log::class)
                    ->info("No items found for RMA ID: {$this->rmaId}", self::LOG_OPTION);
            }
        } catch (\Exception $e) {
            \Magento\Framework\App\ObjectManager::getInstance()
                ->get(\Branch8\Rma\Helper\Log::class)
                ->exception($e, self::LOG_OPTION, __METHOD__, ['rma_id' => $this->rmaId]);
        }
    }
}
