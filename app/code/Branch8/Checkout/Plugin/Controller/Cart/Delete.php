<?php

namespace Branch8\Checkout\Plugin\Controller\Cart;

use Magento\Checkout\Model\Cart as CustomerCart;
use Magento\Checkout\Model\Session;
use Magento\Framework\App\Action\Context;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Data\Form\FormKey\Validator;
use Magento\Store\Model\StoreManagerInterface;

class Delete extends \Magento\Checkout\Controller\Cart\Delete
{
    /**
     * @var \Magento\Framework\Controller\Result\JsonFactory
     */
    protected $_resultJsonFactory;

    /**
     * @param Context $context
     * @param ScopeConfigInterface $scopeConfig
     * @param Session $checkoutSession
     * @param StoreManagerInterface $storeManager
     * @param Validator $formKeyValidator
     * @param CustomerCart $cart
     * @param \Magento\Framework\Controller\Result\JsonFactory $resultJsonFactory
     */
    public function __construct(
        Context $context,
        ScopeConfigInterface $scopeConfig,
        Session $checkoutSession,
        StoreManagerInterface $storeManager,
        Validator $formKeyValidator,
        CustomerCart $cart,
        \Magento\Framework\Controller\Result\JsonFactory $resultJsonFactory
    ) {
        parent::__construct($context, $scopeConfig, $checkoutSession, $storeManager, $formKeyValidator, $cart);
        $this->_resultJsonFactory = $resultJsonFactory;
    }

    public function aroundExecute($subject, $process)
    {
        $result = $this->_resultJsonFactory->create();
        if (!$this->_formKeyValidator->validate($this->getRequest())) {
            $this->messageManager->addErrorMessage(__('Invalid form key'));
            return $result->setData(['success' => false]);
        }
        $this->messageManager->getMessages(true);
        $id = (int)$this->getRequest()->getParam('id');
        if ($id) {
            try {
                $this->cart->removeItem($id);
                // We should set Totals to be recollected once more because of Cart model as usually is loading
                // before action executing and in case when triggerRecollect setted as true recollecting will
                // executed and the flag will be true already.
                $this->cart->getQuote()->setTotalsCollectedFlag(false);
                $this->cart->save();
            } catch (\Exception $e) {
                $this->messageManager->addErrorMessage(__('We can\'t remove the item.'));
                if(\Branch8\HotaiCore\Helper\DebugLog::isEnable('Branch8_Checkout', 'exceptionlog')){
                    $this->_objectManager->get(\Psr\Log\LoggerInterface::class)->critical($e);
                }
            }
        }
        $updateMessages = $this->messageManager->getMessages(false);
        $errorMsgCount = $updateMessages->getCountByType(\Magento\Framework\Message\MessageInterface::TYPE_ERROR);
        if(!$errorMsgCount){
            $result->setData(['success' => true]);
        }else{
            $result->setData(['success' => false]);
        }

        return $result;
    }
}