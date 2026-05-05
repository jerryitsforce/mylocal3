<?php

namespace Branch8\OneStepCheckout\Controller\Ajax;

class SaveCustomData extends \Magento\Framework\App\Action\Action
{

    /**
     * @var \Magento\Checkout\Model\Session
     */
    protected $checkoutSession;
    
    /**
     * @var \Magento\Quote\Model\QuoteIdMaskFactory
     */
    protected $quoteIdMaskFactory;

    /**
     * @var \Magento\Quote\Api\CartRepositoryInterface
     */
    protected $quoteRepository;

    /**
     * @param \Magento\Framework\App\Action\Context $context
     * @param \Magento\Checkout\Model\Session $checkoutSession
     * @param \Magento\Quote\Model\QuoteIdMaskFactory $quoteIdMaskFactory
     * @param \Magento\Quote\Api\CartRepositoryInterface $quoteRepository
     */
    public function __construct(
        \Magento\Framework\App\Action\Context $context,
        \Magento\Checkout\Model\Session $checkoutSession,
        \Magento\Quote\Model\QuoteIdMaskFactory $quoteIdMaskFactory,
        \Magento\Quote\Api\CartRepositoryInterface $quoteRepository
    ) {
        parent::__construct($context);
        $this->checkoutSession = $checkoutSession;
        $this->quoteRepository = $quoteRepository;
        $this->quoteIdMaskFactory = $quoteIdMaskFactory;
    }

    /**
     * @return \Magento\Framework\Controller\Result\Raw
     */
    public function execute()
    {
        $post = $this->getRequest()->getPostValue();
        if ($post) {
            $cartId = $post['cartId'];
            $referrerCode = $post['referrer_code'];
            $orderNote = $post['order_note'];
            $login = $post['is_customer'];

            if ($login === 'false') {
                $cartId = $this->quoteIdMaskFactory->create()->load($cartId, 'masked_id')->getQuoteId();
            }

            //save to quote will lose data because extension WebkulMpsplitorder will delete current quote when split order
            $quote = $this->quoteRepository->getActive($cartId);
            if ($quote->getItemsCount()) {
                $quote->setData('referrer_code', $referrerCode);
                $quote->setData('order_note', $orderNote);
                $this->quoteRepository->save($quote);
            }
            
            //save data to checkout session
            $this->checkoutSession->setData('referrer_code', $referrerCode);
            $this->checkoutSession->setData('order_note', $orderNote);
        }
    }
}