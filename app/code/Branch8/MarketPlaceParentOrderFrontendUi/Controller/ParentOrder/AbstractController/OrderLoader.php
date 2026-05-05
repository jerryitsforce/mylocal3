<?php
declare(strict_types=1);

namespace Branch8\MarketPlaceParentOrderFrontendUi\Controller\ParentOrder\AbstractController;

use Branch8\MarketPlaceParentOrder\Model\ParentOrderFactory;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Registry;
use Magento\Framework\Controller\Result\ForwardFactory;
use Magento\Framework\App\ActionFlag;
use Magento\Framework\App\ActionInterface;

class OrderLoader implements OrderLoaderInterface
{
    /**
     * @var \Magento\Sales\Model\OrderFactory
     */
    protected $parentOrderFactory;

    /**
     * @var \Magento\Framework\Registry
     */
    protected $registry;

    /**
     * @var OrderViewAuthorizationInterface
     */
    protected $orderAuthorization;

    /**
     * @var \Magento\Framework\UrlInterface
     */
    protected $url;

    /**
     * @var ForwardFactory
     */
    protected $resultForwardFactory;

    /**
     * @var \Magento\Framework\App\ResponseFactory
     */
    protected $responseFactory;

    private $actionFlag;

    /**
     * @param ParentOrderFactory $parentOrderFactory
     * @param OrderViewAuthorizationInterface $orderAuthorization
     * @param Registry $registry
     * @param \Magento\Framework\UrlInterface $url
     * @param ForwardFactory $resultForwardFactory
     * @param \Magento\Framework\App\ResponseFactory $responseFactory
     */
    public function __construct(
        ParentOrderFactory              $parentOrderFactory,
        OrderViewAuthorizationInterface $orderAuthorization,
        Registry                        $registry,
        \Magento\Framework\UrlInterface $url,
        ForwardFactory                  $resultForwardFactory,
        \Magento\Framework\App\ResponseFactory $responseFactory,
        ActionFlag $actionFlag
    ) {
        $this->parentOrderFactory = $parentOrderFactory;
        $this->orderAuthorization = $orderAuthorization;
        $this->registry = $registry;
        $this->url = $url;
        $this->resultForwardFactory = $resultForwardFactory;
        $this->responseFactory = $responseFactory;
        $this->actionFlag = $actionFlag;
    }

    /**
     * @param RequestInterface $request
     * @return bool|\Magento\Framework\Controller\Result\Forward|\Magento\Framework\Controller\Result\Redirect
     */
    public function load(RequestInterface $request)
    {
        $orderId = (int)$request->getParam('id');
        if (!$orderId) {
            /** @var \Magento\Framework\Controller\Result\Forward $resultForward */
            $resultForward = $this->resultForwardFactory->create();
            return $resultForward->forward('noroute');
        }

        $order = $this->parentOrderFactory->create()->load($orderId);

        if ($this->orderAuthorization->canView($order)) {
            $this->registry->register('current_parent_order', $order);
            return true;
        }
        $this->actionFlag->set('', \Magento\Framework\App\Action\Action::FLAG_NO_DISPATCH, true);
        /** @var \Magento\Framework\Controller\Result\Redirect $resultRedirect */
        $this->responseFactory->create()->setRedirect($this->url->getUrl('*/*/history'))->sendResponse();
        exit(0);
    }
}
