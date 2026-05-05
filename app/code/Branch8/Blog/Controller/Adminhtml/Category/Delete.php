<?php
/**
 *
 * @category    Branch8
 * @package     Branch8_Blog
 */

namespace Branch8\Blog\Controller\Adminhtml\Category;

use Exception;
use Magento\Framework\App\ResponseInterface;
use Magento\Framework\Controller\Result\Redirect;
use Magento\Framework\Controller\ResultInterface;
use Branch8\Blog\Controller\Adminhtml\Category;

/**
 * Class Delete
 * @package Branch8\Blog\Controller\Adminhtml\Category
 */
class Delete extends Category
{
    /**
     * @return ResponseInterface|Redirect|ResultInterface
     */
    public function execute()
    {
        $resultRedirect = $this->resultRedirectFactory->create();
        if ($id = $this->getRequest()->getParam('id')) {
            try {
                $categoryFactory = $this->categoryFactory->create();
                if ($id !== 1) {
                    $parentCategoryCollection = $categoryFactory->getCollection()
                        ->addFieldToFilter('category_id', ['eq' => $id])
                        ->addFieldToFilter('parent_id', ['eq' => 1]);
                    if ($parentCategoryCollection->getSize()) {
                        $pathCategory = $categoryFactory->load($id)->getPath();
                        $collections  = $categoryFactory->getCollection()
                            ->addFieldToFilter('path', ['like' => $pathCategory . '%']);
                        foreach ($collections as $collection) {
                            $collection->delete();
                        }
                    } else {
                        $categoryFactory->load($id)->delete();
                    }

                    $this->messageManager->addSuccessMessage(__('The Blog Category has been deleted.'));

                    $resultRedirect->setPath('branch8_blog/*/');

                    return $resultRedirect;
                }
            } catch (Exception $e) {
                // display error message
                $this->messageManager->addErrorMessage($e->getMessage());
                // go back to edit form
                $resultRedirect->setPath('branch8_blog/*/edit', ['id' => $id]);

                return $resultRedirect;
            }
        }

        // display error message
        $this->messageManager->addErrorMessage(__('Blog Category to delete was not found.'));
        // go to grid
        $resultRedirect->setPath('branch8_blog/*/');

        return $resultRedirect;
    }
}
