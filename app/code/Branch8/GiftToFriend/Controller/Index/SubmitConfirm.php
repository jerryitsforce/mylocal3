<?php
namespace Branch8\GiftToFriend\Controller\Index;
use Branch8\HotaiCore\Model\Order\Status;

use Magento\Framework\Event\ManagerInterface;

class SubmitConfirm extends \Magento\Framework\App\Action\Action
{
    protected $timezone;

    protected $customerSession;

    protected $resultJsonFactory;

    protected $scopeConfig;

    protected $giftHelperConfirm;

    protected $transaction;

    protected $parentOrderDetailCollectionFactory;

    protected $parentOrderFactory;

    protected $giftBoxHelper;

    protected $eventManager;

    protected $rmaDetailCollectionFactory;

    protected $hotaiAuthService;
    /**
     * @param \Magento\Framework\App\Action\Context $context
     */
    public function __construct(
       \Magento\Framework\App\Action\Context $context,
       \Magento\Framework\Stdlib\DateTime\TimezoneInterface $timezone,
       \Magento\Customer\Model\Session $customerSession,
       \Magento\Framework\Controller\Result\JsonFactory $resultJsonFactory,
       \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
       \Branch8\GiftToFriend\Helper\Confirm $giftHelperConfirm,
       \Branch8\GiftToFriend\Helper\GiftBox $giftBoxHelper,
       \Magento\Framework\DB\Transaction $transaction,
       \Branch8\MarketPlaceParentOrder\Model\ParentOrderFactory $parentOrderFactory,
       \Branch8\MarketPlaceParentOrder\Model\ResourceModel\ParentOrderDetail\CollectionFactory $parentOrderDetailCollectionFactory,
       ManagerInterface $eventManager,
       \Webkul\MpRmaSystem\Model\ResourceModel\Details\CollectionFactory $rmaDetailCollectionFactory,
       \Branch8\HotaiAuth\Service\HotaiAuthService $hotaiAuthService
    )
    {
        $this->timezone = $timezone;
        $this->customerSession = $customerSession;
        parent::__construct($context);
        $this->resultJsonFactory = $resultJsonFactory;
        $this->scopeConfig = $scopeConfig;
        $this->giftHelperConfirm = $giftHelperConfirm;
        $this->transaction = $transaction;
        $this->parentOrderDetailCollectionFactory = $parentOrderDetailCollectionFactory;
        $this->giftBoxHelper = $giftBoxHelper;
        $this->parentOrderFactory = $parentOrderFactory;
        $this->eventManager = $eventManager;
        $this->rmaDetailCollectionFactory = $rmaDetailCollectionFactory;
        $this->hotaiAuthService = $hotaiAuthService;
    }

    public function execute()
    {
        $request = $this->getRequest();
        $result = $this->resultJsonFactory->create();


        if(!$request->isPost()){
            $returnData['success'] = false;
            $returnData['message'] = __('Invalid request');
            return $result->setData($returnData);
        }

        $code = $request->getPost('sms_code');
        $smsSentData = $this->customerSession->getConfirmCode();
        if(!$smsSentData){
            $returnData['success'] = false;
            $returnData['message'] = __('Invalid request');
            return $result->setData($returnData);
        }

        $curentTime = $this->timezone->date()->format('Y-m-d H:i:s');
        $smsTime = $smsSentData['created_at'];
        $codeLifetime = $this->scopeConfig->getValue('gift_order/general/sms_period');
        if(strtotime($curentTime) - strtotime($smsTime) > $codeLifetime*60){
            $returnData['success'] = false;
            $returnData['message'] = __('The verification code has expired.');
            return $result->setData($returnData);
        }

        $smsSent = $smsSentData['smsCode'];
        if($smsSent != $code){
            $returnData['success'] = false;
            $returnData['message'] = __('The verification code is wrong, please re-enter!');
            return $result->setData($returnData);
        }

        $orderCodeSubmmited = $request->getPost('order_code');
        $parentOrderDetail = $this->parentOrderDetailCollectionFactory->create()
            ->addFieldToFilter('gift_code',  $orderCodeSubmmited)
            ->getFirstItem();
        if(!$parentOrderDetail->getId()){
            $returnData['success'] = false;
            $returnData['message'] = __("No order found with the provided gift code");
            return $result->setData($returnData);
        }

        if($parentOrderDetail->getStatus() == \Branch8\HotaiCore\Model\Order\Status::STATUS_GIFT_INFO_COMPLETE){
            $returnData['success'] = false;
            $returnData['message'] = __("The order has been confirmed.");
            return $result->setData($returnData);
        }

        $parentOrderId = $parentOrderDetail->getParentId();
        $parentOrder = $this->parentOrderFactory->create()->load($parentOrderId);
        if($parentOrderDetail->getStatus() == Status::STATUS_GIFT_INFO_PENDING){
            $isValidStatus = true;
        }else if($parentOrderDetail->getStatus() == \Branch8\HotaiCore\Model\Order\Status::STATUS_COMPLETE){
            $isValidStatus = true;
            $subOrderIds = $parentOrder->getResource()->getSubOrders((int)$parentOrder->getEntityId());
            foreach($subOrderIds as $_order){
                $rmaCol = $this->rmaDetailCollectionFactory->create()
                    ->addFieldToSelect('status')
                    ->addFieldToFilter('order_id', $_order);
                $allRmas = $rmaCol->getItems();
                foreach($allRmas as $_rma){
                    if($this->giftHelperConfirm->isInvalidRmaStatus($_rma->getStatus())){
                        $isValidStatus = false;

                    }
                }
            }
        }else{
            $isValidStatus = false;
        }
        if(!$isValidStatus){
            $returnData['success'] = false;
            $returnData['message'] = __("No order found with the provided gift code");
            return $result->setData($returnData);
        }

        /**
         * Save data
         */
        try{
            $telephone = $request->getPost('phone_number');
            $firstName = $request->getPost('name');
            $street = $request->getPost('street');
            $city = $request->getPost('city');
            $region = $request->getPost('region');
            $regionId = $request->getPost('region_id');

            $addressData = [
                'telephone' => $telephone,
                'firstName' => $firstName,
                'street' => $street,
                'city' => $city ,
                'region' => $region,
                'regionId' => $regionId
            ];

            $memberSeq = '';
            if(trim($telephone) != ''){
                /** Get Member seq for current telephone */
                $memberSeq = $this->hotaiAuthService->getHotaiOneIdByPhoneNumber($telephone);
            }

            $parentOrderDetail = $this->parentOrderDetailCollectionFactory->create()
                ->addFieldToFilter('gift_code',  $orderCodeSubmmited)
                ->getFirstItem();
            if(!$parentOrderDetail->getId()){
                $returnData['success'] = false;
                $returnData['reload'] = true;
                $returnData['message'] = __("The Order does not exist.");
                return $result->setData($returnData);
            }

            if($parentOrderDetail->getIsGiftConfirmed()){
                $returnData['success'] = false;
                $returnData['reload'] = true;
                $returnData['message'] = __("The order has been confirmed.");
                return $result->setData($returnData);
            }

            $giftConfirmedAt = $this->timezone->convertConfigTimeToUtc($this->timezone->date());
            /** Update data for parent order */
            $parentOrderDetail->setStatus(\Branch8\HotaiCore\Model\Order\Status::STATUS_GIFT_INFO_COMPLETE);
            $parentOrderDetail->setIsGiftConfirmed(1);
            $parentOrderDetail->setGiftConfirmedAt($giftConfirmedAt);
            $parentOrderDetail->setRecipientName($addressData['firstName']);
            $parentOrderDetail->setRecipientTelephone($addressData['telephone']);
            $parentOrderDetail->setGiftLastAccessTime($giftConfirmedAt);
            $parentOrderDetail->setRecipientMemberSeq($memberSeq);
            $parentOrderDetail->save();
            $this->transaction->addObject($parentOrderDetail);
            /** Update Parent Address */

            $parentShippingAddress = $parentOrder->getShippingAddress();
            if($parentShippingAddress && $parentShippingAddress->getId()){
                $parentShippingAddress = $this->giftHelperConfirm->updateAddress($parentShippingAddress, $addressData);
                $this->transaction->addObject($parentShippingAddress);

                $parentBillingAddress = $parentOrder->getBillingAddress();
                $parentBillingAddress = $this->giftHelperConfirm->updateAddress($parentBillingAddress, $addressData);
                $parentBillingAddress->setPostcode($parentShippingAddress->getPostcode());
                $this->transaction->addObject($parentBillingAddress);
            }else{
                $parentBillingAddress = $parentOrder->getBillingAddress();
                $parentBillingAddress->setFirstname($addressData['firstName']);
                $parentBillingAddress->setTelephone($addressData['telephone']);
                $this->transaction->addObject($parentBillingAddress);
            }

            $subOrders = $parentOrder->getSubOrders();

            /** Update address for parent order */
            foreach($subOrders as $_order){
                /** Update sub order address */
                $subShippingAddress = $_order->getShippingAddress();
                if($subShippingAddress && $subShippingAddress->getId()){
                    $subShippingAddress = $this->giftHelperConfirm->updateAddress($subShippingAddress, $addressData);
                    $this->transaction->addObject($subShippingAddress);

                    $subBillingAddress = $_order->getBillingAddress();
                    $subBillingAddress = $this->giftHelperConfirm->updateAddress($subBillingAddress, $addressData);
                    $subBillingAddress->setPostcode($subShippingAddress->getPostcode());
                    $this->transaction->addObject($subBillingAddress);
                }else{
                    $subBillingAddress = $_order->getBillingAddress();
                    $subBillingAddress->setFirstname($addressData['firstName']);
                    $this->transaction->addObject($subBillingAddress);
                }

                /** Update confirmed order*/
                $_order->setIsGiftConfirmed(1);
                $_order->setRecipientName($addressData['firstName']);
                $_order->setRecipientTelephone($addressData['telephone']);
                $_order->setRecipientMemberSeq($memberSeq);

                /** Change order status to confirmed */
                $_order->setStatus(\Branch8\HotaiCore\Model\Order\Status::STATUS_GIFT_INFO_COMPLETE);
                $_order->setGiftConfirmedAt($giftConfirmedAt);
                $statusComment = __('Recipient confirmed gift order.');
                $_order->addCommentToStatusHistory($statusComment);
                $parentOrder->addComment($statusComment, \Branch8\HotaiCore\Model\Order\Status::STATUS_GIFT_INFO_COMPLETE);

                $this->transaction->addObject($_order);
                $this->transaction->addObject($parentOrder);

            }
            if(!$this->transaction->save()){
                throw new \Exception('Order confirmation error, please try again.');
            }
            try{
                $customerId = $this->customerSession->getCustomerId();
                /** Assign customer to ticket */
                foreach($subOrders as $order){
                    $this->eventManager->dispatch(\Branch8\HotaiPoint\Helper\Common::EVENT_DEDUCTION_COMMIT_FLOW_END, [
                        "orderId" => $order->getId(),
                        "quoteId" => $order->getQuoteId(),
                        "custom_owner" => [
                            "telephone" => $telephone,
                            "customer_id" => $customerId,
                            'member_seq' => $memberSeq
                        ]
                    ]);

                    /** Update order item status after save comment */
                    $this->_eventManager->dispatch('update_order_item_status_after_save_comment',
                    ['order' => $order]);
                }

                $this->giftBoxHelper->setLastAccessTime($telephone);
            }catch(\Exception $e){
                $returnData['success'] = false;
                $returnData['message'] = __('Order confirmation error - assign customer to ticket, please try again.');
                return $result->setData($returnData);

            }
            /** Set Guest session */
            if(!$this->customerSession->isLoggedIn()){
                $this->giftBoxHelper->setGuestSession($telephone);
            }


            $returnData['success'] = true;
            return $result->setData($returnData);

        }catch(\Exception $e){
            $returnData['success'] = false;
            $returnData['reload'] = true;
            $returnData['message'] = __('Order confirmation error, please try again.');
            return $result->setData($returnData);
        }
    }
}
