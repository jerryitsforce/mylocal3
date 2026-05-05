<?php
/**
 * @author Amasty Team
 * @copyright Copyright (c) Amasty (https://www.amasty.com)
 * @package Advanced Permissions for Magento 2
 */

namespace Branch8\AdvancedPermissions\Observer\Admin\Order;

use Amasty\Rolepermissions\Model\Authorization\GetCurrentUserInterface;
use Branch8\AdvancedPermissions\Block\Adminhtml\Role\Tab\Seller;
use Magento\Backend\Model\UrlInterface;
use Magento\Framework\App\ActionFlag;
use Magento\Framework\App\ActionInterface;
use Magento\Framework\App\ResponseInterface;
use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Message\ManagerInterface;
use Webkul\Marketplace\Model\OrdersFactory as MpOrdersFactory;

class LoadAfterObserver implements ObserverInterface
{
    /**
     * @var \Amasty\Rolepermissions\Helper\Data
     */
    private $helper;

    /**
     * @var \Magento\Framework\App\RequestInterface
     */
    private $request;

    /**
     * @var GetCurrentUserInterface
     */
    private $getCurrentUser;

    /**
     * @var ManagerInterface
     */
    protected $messageManager;

    /**
     * @var UrlInterface
     */
    protected $backendUrl;

    /**
     * @var ResponseInterface
     */
    protected $response;

    /**
     * @var ActionFlag
     */
    private $actionFlag;

    /**
     * @var MpOrdersFactory
     */
    protected $mpOrdersFactory;

    public function __construct(
        \Amasty\Rolepermissions\Helper\Data $helper,
        \Magento\Framework\App\RequestInterface $request,
        GetCurrentUserInterface $getCurrentUser,
        ManagerInterface $messageManager,
        UrlInterface $backendUrl,
        ResponseInterface $response,
        ActionFlag $actionFlag,
        MpOrdersFactory $mpOrdersFactory = null
    ) {
        $this->helper = $helper;
        $this->request = $request;
        $this->getCurrentUser = $getCurrentUser;
        $this->messageManager = $messageManager;
        $this->backendUrl = $backendUrl;
        $this->response = $response;
        $this->actionFlag = $actionFlag;
        $this->mpOrdersFactory = $mpOrdersFactory ?: \Magento\Framework\App\ObjectManager::getInstance()
            ->create(MpOrdersFactory::class);
    }

    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        if ($this->request->getModuleName() == 'api') {
            return;
        }
        $rule = $this->helper->currentRule();

        /** @var \Magento\Customer\Model\Customer $customer */
        $orderId = $observer->getOrder()->getId();

        if (!$this->checkOrderPermissions($rule, $orderId)) {
            $this->redirectHome();
        }
    }

    /**
     * @param \Amasty\Rolepermissions\Model\Rule $rule
     * @param \Magento\Customer\Model\Customer $customer
     *
     * @return bool
     */
    protected function checkOrderPermissions($rule, $orderId)
    {
        if ($rule->getSellerAccessMode() == Seller::MODE_ANY || !$rule->getSellers() || !$orderId) {
            return true;
        }
        $orderSeller = $this->mpOrdersFactory->create()->load($orderId, 'order_id');

        return in_array($orderSeller->getSellerId(), $rule->getSellers());
    }

    public function redirectHome()
    {
        if (!$this->getCurrentUser->execute()) {
            return;
        }

        $this->messageManager->addError(__('Access Denied'));

        $page = $this->backendUrl->getStartupPageUrl();
        $url = $this->backendUrl->getUrl($page);

        /** @see \Magento\Framework\App\FrontController::getActionResponse */
        $this->actionFlag->set('', ActionInterface::FLAG_NO_DISPATCH, true);

        $this->response
            ->setRedirect($url)
            ->sendResponse();
    }
}
