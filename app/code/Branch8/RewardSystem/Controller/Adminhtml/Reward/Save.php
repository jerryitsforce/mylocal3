<?php
namespace Branch8\RewardSystem\Controller\Adminhtml\Reward;

class Save extends \Magento\Backend\App\Action
{
    const ADMIN_RESOURCE = 'Branch8_RewardSystem::manage';

    /**
     * @var \Magento\Framework\View\Result\PageFactory
     */
    protected $_pageFactory;

    /**
     *
     * @var \Branch8\RewardSystem\Model\EventFactory
     */
    protected $eventFactory;

    protected $timezone;

    /**
     * @param \Magento\Backend\App\Action\Context $context
     */
    public function __construct(
       \Magento\Backend\App\Action\Context $context,
       \Branch8\RewardSystem\Model\EventFactory $eventFactory,
       \Magento\Framework\Stdlib\DateTime\TimezoneInterface $timezone
    )
    {
        parent::__construct($context);
        $this->eventFactory = $eventFactory;
        $this->timezone = $timezone;
    }

    /**
     * Index action
     *
     * @return \Magento\Framework\View\Result\Page
     */
    public function execute()
    {
        try{
            $eventId = $this->getRequest()->getParam('entity_id'); 
            $event = $this->eventFactory->create()->load($eventId);
            if($event->getId()){
                $event->setData('updated_at', $this->timezone->convertConfigTimeToUtc($this->timezone->date()));
            }else{
                $event->setData('created_at', $this->timezone->convertConfigTimeToUtc($this->timezone->date()));
                $event->setData('updated_at', NULL);
            }
            $postData = $this->getRequest()->getParams();
            $event->setData('title', $postData['title']);
            $event->setData('status', $postData['status']);
            $event->setData('description', $postData['description']);
            if($postData['start_date']){
                $startDate = $this->timezone->convertConfigTimeToUtc($this->timezone->date($postData['start_date'])->sub(new \DateInterval('PT8H')));
            }else{
                $startDate = NULL;
            }
            $event->setData('start_date', $startDate);

            if($postData['end_date']){
                $endDate = $this->timezone->convertConfigTimeToUtc($this->timezone->date($postData['end_date'])->sub(new \DateInterval('PT8H')));
            }else{
                $endDate = NULL;
            }
            $event->setData('end_date', $endDate);

            $event->setData('user_limit_daily', trim($postData['user_limit_daily']));
            $event->setData('user_limit_total', trim($postData['user_limit_total']));
            $event->setData('event_limit_daily', trim($postData['event_limit_daily']));
            $event->setData('event_limit_total', trim($postData['event_limit_total']));
            $event->setData('notification_title', $postData['notification_title']);
            $event->setData('notification_content', $postData['notification_content']);


            $event->setData('reward_type', $postData['reward_type']);
            if($postData['reward_type'] == \Branch8\RewardSystem\Model\Config\Source\RewardType::TYPE_POOL){
                $event->setData('reward_pool_id', $postData['reward_pool_id']);
                $event->setData('reward_pool_batch_code', $postData['reward_pool_batch_code']);
            }

            if($postData['reward_type'] == \Branch8\RewardSystem\Model\Config\Source\RewardType::TYPE_COUPON){
                $event->setData('reward_rule_id', $postData['reward_rule_id']);
            }

            $conditions = [];
            $hasFirstPurchase = false;
            if(isset($postData['condition'])){
                $conditionPost = $postData['condition'];
                
                foreach($conditionPost as $key => $_cond){
                    $fields = [];
                    if($_cond == \Branch8\RewardSystem\Model\Config\Source\Conditions::COND_API){
                        $triggerCondition = $event->getData('trigger_condition');
                        if(!$triggerCondition){
                            $fields['api_key'] = $this->randAPIKey();
                        }else{
                            $triggerConditionArr = json_decode($triggerCondition, true);
                            $existedConditions = $triggerConditionArr['conditions'];
                            foreach($existedConditions as $_dbCond){
                                if($_dbCond['condition'] == \Branch8\RewardSystem\Model\Config\Source\Conditions::COND_API){
                                    $fields['api_key'] = $_dbCond['fields']['api_key'];
                                }
                            }
                            if(empty($fields['api_key'])){
                                $fields['api_key'] = $this->randAPIKey();
                            }
                        }
                        
                    }else if($_cond == \Branch8\RewardSystem\Model\Config\Source\Conditions::COND_VISIT_URL){
                        $fields['url'] = $postData['url'][$key];
                    }else if($_cond == \Branch8\RewardSystem\Model\Config\Source\Conditions::COND_FIRST_PURCHASE){
                        $fields['first_order_status'] = $postData['first_order_status'][$key];
                        $hasFirstPurchase = true;
                    }else if($_cond == \Branch8\RewardSystem\Model\Config\Source\Conditions::COND_LEVEL_CHANGE){
                        $fields['to_level'] = $postData['to_level'][$key];
                    }else{
                        throw new \Exception(__('No Trigger found'));
                    }

                    $fields['is_onetime_trigger'] = (int)$postData['is_onetime_trigger'][$key];
                    $fields['issue_reward'] = (int)$postData['issue_reward'][$key];
                    $conditions[] = [
                        'condition' => $_cond,
                        'fields' => $fields
                    ];
                }
            }
            
            if(!isset($postData['triggerType'])){
                $postData['triggerType'] = \Branch8\RewardSystem\Model\Config\Source\TriggerType::TYPE_AND;
            }
            $triggerData = [
                'triggerType' => $postData['triggerType'],
                'conditions' => $conditions
            ];
            /**
             * If has any First purchase trigger, the qty limit =1 and the limit type is "Total Usage Limit per User'"
             */
            if($hasFirstPurchase){
                $event->setData('user_limit_type', \Branch8\RewardSystem\Model\Config\Source\UserLimit::TYPE_EVENT);
                $event->setData('user_limit', 1);
            }
            
            $event->setData('trigger_condition', json_encode($triggerData));
            $event->save();
            $this->messageManager->addSuccessMessage(__('Save successfully'));
        }catch(\Exception $e){echo $e->getMessage();die;
            $this->messageManager->addErrorMessage(__('Save Fail'));
        }

        return $this->_redirect('rewardsystem/reward/index')->sendResponse();
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

    protected function randAPIKey(){
        $length = 32;
        $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $randomString = substr(str_shuffle($characters), 0, $length);
     
        return $randomString;
    }
}
