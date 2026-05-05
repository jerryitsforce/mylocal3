<?php
/**
 *
 * @category    Branch8
 * @package     Branch8_Blog
 */

namespace Branch8\Blog\Controller\Author;

use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Framework\App\ResponseInterface;
use Magento\Framework\Controller\Result\ForwardFactory;
use Magento\Framework\Controller\Result\Redirect;
use Magento\Framework\Controller\ResultInterface;
use Magento\Framework\View\Result\Page;
use Magento\Framework\View\Result\PageFactory;
use Branch8\Blog\Helper\Data;
use Branch8\Blog\Model\Config\Source\SideBarLR;

/**
 * Class View
 * @package Branch8\Blog\Controller\Author
 */
class Signup extends Action
{
    /**
     * @var PageFactory
     */
    public $resultPageFactory;

    /**
     * @var ForwardFactory
     */
    protected $resultForwardFactory;

    /**
     * @var Data
     */
    protected $_helperBlog;

    /**
     * View constructor.
     *
     * @param Context $context
     * @param PageFactory $resultPageFactory
     * @param ForwardFactory $resultForwardFactory
     * @param Data $helperData
     */
    public function __construct(
        Context $context,
        PageFactory $resultPageFactory,
        ForwardFactory $resultForwardFactory,
        Data $helperData
    ) {
        $this->_helperBlog          = $helperData;
        $this->resultPageFactory    = $resultPageFactory;
        $this->resultForwardFactory = $resultForwardFactory;

        parent::__construct($context);
    }

    /**
     * @return ResponseInterface|Redirect|ResultInterface|Page
     */
    public function execute()
    {
        $resultRedirect = $this->resultRedirectFactory->create();
        $this->_helperBlog->setCustomerContextId();

        if (!$this->_helperBlog->isEnabled()
            || !$this->_helperBlog->isEnabledAuthor()
            || ($this->_helperBlog->isAuthor() && !$this->_helperBlog->getConfigGeneral('customer_approve') && !$this->_helperBlog->getPostViewPageConfig('enable_to_save'))) {
            $resultRedirect->setPath('customer/account');

            return $resultRedirect;
        }

        if ($this->_helperBlog->isAuthor() && $this->_helperBlog->getConfigGeneral('customer_approve')) {
            $page = $this->resultPageFactory->create();
            $page->getConfig()->setPageLayout(SideBarLR::LEFT);
            $page->getConfig()->getTitle()->set('Signup Author');

            return $page;
        }
        if ($this->_helperBlog->isAuthor() && $this->_helperBlog->getPostViewPageConfig('enable_to_save')) {
            $resultRedirect->setPath('blog/post/save');

            return $resultRedirect;
        }
        if ($this->_helperBlog->isLogin()) {
            $resultRedirect->setPath('blog/*/information');
        } else {
            $resultRedirect->setPath('customer/account');
        }

        return $resultRedirect;
    }
}
