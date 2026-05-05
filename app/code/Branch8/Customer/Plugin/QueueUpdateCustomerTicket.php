<?php
namespace Branch8\Customer\Plugin;

class QueueUpdateCustomerTicket
{
    protected $publisher;

    public function __construct(
        \Magento\Framework\MessageQueue\PublisherInterface $publisher
    ){
        $this->publisher = $publisher;
    }

    public function afterCreatingAccount($subject, $result, $data){
        $customerId = $result->getId();
        $telephone = $data['phone_number'];
        $memberseq = $data['member_seq'];
        $data = ['entity_id' => $customerId, 'member_seq' => $memberseq, 'telephone' => $telephone];
        $this->publisher->publish('buyer.login.update.gift.ticket', json_encode($data));

        return $result;
    }
}
