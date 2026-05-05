<?php

namespace Branch8\RewardSystem\Model\Config\Source;

class PoolOptions extends \Magento\Eav\Model\Entity\Attribute\Source\AbstractSource
{
    protected $ticketEventCollection;
    public function __construct(
        \Branch8\EventTicket\Model\ResourceModel\TicketEvent\CollectionFactory $ticketEventCollection
    ){
        $this->ticketEventCollection = $ticketEventCollection;
    }

    public function getAllOptions()
    {
        $ticketCol = $this->ticketEventCollection->create()
            ->addFieldToFilter('is_enable', 1)
            ->addOrder('entity_id', 'desc');
        $options = [];
        foreach($ticketCol as $_ticket){
            $options[] = [
                'label' => $_ticket->getName(),
                'value' => $_ticket->getId()
            ];
        }

        return $options;

    }
}