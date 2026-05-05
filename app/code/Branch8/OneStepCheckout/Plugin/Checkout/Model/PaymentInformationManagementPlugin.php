<?php

namespace Branch8\OneStepCheckout\Plugin\Checkout\Model;

use Magento\Checkout\Model\PaymentInformationManagement;
use Magento\Quote\Api\Data\PaymentInterface;
use Branch8\WebkulMpsplitorder\Model\ErrorCodeMapping;
use Branch8\WebkulMpsplitorder\Exception\SplitOrderException;

class PaymentInformationManagementPlugin
{

    protected $errorCodeMapping;

    protected $quoteRepository;

    public function __construct(
        \Magento\Quote\Api\CartRepositoryInterface $quoteRepository,
        \Branch8\WebkulMpsplitorder\Model\ErrorCodeMapping $errorCodeMapping
    ){
        $this->quoteRepository = $quoteRepository;
        $this->errorCodeMapping = $errorCodeMapping;
    }

    public function beforeSavePaymentInformationAndPlaceOrder(
        PaymentInformationManagement $subject,
                                     $cartId,
        PaymentInterface $paymentMethod,
        \Magento\Quote\Api\Data\AddressInterface $billingAddress = null
    ){
        if ($extensionAttributes = $paymentMethod->getExtensionAttributes()) {
            $referrerCode = $extensionAttributes->getReferrerCode();
            $orderNote = $extensionAttributes->getOrderNote();

            $quote = $this->quoteRepository->getActive($cartId);
            $quote->setData('referrer_code', $referrerCode);
            $quote->setData('order_note', $orderNote);
            //$this->quoteRepository->save($quote);
        }
        return [$cartId, $paymentMethod, $billingAddress];
    }

    public function aroundSavePaymentInformationAndPlaceOrder($subject, $proceed, $cartId, 
        PaymentInterface $paymentMethod,
        \Magento\Quote\Api\Data\AddressInterface $billingAddress = null
        ){
        try{
            return $proceed($cartId, $paymentMethod, $billingAddress);            
        }catch(\Exception $exception){
            $message = $exception->getMessage();
            if(substr($message, 0, 3) != 'SPE'){
                $message = $this->errorCodeMapping->getErrorMessage(ErrorCodeMapping::ERROR_CODE_SPE02);
                throw new SplitOrderException(__($message));
            }else{
                throw $exception;
            }
        }
    }
}
