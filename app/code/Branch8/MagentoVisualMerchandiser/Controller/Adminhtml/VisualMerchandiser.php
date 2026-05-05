<?php
declare(strict_types=1);

namespace Branch8\MagentoVisualMerchandiser\Controller\Adminhtml;

use Magento\Backend\App\Action;

abstract class VisualMerchandiser extends \Magento\Backend\App\Action
{

    /**
     * Core registry
     *
     * @var \Magento\Framework\Registry
     */
    protected $_coreRegistry = null;

    /**
     * @var \Magento\Framework\Stdlib\DateTime\Filter\Date
     */
    protected $_dateFilter;
    private \Branch8\MagentoVisualMerchandiser\Model\AdvanceRuleFactory $advanceRuleFactory;

    /**
     * @param Action\Context $context
     * @param \Magento\Framework\Registry $coreRegistry
     * @param \Magento\Framework\Stdlib\DateTime\Filter\Date $dateFilter
     * @param \Branch8\MagentoVisualMerchandiser\Model\AdvanceRuleFactory $advanceRuleFactory
     */
    public function __construct(
        \Magento\Backend\App\Action\Context                         $context,
        \Magento\Framework\Registry                                 $coreRegistry,
        \Magento\Framework\Stdlib\DateTime\Filter\Date              $dateFilter,
        \Branch8\MagentoVisualMerchandiser\Model\AdvanceRuleFactory $advanceRuleFactory,
    )
    {
        parent::__construct($context);
        $this->_coreRegistry = $coreRegistry;
        $this->_dateFilter = $dateFilter;
        $this->advanceRuleFactory = $advanceRuleFactory;
    }

    /**
     * Generate elements for condition forms
     *
     * @param string $prefix Form prefix
     * @return void
     */
    protected function conditionsHtmlAction($prefix)
    {
        $form = $this->getRequest()->getParam('form', 'category_form');
        $id = $this->getRequest()->getParam('id');
        $typeArr = explode('|', str_replace('-', '/', $this->getRequest()->getParam('type')));
        $type = $typeArr[0];

        $model = $this->_objectManager->create(
            $type
        )->setId(
            $id
        )->setType(
            $type
        )->setRule(
            $this->advanceRuleFactory->create()
        )->setPrefix(
            $prefix
        );
        if (!empty($typeArr[1])) {
            $model->setAttribute($typeArr[1]);
        }

        if ($model instanceof \Magento\Rule\Model\Condition\AbstractCondition) {
            $model->setJsFormObject($form);
            $model->setData('form_name', 'category_form');
            $html = $model->asHtmlRecursive();
        } else {
            $html = '';
        }
        $this->getResponse()->setBody($html);
    }
}
