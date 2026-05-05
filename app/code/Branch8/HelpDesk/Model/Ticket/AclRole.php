<?php
declare(strict_types=1);

namespace Branch8\HelpDesk\Model\Ticket;

use Magento\Framework\AuthorizationInterface;

class AclRole
{
    private $_authorization;

    const EDIT_TICKET = 'Branch8_HelpDesk::ticket_edit';
    const CHANGE_TEAM_TICKET = 'Branch8_HelpDesk::ticket_change_team';
    const VIEW_TICKET = 'Branch8_HelpDesk::ticket_view';
    const NEW_TICKET = 'Branch8_HelpDesk::ticket_new';
    const ASSIGN_TICKET = 'Branch8_HelpDesk::ticket_assign';
    const REMOVE_TICKET = 'Branch8_HelpDesk::ticket_delete';
    const ANSWER_TICKET = 'Branch8_HelpDesk::answer_ticket';
    const MANAGE_CATEGORY='Branch8_HelpDesk::manage_category';
    const MANAGE_TEAM='Branch8_HelpDesk::manage_team';
    /**
     * @param AuthorizationInterface $authorization
     */
    public function __construct(AuthorizationInterface $authorization)
    {
        $this->_authorization = $authorization;
    }

    /**
     * isAllowedAction
     * @param $resourceId
     * @return bool
     */
    public function isAllowedAction($resourceId)
    {
        return $this->_authorization->isAllowed($resourceId);
    }

    /**
     * canNotPostMessage
     * @return bool
     */
    public function canNotPostMessage()
    {
        return (bool)$this->_authorization->isAllowed(self::ANSWER_TICKET)===false;
    }
}
