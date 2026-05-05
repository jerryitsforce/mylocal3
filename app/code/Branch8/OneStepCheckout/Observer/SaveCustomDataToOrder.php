<?php

namespace  Branch8\OneStepCheckout\Observer;

class SaveCustomDataToOrder implements \Magento\Framework\Event\ObserverInterface
{

    /**
     * @var \Magento\Checkout\Model\Session
     */
    protected $checkoutSession;

    /**
     * @var Magento\Framework\DataObject\Copy
     */
    protected $objectCopyService;

    /**
     * @param \Magento\Checkout\Model\Session $checkoutSession
     * @param \Magento\Framework\DataObject\Copy $objectCopyService
     */
    public function __construct(
        \Magento\Checkout\Model\Session $checkoutSession,
        \Magento\Framework\DataObject\Copy $objectCopyService
    ) {
        $this->checkoutSession = $checkoutSession;
        $this->objectCopyService = $objectCopyService;
    }

    /**
     * List of attributes that should be added to an order.
     * @var array
     */
    private $attributes = [
        'referrer_code',
        'order_note'
    ];

    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        $event = $observer->getEvent();
        $quote = $event->getData('quote');
        $order = $event->getData('order');

        //copy data from checkout session to child quote ( master quote was delete by module WebkulMpsplitorder ) and order
        foreach ($this->attributes as $attribute) {
            $order->setData($attribute, $quote->getData($attribute));
        }

        //copy data to sale_order
        $this->objectCopyService->copyFieldsetToTarget(
            'sales_convert_quote',
            'to_order',
            $quote,
            $order
        );

        return $this;
    }
}