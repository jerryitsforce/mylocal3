<?php
namespace Branch8\CatalogRule\Controller\Adminhtml\Approval;

class Detail extends \Magento\Backend\App\Action
{
    const ADMIN_RESOURCE = 'Branch8_CatalogRule::rule_approval_list';

    const PAGE_TITLE = 'Catalog rule changes';

    /**
     * @var \Magento\Framework\View\Result\PageFactory
     */
    protected $_pageFactory;

    protected $catalogRuleApprovalFactory;

    protected $catalogRuleRepository;

    protected $registry;

    /**
     * @param \Magento\Backend\App\Action\Context $context
     */
    public function __construct(
       \Magento\Backend\App\Action\Context $context,
       \Magento\Framework\View\Result\PageFactory $pageFactory,
       \Branch8\CatalogRule\Model\CatalogRuleApprovalFactory $catalogRuleApprovalFactory,
       \Magento\CatalogRule\Model\CatalogRuleRepository $catalogRuleRepository,
       \Magento\Framework\Registry $registry
    )
    {
        $this->_pageFactory = $pageFactory;
        $this->catalogRuleApprovalFactory = $catalogRuleApprovalFactory;
        $this->catalogRuleRepository = $catalogRuleRepository;
        $this->registry = $registry;
        return parent::__construct($context);
    }

    /**
     * Index action
     *
     * @return \Magento\Framework\View\Result\Page
     */
    public function execute()
    {
        $ruleId = $this->getRequest()->getParam('id');
        $rule = $this->catalogRuleRepository->get($ruleId);
        $this->registry->register('current_promo_catalog_rule', $rule);
        /** @var \Magento\Framework\View\Result\Page $resultPage */
        $resultPage = $this->_pageFactory->create();
        $resultPage->setActiveMenu(static::ADMIN_RESOURCE);
        $resultPage->addBreadcrumb(__(static::PAGE_TITLE), __(static::PAGE_TITLE));
        $resultPage->getConfig()->getTitle()->prepend(__(static::PAGE_TITLE));
        /** Load Aprroval  */
        $apprId = $this->getRequest()->getParam('approval_id');
        if(!$apprId){
            $this->messageManager->addErrorMessage(__('Invalid request.'));
            return $this->_redirect('promocatalog/approval/index')->sendResponse();
        }
        $apprModel = $this->catalogRuleApprovalFactory->create()
            ->load($apprId);
        if(!$apprModel->getId() || $apprModel->getStatus() != \Branch8\CatalogRule\Model\Config\Source\ApproveStatus::STATUS_UNDER_REVIEW){
            $this->messageManager->addErrorMessage(__('Invalid request status.'));
            return $this->_redirect('promocatalog/approval/index')->sendResponse();
        }
        $layout = $resultPage->getLayout();
        $currentRuleBlock = $layout->getBlock('current_rule');
        $currentRuleBlock->setData('apprModel', $apprModel);

        // $ruleId = $apprModel->getRuleId();
        
        return $resultPage;
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
