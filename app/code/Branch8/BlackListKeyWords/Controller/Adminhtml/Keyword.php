<?php
declare(strict_types=1);
/**
 * @project    HotaiConnected
 * @package    Branch8
 * @author     cuongho803@gmail.com
 * @date       20/03/2026
 */

namespace Branch8\BlackListKeyWords\Controller\Adminhtml;
use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\App\Action\HttpGetActionInterface;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\View\Result\PageFactory;
use Psr\Log\LoggerInterface;

class Keyword extends Action implements HttpGetActionInterface
{
    /**
     * Authorization level of a basic admin session.
     */
    const ADMIN_RESOURCE = 'Branch8_BlackListKeyWords::management';

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

    /**
     * @return void
     */
    public function execute()
    {

    }
}
