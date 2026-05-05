<?php
namespace Branch8\GiftToFriend\Controller\GiftBox;

class Listing extends \Magento\Framework\App\Action\Action
{
    /**
     * @var \Magento\Framework\View\Result\PageFactory
     */
    protected $_pageFactory;

    protected $timezone;

    protected $customerSession;

    protected $scopeConfig;

    protected $giftBoxHelper;

    public function __construct(
       \Magento\Framework\App\Action\Context $context,
       \Magento\Framework\View\Result\PageFactory $pageFactory,
       \Magento\Framework\Stdlib\DateTime\TimezoneInterface $timezone,
       \Magento\Customer\Model\Session $customerSession,
       \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
       \Branch8\GiftToFriend\Helper\GiftBox $giftBoxHelper
    )
    {
        $this->_pageFactory = $pageFactory;
        parent::__construct($context);
        $this->timezone = $timezone;
        $this->customerSession = $customerSession;
        $this->scopeConfig = $scopeConfig;
        $this->giftBoxHelper = $giftBoxHelper;
    }
    /**
     * View page action
     *
     * @return \Magento\Framework\Controller\ResultInterface
     */
    public function execute()
    {
        if($this->customerSession->isLoggedIn()){

        } else{
            $guestSession = $this->customerSession->getGuestGiftBoxSession();
            if(!$guestSession){
                // $this->messageManager->addErrorMessage(__('Invalid Request'));
                return $this->_redirect('gift-order/giftBox/giftForm')->sendResponse();
            }
            $currentTime = $this->timezone->date()->format('Y-m-d H:i:s');
            $sessionEndTime = $guestSession['created_at'];
            if(strtotime($currentTime) > strtotime($sessionEndTime)){
                $this->messageManager->addErrorMessage(__('Your session has expired.'));
                return $this->_redirect('gift-order/giftBox/giftForm')->sendResponse();
            }
        }

        return $this->_pageFactory->create();
    }
}
