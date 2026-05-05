<?php

namespace Branch8\GiftToFriend\Cron;

use Magento\Framework\App\ResourceConnection;

class UpdateCustomerId
{
    protected $_conn;

    protected $hotaiAuthService;

    protected $memberSeqs = [];

    protected $customerEntityIds = [];

    const PAGINATE_NUMBER = 1000;

    public function __construct(
        ResourceConnection $resourceConnection,
        \Branch8\HotaiAuth\Service\HotaiAuthService $hotaiAuthService
    ) {
        $this->_conn = $resourceConnection->getConnection();
        $this->hotaiAuthService = $hotaiAuthService;
    }

    public function execute()
    {
        try{
            $this->updateCustomerTicket();
        }catch(\Exception $e){

        }
        try{
            $this->updateGiftOrder();
        }catch(\Exception $e){

        }
    }

    protected function updateCustomerTicket(){
        $sql = 'select record_id, telephone, member_seq from customer_ticket 
            where telephone is not null and (customer_id = 0 or customer_id is null)';
        $query = $this->_conn->query($sql);

        $telephones = [];
        while($row = $query->fetch()){
            $memberSeq = (string)$row['member_seq'];
            // if(trim($memberSeq) != ''){
            //     continue;
            // }
            $quickUpdateData = [];
            if(isset($this->memberSeqs[$row['telephone']])){
                $quickUpdateData['member_seq'] = $this->memberSeqs[$row['telephone']];
            }
            if(isset($quickUpdateData['member_seq']) && isset($this->customerEntityIds[$quickUpdateData['member_seq']])){
                $quickUpdateData['customer_id'] = $this->customerEntityIds[$quickUpdateData['member_seq']];
            }
            if(!empty($quickUpdateData)){
                $this->executeUpdateCustomerTicketOne($row['record_id'], $quickUpdateData);
                continue;
            }

            $telephones[$row['record_id']] = $row['telephone'];
            if(count($telephones) > self::PAGINATE_NUMBER){
                $this->executeUpdateCustomerTicket($telephones);
                $telephones = [];
            }
        }
        if(!empty($telephones)){
            $this->executeUpdateCustomerTicket($telephones);
        }
        
    }

    protected function executeUpdateCustomerTicket($telephones){
        $memberSeqs = $this->queryMemberSeq($telephones);
        $this->memberSeqs = array_merge($this->memberSeqs, $memberSeqs);
        $customerIdSelect = $this->_conn->select()
            ->from('customer_entity', ['entity_id', 'member_seq'])
            ->where('member_seq in (?)', $memberSeqs);
        $customerIdQuery = $this->_conn->query($customerIdSelect);
        $customerData = [];
        while($rowCustomer = $customerIdQuery->fetch()){
            $customerData[strtolower((string)$rowCustomer['member_seq'])] = $rowCustomer['entity_id'];
        }
        $this->customerEntityIds = array_merge($this->customerEntityIds, $customerData);
        foreach($telephones as $customerTicketRecordId => $_telephone){
            if(!isset($memberSeqs[$_telephone])){
                continue;
            }

            $itemMemberSeq = $memberSeqs[$_telephone];
            $setArr['member_seq'] = $itemMemberSeq;
            if(isset($customerData[$itemMemberSeq])){
                $setArr['customer_id'] = $customerData[$itemMemberSeq];
            }
            $this->executeUpdateCustomerTicketOne($customerTicketRecordId, $setArr);
        }
    }

    protected function executeUpdateCustomerTicketOne($customerTicketRecordId, $setArr){
        $this->_conn->update('customer_ticket', $setArr, 'record_id='.$customerTicketRecordId);
    }

    protected function updateGiftOrder(){
        $sqlParentGift = 'select entity_id, parent_id, recipient_telephone, recipient_member_seq from sales_parent_order_detail 
            where is_gift_order = 1 and is_gift_confirmed = 1 and is_removed_gift_box=0 and recipient_member_seq is null';
        $queryParentGift = $this->_conn->query($sqlParentGift);
        $telephones = [];
        while($row = $queryParentGift->fetch()){
            try{
                $telephone = $row['recipient_telephone'];
                if(isset($this->memberSeqs[$telephone])){
                    $this->executeUpdateGiftOrderOne($row['entity_id'], $row['parent_id'], $this->memberSeqs[$telephone]);
                    continue;
                }

                $telephones[$telephone] = ['parent_entity_id' => $row['entity_id'], 'parent_id' => $row['parent_id']];
                if(count($telephones) > self::PAGINATE_NUMBER){
                    $this->executeUpdateGiftOrder($telephones);
                    $telephones = [];
                }

            }catch(\Exception $e){echo $e->getMessage();
                continue;
            }
        }
        if(!empty($telephones)){
            $this->executeUpdateGiftOrder($telephones);
        }
    }

    protected function executeUpdateGiftOrder($telephonesData){
        $telephones = array_keys($telephonesData);
        $memberSeqs = $this->queryMemberSeq($telephones);
        foreach($telephonesData as $phoneNumber => $_telephoneData){
            $parentEntityId = $_telephoneData['parent_entity_id'];
            $parentOrderId = $_telephoneData['parent_id'];
            if(!isset($memberSeqs[$phoneNumber])){
                continue;
            }
            $this->executeUpdateGiftOrderOne($parentEntityId, $parentOrderId, $memberSeqs[$phoneNumber]);
        }
    }

    protected function executeUpdateGiftOrderOne($parentOrderEntityId, $parentOrderId, $memberSeq){
        /** Parent order */
        $this->_conn->update('sales_parent_order_detail', ['recipient_member_seq' => $memberSeq], 'entity_id='.$parentOrderEntityId);
        $this->_conn->update('sales_parent_order_grid', ['recipient_member_seq' => $memberSeq], 'entity_id='.$parentOrderId);
        /** Sub orders */
        $sqlChilds = 'select children_id from sales_parent_order_children where parent_id='.$parentOrderId;
        $allSubOrderEntityIds = $this->_conn->fetchCol($sqlChilds);
        if($allSubOrderEntityIds){
            $allSubOrderEntityIdsStr = implode(',', $allSubOrderEntityIds);
            $sqlUpdateMemberSeqSubOrders = 'update sales_order set recipient_member_seq="'.$memberSeq.'" where entity_id in('.$allSubOrderEntityIdsStr.')';
            $this->_conn->query($sqlUpdateMemberSeqSubOrders);
            $sqlUpdateMemberSeqSubOrdersGrid = 'update sales_order_grid set recipient_member_seq="'.$memberSeq.'" where entity_id in('.$allSubOrderEntityIdsStr.')';
            $this->_conn->query($sqlUpdateMemberSeqSubOrdersGrid);
        }
    }

    protected function queryMemberSeq($telephones){
        try{
            $telephones = array_values($telephones);
            $memberSeqData = $this->hotaiAuthService->getListOneIdByPhoneNumber($telephones);
            $memberSeqs = [];
            foreach($memberSeqData as $_mbs){
                $memberSeqs[$_mbs['mobilePhone']] = strtolower((string)$_mbs['memberId']);
            }
            return $memberSeqs;
        }catch(\Exception $e){
            return [];
        }
    }
}
