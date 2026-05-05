<?php

namespace Branch8\HotaiPay\Controller\CreditCard;

use Magento\Framework\App\Action\Context;
use \Magento\Framework\Registry;
use \Magento\Customer\Model\Session;
use Magento\Framework\App\RequestInterface;
use Branch8\HotaiPay\Model\CreditCard\Session as CreditCardSession;

class Edit extends \Magento\Framework\App\Action\Action
{
    protected $registry;
    protected $_customerSession;
    protected $creditCardSession;
    /**
     * __construct
     *
     * @return void
     */
    public function __construct(
        Context $context,
        Registry $registry,
        Session $customerSession,
        CreditCardSession $creditCardSession
    ) {
        $this->_customerSession = $customerSession;
        $this->registry = $registry;
        $this->creditCardSession = $creditCardSession;
        parent::__construct($context);
    }
    /**
     * Check customer authentication
     *
     * @param RequestInterface $request
     * @return \Magento\Framework\App\ResponseInterface
     */
    public function dispatch(RequestInterface $request)
    {
        $loginUrl = $this->_objectManager->get(\Magento\Customer\Model\Url::class)->getLoginUrl();

        if (!$this->_customerSession->authenticate($loginUrl)) {
            $this->_actionFlag->set('', self::FLAG_NO_DISPATCH, true);
        }
        return parent::dispatch($request);
    }

    /**
     * execute
     *
     * @return void
     */
    public function execute()
    {
        $this->registry->register('params', $this->getRequest()->getParams());
        $this->checkParams();
        $this->_view->loadLayout();
        $this->_view->renderLayout();
    }

    /**
     * checkParams
     *
     * @return void
     */
    private function checkParams()
    {
        $params = $this->getRequest()->getParams();
        if (isset($params['StatusDesc'])) {
            if (strtoupper($params['StatusDesc']) == 'SUCCESS') {
                $this->creditCardSession->unsCreditCardList();
            }
        }
    }
}
