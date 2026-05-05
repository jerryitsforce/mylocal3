<?php
declare(strict_types=1);

namespace Branch8\MarketPlaceParentOrderFrontendUi\Controller\ParentOrder\AbstractController;

use Magento\Framework\App\Action\Context;
use Magento\Framework\View\Result\PageFactory;

abstract class PrintCreditmemo extends \Magento\Framework\App\Action\Action
{
    /**
     * @var OrderViewAuthorizationInterface
     */
    protected $orderAuthorization;

    /**
     * @var \Magento\Framework\Registry
     */
    protected $_coreRegistry;

    /**
     * @var PageFactory
     */
    protected $resultPageFactory;

    private $orderLoader;

    /**
     * @param Context $context
     * @param OrderViewAuthorizationInterface $orderAuthorization
     * @param OrderLoaderInterface $orderLoader
     * @param \Magento\Framework\Registry $registry
     * @param PageFactory $resultPageFactory
     */
    public function __construct(
        Context                         $context,
        OrderViewAuthorizationInterface $orderAuthorization,
        OrderLoaderInterface            $orderLoader,
        \Magento\Framework\Registry     $registry,
        PageFactory                     $resultPageFactory
    )
    {
        $this->orderAuthorization = $orderAuthorization;
        $this->_coreRegistry = $registry;
        $this->resultPageFactory = $resultPageFactory;
        $this->orderLoader = $orderLoader;
        parent::__construct($context);
    }

    /**
     * Print Shipment Action
     *
     * @return \Magento\Framework\Controller\Result\Redirect|\Magento\Framework\View\Result\Page
     */
    public function execute()
    {
        $order = $this->orderLoader->load($this->_request);
        if ($order instanceof \Magento\Framework\Controller\ResultInterface) {
            return $order;
        }
        $order = $this->_coreRegistry->registry('current_parent_order');
        if ($this->orderAuthorization->canView($order)) {
            /** @var \Magento\Framework\View\Result\Page $resultPage */
            $resultPage = $this->resultPageFactory->create();
            $resultPage->addHandle('print');
            return $resultPage;
        } else {
            /** @var \Magento\Framework\Controller\Result\Redirect $resultRedirect */
            $resultRedirect = $this->resultRedirectFactory->create();
            if ($this->_objectManager->get(\Magento\Customer\Model\Session::class)->isLoggedIn()) {
                $resultRedirect->setPath('*/*/history');
            } else {
                $resultRedirect->setPath('sales/guest/form');
            }
            return $resultRedirect;
        }
    }
}
