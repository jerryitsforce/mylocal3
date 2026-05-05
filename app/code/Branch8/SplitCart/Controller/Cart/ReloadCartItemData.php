<?php

namespace Branch8\SplitCart\Controller\Cart;

use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\View\Result\PageFactory;

class ReloadCartItemData extends Action
{
    /**
     * @var PageFactory
     */
    protected $resultPageFactory;
    /**
     * @var JsonFactory
     */
    private $resultJsonFactory;
    /**
     * @var \Magento\Checkout\Model\Session\Proxy
     */
    private \Magento\Checkout\Model\Session\Proxy $checkoutProxy;

    /**
     * @param JsonFactory $resultJsonFactory
     * @param PageFactory $resultPageFactory
     * @param \Magento\Checkout\Model\Session\Proxy $proxy
     * @param Context $context
     */
    public function __construct(
        JsonFactory                           $resultJsonFactory,
        PageFactory                           $resultPageFactory,
        \Magento\Checkout\Model\Session\Proxy $proxy,
        Context                               $context
    )
    {
        parent::__construct($context);
        $this->checkoutProxy = $proxy;
        $this->resultJsonFactory = $resultJsonFactory;
        $this->resultPageFactory = $resultPageFactory;
    }

    /**
     * @return \Magento\Framework\App\ResponseInterface|\Magento\Framework\Controller\Result\Json|\Magento\Framework\Controller\ResultInterface
     */
    public function execute()
    {
        $resultJson = $this->resultJsonFactory->create();
        $data = [
            'error' => false,
            'info' => [
                'qty' => '',
                'optionHtml' => ''
            ]
        ];
        try {

        } catch (\Exception $exception) {
            return $resultJson->setData(['error' => true]);
        }
        return $resultJson->setData(
            $data
        );
    }
}
