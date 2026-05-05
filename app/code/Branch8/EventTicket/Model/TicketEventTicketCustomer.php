<?php

namespace Branch8\EventTicket\Model;

class TicketEventTicketCustomer extends \Magento\Framework\Model\AbstractModel implements \Magento\Framework\DataObject\IdentityInterface{

    const CACHE_TAG = 'ticket_event_ticket_customer';

    protected $_cacheTag = 'ticket_event_ticket_customer';

    protected $_eventPrefix = 'ticket_event_ticket_customer';

    /**
     * @return void
     */
    protected function _construct(){
        $this->_init('Branch8\EventTicket\Model\ResourceModel\TicketEventTicketCustomer');
    }

    /**
     * @return string[]
     */
    public function getIdentities(){
        return [self::CACHE_TAG . '_' . $this->getId()];
    }

    /**
     * @return int
     */
    public function getDefaultValues(){
        $values = 1;

        return $values;
    }

}