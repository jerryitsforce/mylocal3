<?php
declare(strict_types=1);

namespace Branch8\HelpDeskNotification\Block\Adminhtml;

use Branch8\HelpDesk\Model\ResourceModel\Ticket\CollectionFactory;
use Branch8\HelpDesk\Model\TeamFactory;
use Branch8\HelpDesk\Model\Ticket\Status;
use Magento\User\Model\User;

/**
 *
 */
class Window extends \Magento\Backend\Block\Template
{
    const XML_PATH_HEAD_CSR_TEAM = 'helpdesk/general_settings/head_csr_team';
    const XML_PATH_STATUSES_TICKET_DAYS = 'helpdesk/general_settings/status_dashboards';

    /**
     * Authentication
     *
     * @var \Magento\Backend\Model\Auth\Session
     */
    protected $_authSession;

    /**
     * @var \Branch8\HelpDesk\Model\ResourceModel\Ticket\Collection
     */
    protected $collection;

    /**
     * @param \Magento\Backend\Block\Template\Context $context
     * @param \Magento\Backend\Model\Auth\Session $authSession
     * @param TeamFactory $teamFactory
     * @param CollectionFactory $ticketCollectionFactory
     * @param array $data
     */
    public function __construct(
        \Magento\Backend\Block\Template\Context $context,
        \Magento\Backend\Model\Auth\Session     $authSession,
        TeamFactory                             $teamFactory,
        CollectionFactory                       $ticketCollectionFactory,
        array                                   $data = []
    )
    {
        parent::__construct($context, $data);
        $this->_authSession = $authSession;
        $this->teamFactory = $teamFactory;
        $this->ticketCollectionFactory = $ticketCollectionFactory;
    }

    /**
     * Render block
     *
     * @return string
     */
    protected function _toHtml()
    {
        if ($this->canShow()) {
            $total = count($this->getTicketCollection());
            $text = $total <= 1 ?
                __('You have %1 ticket is Un-Processed', $this->getInddexLink($total)) :
                __('You have %1 tickets are Un-Processed', $this->getInddexLink($total));
            $this->setHeaderText($this->escapeHtml(__('HelpDesk Ticket Notification')));
            $this->setCloseText($this->escapeHtml(__('close')));
            $this->setReadDetailsText($this->escapeHtml(__('Go To Details')));
            $this->setNoticeMessageText($text);
            $this->setNoticeMessageUrl($this->getUrl('helpdesk/ticket/index'));
            $this->setSeverityText('critical');
            return parent::_toHtml();
        }
        return '';
    }

    /**
     * Get Index Link
     * @param $total
     * @return string
     */
    private function getInddexLink($total)
    {
        return '<a href="' . $this->getUrl('helpdesk/ticket/index') . '">' . $total . '</a>';
    }

    /**
     * Get Disable Popup Url
     * @return string
     */
    public function getTiketHelpDeskLink()
    {
        return $this->getUrl('helpdesk/ticket/index');
    }

    /**
     * @return \Branch8\HelpDesk\Model\ResourceModel\Ticket\Collection
     */
    private function getTicketCollection()
    {
        if ($this->collection !== null) {
            return $this->collection;
        }
        /**
         *
         */
        $statues = explode(',', (string)$this->_scopeConfig->getValue(self::XML_PATH_STATUSES_TICKET_DAYS));
        $userId = (int)$this->_authSession->getUser()->getId();
        $isHeadCsrMember = in_array($userId, $this->getHeadCsrTeamMembers());
        $this->collection = $this->ticketCollectionFactory->create();
        $this->collection->addFieldToFilter(
            'status', [
                'in' => $statues
            ]
        );
        if (!$isHeadCsrMember) {
            $this->collection->addFieldToFilter('user_id', $userId);
        }
        return $this->collection;
    }

    /**
     * Check whether block should be displayed
     *
     * @return bool
     */
    public function canShow()
    {
        $isDashBoard = $this->getRequest()->getFullActionName() == 'adminhtml_dashboard_index';
        return $isDashBoard && count($this->getTicketCollection()) && !$this->_authSession->getShownTicketPopup();
    }

    /**
     * @return string
     */
    public function getDismissUrl()
    {
        return $this->getUrl('helpdesk_notification/notification/dismiss');
    }

    /**
     * Get Head CSR member ids
     * @return array
     */
    private function getHeadCsrTeamMembers()
    {
        /**
         * @var $members \Magento\User\Model\ResourceModel\User\Collection
         * @var $user User
         */
        $members = $this->teamFactory->create()->load(
            (int)$this->_scopeConfig->getValue(self::XML_PATH_HEAD_CSR_TEAM)
        )->getTeamMembers();
        $memberIds = [];
        if ($members) {
            foreach ($members as $user) {
                $memberIds[] = $user->getId();
            }
        }
        return $memberIds;
    }
}
