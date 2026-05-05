<?php
namespace Branch8\GiftToFriend\Plugin\Checkout;

use Magento\Quote\Model\QuoteRepository;

class BillingAddressManagement
{
    protected $quoteRepository;

    protected $giftHelperData;

    public function __construct(
        QuoteRepository $quoteRepository,
        \Branch8\GiftToFriend\Helper\Data $giftHelperData
        )
    {
        $this->quoteRepository = $quoteRepository;
        $this->giftHelperData = $giftHelperData;
    }

    public function beforeAssignBillingAddress(
        \Magento\Quote\Model\BillingAddressManagement $subject,
        \Magento\Quote\Api\Data\AddressInterface $address,
        $quote,
        $useForShipping = false
    ) {
        if(!$this->giftHelperData->isFeatureEnable()){
            return [$address, $quote, $useForShipping];
        }
        if(\Branch8\HotaiCore\Helper\DebugLog::isEnable('Branch8_GiftToFriend', 'quote_set_gift_order')){
            $writer = new \Zend_Log_Writer_Stream(BP .'/var/log/gift_order.log');
            $logger = new \Zend_Log();
            $logger->addWriter($writer);
            $logger->info('Quote: '.$quote->getId());
        }

        $isGiftOrder = 0;
        $giftAddressType = NULL;
        $giftAddressFieldsFilled = NULL;
        if (!$extAttributes = $address->getExtensionAttributes()) {
            $isGiftOrder = 0;
            $giftAddressType = NULL;
            $giftAddressFieldsFilled = NULL;
            if(\Branch8\HotaiCore\Helper\DebugLog::isEnable('Branch8_GiftToFriend', 'quote_set_gift_order')){
                $logger->info('!$extAttributes');
            }
        }else{
            $isGiftOrder = $extAttributes->getIsGiftOrder();
            $giftAddressType = $extAttributes->getGiftAddressType();
            $giftAddressFieldsFilled = $extAttributes->getGiftAddressFieldsFilled();
            if(\Branch8\HotaiCore\Helper\DebugLog::isEnable('Branch8_GiftToFriend', 'quote_set_gift_order')){
                $logger->info('Has ext attribute');
            }
        }
        
        $quote->setIsGiftOrder((int)$isGiftOrder);
        $quote->setGiftAddressType((int)$giftAddressType);
        $quote->setGiftAddressFieldsFilled($giftAddressFieldsFilled);
        
        if(\Branch8\HotaiCore\Helper\DebugLog::isEnable('Branch8_GiftToFriend', 'quote_set_gift_order')){
            $logger->info(print_r(['is_gift' => (int)$isGiftOrder, 'addr_type' => (int)$giftAddressType, 'fields' => $giftAddressFieldsFilled], true));
            $logger->info(isset($_SERVER['HTTP_USER_AGENT']) ? $_SERVER['HTTP_USER_AGENT'] : '');
        }
        return [$address, $quote, $useForShipping];
    }
}