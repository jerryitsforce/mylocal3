<?php
/**
 *
 * @category    Branch8
 * @package     Branch8_Blog
 */

namespace Branch8\Blog\Controller\Topic;

use Branch8\Blog\Block\Topic\Listpost;
use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Framework\App\ResponseInterface;
use Magento\Framework\Controller\Result\ForwardFactory;
use Magento\Framework\Controller\Result\RawFactory as ResultRawFactory;
use Magento\Framework\Controller\ResultInterface;
use Magento\Framework\View\Layout;
use Magento\Framework\View\Result\Page;
use Magento\Framework\View\Result\PageFactory;
use Branch8\Blog\Helper\Data as HelperBlog;

/**
 * Class View
 * @package Branch8\Blog\Controller\Topic
 */
class View extends Action
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
     * @var ResultRawFactory
     */
    private ResultRawFactory $resultRawFactory;

    /**
     * @var HelperBlog
     */
    public $helperBlog;

    /**
     * View constructor.
     *
     * @param Context $context
     * @param PageFactory $resultPageFactory
     * @param ForwardFactory $resultForwardFactory
     * @param ResultRawFactory $resultRawFactory
     * @param HelperBlog $helperBlog
     */
    public function __construct(
        Context $context,
        PageFactory $resultPageFactory,
        ForwardFactory $resultForwardFactory,
        ResultRawFactory $resultRawFactory,
        HelperBlog $helperBlog
    ) {
        $this->resultPageFactory = $resultPageFactory;
        $this->resultForwardFactory = $resultForwardFactory;
        $this->resultRawFactory = $resultRawFactory;
        $this->helperBlog = $helperBlog;

        parent::__construct($context);
    }

    /**
     * @return ResponseInterface|ResultInterface|Page
     */
    public function execute()
    {
        $id = $this->getRequest()->getParam('id');
        $topic = $this->helperBlog->getFactoryByType(HelperBlog::TYPE_TOPIC)->create()->load($id);
        if (!$topic->getEnabled()) {
            return $this->_redirect('noroute');
        }

        $isAjax = $this->getRequest()->isAjax();
        $isScroll = $this->getRequest()->getParam('is_scroll');
        if ($isAjax && $isScroll) {
            $result = $this->resultRawFactory->create();
            /** @var Layout $layout */
            $layout = $this->_view->getLayout();
            /** @var Listpost $block */
            $block = $layout->createBlock(Listpost::class)
                ->setTemplate('Branch8_Blog::post/list.phtml');
            $result->setContents($block->toHtml());
            return $result;
        }
        $page = $this->resultPageFactory->create();
        $page->getConfig()->setPageLayout('1column');

        return $page;
    }
}
