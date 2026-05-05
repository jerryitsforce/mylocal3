<?php
declare(strict_types=1);

namespace Branch8\HelpDesk\Controller\Adminhtml\Team;

use Branch8\HelpDesk\Model\Team;
use Magento\Framework\App\Action\HttpGetActionInterface;
use Branch8\HelpDesk\Controller\Adminhtml\Team as AbstractTeam;
/**
 * Edit Team
 */
class Edit extends AbstractTeam implements HttpGetActionInterface
{
    /**
     * @var \Magento\Framework\View\Result\PageFactory
     */
    protected $resultPageFactory;

    /**
     * @param \Magento\Backend\App\Action\Context $context
     * @param \Magento\Framework\Registry $coreRegistry
     * @param \Magento\Framework\View\Result\PageFactory $resultPageFactory
     */
    public function __construct(
        \Magento\Backend\App\Action\Context        $context,
        \Magento\Framework\Registry                $coreRegistry,
        \Magento\Framework\View\Result\PageFactory $resultPageFactory
    )
    {
        $this->resultPageFactory = $resultPageFactory;
        parent::__construct($context, $coreRegistry);
    }

    /**
     * @return \Magento\Backend\Model\View\Result\Page|\Magento\Backend\Model\View\Result\Redirect|\Magento\Framework\App\ResponseInterface|\Magento\Framework\Controller\ResultInterface
     */
    public function execute()
    {
        // 1. Get ID and create model
        $id = $this->getRequest()->getParam('team_id');
        $model = $this->_objectManager->create(Team::class);

        // 2. Initial checking
        if ($id) {
            $model->load($id);
            if (!$model->getId()) {
                $this->messageManager->addErrorMessage(__('This team no longer exists.'));
                /** @var \Magento\Backend\Model\View\Result\Redirect $resultRedirect */
                $resultRedirect = $this->resultRedirectFactory->create();
                return $resultRedirect->setPath('*/*/');
            }
        }

        $this->_coreRegistry->register('team', $model);

        // 5. Build edit form
        /** @var \Magento\Backend\Model\View\Result\Page $resultPage */
        $resultPage = $this->resultPageFactory->create();
        $this->initPage($resultPage)->addBreadcrumb(
            $id ? __('Edit Team') : __('New Team'),
            $id ? __('Edit Team') : __('New Team')
        );
        $resultPage->getConfig()->getTitle()->prepend(__('HelpDesk Team'));
        $resultPage->getConfig()->getTitle()->prepend($model->getId() ? $model->getName() : __('New Category'));
        return $resultPage;
    }
}
