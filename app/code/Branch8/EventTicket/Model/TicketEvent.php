<?php

namespace Branch8\EventTicket\Model;

class TicketEvent extends \Magento\Framework\Model\AbstractModel implements \Magento\Framework\DataObject\IdentityInterface{

    const CACHE_TAG = 'ticket_event';

    protected $_cacheTag = 'ticket_event';

    protected $_eventPrefix = 'ticket_event';

    /**
     * @return void
     */
    protected function _construct(){
        $this->_init('Branch8\EventTicket\Model\ResourceModel\TicketEvent');
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