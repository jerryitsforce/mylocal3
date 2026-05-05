<?php
declare(strict_types=1);

namespace Branch8\HelpDesk\Controller\Adminhtml\Team;

use Branch8\HelpDesk\Model\Team;
use Magento\Framework\App\Action\HttpPostActionInterface;
use Branch8\HelpDesk\Controller\Adminhtml\Team as AbstractTeam;
/**
 * Delete Team
 */
class Delete extends AbstractTeam implements HttpPostActionInterface
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
        $id = $this->getRequest()->getParam('team_id');
        if ($id) {
            try {
                // init model and delete
                $model = $this->_objectManager->create(Team::class);
                $model->load($id);
                $model->delete();
                // display success message
                $this->messageManager->addSuccessMessage(__('You deleted the team.'));
                // go to grid
                return $resultRedirect->setPath('*/*/');
            } catch (\Exception $e) {
                // display error message
                $this->messageManager->addErrorMessage($e->getMessage());
                // go back to edit form
                return $resultRedirect->setPath('*/*/edit', ['team_id' => $id]);
            }
        }
        // display error message
        $this->messageManager->addErrorMessage(__('We can\'t find a team to delete.'));
        // go to grid
        return $resultRedirect->setPath('*/*/');
    }
}
