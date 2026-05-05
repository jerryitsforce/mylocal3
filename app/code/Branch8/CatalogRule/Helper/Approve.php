<?php
namespace Branch8\CatalogRule\Helper;

use Magento\Framework\Filter\FilterInput;
use Magento\Staging\Model\Entity\Update\Save as StagingUpdateSave;

class Approve extends \Magento\Framework\App\Helper\AbstractHelper
{

    protected $approvalFactory;

    protected $catalogRuleRepository;

    protected $adminSession;

    protected $timezone;

    protected $catalogRuleChangeLogFactory;

    protected $_objectManager;

    protected $_dateFilter;

    protected $stagingUpdateSave;

    public function __construct(
        \Magento\Framework\App\Helper\Context $context,
        \Branch8\CatalogRule\Model\ResourceModel\CatalogRuleApproval\CollectionFactory $approvalFactory,
        \Magento\CatalogRule\Model\CatalogRuleRepository $catalogRuleRepository,
        \Magento\Backend\Model\Auth\Session $adminSession,
        \Magento\Framework\Stdlib\DateTime\TimezoneInterface $timezone,
        \Branch8\CatalogRule\Model\CatalogRuleChangeLogFactory $catalogRuleChangeLogFactory,
        // \Magento\Framework\App\ObjectManager $objectManager,
        \Magento\Framework\Stdlib\DateTime\Filter\Date $dateFilter,
        StagingUpdateSave $stagingUpdateSave
    )
    {
        $this->approvalFactory = $approvalFactory;
        $this->catalogRuleRepository = $catalogRuleRepository;
        $this->adminSession = $adminSession;
        $this->timezone = $timezone;
        $this->catalogRuleChangeLogFactory = $catalogRuleChangeLogFactory;
        $this->_objectManager = \Magento\Framework\App\ObjectManager::getInstance();
        $this->_dateFilter = $dateFilter;
        $this->stagingUpdateSave = $stagingUpdateSave;
        parent::__construct($context);
    }

    public function approveNewRule($apprModel){
        $apprId = $apprModel->getId();
        $postData = $apprModel->getPostData();
        $postDataArr = json_decode($postData, true);
        $ruleId = $apprModel->getCatalogruleId();
        $isActive = $postDataArr['is_active'];
        $ruleModel = $this->catalogRuleRepository->get($ruleId);

        /** Update is active if the is_active = 1 when create new rule */
        if($isActive == 1){
            $ruleModel->setIsActive(1);
            $this->catalogRuleRepository->save($ruleModel);
        }

        $adminUser = $this->adminSession->getUser();
        $approver = $adminUser->getUserName();
        $updatedAt = $this->timezone->convertConfigTimeToUtc($this->timezone->date());

        /** Create version */
        $ruleData = $ruleModel->getData();
        if(!is_array($ruleData['seller_ids'])){
            if($ruleData['seller_ids'] != null){
                $ruleData['seller_ids'] = explode(',', $ruleData['seller_ids']);
            }else{
                $ruleData['seller_ids'] = [];
            }
            
        }
        $versionData = [
            'catalogrule_id' => $apprModel->getCatalogruleId(),
            'submitter' => $apprModel->getSubmitter(),
            'old_data' => json_encode([]),
            'approved_data' => json_encode($ruleData),
            'approver' => $approver,
            'created_at' => $updatedAt,
            'approval_id' => $apprId
        ];
        $this->catalogRuleChangeLogFactory->create()
            ->setData($versionData)
            ->save();

        /** Update approval record */
        $apprModel->setStatus(\Branch8\CatalogRule\Model\Config\Source\ApproveStatus::STATUS_APPROVED)
            ->setUpdatedAt($updatedAt)
            ->setApprover($approver)
            ->save();
    }

    public function approveEditRule($apprModel){
        $apprId = $apprModel->getId();
        $postData = $apprModel->getPostData();
        $postDataArr = json_decode($postData, true);
        $ruleId = $apprModel->getCatalogruleId();

        $ruleRepository = $this->_objectManager->get(
            \Magento\CatalogRule\Api\CatalogRuleRepositoryInterface::class
        );
        /** @var \Magento\CatalogRule\Model\Rule $model */
        $model = $this->_objectManager->create(\Magento\CatalogRule\Model\Rule::class);

        try {
            
            $data = $postDataArr;
            $fromDate = $postDataArr['from_date'];
            if (!$fromDate) {
                $data['from_date'] = $this->timezone->formatDate();
            }
            $filterValues = ['from_date' => $this->_dateFilter];
            $toDate = $postDataArr['to_date'];
            if ($toDate) {
                $filterValues['to_date'] = $this->_dateFilter;
            }
            $inputFilter = new FilterInput(
                $filterValues,
                [],
                $data
            );
            $data = $inputFilter->getUnescaped();

            $model = $ruleRepository->get($ruleId);
            $oldData = $model->getData();
            
            if(!is_array($oldData['seller_ids'])){
                if($oldData['seller_ids'] === null){
                    $oldData['seller_ids'] = [];
                }else{
                    $oldData['seller_ids'] = explode(',', $oldData['seller_ids']);
                }
                
            }

            // unset($data['conditions_serialized']);
            // unset($data['actions_serialized']);
            $model->loadPost($data);
            $ruleRepository->save($model);

            if ($model->isRuleBehaviorChanged()) {
                $this->_objectManager
                    ->create(\Magento\CatalogRule\Model\Flag::class)
                    ->loadSelf()
                    ->setState(1)
                    ->save();
            }

            $updatedAt = $this->timezone->convertConfigTimeToUtc($this->timezone->date());
            /** Create version */
            $adminUser = $this->adminSession->getUser();
            $approver = $adminUser->getUserName();
            $approvedData = $model->getData();
            
            if(!is_array($approvedData['seller_ids'])){
                if($approvedData['seller_ids'] === null){
                    $approvedData['seller_ids'] = [];
                }else{
                    $approvedData['seller_ids'] = explode(',', $approvedData['seller_ids']);
                }
            }
            $versionData = [
                'catalogrule_id' => $ruleId,
                'submitter' => $apprModel->getSubmitter(),
                'old_data' => json_encode($oldData),
                'approved_data' => json_encode($approvedData),
                'approver' => $approver,
                'created_at' => $updatedAt,
                'approval_id' => $apprId
            ];
            $this->catalogRuleChangeLogFactory->create()
                ->setData($versionData)
                ->save();

            /** Update approval record */
            $apprModel->setStatus(\Branch8\CatalogRule\Model\Config\Source\ApproveStatus::STATUS_APPROVED)
                ->setUpdatedAt($updatedAt)
                ->setApprover($approver)
                ->save();

        }catch(\Exception $e){
            
        }
    }

    public function approveStaging($apprModel){
        $apprId = $apprModel->getId();
        $ruleId = $apprModel->getCatalogruleId();
        $postData = $apprModel->getPostData();
        $postDataArr = json_decode($postData, true);

        $ruleRepository = $this->_objectManager->get(
            \Magento\CatalogRule\Api\CatalogRuleRepositoryInterface::class
        );
        $model = $ruleRepository->get($ruleId);
        $oldData = $model->getData();
        if(!is_array($oldData['seller_ids'])){
            if($oldData['seller_ids'] === null){
                $oldData['seller_ids'] = [];
            }else{
                $oldData['seller_ids'] = explode(',', $oldData['seller_ids']);
            }
        }

        $stgResult = $this->stagingUpdateSave->execute(
            [
                'entityId' => $ruleId,
                'stagingData' => $postDataArr['staging'],
                'entityData' => $postDataArr

            ]
        );
        // var_dump($stgResult);die;
        $updatedAt = $this->timezone->convertConfigTimeToUtc($this->timezone->date());

        /** Create version */
        
        
        $adminUser = $this->adminSession->getUser();
        $approver = $adminUser->getUserName();
        $approvedData = $postDataArr;
        if(isset($approvedData['seller_ids'])){
            if($approvedData['seller_ids'] === null){
                $approvedData['seller_ids'] = [];
            }else{
                $approvedData['seller_ids'] = explode(',', $approvedData['seller_ids']);
            }
        }else{
            $approvedData['seller_ids'] = [];
        }
        $versionData = [
            'catalogrule_id' => $ruleId,
            'submitter' => $apprModel->getSubmitter(),
            'old_data' => json_encode($oldData),
            'approved_data' => json_encode($approvedData),
            'approver' => $approver,
            'created_at' => $updatedAt,
            'approval_id' => $apprId
        ];
        $this->catalogRuleChangeLogFactory->create()
            ->setData($versionData)
            ->save();

        
        $adminUser = $this->adminSession->getUser();
        $approver = $adminUser->getUserName();
        /** Update approval record */
        $apprModel->setStatus(\Branch8\CatalogRule\Model\Config\Source\ApproveStatus::STATUS_APPROVED)
            ->setUpdatedAt($updatedAt)
            ->setApprover($approver)
            ->save();

    }
}
