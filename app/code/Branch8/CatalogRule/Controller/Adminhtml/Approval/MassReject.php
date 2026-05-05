<?php
namespace Branch8\CatalogRule\Controller\Adminhtml\Approval;
use Magento\Framework\Controller\ResultFactory;

class MassReject extends \Magento\Backend\App\Action
{
    const ADMIN_RESOURCE = 'Branch8_CatalogRule::rule_approval_appr_reject';

    /**
     * @var \Magento\Framework\View\Result\PageFactory
     */
    protected $_pageFactory;

    protected $_filter;

    protected $catalogRuleApprovalCollectionFactory;

    protected $adminSession;

    protected $timezone;

    protected $catalogRuleRepository;

    protected $rejectHelper;

    /**
     * @param \Magento\Backend\App\Action\Context $context
     */
    public function __construct(
       \Magento\Backend\App\Action\Context $context,
       \Magento\Ui\Component\MassAction\Filter $filter,
       \Branch8\CatalogRule\Model\ResourceModel\CatalogRuleApproval\CollectionFactory $catalogRuleApprovalCollectionFactory,
       \Magento\Backend\Model\Auth\Session $adminSession,
       \Magento\Framework\Stdlib\DateTime\TimezoneInterface $timezone,
       \Magento\CatalogRule\Model\CatalogRuleRepository $catalogRuleRepository,
       \Branch8\CatalogRule\Helper\Reject $rejectHelper
    )
    {
        $this->_filter = $filter;
        $this->catalogRuleApprovalCollectionFactory = $catalogRuleApprovalCollectionFactory;
        $this->adminSession = $adminSession;
        $this->timezone = $timezone;
        $this->catalogRuleRepository = $catalogRuleRepository;
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
        $ruleData = $this->getRequest()->getParam('rule');
        $ruleIds = array_keys($ruleData);

        $collection = $this->catalogRuleApprovalCollectionFactory->create()
            ->addFieldToFilter('entity_id', $ruleIds);

        $adminUser = $this->adminSession->getUser();
        $approver = $adminUser->getUserName();
        $updatedAt = $this->timezone->convertConfigTimeToUtc($this->timezone->date());

        $successCnt = 0;
        foreach($collection as $_apprModel){
            try{
                if($_apprModel->getStatus() != \Branch8\CatalogRule\Model\Config\Source\ApproveStatus::STATUS_UNDER_REVIEW){
                    continue;
                }
                $_reason = $ruleData[$_apprModel->getId()];
                $_apprModel->setStatus(\Branch8\CatalogRule\Model\Config\Source\ApproveStatus::STATUS_REJECTED)
                    ->setRejectReason($_reason)
                    ->setApprover($approver)
                    ->setUpdatedAt($updatedAt)
                    ->save();

                $this->rejectHelper->addHistoryRejectLog($_apprModel, $approver, $updatedAt);

                $successCnt ++;
            }catch(\Exception $e){
                $ruleId = $_apprModel->getCatalogruleId();
                $rule = $this->catalogRuleRepository->get($ruleId);
                $this->messageManager->addErrorMessage(__('Rejected fail "%1"', $rule->getName()));
            }
            
        }

        if($successCnt){
            $this->messageManager->addSuccessMessage(__('Rejected successfully "%1" records', $successCnt));
        }

        return $this->resultFactory->create(ResultFactory::TYPE_REDIRECT)->setPath('promocatalog/approval/index');
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
