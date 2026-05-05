<?php

namespace Branch8\Refund\Helper;

use Branch8\HotaiCore\Helper\VirtualProduct;
use Exception;
use Magento\Sales\Model\Order;

/**
 * Cancels ticket inventory for visible order items when order is canceled/refunded.
 */
class CancelTicket
{
    private const LOG_CLASS_KEY = 'CancelTicket';

    /** @var VirtualProduct */
    protected $virtualProduct;

    /** @var ConfigurableRefundLogger */
    protected $refundLogger;

    /**
     * @param VirtualProduct $virtualProduct Ticket/virtual product helper
     * @param ConfigurableRefundLogger $refundLogger Admin-gated file logger
     */
    public function __construct(
        VirtualProduct $virtualProduct,
        ConfigurableRefundLogger $refundLogger
    ) {
        $this->virtualProduct = $virtualProduct;
        $this->refundLogger = $refundLogger;
    }

    /**
     * Cancel tickets for each visible line item when applicable.
     *
     * @param Order $order Sales order being canceled
     * @return void
     */
    public function execute(Order $order)
    {
        try {
            $this->refundLogger->log(self::LOG_CLASS_KEY, '[Cancel Ticket] ' . $order->getId());

            foreach ($order->getAllVisibleItems() as $item) {
                $this->refundLogger->log(self::LOG_CLASS_KEY, '[Cancel Ticket Item] ' . $item->getId());

                if (!$this->virtualProduct->checkIsProductTicketTypeByOrderItemId((int) $item->getId())) {
                    $this->refundLogger->log(self::LOG_CLASS_KEY, '[Status] Not Ticket Type.');
                    continue;
                }

                $this->virtualProduct->cancelTickets((int) $item->getId());

                $this->refundLogger->log(self::LOG_CLASS_KEY, '[Status] Successful cancel ticket.');
            }
        } catch (Exception $e) {
            $this->refundLogger->logException(self::LOG_CLASS_KEY, $e, 'execute');
        }
    }
}
