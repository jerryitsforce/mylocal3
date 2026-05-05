<?php
namespace Branch8\CatalogRule\Controller\Adminhtml\History;

class CompareVersion extends \Magento\Backend\App\Action
{
    const ADMIN_RESOURCE = 'Branch8_CatalogRule::rule_change_history';

    /**
     * @var \Magento\Framework\View\Result\PageFactory
     */
    protected $_pageFactory;

    protected $registry;

    protected $catalogRuleChangeLogCollectionFactory;

    protected $_filter;

    protected $catalogRuleFactory;
    /*
     * @param \Magento\Backend\App\Action\Context $context
     */
    public function __construct(
       \Magento\Backend\App\Action\Context $context,
       \Magento\Framework\View\Result\PageFactory $pageFactory,
       \Magento\Ui\Component\MassAction\Filter $filter,
       \Magento\Framework\Registry $registry,
       \Branch8\CatalogRule\Model\ResourceModel\CatalogRuleChangeLog\CollectionFactory $catalogRuleChangeLogCollectionFactory,
       \Magento\CatalogRule\Model\RuleFactory $catalogRuleFactory
    )
    {
        $this->_pageFactory = $pageFactory;
        $this->_filter = $filter;
        $this->catalogRuleChangeLogCollectionFactory = $catalogRuleChangeLogCollectionFactory;
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

        $collection = $this->_filter->getCollection($this->catalogRuleChangeLogCollectionFactory->create());
        $collection->getSelect()->order('entity_id asc');
        $firstVersion = $collection->getFirstItem();
        $approvedData = $firstVersion->getData('approved_data');
        $this->registry->register('versionData', $approvedData);

        $secondItemId = $collection->getLastItem()->getId();

        $resultPage = $this->_pageFactory->create();
        $resultPage->getConfig()->getTitle()->prepend(__('Catalog rule Compare version of "%1"', $emptyModel->getName()));
        

        $layout = $resultPage->getLayout();
        $contentBlock = $layout->getBlock('catalogruleVersion1');
        $contentBlock->setData('history_record', $firstVersion->getData());
        $contentBlock->setData('2ndItemId', $secondItemId);

        

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
