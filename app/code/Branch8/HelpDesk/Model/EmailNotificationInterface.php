<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Branch8\HelpDesk\Model;

use Magento\User\Model\User;

interface EmailNotificationInterface
{
    const XML_PATH_HEAD_CSR_TEAM = 'helpdesk/general_settings/head_csr_team';
    const XML_PATH_SENDER = 'helpdesk/email_settings/sender_email_identity';
    const XML_PATH_EMAIL_CC = 'helpdesk/email_settings/email_receiver';
    const XML_PATH_DISABLE_ATTACHMENTS = 'helpdesk/email_settings/disable_attachments';
    const XML_PATH_ENABLE_SEND_MAIL_ADMIN = 'helpdesk/email_settings/admin_setting/admin_enable_sendmail';
    const XML_PATH_ADMIN_NEW_TICKET_TEMPLATE = 'helpdesk/email_settings/admin_setting/admin_new_ticket_template';
    const XML_PATH_ADMIN_NEW_MESSAGE_TEMPLATE = 'helpdesk/email_settings/admin_setting/admin_new_message_template';

    const XML_PATH_ADMIN_ASSIGN_TICKET_TEMPLATE = 'helpdesk/email_settings/admin_setting/user_ticket_assigned_template';

    const XML_PATH_ENABLE_SEND_MAIL_CUSTOMER = 'helpdesk/email_settings/customer_setting/customer_enable_sendmail';
    const XML_PATH_CUSTOMER_NEW_TICKET_TEMPLATE = 'helpdesk/email_settings/customer_setting/customer_new_ticket_template';
    const XML_PATH_CUSTOMER_NEW_MESSAGE_TEMPLATE = 'helpdesk/email_settings/customer_setting/customer_new_message_template';
    const XML_PATH_CUSTOMER_NEW_STATUS_TEMPLATE = 'helpdesk/email_settings/customer_setting/customer_new_status_template';

    /**
     * @param Ticket $ticket
     * @param $customer
     * @param $attachments
     * @return mixed
     */
    public function notifyCustomerWhenHaveNewTicket(Ticket $ticket, $customer, $attachments = []);

    /**
     * @param Ticket $ticket
     * @param string $userEmail
     * @return mixed
     */
    public function notifyUserWhenHaveNewticket(Ticket $ticket, string $userEmail);

    /**
     * @param Ticket $ticket
     * @param $customer
     * @return mixed
     */
    public function notifyCustomerWhenHaveMessage(Ticket $ticket, $customer, $attachments = []);

    /**
     * @param User $user
     * @param Ticket $ticket
     * @param $attachments
     * @return mixed
     */
    public function notifyUserWhenHaveMessage(User $user, Ticket $ticket, $attachments = []);

    /**
     * @param Ticket $ticket
     * @param $customer
     * @return mixed
     */
    public function notifyCustomerWhenStatusChange(Ticket $ticket, $customer);

    /**
     * @param Ticket $ticket
     * @return $this
     */
    public function notifyToCsrHeadTeamWhenHaveNewTicket(Ticket $ticket, $attachments = []);

    /**
     * @param Ticket $ticket
     * @param string|null $userEmail
     * @param $attachments
     * @return mixed
     */
    public function notifyUserWhenAssignedTicket(Ticket $ticket, string $userEmail = null, $attachments = []);
}
