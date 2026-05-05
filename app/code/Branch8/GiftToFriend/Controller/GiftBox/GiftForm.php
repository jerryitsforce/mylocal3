<?php
namespace Branch8\GiftToFriend\Controller\GiftBox;

class GiftForm extends \Magento\Framework\App\Action\Action
{
    /**
     * @var \Magento\Framework\View\Result\PageFactory
     */
    protected $_pageFactory;

    protected $timezone;

    protected $customerSession;

    /**
     * @param \Magento\Framework\App\Action\Context $context
     */
    public function __construct(
       \Magento\Framework\App\Action\Context $context,
       \Magento\Framework\View\Result\PageFactory $pageFactory,
       \Magento\Framework\Stdlib\DateTime\TimezoneInterface $timezone,
       \Magento\Customer\Model\Session $customerSession
    )
    {
        $this->_pageFactory = $pageFactory;
        parent::__construct($context);
        $this->timezone = $timezone;
        $this->customerSession = $customerSession;
    }
    /**
     * View page action
     *
     * @return \Magento\Framework\Controller\ResultInterface
     */
    public function execute()
    {
        $guestSession = $this->customerSession->getGuestGiftBoxSession();
        if($guestSession){
            $currentTime = $this->timezone->date()->format('Y-m-d H:i:s');
            $sessionEndTime = $guestSession['created_at'];
            if(strtotime($currentTime) <= strtotime($sessionEndTime)){
                return $this->_redirect('gift-order/giftbox/listing')->sendResponse();
            }
        }
        
        $result = $this->_pageFactory->create();
        $result->getConfig()->getTitle()->set(__('接收禮物'));
        return $result;
    }
}
