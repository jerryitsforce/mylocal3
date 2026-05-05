<?php
namespace Branch8\CatalogRule\Plugin\Staging;

class Save
{

    protected $timezone;

    protected $adminSession;

    protected $catalogRuleApprovalFactory;

    protected $messageManager;

    protected $jsonFactory;

    protected $modifyHelper;

    protected $crHelperData;

    public function __construct(
        \Magento\Framework\Stdlib\DateTime\TimezoneInterface $timezone,
        \Magento\Backend\Model\Auth\Session $adminSession,
        \Branch8\CatalogRule\Model\CatalogRuleApprovalFactory $catalogRuleApprovalFactory,
        \Magento\Framework\Message\ManagerInterface $messageManager,
        \Magento\Staging\Controller\Result\JsonFactory $jsonFactory,
        \Branch8\CatalogRule\Helper\Modify $modifyHelper,
        \Branch8\CatalogRule\Helper\Data $crHelperData
    )
    {
        $this->timezone = $timezone;
        $this->adminSession = $adminSession;
        $this->catalogRuleApprovalFactory = $catalogRuleApprovalFactory;
        $this->messageManager = $messageManager;
        $this->jsonFactory = $jsonFactory;
        $this->modifyHelper = $modifyHelper;
        $this->crHelperData = $crHelperData;
    }

    public function aroundExecute($subject, $process){
        if(!$this->crHelperData->isApprovalEnable()){
            return $process();
        }
        
        $ruleId = $subject->getRequest()->getParam('rule_id');

        if(!$this->modifyHelper->canEditCatalogRule((int)$ruleId)){
            $error = true;
            $this->messageManager->addErrorMessage(__('Your rule is pending approval, you cannot edit it at this time.'));
            return $this->jsonFactory->create([], ['error' => $error]);
        }
        try{
            $postData = $subject->getRequest()->getPostValue();
            
            $postType = \Branch8\CatalogRule\Model\Config\Source\ApproveType::TYPE_STAGING;
            $adminUser = $this->adminSession->getUser();
            $submitter = $adminUser->getUserName();
            $status = \Branch8\CatalogRule\Model\Config\Source\ApproveStatus::STATUS_UNDER_REVIEW;
            $createdAt = $this->timezone->convertConfigTimeToUtc($this->timezone->date());
            $this->catalogRuleApprovalFactory->create()
                ->setData(
                    [
                        'entity_id' => NULL,
                        'catalogrule_id' => $ruleId,
                        'post_data' => json_encode($postData),
                        'post_type' => $postType,
                        'submitter' => $submitter,
                        'approver' => NULL,
                        'status' => $status,
                        'created_at' => $createdAt,
                        'updated_at' => NULL,
                        'reject_reason' => NULL
                    ]
                )->save();
            $error = false;
            $this->messageManager->addSuccessMessage(__('Save successfully.'));
        }catch(\Exception $e){
            $this->messageManager->addErrorMessage(__('Saving failed.'));
            $error = true;
        }
        return $this->jsonFactory->create([], ['error' => $error]);
    }
}