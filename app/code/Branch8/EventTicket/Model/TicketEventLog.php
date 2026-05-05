<?php

namespace Branch8\EventTicket\Model;

class TicketEventLog extends \Magento\Framework\Model\AbstractModel implements \Magento\Framework\DataObject\IdentityInterface{

    const CACHE_TAG = 'ticket_event_log';

    protected $_cacheTag = 'ticket_event_log';

    protected $_eventPrefix = 'ticket_event_log';

    /**
     * @return void
     */
    protected function _construct(){
        $this->_init('Branch8\EventTicket\Model\ResourceModel\TicketEventLog');
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