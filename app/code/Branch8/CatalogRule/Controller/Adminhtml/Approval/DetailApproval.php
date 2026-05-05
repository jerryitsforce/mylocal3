<?php
namespace Branch8\CatalogRule\Controller\Adminhtml\Approval;

class DetailApproval extends \Magento\Backend\App\Action
{
    const ADMIN_RESOURCE = 'Branch8_CatalogRule::rule_approval_list';

    const PAGE_TITLE = 'Catalog rule changes';

    /**
     * @var \Magento\Framework\View\Result\PageFactory
     */
    protected $_pageFactory;

    protected $catalogRuleRepository;

    protected $registry;

    protected $catalogRuleApprovalFactory;

    protected $_objectManager;

    /**
     * @param \Magento\Backend\App\Action\Context $context
     */
    public function __construct(
       \Magento\Backend\App\Action\Context $context,
       \Magento\Framework\View\Result\PageFactory $pageFactory,
       \Magento\CatalogRule\Model\CatalogRuleRepository $catalogRuleRepository,
       \Branch8\CatalogRule\Model\CatalogRuleApprovalFactory $catalogRuleApprovalFactory,
       \Magento\Framework\Registry $registry,
       \Magento\Framework\ObjectManagerInterface $objectManager
    )
    {
        $this->_pageFactory = $pageFactory;
        $this->catalogRuleRepository = $catalogRuleRepository;
        $this->registry = $registry;
        $this->catalogRuleApprovalFactory = $catalogRuleApprovalFactory;
        $this->_objectManager = $objectManager;
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
        $layout = $resultPage->getLayout();
        $contentBlock = $layout->getBlock('approval_rule');
        $contentBlock->setData('current_promo_catalog_rule', $rule);

        $apprId = $this->getRequest()->getParam('approval_id');
        $apprRecord = $this->catalogRuleApprovalFactory->create()
            ->load($apprId);
        $postData = $apprRecord->getPostData();
        $postDataArr = json_decode($postData, true);

        $model = $this->_objectManager->create(\Magento\CatalogRule\Model\Rule::class);
        $apprRuleModel = $model->loadPost($postDataArr);

        $contentBlock->setData('approval_rule_model', $apprRuleModel);

        echo $contentBlock->toHtml();die;
        // return $resultPage;
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
