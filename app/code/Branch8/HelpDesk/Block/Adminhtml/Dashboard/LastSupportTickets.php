<?php
declare(strict_types=1);

namespace Branch8\HelpDesk\Block\Adminhtml\Dashboard;

use Branch8\HelpDesk\Model\Ticket\Status;
use Magento\Backend\Block\Template\Context;
use Branch8\HelpDesk\Model\ResourceModel\Ticket\ReportCollectionFactory;

/**
 * Adminhtml dashboard totals bar
 * @api
 * @since 100.0.2
 */
class LastSupportTickets extends \Magento\Backend\Block\Widget
{
    const XML_PATH_NUMBER_UNPROCESS_TICKET_DAYS = 'helpdesk/general_settings/dashboard_unprocessed_ticket_days';
    const XML_PATH_STATUSES_TICKET_DAYS = 'helpdesk/general_settings/status_dashboards';
    /**
     * @var string
     */
    protected $_template = 'Branch8_HelpDesk::dashboard/ticketbar.phtml';
    /**
     * @var \Branch8\HelpDesk\Model\ResourceModel\Ticket\ReportCollection
     */
    private $collectionFactory;
    /**
     * @var
     */
    private $collection;

    private $total = [];

    /**
     * @param Context $context
     * @param ReportCollectionFactory $collectionFactory
     * @param array $data
     */
    public function __construct(
        Context                 $context,
        ReportCollectionFactory $collectionFactory,
        array                   $data = []
    )
    {
        $this->collectionFactory = $collectionFactory;
        parent::__construct($context, $data);
    }

    protected function _prepareLayout()
    {
        $statues = explode(',', (string)$this->_scopeConfig->getValue(self::XML_PATH_STATUSES_TICKET_DAYS));
        if ($this->collection === null) {
            $this->collection = $this->collectionFactory->create();
            $this->collection->addCreateAtPeriodFilter('3d')->addFieldToFilter(
                'status', [
                    'in' => $statues
                ]
            )->calculateTotals();
            $this->collection->load();
            $totals = $this->collection->getFirstItem();
            $this->setTotalValue((int)$totals->getTicket());
        }
        parent::_prepareLayout();
    }

    /**
     * @param $value
     * @return void
     */
    private function setTotalValue($value)
    {
        $this->total['value'] = $value;
    }

    /**
     * @return array
     */
    public function getTotal()
    {
        return $this->total;
    }

    /**
     * @return int
     */
    public function getPeriod()
    {
        return (int)$this->_scopeConfig->getValue(self::XML_PATH_NUMBER_UNPROCESS_TICKET_DAYS);
    }

    /**
     * @return string
     */
    public function getViewTicketLink()
    {
        return $this->_urlBuilder->getUrl('helpdesk/ticket');
    }

}
