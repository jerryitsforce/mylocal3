<?php
declare(strict_types=1);

namespace Branch8\Sales\Observer;

use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Sales\Api\OrderRepositoryInterface;
use Branch8\MarketPlaceParentOrder\Api\Data\ParentOrderInterface;
use Magento\Sales\Model\Order\Address;
use Magento\Framework\App\ResourceConnection;
use Branch8\HotaiCore\Helper\Common as HotaiCoreCommonHelper;
use Branch8\Sales\Helper\Data as DataHelper;

class FillInPostcodeIfNeeded implements ObserverInterface
{
    const LOG_FOLDER_NAME = 'Sales/Observer/FillInPostcodeIfNeeded';

    /** @var OrderRepositoryInterface */
    protected $orderRepository;

    /** @var ResourceConnection */
    protected $resourceConnection;

    /** @var HotaiCoreCommonHelper */
    protected $hotaiCoreCommonHelper;

    /** @var DataHelper */
    protected $dataHelper;

    protected $postcodeQueryResult = null;
    protected $addressHandled      = false;

    public function __construct(
        OrderRepositoryInterface $orderRepository,
        ResourceConnection $resourceConnection,
        HotaiCoreCommonHelper $hotaiCoreCommonHelper,
        DataHelper $dataHelper
    ) {
        $this->orderRepository       = $orderRepository;
        $this->resourceConnection    = $resourceConnection;
        $this->hotaiCoreCommonHelper = $hotaiCoreCommonHelper;
        $this->dataHelper            = $dataHelper;
    }

    public function execute(Observer $observer)
    {
        try {
            /** @var ParentOrderInterface $parentOrder */
            $parentOrder = $observer->getData('data_object');
            $this->updateParentOrderPostcode($parentOrder->getId());

            // $parentOrder->getSubOrders() will get null at this event timeing(marketplace_mpsplitorder_submit_after).
            $orderIdsArray = explode(',', $parentOrder->getOrderIds());

            foreach ($orderIdsArray as $orderId) {
                try {
                    $this->postcodeQueryResult = null;
                    $this->addressHandled      = false;

                    $order = $this->orderRepository->get((int) $orderId);

                    $shippingAddress = $order->getShippingAddress();
                    if ($shippingAddress) {
                        $this->handleAddressPostcode($shippingAddress);
                    }

                    $billingAddress = $order->getBillingAddress();
                    if ($billingAddress) {
                        $this->handleAddressPostcode($billingAddress);
                    }

                    if ($this->addressHandled) {
                        $this->orderRepository->save($order);
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
                    'title'            => 'exception during parent order execute',
                    'orderIds'         => $parentOrder->getOrderIds(),
                    'exceptionMessage' => $e->getMessage(),
                ]),
                self::LOG_FOLDER_NAME
            );
        }
    }

    protected function updateParentOrderPostcode(int|string $parentOrderId): void
    {
        $connection = $this->resourceConnection->getConnection();
        $tableName  = $this->resourceConnection->getTableName('sales_parent_order_address');

        $select = $connection->select()->from($tableName);
        $select->where(
            "parent_order_id = ?",
            $parentOrderId
        );

        $parentOrderAddressArray = $connection->fetchAll($select);

        foreach ($parentOrderAddressArray as $parentOrderAddress) {
            if (!$this->checkIfAddressNeedFillInPostCode($parentOrderAddress['postcode'])) {
                continue;
            }

            $postcode = $this->queryPostcodeForAddress($parentOrderAddress['city'], $parentOrderAddress['region']);

            $data = [
                'postcode' => $postcode
            ];

            $connection->update(
                $tableName,
                $data,
                ['entity_id = ?' => $parentOrderAddress['entity_id']]
            );
        }
    }

    protected function handleAddressPostcode(Address $address): void
    {
        $handled = $this->checkIfAddressNeedFillInPostCode($address->getPostcode());

        if ($handled) {
            $postcode = $this->queryPostcodeForAddress($address->getCity(), $address->getRegion());
            $address->setPostcode($postcode);
        }

        $this->addressHandled = $this->addressHandled || $handled;
    }

    protected function checkIfAddressNeedFillInPostCode($postcode): bool
    {
        return empty($postcode) || $postcode === '000' || !is_numeric($postcode);
    }

    protected function checkIfAddressNeedAddPostcodePrefixToStreet(Address $address): bool
    {
        $street = $address->getStreet();

        $streetPrefix = substr($street[0] ?? '', 0, 3);

        return !is_numeric($streetPrefix);
    }

    protected function queryPostcodeForAddress($city, $region): string
    {
        if ($this->postcodeQueryResult) {
            return $this->postcodeQueryResult;
        }

        $this->postcodeQueryResult = $this->dataHelper->queryPostcodeForAddress($city, $region);

        return $this->postcodeQueryResult;
    }
}
