<?php
/**
 *
 * @category    Branch8
 * @package     Branch8_Blog
 */

namespace Branch8\Blog\Controller\Adminhtml\Post;

use Magento\Backend\App\Action\Context;
use Magento\Framework\Controller\ResultInterface;
use Magento\Framework\Registry;
use Magento\Framework\View\Result\LayoutFactory;
use Branch8\Blog\Controller\Adminhtml\Post;
use Branch8\Blog\Model\PostFactory;
use Branch8\Blog\Model\PostHistoryFactory;

/**
 * Class Products
 * @package Branch8\Blog\Controller\Adminhtml\Post
 */
class Products extends Post
{
    /**
     * @var LayoutFactory
     */
    protected $resultLayoutFactory;

    /**
     * @var PostHistoryFactory
     */
    protected $postHistoryFactory;

    /**
     * Products constructor.
     *
     * @param Context $context
     * @param Registry $registry
     * @param PostFactory $productFactory
     * @param PostHistoryFactory $postHistoryFactory
     * @param LayoutFactory $resultLayoutFactory
     */
    public function __construct(
        Context $context,
        Registry $registry,
        PostFactory $productFactory,
        PostHistoryFactory $postHistoryFactory,
        LayoutFactory $resultLayoutFactory
    ) {
        parent::__construct($productFactory, $registry, $context);

        $this->resultLayoutFactory = $resultLayoutFactory;
        $this->postHistoryFactory = $postHistoryFactory;
    }

    /**
     * Save action
     *
     * @return ResultInterface
     */
    public function execute()
    {
        if ($this->getRequest()->getParam('history')) {
            $history = $this->postHistoryFactory->create()->load($this->getRequest()->getParam('id'));
            $this->coreRegistry->register('branch8_blog_post', $history);
        } else {
            $this->initPost(true);
        }

        return $this->resultLayoutFactory->create();
    }
}
