<?php
namespace Branch8\GiftToFriend\Observer;

use Exception;

class GiftOrderObserver implements \Magento\Framework\Event\ObserverInterface
{
    protected $giftOrderConfimHelper;

    protected $giftHelperData;

    protected $_conn;

    protected $customerSession;

    protected $parentOrderFactory;

    protected $timezone;

    protected $scopeConfig;

    protected $publisher;

    public function __construct(
        \Branch8\GiftToFriend\Helper\Confirm $giftOrderConfimHelper,
        \Branch8\GiftToFriend\Helper\Data $giftHelperData,
        \Magento\Framework\App\ResourceConnection $resourceConnection,
        \Magento\Customer\Model\Session $customerSession,
        \Branch8\MarketPlaceParentOrder\Model\ParentOrderFactory $parentOrderFactory,
        \Magento\Framework\Stdlib\DateTime\TimezoneInterface $timezone,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Magento\Framework\MessageQueue\PublisherInterface $publisher
    )
    {
        $this->giftOrderConfimHelper = $giftOrderConfimHelper;
        $this->giftHelperData = $giftHelperData;
        $this->_conn = $resourceConnection->getConnection();
        $this->customerSession = $customerSession;
        $this->parentOrderFactory = $parentOrderFactory;
        $this->timezone = $timezone;
        $this->scopeConfig = $scopeConfig;
        $this->publisher = $publisher;
    }

    public function execute(\Magento\Framework\Event\Observer $observer)
    {

        if(!$this->giftHelperData->isFeatureEnable()){
            return;
        }
        $parentOrder = $observer->getData('data_object');
        $parentOrderId = $parentOrder->getId();
        $b8ParentOrder = $this->parentOrderFactory->create()->load($parentOrderId);
        $parentQuote = $observer->getData('quote');
        if(!$parentQuote->getData('is_gift_order')){
            return;
        }
        try{
            $giftAddressType = $parentQuote->getData('gift_address_type');
            $recipientAddressName = '';
            $recipientAddressPhone = '';
            if($giftAddressType == \Branch8\GiftToFriend\Model\Config\Source\AddressType::BUYER_INPUT_ADDRESS){
                $shippingAddress = $b8ParentOrder->getShippingAddress();
                if($shippingAddress && $shippingAddress->getId()){
                    $recipientAddressName = $shippingAddress->getFirstname();
                    $recipientAddressPhone = $shippingAddress->getTelephone();
                }else{
                    $billingAddress = $b8ParentOrder->getBillingAddress();
                    $recipientAddressName = $billingAddress->getFirstname();
                    $recipientAddressPhone = $billingAddress->getTelephone();
                }
            }
            $parentDetail = $b8ParentOrder->getDetail();
            $parentCreated = $parentDetail->getCreatedAt();
            $giftExpiredAt = $this->giftOrderConfimHelper->getExpiredDate($parentCreated, 'Y-m-d H:i:s');

            $subOrderIds = $observer->getData('sub_order_ids');
            /**
             * Save gift order infor
             */
            $giftCode = $this->generateRandomString(10);
            $salesPresentativeRecipientInfo = $this->customerSession->getGiftRecipientInfor();
            if($salesPresentativeRecipientInfo){
                $salesPresentativeInfor = json_encode([
                    'subIds' => $subOrderIds,
                    'appId' => $this->scopeConfig->getValue(\Branch8\HotaiAuth\Helper\HotaiScopeConfig::HOTAI_AUTH_CONFIG_PATH_APP_ID),
                    'recipientInfor' => $salesPresentativeRecipientInfo
                ]);
            }else{
                $salesPresentativeInfor = NULL;
            }
            
            $giftAddressFieldsFilled = $parentQuote->getData('gift_address_fields_filled');
            $parentOrderDataUpdate = [
                'is_gift_order' => 1,
                'gift_code' => $giftCode,
                'is_gift_confirmed' => 0,
                'gift_address_type' => $giftAddressType,
                'recipient_name' => $recipientAddressName,
                'recipient_telephone' => $recipientAddressPhone,
                'gift_expired_at' => $giftExpiredAt,
                'sales_presentative_infor' => $salesPresentativeInfor,
                'gift_address_fields_filled' => $giftAddressFieldsFilled
            ];
            /** 
             * These field is separated, so using sql to update. No need to check other core business
             * Using SQL to update main table, so using sql to update if the parent order synced to grid
             * If the grid row does not exist, skip
             */
            $this->_conn->update('sales_parent_order_detail', $parentOrderDataUpdate, 'parent_id = '.$parentOrder->getId());
            unset($parentOrderDataUpdate['gift_expired_at']);
            unset($parentOrderDataUpdate['sales_presentative_infor']);
            unset($parentOrderDataUpdate['gift_address_fields_filled']);
            $this->_conn->update('sales_parent_order_grid', $parentOrderDataUpdate, 'entity_id = '.$parentOrder->getId());

            
            $this->_conn->update('sales_order', [
                'is_gift_order' => 1,
                'is_gift_confirmed' => 0,
                'gift_address_type' => $giftAddressType,
                'recipient_name' => $recipientAddressName,
                'recipient_telephone' => $recipientAddressPhone,
                'gift_expired_at' => $giftExpiredAt
            ], 'entity_id in('.implode(',', $subOrderIds).')');

            $this->_conn->update('sales_order_grid', [
                'is_gift_order' => 1,
                'is_gift_confirmed' => 0,
                'gift_address_type' => $giftAddressType,
                'recipient_name' => $recipientAddressName,
                'recipient_telephone' => $recipientAddressPhone
            ], 'entity_id in('.implode(',', $subOrderIds).')');

            /** Push at checkout process */
            // if($salesPresentativeRecipientInfo){
            //     $this->publisher->publish(
            //         'gift.order.push.sales_presentative',
            //         json_encode([
            //             'parent_order_id' => $parentOrder->getId(), 
            //             'order_status' => 'gift_info_pending', 
            //             'rma_status' => '',
            //             'time' => $this->timezone->convertConfigTimeToUtc($this->timezone->date()),
            //             'changeType' => 'order_status'
            //         ])
            //     );
            // }

            /** Clear Sales presentative addr */
            $this->customerSession->unsGiftRecipientInfor();
            
        }catch(\Exception $e){

            // throw new Exception(__($e->getMessage()));

        }
    }

    protected function generateRandomString($length = 6) {
        $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $randomString = substr(str_shuffle($characters), 0, $length);
     
        return $randomString;
    }
}