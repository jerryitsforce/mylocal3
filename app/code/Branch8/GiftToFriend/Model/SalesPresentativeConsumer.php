<?php
namespace Branch8\GiftToFriend\Model;

use Magento\Framework\MessageQueue\ConsumerConfiguration;

class SalesPresentativeConsumer extends ConsumerConfiguration{
    const CONSUMER_NAME = "gift.order.push.sales_presentative";
    const QUEUE_NAME = "gift.order.push.sales_presentative";

    const PUSH_STATUSES = ['gift_info_pending', 'gift_info_complete', 'arrived', 'canceled'];

    const RMA_PUSH_STATUSES = ['rma_processing'];

    protected $timezone;

    protected $_conn;

    protected $salesPresentativeApi;

    public function __construct(
        \Magento\Framework\Stdlib\DateTime\TimezoneInterface $timezone,
        \Branch8\GiftToFriend\Helper\SalesPresentativeApi $salesPresentativeApi,
        \Magento\Framework\App\ResourceConnection $resourceConnection
    ){
        $this->timezone = $timezone;
        $this->salesPresentativeApi = $salesPresentativeApi;
        $this->_conn = $resourceConnection->getConnection();
    }

    public function process($request){
    
        $data = json_decode($request, true);
        $orderStatus = $data['order_status'];
        $rmaStatus = $data['rma_status'];
        if(!in_array($orderStatus, self::PUSH_STATUSES) && !in_array($rmaStatus, self::RMA_PUSH_STATUSES)){
            return;
        }

        $parentOrderId = $data['parent_order_id'];
        
        $sql = 'select increment_id, sales_presentative_infor, created_at from sales_parent_order_detail where parent_id='.$parentOrderId;
        $orderData = $this->_conn->fetchRow($sql);
        
        $incrementId = $orderData['increment_id'];
        $salesPresentativeInfor = $orderData['sales_presentative_infor'];
        
        
        $salesPresentativeInforData = json_decode($salesPresentativeInfor, true);
        $recipientInfor = $salesPresentativeInforData['recipientInfor'];
        $salesInfor = $recipientInfor['sales'];
        // $addrInfor = $recipientInfor['address'];
        // $addr = $addrInfor['detail'].''.$addrInfor['district'].''.$addrInfor['city'];

        $purposeMemo = $this->getPurposeMemo($parentOrderId);

        $triggerDatetime = new \DateTime($data['time']);
        $startTime = $this->timezone->date($triggerDatetime)->format('Y-m-d H:i:s');
        $triggerTime = $this->timezone->date($triggerDatetime)->format('H:i:s');
        $endTime = $this->timezone->date($triggerDatetime)->add(new \DateInterval('PT1S'))->format('Y-m-d H:i:s');
        if($triggerTime == '23:59:59'){
            $endTime = $startTime;
        }
        
        $changeType = $data['changeType'];
        $resultParam = $this->getResultParam($changeType, $orderStatus, $rmaStatus);
        $resultMemo = $this->getResultMemoParam($changeType, $orderStatus, $rmaStatus);

        /** Name and address */
        $sqlAddressShipping = 'select * from sales_parent_order_address where parent_order_id='.$parentOrderId;
        $allAddresses = $this->_conn->fetchAll($sqlAddressShipping);
        $custName = '';
        $custAddr = '';
        foreach($allAddresses as $_addr){
            $custName = $_addr['firstname'];
            $custAddr = $_addr['street'].','.$_addr['city'].','.$_addr['region'].','.$_addr['postcode'];
            if($_addr['address_type'] == 'shipping'){
                $custName = $_addr['firstname'];
                break;
            }
        }

        $apiData = [
            'hotaiAppId' => $salesPresentativeInforData['appId'],
            'dealerCode' => $salesInfor['dealerCode'],
            'branchCode' => $salesInfor['branchCode'],
            'sectionCode' => $salesInfor['sectionCode'],
            'salesCode' => $salesInfor['salesCode'],
            // 'userID' => $data['userID'],
            'customerId' => $recipientInfor['customerId'],
            'customerName' => $custName,
            'custAddress' => $custAddr,
            'customerType' => $recipientInfor['customerType'],
            'refID' => $incrementId,
            'purposeMemo' => $purposeMemo,
            'startTime' => $startTime,
            'endTime' => $endTime,
            'result' => $resultParam,
            'resultMemo' => $resultMemo
        ];
        
        $apiResult = $this->salesPresentativeApi->pushSalePresentativeApi($apiData);
        if(!$apiResult){
            throw new \Exception(__('Error on push Sales Presentative data to Hotai'));
        }
        $resultData = json_decode($apiResult, true);
        if(!isset($resultData['ResultCode']) || $resultData['ResultCode'] != '0000'){
            throw new \Exception(__('Error on push Sales Presentative data to Hotai'));
        }
        
    }

    protected function getResultParam($changeType, $orderStatus, $rmaStatus){
        $mappingData = $this->mappingOrderStatus($changeType, $orderStatus, $rmaStatus);
        if(isset($mappingData['code'])){
            return $mappingData['code'];
        }

        return 0;
    }

    protected function getResultMemoParam($changeType, $orderStatus, $rmaStatus){
        $mappingData = $this->mappingOrderStatus($changeType, $orderStatus, $rmaStatus);
        if(isset($mappingData['text'])){
            return $mappingData['text'];
        }

        return '';
    }

    protected function getPurposeMemo($parentOrderId){
        $sqlChilds = 'select children_id from sales_parent_order_children where parent_id='.$parentOrderId;
        $allSubOrderIds = $this->_conn->fetchCol($sqlChilds);
        $sqlSubItems = 'select name from sales_order_item where order_id in('.implode(',', $allSubOrderIds).') and parent_item_id is null;';
        $names = $this->_conn->fetchCol($sqlSubItems);

        return implode(',', $names);
    }

    protected function mappingOrderStatus($changeType, $orderStatus, $rmaStatus){
        $mappingOrderArr = [
            'gift_info_pending' => ['code' => 21, 'text' => '已成立'],
            'gift_info_complete' => ['code' => 22, 'text' => '出貨中'],
            'arrived' => ['code' => 23, 'text' => '已接收'],
            'canceled' => ['code' => 24, 'text' => '未接受']
        ];
        $mappingRmaArr = [
            'rma_processing' => ['code' => 24, 'text' => '未接受']
        ];
        if($changeType == 'order_status'){
            if(isset($mappingOrderArr[$orderStatus])){
                return $mappingOrderArr[$orderStatus];
            }
            return false;
        }

        if($changeType == 'rma_status'){
            if(isset($mappingRmaArr[$rmaStatus])){
                return $mappingRmaArr[$rmaStatus];
            }
            return false;
        }
        return false;
    }


}