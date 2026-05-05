<?php
namespace Branch8\CatalogRule\Rewrite\Block\Adminhtml\Promo\Catalogrule\Edit\Tab;

use Magento\Backend\Block\Template\Context;
use Magento\Framework\Registry;
use Magento\Framework\Data\FormFactory;
use Magento\Rule\Block\Conditions as BlockConditions;
use Magento\Backend\Block\Widget\Form\Renderer\Fieldset;
use Branch8\CatalogRule\Model\CatalogRuleApprovalFactory;
use Magento\CatalogRule\Model\Rule;

class Conditions extends \Magento\CatalogRule\Block\Adminhtml\Promo\Catalog\Edit\Tab\Conditions
{

    protected $catalogRuleApprovalFactory;

    protected $_rule;

    protected $serializer;

    public function __construct(
        Context $context, Registry $registry, FormFactory $formFactory, 
        BlockConditions $conditions, Fieldset $rendererFieldset, 
        CatalogRuleApprovalFactory $catalogRuleApprovalFactory,
        Rule $rule,
        \Magento\Framework\Serialize\Serializer\Json $serializer,
        array $data = [])
    {
        $this->catalogRuleApprovalFactory = $catalogRuleApprovalFactory;
        $this->_rule = $rule;
        $this->serializer = $serializer;
        return parent::__construct($context, $registry, $formFactory, $conditions, $rendererFieldset, $data);
    }

    protected function _prepareForm()
    {
        $model = $this->_coreRegistry->registry('current_promo_catalog_rule');
        $apprId = $this->getRequest()->getParam('approval_id');
        $fullActionName = $this->getRequest()->getFullActionName();
        if($apprId && $fullActionName == 'promocatalog_approval_detailApproval'){
            $obj = \Magento\Framework\App\ObjectManager::getInstance();
            $apprModel = $this->catalogRuleApprovalFactory->create()->load($apprId);
            $postData = $apprModel->getPostData();
            $postDataArr = json_decode($postData, true);
            $postDataArr = $this->generateConditions($postDataArr);

            $rule = $obj->create(\Magento\CatalogRule\Model\RuleFactory::class);
            $model = $rule->create()->setData($postDataArr);
        }else if($fullActionName == 'promocatalog_history_compareVersion' || $fullActionName == 'promocatalog_history_compareVersion2'){
            $approvedData = $this->_coreRegistry->registry('versionData');
            $approvedDataArr = json_decode($approvedData, true);
            $approvedDataArr = $this->generateConditions($approvedDataArr);

            $obj = \Magento\Framework\App\ObjectManager::getInstance();
            $rule = $obj->create(\Magento\CatalogRule\Model\RuleFactory::class);
            $model = $rule->create()->setData($approvedDataArr);
        }else if($fullActionName == 'promocatalog_history_detail' || $fullActionName == 'promocatalog_history_detail2'){
            $approvedData = $this->_coreRegistry->registry('versionData');
            $approvedDataArr = json_decode($approvedData, true);
            $approvedDataArr = $this->generateConditions($approvedDataArr);

            $obj = \Magento\Framework\App\ObjectManager::getInstance();
            $rule = $obj->create(\Magento\CatalogRule\Model\RuleFactory::class);
            $model = $rule->create()->setData($approvedDataArr);
        }
        
        /** @var \Magento\Framework\Data\Form $form */
        $form = $this->addTabToForm($model);
        $this->setForm($form);

        return $this;
    }


    protected function generateConditions($postDataArr){
        if (isset($postDataArr['rule'])) {
            $postDataArr['conditions'] = $postDataArr['rule']['conditions'];
            unset($postDataArr['rule']);
            unset($postDataArr['conditions_serialized']);
            unset($postDataArr['actions_serialized']);
            $emptyModel = $this->_rule;
            $emptyModel->loadPost($postDataArr);
            
            $conditions = $emptyModel->getConditions()->asArray();
            $conditionsSerizlized = $this->serializer->serialize($conditions);
            $postDataArr['conditions_serialized'] = $conditionsSerizlized;
            $actions = $emptyModel->getActions()->asArray();
            $actionSerialized = $this->serializer->serialize($actions);
            $postDataArr['actions_serialized'] = $actionSerialized;
        }

        return $postDataArr;
    }
}
