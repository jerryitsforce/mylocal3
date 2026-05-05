<?php
namespace Branch8\GiftToFriend\Controller\Index;

use Branch8\MarketPlaceParentOrder\Model\ResourceModel\ParentOrderDetail\CollectionFactory;
use Magento\Framework\Registry;
use Branch8\MarketPlaceParentOrderFrontendUi\Controller\ParentOrder\AbstractController\OrderViewAuthorizationInterface;
use Branch8\MarketPlaceParentOrder\Model\ParentOrderRepository;
use Branch8\HotaiCore\Model\Order\Status;

class Index extends \Magento\Framework\App\Action\Action
{
    /**
     * @var \Magento\Framework\View\Result\PageFactory
     */
    protected $_pageFactory;

    protected $parentOrderCollectionFactory;

    protected $registry;

    protected $orderAuthorization;

    protected $detailFactory;

    protected $timezone;

    protected $giftHelperConfirm;

    /** @var ParentOrderRepository $parentOrderRepository */
    protected $parentOrderRepository;

    protected $customerSession;

    protected $rmaDetailCollectionFactory;

    /**
     * @param \Magento\Framework\App\Action\Context $context
     */
    public function __construct(
       \Magento\Framework\App\Action\Context $context,
       \Magento\Framework\View\Result\PageFactory $pageFactory,
       CollectionFactory $parentOrderCollectionFactory,
       Registry $registry,
       \Magento\Framework\Stdlib\DateTime\TimezoneInterface $timezone,
       \Branch8\GiftToFriend\Helper\Confirm $giftHelperConfirm,
        ParentOrderRepository $parentOrderRepository,
        \Magento\Customer\Model\Session $customerSession,
        \Webkul\MpRmaSystem\Model\ResourceModel\Details\CollectionFactory $rmaDetailCollectionFactory

    )
    {
        $this->_pageFactory = $pageFactory;
        parent::__construct($context);
        $this->parentOrderCollectionFactory = $parentOrderCollectionFactory;
        $this->registry = $registry;
        $this->timezone = $timezone;
        $this->giftHelperConfirm = $giftHelperConfirm;
        $this->parentOrderRepository = $parentOrderRepository;
        $this->customerSession = $customerSession;
        $this->rmaDetailCollectionFactory = $rmaDetailCollectionFactory;
    }

    public function execute()
    {
        $giftCode = $this->getRequest()->getParam('code', '');
        if((string)$giftCode == ''){
            $this->messageManager->addErrorMessage(__('Gift code is required.'));
            return $this->_redirect('/')->sendResponse();
        }

        $order = $this->parentOrderCollectionFactory->create()
            ->addFieldToFilter('gift_code', $giftCode)
            ->getFirstItem();
            
        if(!$order->getId()){
            $this->messageManager->addErrorMessage(__('No order found with the provided gift code'));
            return $this->_redirect('/')->sendResponse();
        }

        if($order->getIsGiftConfirmed()){
            $this->messageManager->addErrorMessage(__('The order has been confirmed.'));
            if($this->customerSession->isLoggedIn()){
                return $this->_redirect('gift-order/giftbox/listing')->sendResponse();
            }else{
                $guestSession = $this->customerSession->getGuestGiftBoxSession();
                $currentTime = $this->timezone->date()->format('Y-m-d H:i:s');
                $sessionEndTime = isset($guestSession['created_at']) ? $guestSession['created_at'] : '';
                if($guestSession && !empty($guestSession['created_at']) && strtotime($currentTime) <= strtotime($sessionEndTime)){
                    return $this->_redirect('gift-order/giftbox/listing')->sendResponse();
                }else{
                    return $this->_redirect('gift-order/giftbox/giftForm')->sendResponse();
                }
            }
        }
        
        $parentOrder = $this->parentOrderRepository->getByIncrementId($order->getIncrementId());
        if($order->getStatus() == Status::STATUS_GIFT_INFO_PENDING){
            $isValidStatus = true;
            // $this->messageManager->addErrorMessage(__('No order found with the provided gift code'));
            // return $this->_redirect('/')->sendResponse();
        }else if($order->getStatus() == Status::STATUS_COMPLETE){
            $isValidStatus = true;
            
            $subOrders = $parentOrder->getResource()->getSubOrders((int)$parentOrder->getEntityId());
            foreach($subOrders as $_order){
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
            $this->messageManager->addErrorMessage(__('No order found with the provided gift code'));
            return $this->_redirect('/')->sendResponse();
        }

        $this->registry->register('gift_parent_order_detail', $order);
        $this->registry->register('gift_parent_order', $parentOrder);
        $resultPage = $this->_pageFactory->create();
        $resultPage->getConfig()->getTitle()->set(__('接收禮物'));
        
        return $resultPage;
    }
}
