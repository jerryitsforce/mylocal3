<?php

namespace Branch8\Checkout\Override\Controller\Cart;
use Magento\Checkout\Model\Cart\RequestQuantityProcessor;
use Magento\Framework\Message\ManagerInterface;

/**
 * Override core class because we change the return type
 */
class UpdatePost extends \Magento\Checkout\Controller\Cart\UpdatePost
{
    /**
     * @var \Magento\Framework\Controller\Result\JsonFactory
     */
    protected $_resultJsonFactory;
    /**
     * @var RequestQuantityProcessor
     */
    private $quantityProcessor;
    /**
     * @var ManagerInterface
     */
    protected $_messageManager;

    /**
     * @param \Magento\Framework\App\Action\Context $context
     * @param \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
     * @param \Magento\Checkout\Model\Session $checkoutSession
     * @param \Magento\Store\Model\StoreManagerInterface $storeManager
     * @param \Magento\Framework\Data\Form\FormKey\Validator $formKeyValidator
     * @param \Magento\Checkout\Model\Cart $cart
     * @param ManagerInterface $messageManager
     * @param \Magento\Framework\Controller\Result\JsonFactory $resultJsonFactory
     * @param RequestQuantityProcessor|null $quantityProcessor
     */
    public function __construct(
        \Magento\Framework\App\Action\Context $context,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Magento\Checkout\Model\Session $checkoutSession,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Magento\Framework\Data\Form\FormKey\Validator $formKeyValidator,
        \Magento\Checkout\Model\Cart $cart,
        ManagerInterface $messageManager,
        \Magento\Framework\Controller\Result\JsonFactory $resultJsonFactory,
        RequestQuantityProcessor $quantityProcessor = null
    ) {
        parent::__construct(
            $context,
            $scopeConfig,
            $checkoutSession,
            $storeManager,
            $formKeyValidator,
            $cart,
            $quantityProcessor
        );
        $this->_messageManager = $messageManager;
        $this->_resultJsonFactory = $resultJsonFactory;
        $this->quantityProcessor = $quantityProcessor ?: $this->_objectManager->get(RequestQuantityProcessor::class);
    }
    /**
     * Update shopping cart data action
     *
     * @return \Magento\Framework\Controller\Result\Json
     */
    public function execute()
    {
        $result = $this->_resultJsonFactory->create();
        if (!$this->_formKeyValidator->validate($this->getRequest())) {
            $this->_messageManager->addErrorMessage(__('Invalid form key'));
            $result->setData(['success' => false]);
            return $result;
        }

        $updateAction = (string)$this->getRequest()->getParam('update_cart_action');
        $this->_messageManager->getMessages(true);
        try {
            switch ($updateAction) {
                case 'empty_cart':
                    $this->_emptyShoppingCart();
                    break;
                case 'update_qty':
                    $this->_updateShoppingCart();
                    break;
                default:
                    $this->_updateShoppingCart();
            }
        }catch (\Exception $e){
            $result->setData(['success' => false]);
            return $result;
        }

        $updateMessages = $this->_messageManager->getMessages(false);
        $errorMsgCount = $updateMessages->getCountByType(\Magento\Framework\Message\MessageInterface::TYPE_ERROR);
        if(!$errorMsgCount){
            $result->setData(['success' => true]);
        }else{
            $result->setData(['success' => false]);
        }

        return $result;
    }
}