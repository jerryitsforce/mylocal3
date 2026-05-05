<?php

namespace Branch8\EventTicket\Block\Adminhtml\Event\Edit;

use Magento\Directory\Helper\Data as DirectoryHelper;
use Magento\Framework\Json\Helper\Data as JsonHelper;
use Magento\Framework\Registry;

class Append extends \Magento\Backend\Block\Template
{

    protected $registry;

    protected $eventTicketHelper;

    public function __construct(
        \Magento\Backend\Block\Template\Context $context,
        Registry $registry,
        \Branch8\EventTicket\Helper\Data $eventTicketHelper,
        array $data = [],
        ?JsonHelper $jsonHelper = null,
        ?DirectoryHelper $directoryHelper = null
    )
    {
        parent::__construct($context, $data, $jsonHelper, $directoryHelper);
        $this->registry = $registry;
        $this->eventTicketHelper = $eventTicketHelper;
    }
    public function canEditType(){
        $eventId = $this->registry->registry('current_pool');
        return (int)$eventId;
    }

    public function canEditSeller(){
        return (int)$this->registry->registry('pool_has_serial');
    }

}