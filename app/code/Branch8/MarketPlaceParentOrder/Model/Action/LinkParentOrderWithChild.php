<?php

namespace Branch8\MarketPlaceParentOrder\Model\Action;

use Branch8\MarketPlaceParentOrder\Api\ParentOrderManagementInterface;
use Branch8\MarketPlaceParentOrder\Api\ParentOrderRepositoryInterface;
use Branch8\MarketPlaceParentOrder\Helper\Log;
use Branch8\MarketPlaceParentOrder\Model\ParentOrderAddress;
use Branch8\MarketPlaceParentOrder\Model\ParentOrderFactory;
use Branch8\MarketPlaceParentOrder\Model\Services\AssignDataForParentOrder;
use Branch8\MarketPlaceParentOrder\Model\Services\CopyAddressesFromSalesOrderToParentOrder;
use Branch8\MarketPlaceParentOrder\Model\Services\ProcessParentOrdersWhenCheckoutWithFullPoint;
use Magento\Framework\Exception\LocalizedException;
use Magento\Quote\Model\Quote;
use Magento\Sales\Model\OrderRepository;
use Webkul\Mpsplitorder\Model\Mpsplitorder;

class LinkParentOrderWithChild
{
    /**
     * @var ParentOrderRepositoryInterface
     */
    private ParentOrderRepositoryInterface $parentOrderRepository;
    /**
     * @var LoggerInterface
     */
    private Log $log;
    private OrderRepository $orderRepository;
    private AssignDataForParentOrder $assignDataForParentOrder;
    private ParentOrderFactory $parentOrderFactory;
    private CopyAddressesFromSalesOrderToParentOrder $copyAddressesFromSalesOrder;

    private ParentOrderManagementInterface $parentOrderManagement;
    private ProcessParentOrdersWhenCheckoutWithFullPoint $processParentOrderWithFullpoint;
    /**
     * @var \Magento\Customer\Model\ResourceModel\CustomerRepository
     */
    protected $customerRepository;

    /**
     * @param ParentOrderRepositoryInterface $parentOrderRepository
     * @param OrderRepository $orderRepository
     * @param ParentOrderFactory $parentOrderFactory
     * @param AssignDataForParentOrder $assignDataForParentOrder
     * @param CopyAddressesFromSalesOrderToParentOrder $copyAddressesFromSalesOrderToParentOrder
     * @param ParentOrderManagementInterface $parentOrderManagement
     * @param ProcessParentOrdersWhenCheckoutWithFullPoint $processParentOrdersWhenCheckoutWithFullPoint
     * @param Log $log
     * @param \Magento\Customer\Model\ResourceModel\CustomerRepository $customerRepository
     */
    public function __construct(
        ParentOrderRepositoryInterface                           $parentOrderRepository,
        OrderRepository                                          $orderRepository,
        ParentOrderFactory                                       $parentOrderFactory,
        AssignDataForParentOrder                                 $assignDataForParentOrder,
        CopyAddressesFromSalesOrderToParentOrder                 $copyAddressesFromSalesOrderToParentOrder,
        ParentOrderManagementInterface                           $parentOrderManagement,
        ProcessParentOrdersWhenCheckoutWithFullPoint             $processParentOrdersWhenCheckoutWithFullPoint,
        Log                                                      $log,
        \Magento\Customer\Model\ResourceModel\CustomerRepository $customerRepository
    )
    {
        $this->assignDataForParentOrder = $assignDataForParentOrder;
        $this->log = $log;
        $this->orderRepository = $orderRepository;
        $this->parentOrderFactory = $parentOrderFactory;
        $this->parentOrderRepository = $parentOrderRepository;
        $this->copyAddressesFromSalesOrder = $copyAddressesFromSalesOrderToParentOrder;
        $this->parentOrderManagement = $parentOrderManagement;
        $this->processParentOrderWithFullpoint = $processParentOrdersWhenCheckoutWithFullPoint;
        $this->customerRepository = $customerRepository;
    }

    /**
     * @param Mpsplitorder $parentOrder
     * @param Quote $masterQuote
     * @param $subOrderIds
     * @param $sendMail
     * @param $processFullpoint
     * @return \Branch8\MarketPlaceParentOrder\Model\ParentOrder|Mpsplitorder
     * @throws \Exception
     */
    public function execute(Mpsplitorder $parentOrder, Quote $masterQuote = null, $subOrderIds = [], $sendMail = true, $processFullpoint = true)
    {
        /**
         * @var $dataObject Mpsplitorder
         * Todo:
         * We can move this action into cron process to reduce the checkout time
         */
        $dataObject = $parentOrder;
        try {
            if ($dataObject && $dataObject->isObjectNew() && $dataObject->getId()) {
                $id = $dataObject->getId();
                $shippingAddress = $billingAddress = '';
                if (!$subOrderIds) {
                    try {
                        $suborders = explode(',', $dataObject->getData('order_ids'));
                    } catch (\Exception $exception) {
                        $suborders = [];
                    }
                } else {
                    $suborders = $subOrderIds;
                }
                if (empty($suborders)) {
                    throw new LocalizedException(
                        __('Can not link parent order, suborders are empty : parentOrderId:%1-MasterQuoteId:%2',
                            $parentOrder->getId(),
                            $masterQuote ? $masterQuote->getId() : ''
                        )
                    );
                }
                $lastSubOrder = null;
                foreach ($suborders as $subOrderId) {
                    $subOrder = $this->orderRepository->get(
                        $subOrderId
                    );
                    if ($subOrder->getShippingAddress()) {
                        $lastSubOrder = $subOrder;
                        break;
                    }
                }
                if (!$lastSubOrder) {
                    $lastSubOrder = $this->orderRepository->get(
                        $dataObject->getData('last_order_id')
                    );
                }
                $detail = $this->assignDataForParentOrder->copy(
                    $lastSubOrder
                );
                $addresses = $this->copyAddressesFromSalesOrder->copy($lastSubOrder);
                $detail->setParentId((int)$id);
                if ($addresses) {
                    /**
                     * @var $address ParentOrderAddress
                     */
                    foreach ($addresses as $address) {
                        $address->setParentOrderId((int)$id)->save();
                        if ($address->getAddressType() === ParentOrderAddress::TYPE_SHIPPING) {
                            $shippingAddress = $address;
                        }
                        if ($address->getAddressType() === ParentOrderAddress::TYPE_BILLING) {
                            $billingAddress = $address;
                        }
                    }
                }
                if ($shippingAddress) {
                    $detail->setShippingAddress($shippingAddress->getId());
                }
                if ($billingAddress) {
                    $detail->setBillingAddress($billingAddress->getId());
                }
                $detail->setPaymentMethod(
                    $lastSubOrder->getPayment() ?
                        $lastSubOrder->getPayment()->getMethod() : ''
                );
                $detail->setIncrementId($dataObject->getData('hotai_reserved_order_id'));
                $this->parentOrderManagement->assignSubordersToParentOrder(
                    $id,
                    $suborders
                );
                if ($parentOrder->getDesireStatus()) {
                    $detail->setStatus($parentOrder->getDesireStatus());
                }
                $detail->save();
                /**
                 * Reset billing name for virtual order
                 */
                if ($masterQuote && $masterQuote->isVirtual()) {
                    $customerId = $masterQuote->getCustomerId();
                    $customer = $this->customerRepository->getById($customerId);
                    $customerFirstname = $customer->getFirstname();
                    $telephone = $customer->getCustomAttribute('phone_number')->getValue();
                    $sqlParentBillingName = 'update sales_parent_order_address set firstname="' . $customerFirstname . '", lastname="Hotai", telephone="' . $telephone . '" where parent_order_id=' . $dataObject->getId() . ' and address_type="billing"';
                    $this->parentOrderFactory->create()
                        ->getResourceCollection()->getConnection()
                        ->query($sqlParentBillingName);

                }
                //app/code/Branch8/MarketPlaceParentOrder/Model/Services/ProcessParentOrdersWhenCheckoutWithFullPoint.php make $order->getSubOrders() return empty array
                //so i use $this->parentOrderFactory->create()->load($orderId) instead of $this->orderRepository->get($orderId);
                //TODO: in future, we should use $this->orderRepository->get($orderId) instead of $this->parentOrderFactory->create()->load($orderId)
                $parentOrder = $this->parentOrderFactory->create()->load((int)$dataObject->getId());
                $parentOrder->setDetail($detail);
                // send mail
                if ($sendMail && $parentOrder->getDetail()->getStatus() == \Magento\Sales\Model\Order::STATE_PROCESSING) {
                    $this->parentOrderManagement->sendConfirmationEmail($parentOrder);
                }
                /*
                * TODO process by cron
                *
                */
                if ($processFullpoint) {
                    $this->processParentOrderWithFullpoint->execute($parentOrder);
                }
            }
        } catch (\Throwable $exception) {
            $this->log->logException('LinkParentOrderWithChild', $exception);
            throw $exception;
        }
        return $parentOrder;
    }
}
