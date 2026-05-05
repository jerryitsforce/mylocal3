<?php

/**
 * Copyright ©  All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Branch8\Repayment\Block\Checkout;

use \Magento\Framework\Registry;
use \Magento\Framework\View\Element\Template\Context;
use \Branch8\MarketPlaceParentOrder\Api\Data\ParentOrderInterface;
use \Branch8\MarketPlaceParentOrder\Model\ResourceModel\ParentOrder\CollectionFactory as ParentOrderCollection;
use \Branch8\Repayment\Helper\Data;
use \Branch8\HotaiPay\Model\Ui\ConfigProvider as HotaiPayConfigProvider;
use \Branch8\Repayment\Model\OrderManagement;
use \Magento\Customer\Model\Session as CustomerSession;
use \Magento\Sales\Model\ResourceModel\Order\Payment\Collection;
use Branch8\Repayment\Helper\Log as RepaymentLog;

class Processor extends \Magento\Framework\View\Element\Template
{
    private $registry;
    protected $orders;
    protected $parentOrderCollection;
    protected $paymentMethodManagement;
    protected $orderPayment;
    private $configData;
    public $hotaiPayConfigProvider;
    protected $orderManagement;
    protected $customerSession;
    protected RepaymentLog $repaymentLog;

    /**
     * @var \Branch8\HotaiPay\Model\CreditCard\Session
     */
    
    public $creditCardSession;

    /**
     * Initialize checkout processor block dependencies.
     *
     * @param Context $context Magento template context.
     * @param Registry $registry Magento registry.
     * @param ParentOrderCollection $parentOrderCollection Parent order collection factory.
     * @param Collection $orderPayment Order payment collection.
     * @param Data $configData Repayment helper.
     * @param HotaiPayConfigProvider $hotaiPayConfigProvider HotaiPay config provider.
     * @param OrderManagement $orderManagement Repayment order management.
     * @param CustomerSession $customerSession Customer session.
     * @param \Branch8\HotaiPay\Model\CreditCard\Session $creditCardSession Credit card session.
     * @param RepaymentLog $repaymentLog Repayment log helper.
     * @param array<string, mixed> $data Block data.
     */
    public function __construct(
        Context $context,
        Registry $registry,
        ParentOrderCollection $parentOrderCollection,
        Collection $orderPayment,
        Data $configData,
        HotaiPayConfigProvider $hotaiPayConfigProvider,
        OrderManagement $orderManagement,
        CustomerSession $customerSession,
        \Branch8\HotaiPay\Model\CreditCard\Session $creditCardSession,
        RepaymentLog $repaymentLog,
        array $data = []
    ) {
        $this->registry = $registry;
        $this->parentOrderCollection = $parentOrderCollection;
        $this->orderPayment = $orderPayment;
        $this->configData = $configData;
        $this->hotaiPayConfigProvider = $hotaiPayConfigProvider;
        $this->orderManagement = $orderManagement;
        $this->customerSession = $customerSession;
        $this->creditCardSession = $creditCardSession;
        $this->repaymentLog = $repaymentLog;
        parent::__construct($context, $data);
    }


    /**
     * Get grand total for one parent order.
     *
     * @param ParentOrderInterface $parentOrder Parent order entity.
     * @return int
     */
    public function getGrandTotal(ParentOrderInterface $parentOrder)
    {
        try {
            $total = $this->orderManagement->getTotalByCode($parentOrder);
            return $total;
        } catch (\Exception $exception) {
            $this->repaymentLog->exception(
                $exception,
                $this->repaymentLog->processorBlockOption(),
                __METHOD__,
                ['parent_order_id' => $parentOrder->getParentId()]
            );
            return 0;
        }
    }

    /**
     * Get point-used total for one parent order.
     *
     * @param ParentOrderInterface $parentOrder Parent order entity.
     * @return int
     */
    public function getPointTotal(ParentOrderInterface $parentOrder)
    {
        try {
            $total = $this->orderManagement->getTotalByCode($parentOrder, 'point_used_total');
            return $total;
        } catch (\Exception $exception) {
            $this->repaymentLog->exception(
                $exception,
                $this->repaymentLog->processorBlockOption(),
                __METHOD__,
                ['parent_order_id' => $parentOrder->getParentId()]
            );
            return 0;
        }
    }

    /**
     * Build repayment reminder message with due time.
     *
     * @param \Magento\Sales\Model\Order $_order Order model.
     * @return \Magento\Framework\Phrase
     */
    public function getRepaymentMessage($_order)
    {
        $createdAt = $this->_localeDate->date($_order->getCreatedAt())->format('Y/m/d');
        // let add +24h to created_at
        $createdAt = $createdAt . ' 23:59';
        return __("請於{$createdAt} 前完成付款，逾期將自動取消訂單");
    }
    
    /**
     * Get available parent orders for repayment page.
     *
     * @return array|\Branch8\MarketPlaceParentOrder\Model\ResourceModel\ParentOrder\Collection
     */
    public function getOrders()
    {
        $this->orders = [];
        if (!$this->orders) {
            $orderCollection = $this->orderManagement->getOrders($this->registry->registry('parent_order_id'));
        }

        if ($orderCollection) {
            foreach($orderCollection as $order) {
                $createdAt = $order->getCreatedAt();
                if (time() - strtotime($createdAt) > 60*60*24) {
                    continue;
                }
                
                $this->orders = $orderCollection;
            }
        }
        return $this->orders;
    }

    /**
     * Get first parent order id from loaded collection.
     *
     * @return int|string|null
     */
    public function getParentOrderId()
    {
        if ($this->orders) {
            foreach ($this->orders as $key => $value) {
                return $key;
            }
        }

        return null;
    }

    
    /**
     * Get available repayment method options from configuration.
     *
     * @return array
     */
    public function getAvailableRepaymentMethod()
    {
        $method = $this->configData->getAvaiableRepaymentMethod();
        if ($method) {
            $methodArray = explode(',', $method);
            $collection = $this->orderPayment;
            $collection->getSelect()->group('method');
            $paymentMethods = [];

            foreach ($collection as $col) {
                if (in_array($col->getMethod(), $methodArray)) {
                    $paymentMethods[$col->getMethod()] = [
                        'value' => $col->getMethod(),
                        'label' => isset($col->getAdditionalInformation()['method_title'])? $col->getAdditionalInformation()['method_title'] : '',
                        'data' => $this->paymentData($col->getMethod())
                    ];
                }
            }

            return $paymentMethods;
        }

        return [];
    }
    
    /**
     * Build payment metadata by method code.
     *
     * @param string $payment Payment method code.
     * @return array
     */
    private function paymentData($payment)
    {
        switch ($payment) {
            case 'hotaipay':
                $configData = $this->hotaiPayConfigProvider->getConfigExcludeExpire();
                if ($configData) {
                    return $configData['payment'][$this->hotaiPayConfigProvider::CODE];
                }
                return [];
            default:
                return [];
        }
    }
    
    /**
     * Get repayment submit URL.
     *
     * @return string
     */
    public function getRepayUrl()
    {
        return $this->getUrl('repayment/checkout/verify');
    }

    /**
     * Get repayment redirect URL.
     *
     * @return string
     */
    public function getRedirectUrl()
    {
        return $this->getUrl('repayment/checkout/processor/order/'.$this->registry->registry('parent_order_id'));
    }

    /**
     * Get URL to add HotaiPay credit card.
     *
     * @return string
     */
    public function getHotaiCreditCardAddUrl()
    {
        return $this->getUrl('hotaipay/creditcard/add');
    }

    /**
     * Get order history URL.
     *
     * @return string
     */
    public function getOrderHistoryUrl()
    {
        return $this->getUrl('sales/parentOrder/history');
    }

    /**
     * Get credit card list error response.
     *
     * @return mixed
     */
    public function getErrorResponse()
    {
        return $this->creditCardSession->getCardListErrorResponse();
    }

    /**
     * Clear credit card session response.
     *
     * @return mixed
     */
    public function unsetResponse()
    {
        return $this->creditCardSession->unsResponse();
    }

    /**
     * Get registered request params.
     *
     * @return mixed
     */
    public function getParams()
    {
        return $this->registry->registry('params');
    }

    /**
     * Get original request params from request object.
     *
     * @return array<string, mixed>
     */
    public function getOriginalParams()
    {
        return $this->getRequest()->getParams(); ;
    }

}
