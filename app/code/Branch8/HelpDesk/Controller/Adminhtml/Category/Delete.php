<?php
declare(strict_types=1);

namespace Branch8\HelpDesk\Controller\Adminhtml\Category;

use Branch8\HelpDesk\Model\Category;
use Magento\Framework\App\Action\HttpPostActionInterface;
use Branch8\HelpDesk\Controller\Adminhtml\Category as AbstractCategory;

/**
 * Delete category
 */
class Delete extends AbstractCategory implements HttpPostActionInterface
{
    /**
     * Delete action
     *
     * @return \Magento\Framework\Controller\ResultInterface
     */
    public function execute()
    {
        /** @var \Magento\Backend\Model\View\Result\Redirect $resultRedirect */
        $resultRedirect = $this->resultRedirectFactory->create();
        // check if we know what should be deleted
        $id = $this->getRequest()->getParam('category_id');
        if ($id) {
            try {
                // init model and delete
                $model = $this->_objectManager->create(Category::class);
                $model->load($id);
                $model->delete();
                // display success message
                $this->messageManager->addSuccessMessage(__('You deleted the category.'));
                // go to grid
                return $resultRedirect->setPath('*/*/');
            } catch (\Exception $e) {
                // display error message
                $this->messageManager->addErrorMessage($e->getMessage());
                // go back to edit form
                return $resultRedirect->setPath('*/*/edit', ['category_id' => $id]);
            }
        }
        // display error message
        $this->messageManager->addErrorMessage(__('We can\'t find a category to delete.'));
        // go to grid
        return $resultRedirect->setPath('*/*/');
    }
}
