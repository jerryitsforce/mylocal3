<?php
namespace Branch8\CatalogRule\Controller\Adminhtml\History;

class CompareVersion2 extends \Magento\Backend\App\Action
{
    const ADMIN_RESOURCE = 'Branch8_CatalogRule::rule_change_history';

    /**
     * @var \Magento\Framework\View\Result\PageFactory
     */
    protected $_pageFactory;

    protected $registry;

    protected $catalogRuleFactory;

    protected $catalogRuleChangeLogFactory;
    /*
     * @param \Magento\Backend\App\Action\Context $context
     */
    public function __construct(
       \Magento\Backend\App\Action\Context $context,
       \Magento\Framework\View\Result\PageFactory $pageFactory,
       \Magento\Framework\Registry $registry,
       \Magento\CatalogRule\Model\RuleFactory $catalogRuleFactory,
        \Branch8\CatalogRule\Model\CatalogRuleChangeLogFactory $catalogRuleChangeLogFactory
    )
    {
        $this->_pageFactory = $pageFactory;
        $this->registry = $registry;
        $this->catalogRuleFactory = $catalogRuleFactory;
        $this->catalogRuleChangeLogFactory = $catalogRuleChangeLogFactory;
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
        $approvedData = $logModel->getData('approved_data');
        $this->registry->register('versionData', $approvedData);

        $resultPage = $this->_pageFactory->create();

        $layout = $resultPage->getLayout();
        $contentBlock = $layout->getBlock('compare.version2');
        $contentBlock->setData('history_record', $logModel->getData());

        /** Prev log infor */
        $prevLogId = $this->getRequest()->getParam('prev_log_id');
        $prevLogModel = $this->catalogRuleChangeLogFactory->create()->load($prevLogId);
        $contentBlock->setData('prev_history_record', $prevLogModel->getData());

        echo $contentBlock->toHtml();die;
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
