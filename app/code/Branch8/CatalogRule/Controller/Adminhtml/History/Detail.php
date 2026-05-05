<?php
namespace Branch8\CatalogRule\Controller\Adminhtml\History;

class Detail extends \Magento\Backend\App\Action
{
    const ADMIN_RESOURCE = 'Branch8_CatalogRule::rule_change_history';

    /**
     * @var \Magento\Framework\View\Result\PageFactory
     */
    protected $_pageFactory;

    protected $registry;

    protected $catalogRuleChangeLogFactory;

    protected $catalogRuleFactory;
    /*
     * @param \Magento\Backend\App\Action\Context $context
     */
    public function __construct(
       \Magento\Backend\App\Action\Context $context,
       \Magento\Framework\View\Result\PageFactory $pageFactory,
       \Magento\Framework\Registry $registry,
       \Branch8\CatalogRule\Model\CatalogRuleChangeLogFactory $catalogRuleChangeLogFactory,
       \Magento\CatalogRule\Model\RuleFactory $catalogRuleFactory
    )
    {
        $this->_pageFactory = $pageFactory;
        $this->catalogRuleChangeLogFactory = $catalogRuleChangeLogFactory;
        $this->registry = $registry;
        $this->catalogRuleFactory = $catalogRuleFactory;
        return parent::__construct($context);
    }

    /**
     * Index action
     *
     * @return \Magento\Framework\View\Result\Page
     */
    public function execute()
    {
        $catalogRuleId = $this->getRequest()->getParam('id');
        $emptyModel = $this->catalogRuleFactory->create()->load($catalogRuleId);
        $this->registry->register('current_promo_catalog_rule', $emptyModel);

        $logId = $this->getRequest()->getParam('log_id');
        $logModel = $this->catalogRuleChangeLogFactory->create()->load($logId);
        if(!$logModel->getId() || $logModel->getCatalogruleId() != $catalogRuleId){
            return $this->_redirect('promocatalog/history/index', ['catalogrule_id' => $catalogRuleId])->sendResponse();
        }
        
        $approvedData = $logModel->getData('old_data');
        $this->registry->register('versionData', $approvedData);

        $this->registry->register('catalogrule_id', $logModel->getCatalogruleId());

        $resultPage = $this->_pageFactory->create();
        $resultPage->getConfig()->getTitle()->prepend(__('Catalog Rule change detail "%1"', $emptyModel->getName()));
        

        $layout = $resultPage->getLayout();
        $contentBlock = $layout->getBlock('catalogruleVersion1');
        $contentBlock->setData('history_record', $logModel->getData());

        

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
