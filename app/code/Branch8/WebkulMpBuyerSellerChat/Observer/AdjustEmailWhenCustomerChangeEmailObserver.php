<?php
declare(strict_types=1);

namespace Branch8\WebkulMpBuyerSellerChat\Observer;

use Branch8\WebkulMpBuyerSellerChat\Model\ChatRole;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Branch8\WebkulMpBuyerSellerChat\Helper\Logger as CustomLogger;

/**
 * Dispatcher for the `AdjustEmailWhenCustomerChangeEmail` event.
 */
class AdjustEmailWhenCustomerChangeEmailObserver implements ObserverInterface
{
    /**
     * @var ResourceConnection
     */
    private ResourceConnection $resource;
    /**
     * @var LoggerInterface
     */
    private CustomLogger $logger;

    /**
     * @param ResourceConnection $resource
     * @param CustomLogger $logger
     */
    public function __construct(
        ResourceConnection $resource,
        CustomLogger       $logger
    )
    {
        $this->logger = $logger;
        $this->resource = $resource;
    }

    /**
     * Handle the `AdjustEmailWhenCustomerChangeEmail` event.
     *
     * @param Observer $observer
     *
     * @return void
     */
    public function execute(Observer $observer): void
    {
        /**
         * @var \Magento\Customer\Model\Data\Customer $customer
         * @var $connection \Magento\Framework\DB\Adapter\AdapterInterface
         **/
        $customer = $observer->getEvent()->getCustomerDataObject();
        try {
            $email = $customer->getEmail();
            $entityId = $customer->getId();
            $connection = $this->resource->getConnection();
            $bind = [
                'email' => $email,
            ];
            $where = ['object_id = ?' => $entityId, 'registered_as IN (?)' => [ChatRole::SELLER]];
            $connection->update('marketplace_chat_profile_info', $bind, $where);
        } catch (\Exception $e) {
            $this->logger->critical($e->getMessage());
        }
    }
}
