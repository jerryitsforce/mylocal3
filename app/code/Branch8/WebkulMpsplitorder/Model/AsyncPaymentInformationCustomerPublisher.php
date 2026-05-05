<?php
namespace Branch8\WebkulMpsplitorder\Model;

use Magento\Checkout\Model\PaymentInformationManagement;
use Magento\Quote\Api\Data\PaymentInterface;
use Branch8\WebkulMpsplitorder\Model\ErrorCodeMapping;
use Branch8\WebkulMpsplitorder\Exception\SplitOrderException;

class AsyncPaymentInformationCustomerPublisher{

    protected $errorCodeMapping;

    public function __construct(\Branch8\WebkulMpsplitorder\Model\ErrorCodeMapping $errorCodeMapping){
        $this->errorCodeMapping = $errorCodeMapping;
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
