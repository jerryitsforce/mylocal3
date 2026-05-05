<?php
/**
 *
 * @category    Branch8
 * @package     Branch8_Blog
 */

namespace Branch8\Blog\Controller\Adminhtml\Author;

use Exception;
use Magento\Backend\App\Action\Context;
use Magento\Framework\Controller\Result\Redirect;
use Magento\Framework\Registry;
use Branch8\Blog\Controller\Adminhtml\Author;
use Branch8\Blog\Model\AuthorFactory;
use Branch8\Blog\Model\PostFactory;

/**
 * Class Delete
 * @package Branch8\Blog\Controller\Adminhtml\Post
 */
class Delete extends Author
{
    /**
     * @var PostFactory
     */
    protected $_postFactory;

    /**
     * Delete constructor.
     *
     * @param Context $context
     * @param Registry $coreRegistry
     * @param AuthorFactory $authorFactory
     * @param PostFactory $postFactory
     */
    public function __construct(
        Context $context,
        Registry $coreRegistry,
        AuthorFactory $authorFactory,
        PostFactory $postFactory
    ) {
        $this->_postFactory = $postFactory;

        parent::__construct($context, $coreRegistry, $authorFactory);
    }

    /**
     * @return Redirect
     */
    public function execute()
    {
        $resultRedirect = $this->resultRedirectFactory->create();

        if ($id = $this->getRequest()->getParam('id')) {
            $post = $this->_postFactory->create();
            $postCollectionSize = $post->getCollection()->addFieldToFilter('author_id', ['eq' => $id])->getSize();
            if ($postCollectionSize > 0) {
                $this->messageManager->addErrorMessage(__('You can not delete this author.'
                    . ' This is the author of %1 post(s)', $postCollectionSize));
                $resultRedirect->setPath('branch8_blog/*/edit', ['id' => $id]);

                return $resultRedirect;
            }
            try {
                $this->authorFactory->create()
                    ->load($id)
                    ->delete();

                $this->messageManager->addSuccessMessage(__('The Author has been deleted.'));
            } catch (Exception $e) {
                $this->messageManager->addErrorMessage($e->getMessage());
                $resultRedirect->setPath('branch8_blog/*/edit', ['id' => $id]);

                return $resultRedirect;
            }
        } else {
            $this->messageManager->addErrorMessage(__('Author to delete was not found.'));
        }

        $resultRedirect->setPath('branch8_blog/*/');

        return $resultRedirect;
    }
}
