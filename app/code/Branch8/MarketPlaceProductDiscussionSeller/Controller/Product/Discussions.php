<?php
declare(strict_types=1);

namespace Branch8\MarketPlaceProductDiscussionSeller\Controller\Product;

use Magento\Framework\App\Action\HttpGetActionInterface;
use Magento\Framework\Controller\Result\RedirectFactory;

/**
 * Controller for the 'marketplace/product/discussions' URL route.
 */
class Discussions implements HttpGetActionInterface
{
    private \Magento\Framework\View\Result\PageFactory $resultPageFactory;
    private RedirectFactory $resultRedirectFactory;
    private \Webkul\Marketplace\Helper\Data $helperData;
    private \Magento\Customer\Model\Session $customerSession;
    /**
     * @param \Magento\Customer\Model\Session $customerSession
     * @param \Webkul\Marketplace\Helper\Data $helperData
     * @param \Magento\Framework\View\Result\PageFactory $resultPageFactory
     * @param RedirectFactory $resultRedirectFactory
     */
    public function __construct(
        \Magento\Customer\Model\Session            $customerSession,
        \Webkul\Marketplace\Helper\Data            $helperData,
        \Magento\Framework\View\Result\PageFactory $resultPageFactory,
        RedirectFactory                            $resultRedirectFactory
    )
    {
        $this->helperData = $helperData;
        $this->customerSession = $customerSession;
        $this->resultRedirectFactory = $resultRedirectFactory;
        $this->resultPageFactory = $resultPageFactory;
    }

    /**
     * Execute controller action.
     */
    public function execute()
    {
        $isPartner = $this->helperData->isSeller();
        if ($isPartner == 1) {
            /** @var \Magento\Framework\View\Result\Page $resultPage */
            $resultPage = $this->resultPageFactory->create();
            $resultPage->getConfig()->getTitle()->set(
                __('Q&A')
            );
            return $resultPage;
        } else {
            return $this->resultRedirectFactory->create()->setPath(
                'marketplace/account/becomeseller',
                ['_secure' => $this->getRequest()->isSecure()]
            );
        }
    }
}
