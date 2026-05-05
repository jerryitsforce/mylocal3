<?php

namespace Branch8\HotaiCheckoutNumber\Plugin;

use Branch8\HotaiCheckoutNumber\Helper\Common as CommonHelper;
use Branch8\HotaiCheckoutNumber\Model\Config\Source\LogOption;
use Ecpay\General\Helper\Services\Config\InvoiceService;
use Magento\Sales\Api\OrderRepositoryInterface as OrderRepository;

class SetCheckoutNumberAfterEcpayInvoice
{
    const LOG_FOLDER_NAME = 'HotaiCheckoutNumber/Plugin/SetCheckoutNumberAfterEcpayInvoice';

    private const DEBUG_LOG_OPTION = LogOption::LOG_PLUGIN_SET_CHECKOUT_NUMBER_AFTER_ECPAY_INVOICE;

    /** @var OrderRepository */
    protected $orderRepository;

    /** @var CommonHelper */
    protected $commonHelper;

    public function __construct(
        OrderRepository $orderRepository,
        CommonHelper $commonHelper
    ) {
        $this->orderRepository = $orderRepository;
        $this->commonHelper    = $commonHelper;
    }

    public function afterInvoiceIssue(InvoiceService $subject, $result, string $orderId)
    {
        try {
            /** @var \Magento\Sales\Model\Order $order */
            $order = $this->orderRepository->get($orderId);

            if ($this->checkHandleCondition($order)) {
                $this->saveHotaiCheckoutNumberToOrder($order);
            }

            return $result;
        } catch (\Exception $e) {
            $this->commonHelper->writeLogIfEnabled(
                "SetCheckoutNumberAfterEcpayInvoice plugin for order id - {$order->getId()}, exception: " . $e->getMessage(),
                self::LOG_FOLDER_NAME,
                self::DEBUG_LOG_OPTION
            );
        }
    }

    /**
     * 判斷Ecpay開立發票的目標sales_order是否有建立發票號碼(ecpay_invoice_number)可以同步到和泰訂單結帳序號(hotai_checkout_number)欄位
     * @param \Magento\Sales\Model\Order $order
     * @return boolean
     */
    protected function checkHandleCondition(\Magento\Sales\Model\Order $order): bool
    {
        $ecpayInvoiceNumber  = $order->getData('ecpay_invoice_number');

        if (empty($ecpayInvoiceNumber)) {
            return false;
        }

        return true;
    }

    /**
     * 將Ecpay的發票號碼(ecpay_invoice_number)同步到和泰訂單結帳序號(hotai_checkout_number)欄位
     * @param \Magento\Sales\Model\Order $order
     * @return void
     */
    protected function saveHotaiCheckoutNumberToOrder(\Magento\Sales\Model\Order $order): void
    {
        $order->setData('hotai_checkout_number', $order->getData('ecpay_invoice_number'));
        $this->orderRepository->save($order);
    }
}
