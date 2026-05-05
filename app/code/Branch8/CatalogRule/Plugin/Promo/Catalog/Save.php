<?php
namespace Branch8\CatalogRule\Plugin\Promo\Catalog;

use Magento\Framework\Event\ManagerInterface as EventManagerInterface;
use Magento\Framework\Filter\FilterInput;
use Magento\Framework\Message\ManagerInterface as MessageManagerInterface;

class Save 
{
    protected $_actionFlag;

    protected $adminSession;

    protected $response;

    protected $_objectManager;

    protected $_eventManager;

    protected $localeDate;

    protected $_dateFilter;

    protected $messageManager;

    protected $dataPersistor;

    protected $adminAuthSession;

    protected $catalogRuleApprovalFactory;

    protected $modifyHelper;

    protected $redirectFactory;

    protected $serializer;

    protected $crHelperData;

    public function __construct(
        \Magento\Framework\App\ActionFlag $actionFlag,
        \Magento\Backend\Model\Session $adminSession,
        \Magento\Backend\Model\Auth\Session $adminAuthSession,
        \Magento\Framework\App\ResponseInterface $response,
        \Magento\Framework\ObjectManagerInterface $objectManager,
        EventManagerInterface $eventManager,
        \Magento\Framework\Stdlib\DateTime\TimezoneInterface $localeDate,
        \Magento\Framework\Stdlib\DateTime\Filter\Date $dateFilter,
        MessageManagerInterface $messageManager,
        \Magento\Framework\App\Request\DataPersistorInterface $dataPersistor,
        \Branch8\CatalogRule\Model\CatalogRuleApprovalFactory $catalogRuleApprovalFactory,
        \Branch8\CatalogRule\Helper\Modify $modifyHelper,
        \Magento\Framework\Controller\Result\RedirectFactory $redirectFactory,
        \Magento\Framework\Serialize\Serializer\Json $serializer,
        \Branch8\CatalogRule\Helper\Data $crHelperData
    )
    {
        $this->_actionFlag = $actionFlag;
        $this->adminSession = $adminSession;
        $this->response = $response;
        $this->_objectManager = $objectManager;
        $this->_eventManager = $eventManager;
        $this->localeDate = $localeDate;
        $this->_dateFilter= $dateFilter;
        $this->messageManager = $messageManager;
        $this->dataPersistor = $dataPersistor;
        $this->adminAuthSession = $adminAuthSession;
        $this->catalogRuleApprovalFactory = $catalogRuleApprovalFactory;
        $this->modifyHelper = $modifyHelper;
        $this->redirectFactory = $redirectFactory;
        $this->serializer = $serializer;
        $this->crHelperData = $crHelperData;
    }

    public function aroundExecute($subject, $process){
        if(!$this->crHelperData->isApprovalEnable()){
            return $process();
        }
        $id = $subject->getRequest()->getParam('rule_id');

        if((int)$id && !$this->modifyHelper->canEditCatalogRule((int)$id)){
            $resultRedirect = $this->redirectFactory->create();
            $this->messageManager->addErrorMessage(__('Your rule is pending approval, you cannot edit it at this time.'));
            $resultRedirect->setPath('catalog_rule/promo_catalog/index'); 
            return $resultRedirect;
        }

        if (!$subject->getRequest()->getPostValue()) {
            $this->_redirect($subject, 'catalog_rule/*/');
        }
        $ruleRepository = $this->_objectManager->get(
            \Magento\CatalogRule\Api\CatalogRuleRepositoryInterface::class
        );
        /** @var \Magento\CatalogRule\Model\Rule $model */
        $model = $this->_objectManager->create(\Magento\CatalogRule\Model\Rule::class);

        try{
            $this->_eventManager->dispatch(
                'adminhtml_controller_catalogrule_prepare_save',
                ['request' => $subject->getRequest()]
            );
            $data = $subject->getRequest()->getPostValue();
            if (!$subject->getRequest()->getParam('from_date')) {
                $data['from_date'] = $this->localeDate->formatDate();
            }
            $filterValues = ['from_date' => $this->_dateFilter];
            if ($subject->getRequest()->getParam('to_date')) {
                $filterValues['to_date'] = $this->_dateFilter;
            }
            $inputFilter = new FilterInput(
                $filterValues,
                [],
                $data
            );
            $data = $inputFilter->getUnescaped();
            
            if ($id) {
                $model = $ruleRepository->get($id);
            }

            $validateResult = $model->validateData(new \Magento\Framework\DataObject($data));
            if ($validateResult !== true) {
                foreach ($validateResult as $errorMessage) {
                    $this->messageManager->addErrorMessage($errorMessage);
                }
                $this->_getSession()->setPageData($data);
                $this->dataPersistor->set('catalog_rule', $data);
                $this->_redirect($subject, 'catalog_rule/*/edit', ['id' => $model->getId()]);
                return;
            }

            $postData = $data;

            if (!$id) {
                /** New rule, disable to submit review
                 * After review update the status = $post['status']
                 */

                 if (isset($data['rule'])) {
                    $data['conditions'] = $data['rule']['conditions'];
                    unset($data['rule']);
                }

                unset($data['conditions_serialized']);
                unset($data['actions_serialized']);

                $model->loadPost($data);
                $model->setIsActive(0);
                $ruleRepository->save($model);
            }else{
                
                if (isset($postData['rule'])) {
                    $postData['conditions'] = $postData['rule']['conditions'];
                    unset($postData['rule']);
                    unset($postData['conditions_serialized']);
                    unset($postData['actions_serialized']);
                    $emptyModel = $this->_objectManager->create(\Magento\CatalogRule\Model\Rule::class);
                    $emptyModel->loadPost($postData);
                    
                    $conditions = $emptyModel->getConditions()->asArray();
                    $conditionsSerizlized = $this->serializer->serialize($conditions);
                    $postData['conditions_serialized'] = $conditionsSerizlized;
                    $actions = $emptyModel->getActions()->asArray();
                    $actionSerialized = $this->serializer->serialize($actions);
                    $postData['actions_serialized'] = $actionSerialized;
                }
                
                $isChanged = false;
                /**
                 * Validate changes
                 * If user submit a existed rule, if no changes, no create a record
                 */

                 /** Validate Related dynamic blocks */

                if(isset($postData['related_banners'])){
                    $newRelatedBanner = $postData['related_banners'];
                }else{
                    $newRelatedBanner = [];
                }
                sort($newRelatedBanner);
                
                $oldRelatedBanner = $model->getRelatedBanners();
                sort($oldRelatedBanner);

                if($newRelatedBanner != $oldRelatedBanner){
                    $isChanged = true;
                }
                
                /**Validate actions */
                if(isset($postData['actions_serialized'])){
                    $newActions = $postData['actions_serialized'];
                }else{
                    $newActions = '';
                }
                $oldActions = $model->getData('actions_serialized');
                if($newActions != $oldActions){
                    $isChanged = true;
                }

                /** Validate conditions */
                if(isset($postData['conditions_serialized'])){
                    $newConditions = $postData['conditions_serialized'];
                }else{
                    $newConditions = '';
                }
                $oldConditions = $model->getData('conditions_serialized');
                if($newConditions != $oldConditions){
                    $isChanged = true;
                }
                /** Validate flat fields */
                $ruleData = $model->getData();
                unset($ruleData['conditions_serialized']);
                unset($ruleData['actions_serialized']);
                unset($ruleData['related_banners']);
                foreach($ruleData as $key => $val){
                    if(!isset($postData[$key])){
                        $postData[$key] = null;
                    }
                    if($val != $postData[$key]){
                        $isChanged = true;
                    }
                }
                if(!$isChanged){
                    $this->messageManager->addErrorMessage(__('No changes.'));
                    return $this->_redirect($subject, 'catalog_rule/*/')->sendResponse();
                }
            }

            /** Submit review */
            $ruleId = $model->getId();
            $adminSession = $this->adminAuthSession->getUser();
            $submitter = $adminSession->getUserName();
            
            if($id){
                $postType = \Branch8\CatalogRule\Model\Config\Source\ApproveType::TYPE_EDIT;
            }else{
                $postType = \Branch8\CatalogRule\Model\Config\Source\ApproveType::TYPE_NEW;
            }

            $status = \Branch8\CatalogRule\Model\Config\Source\ApproveStatus::STATUS_UNDER_REVIEW;
            $createdAt = $this->localeDate->convertConfigTimeToUtc($this->localeDate->date());
            
            $this->catalogRuleApprovalFactory->create()
                ->setData([
                    'entity_id' => NULL,
                    'catalogrule_id' => $ruleId,
                    'post_data' => json_encode($postData),
                    'post_type' => $postType,
                    'submitter' => $submitter,
                    'status' => $status,
                    'created_at' => $createdAt,
                    'updated_at' => NULL
                ])->save();
            $this->messageManager->addSuccessMessage(__('Submitted for approval successfully.'));
            return $this->_redirect($subject, 'catalog_rule/*/')->sendResponse();
        }catch(\Exception $e){
            $this->messageManager->addErrorMessage(__('Submission for review failed.'));
            return $this->_redirect($subject, 'catalog_rule/*/')->sendResponse();
        }

    }

    protected function _redirect($subject, $path, $arguments = [])
    {
        $this->_getSession()->setIsUrlNotice($this->_actionFlag->get('', \Magento\Backend\App\AbstractAction::FLAG_IS_URLS_CHECKED));
        $this->getResponse()->setRedirect($subject->getUrl($path, $arguments));
        return $this->getResponse();
    }

    protected function _getSession(){
        return $this->adminSession;
    }

    protected function getResponse(){
        return $this->response;
    }

}
