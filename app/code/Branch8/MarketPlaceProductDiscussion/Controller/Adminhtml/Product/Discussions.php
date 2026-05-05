<?php
declare(strict_types=1);

namespace Branch8\MarketPlaceProductDiscussion\Controller\Adminhtml\Product;

use Magento\Backend\App\Action;
use Magento\Framework\App\Action\HttpGetActionInterface;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\App\ResponseInterface;
use Magento\Framework\Controller\ResultInterface;
use Magento\Framework\View\Result\PageFactory;
use Psr\Log\LoggerInterface;
use Magento\Backend\App\Action\Context;

/**
 * Controller for the 'catalog/product/discussions' URL route.
 */
class Discussions extends Action implements HttpGetActionInterface
{
    /**
     * Authorization level of a basic admin session.
     */
    const ADMIN_RESOURCE = 'Branch8_MarketPlaceProductDiscussion::manage';

    protected PageFactory $resultPageFactory;

    protected RequestInterface $request;

    /**
     * @param PageFactory $resultPageFactory
     * @param RequestInterface $request
     * @param Context $context
     * @param LoggerInterface $logger
     */
    public function __construct(
        PageFactory      $resultPageFactory,
        RequestInterface $request,
        Context          $context,
        LoggerInterface  $logger,
    )
    {
        $this->request = $request;
        $this->resultPageFactory = $resultPageFactory;
        parent::__construct($context);
    }

    public function execute()
    {

    }
}
