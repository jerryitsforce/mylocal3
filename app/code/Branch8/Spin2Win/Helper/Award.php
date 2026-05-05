<?php

namespace Branch8\Spin2Win\Helper;

use Magento\Framework\App\Helper\AbstractHelper;
use Magento\SalesRule\Api\RuleRepositoryInterface;

class Award extends AbstractHelper
{

    protected $emailHelper;

    protected $ruleData = [];

    protected $segmentData = [];

    protected $_conn;

    protected $timezone;

    protected $couponGenerator;

    protected $segmentsFactory;

    protected $saleRuleFactory;

    protected $apiPointHelper;

    protected $ruleRepository;

    public function __construct(
        \Magento\Framework\App\Helper\Context $context,
        \Webkul\SpinToWin\Helper\Email $emailHelper,
        \Magento\Framework\App\ResourceConnection $resourceConnection,
        \Magento\Framework\Stdlib\DateTime\TimezoneInterface $timezone,
        \Magento\SalesRule\Model\CouponGenerator $couponGenerator,
        \Webkul\SpinToWin\Model\SegmentsFactory $segmentsFactory,
        \Magento\Salesrule\Model\RuleFactory $saleRuleFactory,
        \Branch8\HotaiPoint\Helper\Api $apiPointHelper,
        RuleRepositoryInterface $ruleRepository
    ){
        parent::__construct($context);
        $this->emailHelper = $emailHelper;
        $this->_conn = $resourceConnection->getConnection();
        $this->timezone = $timezone;
        $this->couponGenerator = $couponGenerator;
        $this->segmentsFactory = $segmentsFactory;
        $this->saleRuleFactory = $saleRuleFactory;
        $this->apiPointHelper = $apiPointHelper;
        $this->ruleRepository = $ruleRepository;
    }

    public function getPoolSerialForPrize($poolId, $customerMemberSeq){
    
        try{
            $this->_conn->beginTransaction();
            $cnt = 0;
            $customerTicketId = null;
            while(true){
                $cnt ++;
                $sqlSerial = $this->_conn->select()
                    ->from(['pool' => 'ticket_event_ticket'])
                    ->columns(new \Zend_Db_Expr('addtime(start_date, "08:00:00") as st_date'))
                    ->columns(new \Zend_Db_Expr('addtime(end_date, "08:00:00") as e_date'))
                    ->where('event_id = ?', $poolId)
                    ->where('status = 0')
                    ->limit(1);
                $serialData = $this->_conn->fetchRow($sqlSerial);

                if(empty($serialData)) {
                   throw new \Exception(__('There is no available serial number in this pool.'));
                }
            
                $numOfUpdateItem = $this->_conn->update('ticket_event_ticket', ['status' => \Branch8\CustomerTicketTable\Ui\Source\Status::STATUS_UNUSED], 'entity_id = '.$serialData['entity_id']);
                if(!$numOfUpdateItem){
                    if($cnt >= 20){
                        return null;
                    }
                    continue;
                }

                $sqlQueryCustomerTicket = 'select record_id from customer_ticket where ticket_table_name="ticket_event_ticket" and 
                    ticket_table_record_id='.$serialData['entity_id'].';';
                $recordId = $this->_conn->fetchOne($sqlQueryCustomerTicket);
                if(!$recordId){
                    throw new \Exception(__("Can't find the serial in Customer Ticket."));
                }
                $updatedAt = $this->timezone->convertConfigTimeToUtc($this->timezone->date());
                $sqlUpdateCustomerTicket = 'update customer_ticket set status='.\Branch8\CustomerTicketTable\Ui\Source\Status::STATUS_UNUSED.', 
                    updated_at="'.$updatedAt.'", member_seq="'.$customerMemberSeq.'" where record_id='.$recordId;
                $this->_conn->fetchOne($sqlUpdateCustomerTicket);
                $this->_conn->commit();
                return ['serial' => $serialData['serial_number'], 'serial_number_id' => $recordId];
                break;
            }
            /** serial_number_id = id in customer_ticket */
            throw new \Exception(__('There is no available serial number in this pool.'));
        }catch(\Exception $e){
            $this->_conn->rollBack();
            throw new \Exception(__('There is no available serial number in this pool.'));
        }
        throw new \Exception(__('There is no available serial number in this pool.'));
    }

    public function getPoolName($poolId){
        $checkSql = $this->_conn->select()
            ->from(['add' => 'ticket_event'])
            ->where('entity_id = ?', $poolId)
            ->where('is_enable = 1');
        $result = $this->_conn->fetchRow($checkSql);
        if($result && $result['entity_id']){
            return $result['name'];
        }
        return false;
    }

    public function couponAward($prizeData)
    {
        if(!$this->segmentData[$prizeData['segmentid']]){
            $this->segmentData[$prizeData['segmentid']] = $this->segmentsFactory->create()->load($prizeData['segmentid']);
        }
        $model = $this->segmentData[$prizeData['segmentid']];

        $segment = [];
        $segment['label'] = $model->getLabel();
        $segment['heading'] = $model->getHeading();
        $segment['description'] = $model->getDescription();


        if($prizeData['segment_type'] == 'coupon'){
            $segment['coupon'] = $prizeData['coupon'];

            $ruleId =  $prizeData['rule_id'];
            if(!isset($this->ruleData[$ruleId])){
                $rule = $this->saleRuleFactory->create()->load($ruleId);
                $this->ruleData[$ruleId] = $rule;
            }
            $rule = $this->ruleData[$ruleId];
            $segment['expiry'] = $rule->getToDate();
        }else if($prizeData['segment_type'] == 'virtual_item'){

        }else if($prizeData['segment_type'] == 'physical_item'){

        }else if($prizeData['segment_type'] == 'reward_point'){

        }


        $this->emailHelper->sendCouponNotification($prizeData['email'], $prizeData['name'], $segment);
    }

    public function assignConsolation($spin, $consolationData, $prizeReportId, $customerId, $memberSeq){
        $spinId = $spin->getId();
        if ($consolationData['consolation_type'] == \Branch8\Spin2Win\Model\Config\Source\SegmentType::COUPON_TYPE) {
            try{
                $ruleId = $consolationData['rule_id'];
                $salesRule = $this->ruleRepository->getById($ruleId);
                $ruleName = $salesRule->getName();

                $couponSpecData = [
                    'rule_id' => $ruleId,
                    'qty' => 1,
                    'length' => 12,
                    'format' => 'alphanum',
                ];
                $coupon = $this->couponGenerator->generateCodes($couponSpecData)[0];
                $consolationReportData = [
                    'consolation_report_id' => NULL, 
                    'spin_id' => $spinId,
                    'customer_id' => $customerId,
                    'consolation_type' => $consolationData['consolation_type'],
                    'coupon' => $coupon,
                    'rule_id' => $consolationData['rule_id'],
                    'coupon_expired_date' => $consolationData['coupon_expired_date'],
                    'coupon_image' => $consolationData['consolation_coupon_image'],
                    'consolation_rule_name' => $ruleName
                ];
            }catch(\Exception $e){
                throw new \Exception(__('Error creating coupon code for customer.'));
            }
        }else if($consolationData['consolation_type'] == \Branch8\Spin2Win\Model\Config\Source\SegmentType::VIRTUAL_TYPE){
            $serialNumberData = $this->getPoolSerialForPrize($consolationData['pool_id'], $memberSeq);
            $poolName = $this->getPoolName($consolationData['pool_id']);
            if(!$poolName){
                throw new \Exception(__('Error assigning serial number to customer.'));
            }
            if($serialNumberData){
                $consolationReportData = [
                    'consolation_report_id' => NULL, 
                    'spin_id' => $spinId,
                    'customer_id' => $customerId,
                    'consolation_type' => $consolationData['consolation_type'],
                    'serial_number' => $serialNumberData['serial'],
                    'serial_number_id' => $serialNumberData['serial_number_id'],
                    'pool_id' => $consolationData['pool_id'],
                    'consolation_pool_name' => $poolName
                ];
            }else{
                throw new \Exception(__('Error assigning serial number to customer.'));
            }
        }else if($consolationData['consolation_type'] == \Branch8\Spin2Win\Model\Config\Source\SegmentType::PHYSICAL_TYPE){
            $sku = $consolationData['sku'];
            $consolationReportData = [
                'consolation_report_id' => NULL, 
                'spin_id' => $spinId,
                'customer_id' => $customerId,
                'consolation_type' => $consolationData['consolation_type'],
                'sku' => $sku,
                'physical_image' => $consolationData['consolation_physical_image'],
                'physical_expire_date' => $consolationData['physical_expire_date']
            ];
        }else if($consolationData['consolation_type'] == \Branch8\Spin2Win\Model\Config\Source\SegmentType::REWARD_POINT_TYPE){
            
            $rewardPoint = $consolationData['reward_point'];
            /**
             * Call API add point
             */
            $addPointResult = $this->getRewardPointForConsolation($spin->getData(), $customerId, $consolationData);
            if($addPointResult['result']){
                $consolationBuNo = '';
                $consolationRsNo = '';
                if($consolationData['reward_point_account'] == \Branch8\Spin2Win\Model\Config\Source\PointsAccount::TYPE_CUSTOMIZE){
                    $consolationBuNo = $consolationData['point_bu_no'];
                    $consolationRsNo = $consolationData['point_rs_no'];
                }
                $consolationReportData = [
                    'consolation_report_id' => NULL, 
                    'spin_id' => $spinId,
                    'customer_id' => $customerId,
                    'consolation_type' => $consolationData['consolation_type'],
                    'reward_point' => $rewardPoint,
                    'point_expire_date' => $consolationData['point_expire_date'],
                    'reward_point_account' => $consolationData['reward_point_account'],
                    'point_bu_no' => $consolationBuNo,
                    'point_rs_no' => $consolationRsNo,
                    'prize_trace_no' => $addPointResult['trace_no']
                 ];
            }else{
                throw new \Exception(__('Error on add prize point to customer.'));
            }
        }else{/** Lose */
            $consolationReportData = [
                'consolation_report_id' => NULL, 
                'spin_id' => $spinId,
                'customer_id' => $customerId,
                'consolation_type' => $consolationData['consolation_type']
            ];
        }
        $consolationReportData['created_at'] = $this->timezone->convertConfigTimeToUtc($this->timezone->date());
        $consolationReportData['consolation_id'] = $consolationData['consolation_id'];
        $consolationReportData['prize_report_id'] = $prizeReportId;
        $consolationReportData['consolation_title'] = $consolationData['consolation_title'];
        $consolationReportData['consolation_description'] = $consolationData['consolation_description'];
        
        $this->_conn->insert('spintowin_consolation_reports', $consolationReportData);
        
        return $consolationReportData;
    }

    public function getRewardPointForPrize($spin, $customerId, $prizeData){
        $result = ['result' => false, 'trace_no' => ''];
        try{
            $taiwanDateObj = new \DateTime();
            $taiwanDateObj->setTimezone(new \DateTimeZone("Asia/Taipei"));
            $transTimestamp = $taiwanDateObj->getTimestamp();
            $transSN        = "spin".$prizeData['entity_id'].'_'.$customerId.'_'.$transTimestamp;
            $addPoint       = $prizeData['reward_point'];
            $transDesc      = substr($spin['name'], 0, 45).'...';
            $pointValidType = \Branch8\HotaiPoint\Model\HotaiPointApiRecord::POINT_VALID_TYPE_CUSTOM;
            $pointValidDate = str_replace('-', '', $prizeData['point_expire_date']);
            $buNo = '';
            $rsNo = '';
            if((int)$prizeData['reward_point_account']){
                $buNo = (string)$prizeData['point_bu_no'];
                $rsNo = (string)$prizeData['point_rs_no'];
            }
            if($buNo && $rsNo){
                $apiResponse = $this->apiPointHelper->requestApiAddPointNon(
                    $customerId,
                    $addPoint,
                    $transSN,
                    $transTimestamp,
                    $transDesc,
                    $pointValidType,
                    $pointValidDate,
                    $buNo,
                    $rsNo
                );
            }else{
                $apiResponse = $this->apiPointHelper->requestApiAddPointNon(
                    $customerId,
                    $addPoint,
                    $transSN,
                    $transTimestamp,
                    $transDesc,
                    $pointValidType,
                    $pointValidDate
                );
            }
            
            if($apiResponse['returnCode'] == '0000'){
                $result['result'] = true;
                $result['trace_no'] = $apiResponse['data']['traceNo'];
                return $result;
            }else{
                $result['result'] = false;
                return $result;
            }
            
        }catch(\Exception $e){
            return $result;
        }
    }

    public function getRewardPointForConsolation($spin, $customerId, $consolationData){
        $result = ['result' => false, 'trace_no' => ''];
        try{
            $taiwanDateObj = new \DateTime();
            $taiwanDateObj->setTimezone(new \DateTimeZone("Asia/Taipei"));
            $transTimestamp = $taiwanDateObj->getTimestamp();
            $transSN        = "spin_c_".$consolationData['consolation_id'].'_'.$customerId.'_'.$transTimestamp;
            $addPoint       = $consolationData['reward_point'];
            $transDesc      = substr($spin['name'], 0, 45).'...';
            $pointValidType = \Branch8\HotaiPoint\Model\HotaiPointApiRecord::POINT_VALID_TYPE_CUSTOM;
            $pointValidDate = str_replace('-', '', $consolationData['point_expire_date']);
            $buNo = '';
            $rsNo = '';
            if((int)$consolationData['reward_point_account']){
                $buNo = (string)$consolationData['point_bu_no'];
                $rsNo = (string)$consolationData['point_rs_no'];
            }
            if($buNo && $rsNo){
                $apiResponse = $this->apiPointHelper->requestApiAddPointNon(
                    $customerId,
                    $addPoint,
                    $transSN,
                    $transTimestamp,
                    $transDesc,
                    $pointValidType,
                    $pointValidDate,
                    $buNo,
                    $rsNo
                );
            }else{
                $apiResponse = $this->apiPointHelper->requestApiAddPointNon(
                    $customerId,
                    $addPoint,
                    $transSN,
                    $transTimestamp,
                    $transDesc,
                    $pointValidType,
                    $pointValidDate
                );
            }
            if($apiResponse['returnCode'] == '0000'){
                $result['result'] = true;
                $result['trace_no'] = $apiResponse['data']['traceNo'];
                return $result;
            }else{
                $result['result'] = false;
                return $result;
            }
            
        }catch(\Exception $e){
            return $result;
        }
    }

    public function isCustomerHasSpinAddress($customerId, $spinId){
        $checkSql = $this->_conn->select()
            ->from(['add' => 'spintowin_award_address'])
            ->where('spin_id = ?', $spinId)
            ->where('customer_id = ?', $customerId);
        $address = $this->_conn->fetchRow($checkSql);
        if(!empty($address)){
            return true;
        }else{
            return false;
        }
    }

    public function getNotificationDesc($customerId, $spinId, $prizeType, $prizeTitle){
        $desc = __('恭喜你中獎! 獲得「%1」', $prizeTitle);
        if($prizeType == \Branch8\Spin2Win\Model\Config\Source\SegmentType::PHYSICAL_TYPE 
        && $this->isCustomerHasSpinAddress($customerId, $spinId)){
            $desc = __('恭喜你中獎! 「%1」已寄出', $prizeTitle);
        }

        return $desc;
    }
}