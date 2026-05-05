<?php

namespace Branch8\PointMoneyCollect\Controller\Checkout;

use Magento\Framework\App\Action\Action;
use Magento\Checkout\Model\Session as CheckoutSession;
use Magento\Framework\Controller\Result\JsonFactory;

class ValidatePoint extends Action{
    /**
     * @var \Branch8\PointMoneyCollect\Helper\Data
     */
    protected $pointHelperData;
    /**
     * @var CheckoutSession
     */
    protected $checkoutSession;
    /**
     * @var JsonFactory
     */
    protected $resultJsonFactory;

    /**
     * @param \Magento\Framework\App\Action\Context $context
     * @param \Branch8\PointMoneyCollect\Helper\Data $pointHelperData
     * @param CheckoutSession $checkoutSession
     * @param JsonFactory $resultJsonFactory
     */
    public function __construct(
        \Magento\Framework\App\Action\Context $context,
        \Branch8\PointMoneyCollect\Helper\Data $pointHelperData,
        CheckoutSession $checkoutSession,
        JsonFactory $resultJsonFactory
    ){
        parent::__construct($context);
        $this->pointHelperData = $pointHelperData;
        $this->checkoutSession = $checkoutSession;
        $this->resultJsonFactory = $resultJsonFactory;
    }

    public function execute() {
        $result = $this->resultJsonFactory->create();
        $data = ['success' => true];
        $quote = $this->checkoutSession->getQuote();
        if(!$this->pointHelperData->isValidPointApply($quote)){
            $data['success'] = false;
        }
        $result->setData($data);
        return $result;
    }
}