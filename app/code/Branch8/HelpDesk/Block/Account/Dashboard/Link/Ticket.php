<?php
declare(strict_types=1);

namespace Branch8\HelpDesk\Block\Account\Dashboard\Link;

use Branch8\HelpDesk\Model\Ticket\BelongTo;

/**
 * Menu in Customer Account Dashboard
 */
class Ticket extends \Magento\Framework\View\Element\Html\Link\Current
{
    private $customerSession;
    /**
     * @var
     */
    protected $unreadMessageCollectionFactory;
    /**
     * @var \Branch8\HelpDesk\Model\MessageFactory|\Branch8\HelpDesk\Model\ResourceModel\Message\CollectionFactory
     */
    protected $unreadCollection;

    /**
     * @var \Branch8\HelpDesk\Model\ResourceModel\Ticket\CollectionFactory
     */
    private $ticketCollectionFactory;

    /**
     * @var \Branch8\HelpDesk\Model\ResourceModel\Ticket\Collection
     */
    private $ticketCollection;

    /**
     * @param \Magento\Framework\View\Element\Template\Context $context
     * @param \Magento\Framework\App\DefaultPathInterface $defaultPath
     * @param \Magento\Customer\Model\Session $customerSession
     * @param \Branch8\HelpDesk\Model\ResourceModel\Message\CollectionFactory $collectionFactory
     * @param \Branch8\HelpDesk\Model\ResourceModel\Ticket\CollectionFactory $ticketCollectionFactory
     * @param array $data
     */
    public function __construct(
        \Magento\Framework\View\Element\Template\Context                $context,
        \Magento\Framework\App\DefaultPathInterface                     $defaultPath,
        \Magento\Customer\Model\Session                                 $customerSession,
        \Branch8\HelpDesk\Model\ResourceModel\Message\CollectionFactory $collectionFactory,
        \Branch8\HelpDesk\Model\ResourceModel\Ticket\CollectionFactory  $ticketCollectionFactory,
        array                                                           $data = []
    )
    {
        parent::__construct($context, $defaultPath, $data);
        $this->customerSession = $customerSession;
        $this->unreadMessageCollectionFactory = $collectionFactory;
        $this->ticketCollectionFactory = $ticketCollectionFactory;
    }

    /**
     * Get all UnRead message customer
     * @return \Branch8\HelpDesk\Model\MessageFactory|\Branch8\HelpDesk\Model\ResourceModel\Message\CollectionFactory
     */
    public function getUnreadMessageCollection()
    {
        if (!$this->unreadCollection) {
            $this->unreadCollection = $this->unreadMessageCollectionFactory->create();
            $this->unreadCollection
                ->addFieldToFilter(
                    'customer_id',
                    $this->customerSession->getCustomerId()
                )->addFieldToFilter(
                    'belong_to', ['notin' => [BelongTo::CUSTOMER]]
                )
                ->addFieldToFilter('is_read', 0)
                ->setOrder('message_id', 'DESC')
                ->setPageSize(5);
        }

        return $this->unreadCollection;
    }

    /**
     * Get Unread Message Count
     *
     * @return int
     */
    public function getUnreadMessageCount()
    {
        return $this->getUnreadMessageCollection()->getSize();
    }

    /**
     * @return \Branch8\HelpDesk\Model\ResourceModel\Ticket\Collection
     */
    public function getCustomerTicketCollection()
    {
        if (!$this->ticketCollection) {
            $this->ticketCollection = $this->ticketCollectionFactory->create()
                ->addFieldToFilter(
                    'customer_id',
                    $this->customerSession->getCustomerId()
                );
        }
        return $this->ticketCollection;
    }

    /**
     * @return mixed
     */
    public function getTicketCount()
    {
        return $this->getCustomerTicketCollection()->getSize();
    }

    /**
     * @return mixed
     */
    public function getCustomer()
    {
        return $this->customerSession->getCustomer();
    }

    /**
     * Render block HTML
     *
     * @return string
     */
    protected function _toHtml()
    {
        if ($this->getTemplate()) {
            return parent::_toHtml();
        }
        $html = '';
        $customer = $this->getCustomer();
        if (!$customer) {
            return;
        }

        $message = '';
        if ($this->getTicketCount() > 0) {
            if ($this->getTicketCount() == 1) {
                $message = ' (' . $this->getTicketCount() . ' ticket)';
            } else {
                $message = ' (' . $this->getTicketCount() . ' tickets)';
            }
        }
        $highlight = '';
        if ($this->getIsHighlighted()) {
            $highlight = ' current';
        }

        if ($this->isCurrent()) {
            $html = '<li class="helpdesk nav item current lrw-nav-item">';
            $html .= '<strong>'
                . '<span>' . $this->escapeHtml((string)new \Magento\Framework\Phrase($this->getLabel())) . $message . '</span>';
            $html .= '</strong>';
            $html .= '</li>';
        } else {
            $html = '<li class="helpdesk nav item' . $highlight . ' lrw-nav-item">';
            $html .= '<a class="notifications-action" href="' . $this->escapeHtml($this->getHref()) . '"';
            $html .= $this->getTitle()
                ? ' title="' . $this->escapeHtml((string)new \Magento\Framework\Phrase($this->getTitle())) . '"'
                : '';
            $html .= $this->getAttributesHtml() . '>';

            if ($this->getIsHighlighted()) {
                $html .= '<strong>';
            }

            $html .= '<span>' . $this->escapeHtml((string)new \Magento\Framework\Phrase($this->getLabel())) . $message . '</span>';

            if ($this->getIsHighlighted()) {
                $html .= '</strong>';
            }
            $html .= '</a></li>';
        }
        return $html;
    }
}
