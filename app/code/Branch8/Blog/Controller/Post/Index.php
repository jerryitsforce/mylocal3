<?php
/**
 *
 * @category    Branch8
 * @package     Branch8_Blog
 */

namespace Branch8\Blog\Controller\Post;

use Branch8\Blog\Block\Post\Listpost;
use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Framework\View\Layout;
use Magento\Framework\View\Result\Page;
use Magento\Framework\View\Result\PageFactory;
use Branch8\Blog\Helper\Data;
use Magento\Framework\Controller\Result\RawFactory as ResultRawFactory;

/**
 * Class Index
 * @package Branch8\Blog\Controller\Post
 */
class Index extends Action
{
    /**
     * @var PageFactory
     */
    public $resultPageFactory;

    /**
     * @var ResultRawFactory
     */
    private ResultRawFactory $resultRawFactory;

    /**
     * @var Data
     */
    protected $_helperBlog;

    /**
     * Index constructor.
     *
     * @param Context $context
     * @param PageFactory $resultPageFactory
     * @param ResultRawFactory $resultRawFactory
     * @param Data $helperData
     */
    public function __construct(
        Context $context,
        PageFactory $resultPageFactory,
        ResultRawFactory $resultRawFactory,
        Data $helperData
    ) {
        $this->_helperBlog = $helperData;
        $this->resultPageFactory = $resultPageFactory;
        $this->resultRawFactory = $resultRawFactory;

        parent::__construct($context);
    }

    /**
     * @return Page
     */
    public function execute()
    {
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
