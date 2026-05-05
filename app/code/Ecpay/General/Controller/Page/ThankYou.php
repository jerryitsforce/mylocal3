<?php
namespace Ecpay\General\Controller\Page;

use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Framework\View\Result\PageFactory;
use Magento\Framework\App\RequestInterface;

class ThankYou extends Action
{
    protected $_pageFactory;
    protected $_requestInterface;
    protected $_loggerInterface;

    public function __construct(
        Context $context,
        PageFactory $pageFactory,
        RequestInterface $requestInterface,
    ) {
        $this->_pageFactory = $pageFactory;
        $this->_requestInterface = $requestInterface;
        return parent::__construct($context);
    }

    public function execute()
    {
        return $this->_pageFactory->create();
    }
}
