<?php

namespace Branch8\HotaiCheckoutNumber\Observer;

use Branch8\HotaiCheckoutNumber\Helper\Common as CommonHelper;
use Branch8\HotaiCheckoutNumber\Model\Config\Source\LogOption;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Sales\Model\Order;
use Magento\Sales\Api\OrderRepositoryInterface;

class SetCheckoutNumberForNoMoney implements ObserverInterface
{
    const LOG_FOLDER_NAME = 'HotaiCheckoutNumber/Observer/SetCheckoutNumberForNoMoney';

    private const DEBUG_LOG_OPTION = LogOption::LOG_OBSERVER_SET_CHECKOUT_NUMBER_FOR_NO_MONEY;

    /** @var CommonHelper */
    protected $commonHelper;

    /** @var OrderRepositoryInterface */
    protected $orderRepository;

    public function __construct(
        CommonHelper $commonHelper,
        OrderRepositoryInterface $orderRepository
    ) {
        $this->commonHelper    = $commonHelper;
        $this->orderRepository = $orderRepository;
    }

    public function execute(Observer $observer)
    {
        try {
            $data    = $observer->getEvent()->getData();
            $orderId = $data["orderId"] ?? "";

            /** @var \Magento\Sales\Model\Order $order */
            $order = $this->orderRepository->get($orderId);

            if (!$this->checkGrandTotalCondition($order)) {
                return;
            }

            // if ($this->checkCheckoutNumberAlreadyExist($order)) {
            //     return;
            // }

            $this->setHotaiCheckoutNumberForNoMoneyOrder($order);
        } catch (\Exception $e) {
            $this->commonHelper->writeLogIfEnabled(
                "SetCheckoutNumberForNoMoney observer for order id - {$orderId}, exception: " . $e->getMessage(),
                self::LOG_FOLDER_NAME,
                self::DEBUG_LOG_OPTION
            );
        }
    }

    /**
     * 確認訂單的grand_total欄位是否為空
     *
     * @param Order $order
     * @return boolean
     */
    protected function checkGrandTotalCondition(Order $order): bool
    {
        return $order->getGrandTotal() <= 0;
    }

    /**
     * 確認和泰子訂單編號(hotai_child_order_number)是否已經有值
     * 有的話就不要再寫以免把ecpay_invoice_updated_at更新被其他報表拉到
     * @param Order $order
     * @return boolean
     */
    protected function checkCheckoutNumberAlreadyExist(Order $order): bool
    {
        return !empty($order->getData("hotai_checkout_number"));
    }

    /**
     * 將和泰子訂單編號(hotai_child_order_number)同步到和泰訂單結帳序號(hotai_checkout_number)
     *
     * @param Order $order
     * @return void
     */
    protected function setHotaiCheckoutNumberForNoMoneyOrder(Order $order): void
    {
        $order->setData('hotai_checkout_number', "P" . $order->getData('hotai_child_order_number'));

        $this->orderRepository->save($order);
    }
}
