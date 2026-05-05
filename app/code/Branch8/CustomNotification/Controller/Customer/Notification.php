<?php
namespace Branch8\CustomNotification\Controller\Customer;
use Magenest\NotificationBox\Helper\Helper;
use Magento\Customer\Model\Session as CustomerSession;
use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Framework\Controller\ResultFactory;

class Notification extends Action {

    /** @var Helper  */
    protected $helper;

    /** @var ResultFactory  */
    protected $resultFactory;

    /**
     * @var CustomerSession
     */
    private $customerSession;

    /**
     * @param Context $context
     * @param ResultFactory $resultFactory
     * @param Helper $helper
     */
    public function __construct(
        Context $context,ResultFactory
        $resultFactory,
        Helper $helper,
        CustomerSession $customerSession
    )
    {
        $this->helper = $helper;
        $this->resultFactory = $resultFactory;
        $this->customerSession = $customerSession;
        parent::__construct($context);
    }

    public function execute() {
        if (!$this->customerSession->isLoggedIn()) {
            $currentUrl = $this->_url->getCurrentUrl();
            $encodedReferer = base64_encode($currentUrl);
            return $this->_redirect('customer/account/login/referer/' . $encodedReferer);
        }
        if(!$this->helper->getEnableModule()){
            $redirect = $this->resultFactory->create(\Magento\Framework\Controller\ResultFactory::TYPE_REDIRECT);
            $redirect->setPath('no-route');
            return $redirect;
        }
        $this->_view->loadLayout();
        $this->_view->renderLayout();
    }
}
