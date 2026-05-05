<?php
namespace Branch8\CatalogRule\Controller\Adminhtml\History;

class Index extends \Magento\Backend\App\Action
{
    const ADMIN_RESOURCE = 'Branch8_CatalogRule::rule_change_history';

    const PAGE_TITLE = 'Page Title';

    /**
     * @var \Magento\Framework\View\Result\PageFactory
     */
    protected $_pageFactory;

    protected $registry;

    protected $catalogRuleRepository;
    /**
     * @param \Magento\Backend\App\Action\Context $context
     */
    public function __construct(
       \Magento\Backend\App\Action\Context $context,
       \Magento\Framework\View\Result\PageFactory $pageFactory,
       \Magento\Framework\Registry $registry,
       \Magento\CatalogRule\Model\CatalogRuleRepository $catalogRuleRepository
    )
    {
        $this->_pageFactory = $pageFactory;
        $this->registry = $registry;
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
        /** @var \Magento\Framework\View\Result\Page $resultPage */
        $resultPage = $this->_pageFactory->create();
        $resultPage->setActiveMenu(static::ADMIN_RESOURCE);
        $resultPage->addBreadcrumb(__(static::PAGE_TITLE), __(static::PAGE_TITLE));

        $ruleId = $this->getRequest()->getParam('catalogrule_id');
        $rule = $this->catalogRuleRepository->get($ruleId);
        $resultPage->getConfig()->getTitle()->prepend(__('Catalog rule Histoy "%1"', $rule->getName()));
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
