<?php
declare(strict_types=1);

namespace Branch8\HelpDesk\Controller\Adminhtml\Ticket;

use Branch8\HelpDesk\Controller\Adminhtml\Ticket;
use Branch8\HelpDesk\Model\Ticket\AclRole;
use Branch8\HelpDesk\Model\Ticket\Status;

class Edit extends \Branch8\HelpDesk\Controller\Adminhtml\Ticket
{
    const ADMIN_RESOURCE = AclRole::VIEW_TICKET;
    /**
     * @var \Magento\Framework\View\Result\PageFactory
     */
    protected $resultPageFactory;

    protected $authSession;

    protected $helper;

    /**
     * @param \Magento\Backend\App\Action\Context $context
     * @param \Magento\Backend\Model\Auth\Session $authSession
     * @param \Magento\Framework\Registry $coreRegistry
     * @param \Magento\Framework\View\Result\PageFactory $resultPageFactory
     */
    public function __construct(
        \Magento\Backend\App\Action\Context        $context,
        \Magento\Backend\Model\Auth\Session        $authSession,
        \Magento\Framework\Registry                $coreRegistry,
        \Magento\Framework\View\Result\PageFactory $resultPageFactory
    )
    {
        $this->authSession = $authSession;
        $this->resultPageFactory = $resultPageFactory;
        parent::__construct($context, $coreRegistry);
    }

    /**
     * Edit HelpDesk Form
     *
     * @return \Magento\Framework\Controller\ResultInterface
     * @SuppressWarnings(PHPMD.NPathComplexity)
     */
    public function execute()
    {
        // 1. Get ID and create model
        $id = $this->getRequest()->getParam('ticket_id');
        $model = $this->_objectManager->create('Branch8\HelpDesk\Model\Ticket');

        // 2. Initial checking
        if ($id) {
            $model->load($id);
            if (!$model->getId()) {
                $this->messageManager->addError(__('This Ticket no longer exists.'));
                /** @var \Magento\Backend\Model\View\Result\Redirect $resultRedirect */
                $resultRedirect = $this->resultRedirectFactory->create();
                return $resultRedirect->setPath('*/*/');
            }
            $this->setStatusToReadIfIsNew($model);
        }
        // 3. Set entered data if was error when we do save
        $data = $this->_objectManager->get('Magento\Backend\Model\Session')->getFormData(true);
        if (!empty($data)) {
            $model->setData($data);
        }

        // 4. Register model to use later in forms
        $this->_coreRegistry->register('helpdesk_ticket', $model);

        /** @var \Magento\Backend\Model\View\Result\Page $resultPage */
        $resultPage = $this->resultPageFactory->create();

        // 5. Build edit form
        $this->initPage($resultPage)->addBreadcrumb(
            $id ? __('Edit Ticket') : __('New Ticket'),
            $id ? __('Edit Ticket') : __('New Ticket')
        );
        $resultPage->getConfig()->getTitle()->prepend(__('Tickets'));
        $resultPage->getConfig()->getTitle()->prepend($model->getId() ? $model->getSubject() : __('New Ticket'));
        return $resultPage;
    }

    /**
     * @param Ticket $ticket
     * @return void
     */
    private function setStatusToReadIfIsNew(\Branch8\HelpDesk\Model\Ticket $ticket)
    {
        if ($ticket->getStatus() == Status::UNREAD) {
            $ticket->setStatus(Status::READ)->save();
        }
    }
}
