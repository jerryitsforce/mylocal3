<?php
namespace Branch8\CatalogRule\Plugin;

class DataProvider
{
    protected $_request;

    protected $catalogRuleApprovalFactory;

    protected $registry;

    public function __construct(
        \Magento\Framework\App\RequestInterface $request,
        \Branch8\CatalogRule\Model\CatalogRuleApprovalFactory $catalogRuleApprovalFactory,
        \Magento\Framework\Registry $registry
    )
    {
        $this->_request = $request;
        $this->catalogRuleApprovalFactory = $catalogRuleApprovalFactory;
        $this->registry = $registry;
    }

    public function afterGetData($subject, $result){

        $dataSourceName = $subject->getName();
        if($dataSourceName == 'catalog_rule_form_approval_data_source'){
            $apprId = $this->_request->getParam('approval_id');
            /** Load approval record */
            $apprModel = $this->catalogRuleApprovalFactory->create()
                ->load((int)$apprId);
            $postData = $apprModel->getPostData();
            $ruleId = $apprModel->getCatalogruleId();
            $result[$ruleId] = json_decode($postData, true);
            return $result;
        }

        if($dataSourceName == 'catalog_rule_form_compare1_data_source' || $dataSourceName == 'catalog_rule_form_compare2_data_source'){
            /** Load approval record */
            $versionData = $this->registry->registry('versionData');
            $versionDataArr = json_decode($versionData, true);
            if(isset($versionDataArr['rule_id'])){
                $ruleId = $versionDataArr['rule_id'];
            }else{
                $ruleId = $this->registry->registry('catalogrule_id');
            }
            $result[$ruleId] = $versionDataArr;
            return $result;
        }

        return $result;
        
    }
}
