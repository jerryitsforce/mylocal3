<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Branch8\HelpDesk\Model;

use Branch8\HelpDesk\Api\MessageRepositoryInterface;
use Branch8\HelpDesk\Api\TicketRepositoryInterface;
use Branch8\HelpDesk\Model\Ticket\Priority;
use Branch8\HelpDesk\Model\Ticket\Status;
use Branch8\MaskInformation\Api\MaskRulesCompositeInterface;
use Magento\Customer\Model\Customer;
use Magento\Framework\App\Area;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\ObjectManager;
use Magento\Framework\Event\Manager;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Mail\MessageInterface;
use Magento\Framework\Mail\Template\SenderResolverInterface;
use Magento\Framework\Stdlib\DateTime\TimezoneInterface;
use Magento\Framework\UrlInterface;
use Magento\Store\Model\App\Emulation;
use Magento\Store\Model\ScopeInterface;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Framework\Reflection\DataObjectProcessor;
use Magento\Framework\Translate\Inline\StateInterface;
use Magento\User\Model\User;
use Branch8\HelpDesk\Helper\Logger as LoggerInterface;
use Magento\Framework\DataObject;

/**
 * Email Notification
 */
class EmailNotification implements EmailNotificationInterface
{
    /**
     * @var StoreManagerInterface
     */
    private $storeManager;

    /**
     * @var TransportBuilder
     */
    private $transportBuilder;

    /**
     * @var DataObjectProcessor
     */
    protected $dataProcessor;

    /**
     * @var ScopeConfigInterface
     */
    private $scopeConfig;

    /**
     * @var SenderResolverInterface
     */
    private $senderResolver;

    /**
     * @var Emulation
     */
    private $emulation;
    /**
     * @var TicketRepositoryInterface
     */
    public TicketRepositoryInterface $ticketRepository;
    /**
     * @var MessageRepositoryInterface
     */
    public MessageRepositoryInterface $messageRepository;
    /**
     * @var StateInterface
     */
    public StateInterface $inlineTranslation;
    /**
     * @var LoggerInterface
     */
    public LoggerInterface $logger;
    /**
     * @var Status
     */
    public Status $status;
    /**
     * @var Priority
     */
    public Priority $priority;
    /**
     * @var TimezoneInterface
     */
    public TimezoneInterface $timezone;
    /**
     * @var \Branch8\HelpDesk\Model\TeamFactory
     */
    public TeamFactory $teamFactory;
    private \Magento\Framework\Archive\Zip $archive;

    private $ticketOrderIds = [];

    private $mine;

    private $categoryCollectionOptions = [];
    private ResourceModel\Category\CollectionFactory $categoryCollectionFactory;
    private \Magento\Framework\DB\Adapter\AdapterInterface $connection;
    public Manager $eventManager;

    private $maskRulesComposite;


    /**
     * @param TicketRepositoryInterface $ticketRepository
     * @param MessageRepositoryInterface $messageRepository
     * @param StoreManagerInterface $storeManager
     * @param Status $status
     * @param Priority $priority
     * @param TransportBuilder $transportBuilder
     * @param ResourceModel\Category\CollectionFactory $categoryCollectionFactory
     * @param StateInterface $inlineTranslation
     * @param ScopeConfigInterface $scopeConfig
     * @param LoggerInterface $logger
     * @param TimezoneInterface $timezone
     * @param TeamFactory $teamFactory
     * @param \Magento\Framework\App\ResourceConnection $resource
     * @param MaskRulesCompositeInterface $maskRulesComposite
     * @param Manager $eventManager
     * @param \Magento\Framework\Archive\Zip $archive
     * @param \Magento\Framework\Filesystem\Driver\File\Mime $mine
     * @param SenderResolverInterface|null $senderResolver
     * @param Emulation|null $emulation
     */
    public function __construct(
        TicketRepositoryInterface                                        $ticketRepository,
        MessageRepositoryInterface                                       $messageRepository,
        StoreManagerInterface                                            $storeManager,
        Status                                                           $status,
        Priority                                                         $priority,
        TransportBuilder                                                 $transportBuilder,
        \Branch8\HelpDesk\Model\ResourceModel\Category\CollectionFactory $categoryCollectionFactory,
        StateInterface                                 $inlineTranslation,
        ScopeConfigInterface                           $scopeConfig,
        LoggerInterface                                $logger,
        TimezoneInterface                              $timezone,
        TeamFactory                                    $teamFactory,
        \Magento\Framework\App\ResourceConnection      $resource,
        MaskRulesCompositeInterface                    $maskRulesComposite,
        Manager                                        $eventManager,
        \Magento\Framework\Archive\Zip                 $archive,
        \Magento\Framework\Filesystem\Driver\File\Mime $mine,
        SenderResolverInterface                        $senderResolver = null,
        Emulation                                      $emulation = null
    )
    {
        $this->mine = $mine;
        $this->archive = $archive;
        $this->inlineTranslation = $inlineTranslation;
        $this->ticketRepository = $ticketRepository;
        $this->messageRepository = $messageRepository;
        $this->storeManager = $storeManager;
        $this->transportBuilder = $transportBuilder;
        $this->scopeConfig = $scopeConfig;
        $this->logger = $logger;
        $this->status = $status;
        $this->priority = $priority;
        $this->timezone = $timezone;
        $this->teamFactory = $teamFactory;
        $this->eventManager = $eventManager;
        $this->categoryCollectionFactory = $categoryCollectionFactory;
        $this->connection = $resource->getConnection();
        $this->maskRulesComposite = $maskRulesComposite;
        $this->senderResolver = $senderResolver ?? ObjectManager::getInstance()->get(SenderResolverInterface::class);
        $this->emulation = $emulation ?? ObjectManager::getInstance()->get(Emulation::class);
        foreach ($this->categoryCollectionFactory->create() as $item) {
            $this->categoryCollectionOptions[$item->getId()] = $item->getTitle();
        }
    }

    /**
     * GetDefaultArrayTicketData
     * @param Ticket $ticket
     * @return array
     */
    private function getDefaultArrayTicketData(Ticket $ticket)
    {
        return [
            'title' => $ticket->getTitle(),
            'phone' => $this->maskRulesComposite->mask('phone', (string)$ticket->getPhone()),
            'code' => $ticket->getCode(),
            'customer_name' => $ticket->getCustomerName(),
            'customer_email' => $this->maskRulesComposite->mask('email', (string)$ticket->getCustomerEmail()),
            'user_name' => $ticket->getUser()->getName() ? $ticket->getUser()->getName() : __('Not Assign')->render(),
            'team' => $ticket->getTeam()->getName() ? $ticket->getTeam()->getName() : __('Not Assign')->render(),
            'staff' => $ticket->getUser() ? $ticket->getUser()->getName() : __('Staff')->render(),
            'category' => isset($this->categoryCollectionOptions[$ticket->getCategoryId()]) ? $this->categoryCollectionOptions[$ticket->getCategoryId()] : '',
            'orders' => $this->getOrdersIncrementId($ticket),
            'status' => $this->status->getStatusLabel($ticket->getStatus()),
            'frontend_status' => $this->status->getStatusLabel($ticket->getFrontendStatus()),
            'priority' => $this->priority->getPriorityLabel($ticket->getPriority()),
            'createdAt' => $this->getCreatedAtFormatted($ticket->getCreatedAt()),
            'content' => $ticket->getContent(),
            'lastReplyBy' => $ticket->getLastReplyName(),
            'lastReplyMessage' => count($ticket->getMessages()) ?
                $ticket->getMessages()->getFirstItem()->getContent() : '',
            'frontendTicketUrl' => $this->getFrontEndTicketLinkUrl($ticket)
        ];
    }

    /**
     * @param Ticket $ticket
     * @return mixed|string
     */
    public function getOrdersIncrementId(Ticket $ticket)
    {
        if (isset($this->ticketOrderIds[$ticket->getId()])) {
            return $this->ticketOrderIds[$ticket->getId()];
        }
        try {
            $orderIds = explode(',', (string)$ticket->getOrder());
            if ($orderIds) {
                $table = 'sales_order';
                $select = $this->connection->select();
                $select->from($table, ['increment_id'])->where('entity_id in (?)', $orderIds);
                $orderIds = $this->connection->fetchCol($select);
            }

        } catch (\Exception $exception) {
            $orderIds = [];
        }
        if ($orderIds) {
            $orderIds = array_map(function ($value) {
                return "#" . $value;
            }, $orderIds);
            $this->ticketOrderIds[$ticket->getId()] = join(',', $orderIds);
        } else {
            $this->ticketOrderIds[$ticket->getId()] = '';
        }
        return $this->ticketOrderIds[$ticket->getId()];
    }

    /**
     * NotifyToCsrHeadTeamWhenHaveNewTicket
     * @param Ticket $ticket
     * @return EmailNotification
     */
    public function notifyToCsrHeadTeamWhenHaveNewTicket(Ticket $ticket, $attachments = [])
    {
        /**
         * @var $members \Magento\User\Model\ResourceModel\User\Collection
         * @var $user User
         */
        $members = $this->teamFactory->create()->load(
            (int)$this->getValue(self::XML_PATH_HEAD_CSR_TEAM)
        )->getTeamMembers();
        if ($members) {
            $mails = [];
            foreach ($members as $user) {
                $mails[] = ['email' => $user->getEmail(), 'name' => $user->getName()];
            }
            $ticketData = new DataObject(
                [
                    'ticket' => $this->getDefaultArrayTicketData($ticket)
                ]
            );
            $this->notify(
                $ticketData,
                $this->getValue(self::XML_PATH_ADMIN_NEW_TICKET_TEMPLATE),
                $mails,
                [],
                null,
                $attachments
            );
        }
        return $this;
    }

    /**
     * NotifyUserWhenHaveNewticket
     * @param Ticket $ticket
     * @param string $userEmail
     * @return $this
     */
    public function notifyUserWhenHaveNewticket(Ticket $ticket, string $userEmail = null)
    {
        $enableNotify = (bool)$this->getValue(self::XML_PATH_ENABLE_SEND_MAIL_ADMIN);
        $cc = $this->getCc();
        if ($enableNotify) {
            $ticketData = new DataObject(
                [
                    'ticket' => $this->getDefaultArrayTicketData($ticket)
                ]
            );
            $this->notify(
                $ticketData,
                $this->getValue(self::XML_PATH_ADMIN_NEW_TICKET_TEMPLATE),
                [['email' => $userEmail, 'name' => $ticket->getUserName() ?: $ticket->getUser()->getUserName()]],
                $cc
            );
        }
        return $this;
    }


    /**
     * @return void
     */
    public function notifyUserWhenAssignedTicket(Ticket $ticket, string $userEmail = null, $attachments = [])
    {
        $enableNotify = (bool)$this->getValue(self::XML_PATH_ENABLE_SEND_MAIL_ADMIN);
        $cc = $this->getCc();
        if ($enableNotify) {
            $ticketData = new DataObject(
                [
                    'ticket' => $this->getDefaultArrayTicketData($ticket)
                ]
            );
            $this->notify(
                $ticketData,
                (string)$this->getValue(self::XML_PATH_ADMIN_ASSIGN_TICKET_TEMPLATE),
                [['email' => $userEmail, 'name' => $ticket->getUserName() ?: $ticket->getUser()->getUserName()]],
                $cc,
                null,
                $attachments
            );
        }
        return $this;
    }

    /**
     * NotifyCustomerWhenHaveMessage
     * @param Ticket $ticket
     * @param $customer
     * @return $this
     * @throws LocalizedException
     */
    public function notifyCustomerWhenHaveMessage(Ticket $ticket, $customer, $attachments = [])
    {
        $enableNotify = (bool)$this->getValue(self::XML_PATH_ENABLE_SEND_MAIL_CUSTOMER);
        if (empty($customer) || !$enableNotify) {
            return;
        }
        $config = $this->resolveCustomerMailSendingConfig($ticket, $customer);
        if ($config) {
            $cc = $this->getCc();
            $ticketData = new DataObject(
                [
                    'ticket' => $this->getDefaultArrayTicketData($ticket)
                ]
            );
            $this->notify(
                $ticketData,
                $this->getValue(self::XML_PATH_CUSTOMER_NEW_MESSAGE_TEMPLATE),
                [['email' => $config['email'], 'name' => $config['name']]],
                $cc,
                (string)$config['storeId'],
                $attachments
            );
        }
        return $this;
    }

    /**
     * @param User $user
     * @param Ticket $ticket
     * @param $attachments
     * @return $this|mixed|void
     */
    public function notifyUserWhenHaveMessage(User $user, Ticket $ticket, $attachments = [])
    {
        $enableNotify = (bool)$this->getValue(self::XML_PATH_ENABLE_SEND_MAIL_ADMIN);
        $cc = $this->getCc();
        $emails = [];
        if (!$enableNotify) {
            return;
        }
        if ($user->getId()) {
            $emails [] = ['email' => $user->getEmail(), 'name' => $user->getUserName()];
        }
        /// we send mail to receiver if this ticket no have user;
        if (empty($emails) && $cc) {
            $emails = $cc;
            $cc = [];
        }
        $ticketData = new DataObject(
            [
                'ticket' => $this->getDefaultArrayTicketData($ticket)
            ]
        );
        //if have emails;
        if ($emails) {
            $this->notify(
                $ticketData,
                $this->getValue(self::XML_PATH_ADMIN_NEW_MESSAGE_TEMPLATE),
                $emails,
                $cc,
                null,
                $attachments
            );

        }
        return $this;
    }

    /**
     * @param Ticket $ticket
     * @param $customer
     * @return $this|mixed|void
     * @throws LocalizedException
     */
    public function notifyCustomerWhenStatusChange(Ticket $ticket, $customer)
    {
        $config = $this->resolveCustomerMailSendingConfig($ticket, $customer);
        if (empty($config)) {
            return;
        }
        $enableNotify = (bool)$this->getValue(self::XML_PATH_ENABLE_SEND_MAIL_CUSTOMER);
        $ticketData = $this->getDefaultArrayTicketData($ticket);
        $cc = $this->getCc();
        if ($enableNotify) {
            $this->notify(
                new DataObject(
                    [
                        'ticket' => $ticketData
                    ]
                ),
                $this->getValue(self::XML_PATH_CUSTOMER_NEW_STATUS_TEMPLATE),
                [['email' => $config['email'], 'name' => $config['name']]],
                $cc,
                (string)$config['storeId']
            );
        }
        return $this;
    }

    /**
     * @param Ticket $ticket
     * @param $customer
     * @return array
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    private function resolveCustomerMailSendingConfig(Ticket $ticket, $customer)
    {
        try {
            $storeId = \Magento\Store\Model\Store::DEFAULT_STORE_ID;
            if ($customer instanceof Customer) {
                $customerEmail = $customer->getEmail();
                $storeId = $customer->getStoreId();
            } else {
                $customerEmail = $customer;
            }
        } catch (\Exception $exception) {
            $this->logger->error($exception->getMessage(), ['context' => 'branch8_helpdesk']);
            return null;
        }
        return [
            'storeId' => $storeId,
            'email' => $customerEmail,
            'name' => is_object($customer) ? $customer->getName() : $ticket->getCustomerName()
        ];
    }

    /**
     * @param Ticket $ticket
     * @param $customer
     * @param $attachments
     * @return $this|mixed|void
     * @throws LocalizedException
     */
    public function notifyCustomerWhenHaveNewTicket(Ticket $ticket, $customer, $attachments = [])
    {
        $config = $this->resolveCustomerMailSendingConfig($ticket, $customer);
        if (empty($config)) {
            return;
        }
        $enableNotify = (bool)$this->getValue(self::XML_PATH_ENABLE_SEND_MAIL_CUSTOMER);
        $cc = $this->getCc();
        $ticketData = $this->getDefaultArrayTicketData($ticket);
        if ($enableNotify) {
            $ticketData = new DataObject(
                [
                    'ticket' => $ticketData
                ]
            );
            $this->notify(
                $ticketData,
                $this->getValue(self::XML_PATH_CUSTOMER_NEW_TICKET_TEMPLATE),
                [['email' => $config['email'], 'name' => $config['name']]],
                $cc,
                (string)$config['storeId'],
                $attachments
            );
        }
        return $this;
    }

    /**
     * @param DataObject $object
     * @param string $template
     * @param array $mails
     * @param array $cc
     * @param string|null $storeId
     * @return void
     */
    private function notify(
        DataObject $object,
        string     $template,
        array      $mails,
        array      $cc = [],
        string     $storeId = null,
        array      $attachments = []
    )
    {

        try {
            $data = $object->getData();
            if ($attachments) {
                $ids = [];
                /**
                 * @var $attachment \Branch8\HelpDesk\Model\Attachment
                 */
                foreach ($attachments as $attachment) {
                    $ids[] = $attachment->getId();
                }
                $data['attachment_ids'] = implode(',', $ids);
            } else {
                $data['attachment_ids'] = '';
            }
            $storeId = $storeId ?: $this->storeManager->getStore()->getId();
            $transportBuilder = $this->transportBuilder
                ->setTemplateIdentifier($template)
                ->setTemplateOptions(
                    [
                        'area' => \Magento\Framework\App\Area::AREA_FRONTEND,
                        'store' => $storeId ?: \Magento\Store\Model\Store::DEFAULT_STORE_ID,
                    ]
                )->setTemplateVars($data)
                ->setFrom($this->getSender());
            foreach ($mails as $to) {
                $transportBuilder->addTo($to['email'], $to['name']);
            }
            if ($attachments) {
                $this->processAddAttachment($transportBuilder, $attachments);
            }
            if ($cc) {
                foreach ($cc as $c) {
                    $transportBuilder->addCc($c['email'], '');
                }
            }
            $transport = $transportBuilder->getTransport();
            try {
                /**
                 * @var $message MessageInterface
                 */
                $transport->sendMessage();
                $this->inlineTranslation->resume();
            } catch (\Exception $e) {
                $this->logger->error($e->getMessage(), ['trace' => $e->getTraceAsString()]);
            }
        } catch (\Exception $e) {
            $this->logger->error($e->getMessage(), ['trace' => $e->getTraceAsString()]);
            $this->inlineTranslation->resume();
            return;
        }
    }

    /**
     * GetSender
     * @return mixed
     */
    private function getSender()
    {
        return $this->getValue(self::XML_PATH_SENDER);
    }

    /**
     * GetValue
     * @param $path
     * @return mixed
     */
    private function getValue($path)
    {
        return $this->scopeConfig->getValue(
            $path
        );
    }

    /**
     * GetCreatedAtFormatted
     * @param $date
     * @return string
     */
    public function getCreatedAtFormatted($date)
    {
        if (!$date) {
            return '';
        }
        return $this->timezone->formatDateTime(
            $date
        );
    }

    /**
     * GetCc
     * @return array
     */
    private function getCc()
    {
        $return = [];
        try {
            $cc = explode(',', (string)$this->getValue(self::XML_PATH_EMAIL_CC));
            if ($cc) {
                foreach ($cc as &$item) {
                    $return[] = ['email' => $item, 'name' => $item];
                }
            }
        } catch (\Exception $exception) {
            $this->logger->error($exception->getMessage(), ['context' => 'branch8_helpdesk']);
            return $return;
        }
        return $return;
    }

    /**
     * Get Frontend Link Ticket Url
     */
    public function getFrontEndTicketLinkUrl(Ticket $ticket)
    {
        $ticketUrl = '';
        try {
            $customerId = $ticket->getCustomer()->getId();
            $customer = $ticket->getCustomer();
            $storeId = \Magento\Store\Model\Store::DEFAULT_STORE_ID;
            if ($customerId && $customer->getStoreId()) {
                $storeId = $customer->getStoreId();
            } else if ($customerId && $this->storeManager
                    ->getWebsite($customer->getWebsiteId())
                    ->getDefaultStore()->getId()
            ) {
                $storeId = $this->storeManager
                    ->getWebsite($customer->getWebsiteId())
                    ->getDefaultStore()->getId();
            }
            if ($storeId !== \Magento\Store\Model\Store::DEFAULT_STORE_ID) {
                $this->emulation->startEnvironmentEmulation($storeId,
                    Area::AREA_FRONTEND,
                    true
                );
                $ticketUrl = sprintf('%s/%s/%s/%s',
                    trim($this->scopeConfig->getValue(
                        'web/secure/base_url',
                        ScopeInterface::SCOPE_STORE,
                        $storeId
                    ), '/'),
                    'helpdesk/ticket/view',
                    'ticket_id',
                    $ticket->getId()
                );
                $this->emulation->stopEnvironmentEmulation();
            }
        } catch (\Exception $exception) {
            $this->logger->error($exception->getMessage(), ['context' => 'branch8_helpdesk']);
        }
        return $ticketUrl;
    }

    /**
     * @param TransportBuilder $transportBuilder
     * @param $attachments
     * @return void
     */
    private function processAddAttachment(TransportBuilder $transportBuilder, $attachments = array())
    {
        $disableAttachments = (bool)$this->getValue(self::XML_PATH_DISABLE_ATTACHMENTS);

        if ($disableAttachments) {
            return;
        }
        $transportBuilder->resetAttachments();
        /**
         * @var $attachment Attachment
         */
        foreach ($attachments as $attachment) {
            try {
                if (is_file($attachment->getLocalPath())) {
                    $transportBuilder->addAttachment(
                        file_get_contents($attachment->getLocalPath()),
                        $attachment->getName(),
                        $this->mine->getMimeType($attachment->getLocalPath())
                    );
                };
            } catch (\Exception $exception) {
                $this->logger->error($exception->getMessage(), ['trace' => $exception->getTraceAsString()]);
            }
        }
    }
}
