<?php
namespace Branch8\CatalogRule\Controller\Adminhtml\Approval;

class Approve extends \Magento\Backend\App\Action 
{
    const ADMIN_RESOURCE = 'Branch8_CatalogRule::rule_approval_appr_reject';


    /**
     * @var \Magento\Framework\View\Result\PageFactory
     */
    protected $_pageFactory;

    protected $catalogRuleApprovalFactory;

    protected $approveHelper;

    /**
     * @param \Magento\Backend\App\Action\Context $context
     */
    public function __construct(
       \Magento\Backend\App\Action\Context $context,
       \Branch8\CatalogRule\Model\CatalogRuleApprovalFactory $catalogRuleApprovalFactory,
       \Branch8\CatalogRule\Helper\Approve $approveHelper
    )
    {
        $this->catalogRuleApprovalFactory = $catalogRuleApprovalFactory;
        $this->approveHelper = $approveHelper;
        return parent::__construct($context);
    }

    /**
     * Index action
     *
     * @return \Magento\Framework\View\Result\Page
     */
    public function execute()
    {
        // if (!$this->_formKeyValidator->validate($this->getRequest())) {
        //     $this->messageManager->addErrorMessage(__('Invalid request.'));
        //     return $this->_redirect('promocatalog/approval/index')->sendResponse();
        // }
        $apprId = $this->getRequest()->getParam('approval_id');
        $apprModel = $this->catalogRuleApprovalFactory->create()
            ->load($apprId);
        if(!$apprModel->getId()){
            $this->messageManager->addErrorMessage(__('Invalid request.'));
            return $this->_redirect('promocatalog/approval/index')->sendResponse();
        }

        if($apprModel->getStatus() != \Branch8\CatalogRule\Model\Config\Source\ApproveStatus::STATUS_UNDER_REVIEW){
            $this->messageManager->addErrorMessage(__('The request has been updated.'));
            return $this->_redirect('promocatalog/approval/index')->sendResponse();
        }

        $postData = $apprModel->getPostData();
        $postDataArr = json_decode($postData, true);
        if($apprModel->getPostType() == \Branch8\CatalogRule\Model\Config\Source\ApproveType::TYPE_NEW){
            /** Approve new rule*/
            $this->approveHelper->approveNewRule($apprModel);
        }else if($apprModel->getPostType() == \Branch8\CatalogRule\Model\Config\Source\ApproveType::TYPE_EDIT){
            /** Approve Edit */
            $this->approveHelper->approveEditRule($apprModel);
        }else if($apprModel->getPostType() == \Branch8\CatalogRule\Model\Config\Source\ApproveType::TYPE_STAGING){
            /** Approve staging */
            $this->approveHelper->approveStaging($apprModel);
        }

        $this->messageManager->addSuccessMessage('Approved successfully.');
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
