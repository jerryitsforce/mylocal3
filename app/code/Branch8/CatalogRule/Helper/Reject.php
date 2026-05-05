<?php
namespace Branch8\CatalogRule\Helper;


class Reject extends \Magento\Framework\App\Helper\AbstractHelper
{

    protected $catalogRuleRepository;

    protected $catalogRuleChangeLogFactory;

    public function __construct(
        \Magento\Framework\App\Helper\Context $context,
        \Magento\CatalogRule\Api\CatalogRuleRepositoryInterface $catalogRuleRepository,
        \Branch8\CatalogRule\Model\CatalogRuleChangeLogFactory $catalogRuleChangeLogFactory
    )
    {
        parent::__construct($context);
        $this->catalogRuleRepository = $catalogRuleRepository;
        $this->catalogRuleChangeLogFactory = $catalogRuleChangeLogFactory;
    }

    public function addHistoryRejectLog($apprModel, $approver, $updatedAt){
        $ruleId = $apprModel->getCatalogruleId();
        $apprId = $apprModel->getId();
        $ruleRepository = $this->catalogRuleRepository;
        $model = $ruleRepository->get($ruleId);
        $oldData = $model->getData();
        if(!is_array($oldData['seller_ids'])){
            if($oldData['seller_ids'] === null){
                $oldData['seller_ids'] = [];
            }else{
                $oldData['seller_ids'] = explode(',', $oldData['seller_ids']);
            }
            
        }
        $postedData = $apprModel->getPostData();
        $approvedData = json_decode($postedData, true);
        if(!isset($approvedData['seller_ids'])){
            $approvedData['seller_ids'] = [];
        }else{
            if(!is_array($approvedData['seller_ids'])){
                if($approvedData['seller_ids'] === null){
                    $approvedData['seller_ids'] = [];
                }else{
                    $approvedData['seller_ids'] = explode(',', $approvedData['seller_ids']);
                }
            }
        }
        
        
        $versionData = [
            'catalogrule_id' => $ruleId,
            'submitter' => $apprModel->getSubmitter(),
            'old_data' => json_encode($oldData),
            'approved_data' => json_encode($approvedData),
            'approver' => $approver,
            'created_at' => $updatedAt,
            'approval_id' => $apprId,
            'reject_reason' => $apprModel->getRejectReason(),
            'status' => \Branch8\CatalogRule\Model\Config\Source\ApproveStatus::STATUS_REJECTED
        ];
        try{
            $this->catalogRuleChangeLogFactory->create()
                ->setData($versionData)
                ->save();
        }catch(\Exception $e){
echo $e->getMessage();die;
        }
    }
}
