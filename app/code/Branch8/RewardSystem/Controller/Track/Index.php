<?php
namespace Branch8\RewardSystem\Controller\Track;

class Index extends \Magento\Framework\App\Action\Action
{
    /**
     * @var \Magento\Framework\View\Result\PageFactory
     */
    protected $_pageFactory;

    protected $rewardHelper;

    protected $customerSession;

    protected $_conn;

    protected $timezone;

    protected $publisher;

    public function __construct(
       \Magento\Framework\App\Action\Context $context,
       \Branch8\RewardSystem\Helper\Data $rewardHelper,
       \Magento\Customer\Model\Session $customerSession,
       \Magento\Framework\App\ResourceConnection $resourceConnection,
       \Magento\Framework\Stdlib\DateTime\TimezoneInterface $timezone,
       \Magento\Framework\MessageQueue\PublisherInterface $publisher
    )
    {
        parent::__construct($context);
        $this->rewardHelper = $rewardHelper;
        $this->customerSession = $customerSession;
        $this->_conn = $resourceConnection->getConnection();
        $this->timezone = $timezone;
        $this->publisher = $publisher;
    }
    /**
     * Tracking visit url for Automated reward system
     */
    public function execute()
    {
        if(!$this->customerSession->isLoggedIn()){
            exit(0);
        }
        try{
            $referUrl = (string)$this->_redirect->getRedirectUrl();
            if(substr($referUrl, -1) == '/'){
                $referUrl = substr($referUrl, 0, -1);
            }
            $checkEventsResult = $this->rewardHelper->isVisitURL($referUrl);
            if(!empty($checkEventsResult)){
                foreach($checkEventsResult as $_eventId => $_condFields){
                    $customerId = $this->customerSession->getCustomerId();
                    $createdAt = $this->timezone->convertConfigTimeToUtc($this->timezone->date());

                    $isNeedToInsertTriggerData = true;
                    if($_condFields['is_onetime_trigger']){
                        if($this->rewardHelper->isUrlTriggerExisted($_eventId, $customerId, $referUrl)){
                            $isNeedToInsertTriggerData = false;
                        }
                    }
                    if($isNeedToInsertTriggerData){
                        $this->_conn->insert(
                            'branch8_rewardsystem_visit_url',
                            [
                                'entity_id' => NULL,
                                'event_id' => $_eventId,
                                'url' => $referUrl,
                                'customer_id' => $customerId,
                                'created_at' => $createdAt,
                                'report_id' => 0
                            ]
                        );
                    }
                    
                    $isIssueReward = $_condFields['issue_reward'];
                    if($isIssueReward){
                        $this->publisher->publish('automated.reward.check.assign_reward', json_encode([
                            'event_id' => $_eventId,
                            'customer_id' => $customerId
                        ]));
                    }
                }
                
            }
            exit(0);
        }catch(\Exception $e){

        }
    }
}
