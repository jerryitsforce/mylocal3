<?php
declare(strict_types=1);

namespace Branch8\HelpDesk\Block\Adminhtml;

class Ticket extends \Magento\Backend\Block\Widget\Container
{
    public function _construct()
    {
        $this->_blockGroup = 'Branch8_HelpDesk';
        $this->_controller = 'adminhtml_ticket';
        $this->_headerText = __('Tickets');
        $this->_addButtonLabel = __('Add New Ticket');
        parent::_construct();
    }
}
