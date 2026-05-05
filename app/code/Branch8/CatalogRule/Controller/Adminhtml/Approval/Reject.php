<?php
namespace Branch8\CatalogRule\Controller\Adminhtml\Approval;

class Reject extends \Magento\Backend\App\Action 
{
    const ADMIN_RESOURCE = 'Branch8_CatalogRule::rule_approval_appr_reject';


    /**
     * @var \Magento\Framework\View\Result\PageFactory
     */
    protected $_pageFactory;

    protected $catalogRuleApprovalFactory;

    protected $adminSession;

    protected $timezone;

    protected $rejectHelper;

    /**
     * @param \Magento\Backend\App\Action\Context $context
     */
    public function __construct(
       \Magento\Backend\App\Action\Context $context,
       \Branch8\CatalogRule\Model\CatalogRuleApprovalFactory $catalogRuleApprovalFactory,
       \Magento\Backend\Model\Auth\Session $adminSession,
       \Magento\Framework\Stdlib\DateTime\TimezoneInterface $timezone,
       \Branch8\CatalogRule\Helper\Reject $rejectHelper
    )
    {
        $this->catalogRuleApprovalFactory = $catalogRuleApprovalFactory;
        $this->adminSession = $adminSession;
        $this->timezone = $timezone;
        $this->rejectHelper = $rejectHelper;
        return parent::__construct($context);
    }

    /**
     * Index action
     *
     * @return \Magento\Framework\View\Result\Page
     */
    public function execute()
    {
        if (!$this->_formKeyValidator->validate($this->getRequest())) {
            $this->messageManager->addErrorMessage(__('Invalid request.'));
            return $this->_redirect('promocatalog/approval/index')->sendResponse();
        }
        $apprId = $this->getRequest()->getParam('approval_id');
        $apprModel = $this->catalogRuleApprovalFactory->create()
            ->load($apprId);
        if(!$apprModel->getId()){
            $this->messageManager->addErrorMessage(__('Invalid request.'));
            return $this->_redirect('promocatalog/approval/index')->sendResponse();
        }

        if($apprModel->getStatus() != \Branch8\CatalogRule\Model\Config\Source\ApproveStatus::STATUS_UNDER_REVIEW){
            $this->messageManager->addSuccessMessage(__('The request has been updated.'));
            return $this->_redirect('promocatalog/approval/index')->sendResponse();
        }

        $adminUser = $this->adminSession->getUser();
        $approver = $adminUser->getUserName();
        $reason = $this->getRequest()->getParam('reject_reason');
        $updatedAt = $this->timezone->convertConfigTimeToUtc($this->timezone->date());
        $apprModel->setStatus(\Branch8\CatalogRule\Model\Config\Source\ApproveStatus::STATUS_REJECTED)
            ->setRejectReason($reason)
            ->setApprover($approver)
            ->setUpdatedAt($updatedAt)
            ->save();

        $this->rejectHelper->addHistoryRejectLog($apprModel, $approver, $updatedAt);
        

        $this->messageManager->addSuccessMessage('Rejected successful.');
        return $this->_redirect('promocatalog/approval/index')->sendResponse();
    }

    /**
     * Is the user allowed to view the page.
    *
    * @return bool
    */
    protected function _isAllowed()
    {
        return $this->_authorization->isAllowed(static::ADMIN_RESOURCE);
    }
}
