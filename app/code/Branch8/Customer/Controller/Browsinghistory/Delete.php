<?php
declare(strict_types=1);

namespace Branch8\Customer\Controller\Browsinghistory;

use Magento\Framework\App\Action\HttpGetActionInterface;
use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\Controller\ResultInterface;
use Magento\Framework\View\Result\PageFactory;
use Magento\Framework\App\Action\Context;
use Magento\Customer\Model\Session as CustomerSession;

class Delete extends \Magento\Framework\App\Action\Action
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
     * @var \Magento\Reports\Model\Product\Index\ViewedFactory
     */
    protected $viewedFactory;

    /**
     * Delete constructor.
     * @param Context $context
     * @param CustomerSession $customerSession
     * @param \Magento\Reports\Model\Product\Index\ViewedFactory $viewedFactory
     * @param PageFactory $pageFactory
     */
    public function __construct(
        \Magento\Framework\App\Action\Context $context,
        CustomerSession $customerSession,
        \Magento\Reports\Model\Product\Index\ViewedFactory $viewedFactory,
        \Magento\Framework\View\Result\PageFactory $pageFactory
    ) {
        $this->_pageFactory = $pageFactory;
        $this->customerSession = $customerSession;
        $this->viewedFactory = $viewedFactory;
        return parent::__construct($context);
    }

    /**
     * Remove recently viewed product
     * @return \Magento\Framework\App\ResponseInterface|ResultInterface
     */
    public function execute()
    {
        $resultJson = $this->resultFactory->create(ResultFactory::TYPE_JSON);

        if (!$this->customerSession->isLoggedIn()) {
            $currentUrl = $this->_url->getCurrentUrl();
            $encodedReferer = base64_encode($currentUrl);
            return $this->_redirect('customer/account/login/referer/' . $encodedReferer);
        }
        try {
            $viewedProduct = $this->viewedFactory->create();
            $connection = $viewedProduct->getResource()->getConnection();
            $viewedTbl = $viewedProduct->getResource()->getTable("report_viewed_product_index");
            if ($productId = $this->getRequest()->getParam("productId")) {
                $connection->delete(
                    $viewedTbl,
                    [
                        'customer_id = ?' => $this->customerSession->getCustomer()->getId(),
                        'product_id = ?' => $productId,
                    ]
                );
            } else {
                $connection->delete(
                    $viewedTbl,
                    ['customer_id = ?' => $this->customerSession->getCustomer()->getId()]
                );
            }
            $resultJson->setData([
                "status" => true
            ]);
        } catch(\Exception $e) {
            $resultJson->setData([
                "status" => false
            ]);
        }

        return $resultJson;
    }
}

