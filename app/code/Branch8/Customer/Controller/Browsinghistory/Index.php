<?php
declare(strict_types=1);

namespace Branch8\Customer\Controller\Browsinghistory;

use Magento\Framework\App\Action\HttpGetActionInterface;
use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\Controller\ResultInterface;
use Magento\Framework\View\Result\PageFactory;
use Magento\Framework\App\Action\Context;
use Magento\Customer\Model\Session as CustomerSession;

class Index extends \Magento\Framework\App\Action\Action
{
    /**
     * @var CustomerSession
     */
    protected $customerSession;
    /**
     * @var \Magento\Framework\View\Result\PageFactory
     */
    protected $_pageFactory;

    /**
     * Index constructor.
     * @param Context $context
     * @param CustomerSession $customerSession
     * @param PageFactory $pageFactory
     */
    public function __construct(
        \Magento\Framework\App\Action\Context $context,
        CustomerSession $customerSession,
        \Magento\Framework\View\Result\PageFactory $pageFactory
    ) {
        $this->_pageFactory = $pageFactory;
        $this->customerSession = $customerSession;
        return parent::__construct($context);
    }

    /**
     * View page action
     * @return \Magento\Framework\App\ResponseInterface|ResultInterface|\Magento\Framework\View\Result\Page
     */
    public function execute()
    {
        /** @var \Magento\Framework\View\Result\Page $resultPage */
        $resultPage = $this->resultFactory->create(ResultFactory::TYPE_PAGE);

        if (!$this->customerSession->isLoggedIn()) {
            $currentUrl = $this->_url->getCurrentUrl();
            $encodedReferer = base64_encode($currentUrl);
            return $this->_redirect('customer/account/login/referer/' . $encodedReferer);
        }
        if ($this->getRequest()->isAjax())
        {
            $response = $this->resultFactory->create(ResultFactory::TYPE_JSON);
            $block = $resultPage->getLayout()
                ->createBlock('Branch8\Customer\Block\BrowsingHistory\Index')
                ->setTemplate('Branch8_Customer::browsing_history/grid_ajax.phtml')
                ->toHtml();
            $response->setData($block);
            return $response;
        }

        $resultPage->getConfig()->getTitle()->set(__('Browsing History'));

        return $resultPage;
    }
}

