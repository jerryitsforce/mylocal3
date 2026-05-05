<?php

namespace Branch8\Spin2Win\Observer\Salesrule;

class Save implements \Magento\Framework\Event\ObserverInterface
{
    protected $ruleFactory;

    protected $_conn;

    protected $timezone;

    protected $messageManager;

    protected $_response;

    protected $_urlInterFace;

    public function __construct(
        \Magento\Salesrule\Model\RuleFactory $ruleFactory,
        \Magento\Framework\App\ResourceConnection $resourceConnection,
        \Magento\Framework\Stdlib\DateTime\TimezoneInterface $timezone,
        \Magento\Framework\Message\ManagerInterface $messageManager,
        \Magento\Framework\App\ResponseFactory $_response,
        \Magento\Framework\UrlInterface $_urlInterFace
    )
    {
        $this->ruleFactory = $ruleFactory;
        $this->_conn = $resourceConnection->getConnection();
        $this->timezone = $timezone;
        $this->messageManager = $messageManager;
        $this->_response = $_response;
        $this->_urlInterFace = $_urlInterFace;

    }

    public function execute(
        \Magento\Framework\Event\Observer $observer
    ) {
        $request = $observer->getEvent()->getData('request');
        $ruleId = $request->getParam('rule_id');
        $couponType = $request->getParam('coupon_type');
        if(!$ruleId){
            return;
        }
        $rule = $this->ruleFactory->create()->load($ruleId);
        $currentRuleCouponType = $rule->getCouponType();
        if($currentRuleCouponType == \Magento\SalesRule\Model\Rule::COUPON_TYPE_AUTO && $couponType != \Magento\SalesRule\Model\Rule::COUPON_TYPE_AUTO){
            /**
             * Check salesrule is in event
             */
            $currentTime = $this->timezone->convertConfigTimeToUtc($this->timezone->date());
            $sqlCheck = $this->_conn->select()
                ->from(['segment' => 'spintowin_segments'], [])
                ->joinLeft(['spin' => 'spintowin_info'], 'spin.entity_id = segment.spin_id', [])
                ->columns(new \Zend_Db_Expr('segment.entity_id as seg_id'))
                ->where('segment.rule_id = '.$ruleId.' and spin.status = 1 and (spin.scheduled = 0 or (spin.scheduled = 1 and spin.start_date < "'.$currentTime.'" and spin.end_date > "'.$currentTime.'"))');
            $segments = $this->_conn->fetchAll($sqlCheck);
            if(count($segments) > 0){
                $ids = [];
                foreach($segments as $_seg){
                    $ids[$_seg['seg_id']] = $_seg['seg_id'];
                }
                $this->messageManager->addErrorMessage(__("Error, You can't change coupon type. This rule is using for lucky draw event Ids: ".implode(',', $ids)));
                $url = $this->_urlInterFace->getUrl('sales_rule/*/edit', ['id' => $ruleId]);
                $this->_response->create()
                    ->setRedirect($url)
                    ->sendResponse();
                exit(0);
            }

        }
    }
}