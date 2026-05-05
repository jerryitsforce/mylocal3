<?php
/**
 *
 * @category    Branch8
 * @package     Branch8_Blog
 */

namespace Branch8\Blog\Controller\Adminhtml\History;

use Exception;
use Magento\Framework\App\ResponseInterface;
use Magento\Framework\Controller\Result\Redirect;
use Magento\Framework\Controller\ResultInterface;
use Branch8\Blog\Controller\Adminhtml\History;

/**
 * Class Delete
 * @package Branch8\Blog\Controller\Adminhtml\History
 */
class Delete extends History
{
    /**
     * @return ResponseInterface|Redirect|ResultInterface
     */
    public function execute()
    {
        $resultRedirect = $this->resultRedirectFactory->create();
        $postId = null;
        if ($id = $this->getRequest()->getParam('id')) {
            try {
                $history = $this->postHistoryFactory->create()
                    ->load($id);
                $postId = $history->getPostId();
                $history->delete();

                $this->messageManager->addSuccessMessage(__('The Post History has been deleted.'));
            } catch (Exception $e) {
                $this->messageManager->addErrorMessage($e->getMessage());
            }
        } else {
            $this->messageManager->addErrorMessage(__('Post History to delete was not found.'));
        }
        if ($postId) {
            $resultRedirect->setPath('branch8_blog/post/edit', ['id' => $postId]);
        } else {
            $resultRedirect->setPath('branch8_blog/post/index');
        }

        return $resultRedirect;
    }
}
