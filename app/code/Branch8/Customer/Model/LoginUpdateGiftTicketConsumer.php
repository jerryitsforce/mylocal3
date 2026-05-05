<?php
namespace Branch8\Customer\Model;

use Magento\Framework\MessageQueue\ConsumerConfiguration;

class LoginUpdateGiftTicketConsumer extends ConsumerConfiguration{
    
    const CONSUMER_NAME = "buyer.login.update.gift.ticket";

    const QUEUE_NAME = "buyer.login.update.gift.ticket";

    protected $_conn;

    protected $hotaiAuthService;

    public function __construct(
        \Magento\Framework\App\ResourceConnection $resourceConnection,
        \Branch8\HotaiAuth\Service\HotaiAuthService $hotaiAuthService
    ){
        $this->_conn = $resourceConnection->getConnection();
        $this->hotaiAuthService = $hotaiAuthService;
    }

    public function process($request){
    
        $data = json_decode($request, true);
        $customerId = $data['entity_id'];
        $telephone = $data['telephone'];
        $memberseq = $data['member_seq'];

        /** Update for customer_ticket  */
        if(!(int)$customerId){
            $sqlGetCustomer = 'select entity_id from customer_entity where phone_number="'.$telephone.'" and member_seq="'.$memberseq.'" order by entity_id desc limit 1;';
            $customerId = $this->_conn->fetchOne($sqlGetCustomer);
        }
        
        $sqlUpdate = 'update customer_ticket set customer_id='.$customerId.', member_seq = "'.$memberseq.'" where telephone="'.$telephone.'" and customer_id=0';
        $this->_conn->query($sqlUpdate);

        /** Update for Order */
        $this->updateGiftOrder($telephone, $memberseq);
    }

    protected function updateGiftOrder($telephone, $memberSeq){
        $sqlParentGift = 'select entity_id, parent_id, recipient_telephone, recipient_member_seq from sales_parent_order_detail 
            where is_gift_order = 1 and is_gift_confirmed = 1 and recipient_telephone="'.$telephone.'" and recipient_member_seq is null';
        $queryParentGift = $this->_conn->query($sqlParentGift);
        while($row = $queryParentGift->fetch()){
            try{
                /** Parent order */
                $this->_conn->update('sales_parent_order_detail', ['recipient_member_seq' => $memberSeq], 'entity_id='.$row['entity_id']);
                $this->_conn->update('sales_parent_order_grid', ['recipient_member_seq' => $memberSeq], 'entity_id='.$row['parent_id']);
                /** Sub orders */
                $sqlChilds = 'select children_id from sales_parent_order_children where parent_id='.$row['parent_id'];
                $allSubOrderEntityIds = $this->_conn->fetchCol($sqlChilds);
                if($allSubOrderEntityIds){
                    $allSubOrderEntityIdsStr = implode(',', $allSubOrderEntityIds);
                    $sqlUpdateMemberSeqSubOrders = 'update sales_order set recipient_member_seq="'.$memberSeq.'" where entity_id in('.$allSubOrderEntityIdsStr.')';
                    $this->_conn->query($sqlUpdateMemberSeqSubOrders);
                    $sqlUpdateMemberSeqSubOrdersGrid = 'update sales_order_grid set recipient_member_seq="'.$memberSeq.'" where entity_id in('.$allSubOrderEntityIdsStr.')';
                    $this->_conn->query($sqlUpdateMemberSeqSubOrdersGrid);
                }
            }catch(\Exception $e){
                continue;
            }
        }
    }


}