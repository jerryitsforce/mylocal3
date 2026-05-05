<?php
namespace Branch8\CatalogRule\Controller\Adminhtml\Approval;
use Magento\Framework\Controller\ResultFactory;

class MassApprove extends \Magento\Backend\App\Action
{
    const ADMIN_RESOURCE = 'Branch8_CatalogRule::rule_approval_appr_reject';

    /**
     * @var \Magento\Framework\View\Result\PageFactory
     */
    protected $_pageFactory;

    protected $_filter;

    protected $catalogRuleApprovalCollectionFactory;

    protected $approveHelper;

    protected $catalogRuleRepository;

    /**
     * @param \Magento\Backend\App\Action\Context $context
     */
    public function __construct(
       \Magento\Backend\App\Action\Context $context,
       \Magento\Ui\Component\MassAction\Filter $filter,
       \Branch8\CatalogRule\Model\ResourceModel\CatalogRuleApproval\CollectionFactory $catalogRuleApprovalCollectionFactory,
       \Branch8\CatalogRule\Helper\Approve $approveHelper,
       \Magento\CatalogRule\Model\CatalogRuleRepository $catalogRuleRepository
    )
    {
        $this->_filter = $filter;
        $this->catalogRuleApprovalCollectionFactory = $catalogRuleApprovalCollectionFactory;
        $this->approveHelper = $approveHelper;
        $this->catalogRuleRepository = $catalogRuleRepository;
        return parent::__construct($context);
    }

    /**
     * Index action
     *
     * @return \Magento\Framework\View\Result\Page
     */
    public function execute()
    {
        $collection = $this->_filter->getCollection($this->catalogRuleApprovalCollectionFactory->create());
        $successCnt = 0;
        foreach($collection as $_apprModel){
            try{
                if($_apprModel->getStatus() != \Branch8\CatalogRule\Model\Config\Source\ApproveStatus::STATUS_UNDER_REVIEW){
                    continue;
                }
                if($_apprModel->getPostType() == \Branch8\CatalogRule\Model\Config\Source\ApproveType::TYPE_NEW){
                    /** Approve new rule*/
                    $this->approveHelper->approveNewRule($_apprModel);
                }else if($_apprModel->getPostType() == \Branch8\CatalogRule\Model\Config\Source\ApproveType::TYPE_EDIT){
                    /** Approve Edit */
                    $this->approveHelper->approveEditRule($_apprModel);
                }else if($_apprModel->getPostType() == \Branch8\CatalogRule\Model\Config\Source\ApproveType::TYPE_STAGING){
                    /** Approve staging */
                    $this->approveHelper->approveStaging($_apprModel);
                }
                
                $successCnt ++;
                
            }catch(\Exception $e){
                $ruleId = $_apprModel->getCatalogruleId();
                $rule = $this->catalogRuleRepository->get($ruleId);
                $this->messageManager->addErrorMessage(__('Approved fail %1', $rule->getName()));
            }
        }

        if($successCnt){
            $this->messageManager->addSuccessMessage(__('Approved successfully %1 records', $successCnt));
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
