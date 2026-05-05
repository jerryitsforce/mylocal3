<?php
/**
 *
 * @category    Branch8
 * @package     Branch8_Blog
 */

namespace Branch8\Blog\Controller\Adminhtml\Topic;

use Exception;
use Magento\Framework\Controller\Result\Redirect;
use Branch8\Blog\Controller\Adminhtml\Topic;

/**
 * Class Delete
 * @package Branch8\Blog\Controller\Adminhtml\Topic
 */
class Delete extends Topic
{
    /**
     * @return Redirect
     */
    public function execute()
    {
        $resultRedirect = $this->resultRedirectFactory->create();
        if ($id = $this->getRequest()->getParam('id')) {
            try {
                $this->topicFactory->create()
                    ->load($id)
                    ->delete();

                $this->messageManager->addSuccessMessage(__('The Topic has been deleted.'));
            } catch (Exception $e) {
                $this->messageManager->addErrorMessage($e->getMessage());
                $resultRedirect->setPath('branch8_blog/*/edit', ['id' => $id]);

                return $resultRedirect;
            }
        } else {
            $this->messageManager->addErrorMessage(__('Topic to delete was not found.'));
        }

        $resultRedirect->setPath('branch8_blog/*/');

        return $resultRedirect;
    }
}
