<?php
declare(strict_types=1);

namespace Branch8\HelpDesk\ViewModel;

use Branch8\HelpDesk\Api\Data\TicketInterface;
use Branch8\HelpDesk\Api\TicketRepositoryInterface;
use Branch8\HelpDesk\Model\Ticket;
use Branch8\HelpDesk\Model\Ticket\Priority;
use Branch8\HelpDesk\Model\Ticket\Status;
use Branch8\HelpDesk\Model\TicketFactory;
use Branch8\MaskInformation\Model\MaskRulesComposite;
use Magento\Framework\Api\ExtensibleDataObjectConverter;
use Magento\Framework\App\Area;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\UrlInterface;
use Magento\Framework\View\Element\Block\ArgumentInterface;
use Branch8\HelpDesk\Model\ResourceModel\Category\CollectionFactory;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Framework\App\State;

class TicketData implements ArgumentInterface
{
    /**
     *
     */
    const XML_PATH_ADMIN_ENABLE_CHAT = 'helpdesk/general_settings/enable_admin_chat_conversation';
    const XML_PATH_CUSTOMER_ENABLE_CHAT = 'helpdesk/general_settings/enable_customer_chat_conversation';

    const XML_PATH_MAX_IMAGE_UPLOAD_FILES = 'helpdesk/general_settings/max_images_upload_allow';

    const XML_PATH_MAX_IMAGE_MAX_FILE_SIZE = 'helpdesk/general_settings/maxFileSize';



    /**
     * @var null
     */
    private $ticket = null;
    /**
     * @var CollectionFactory
     */
    private $categoryCollectionFactory;
    /**
     * @var null
     */
    private $categoryOptions = null;
    /**
     * @var RequestInterface
     */
    private $request;
    /**
     * @var TicketRepositoryInterface
     */
    private $repository;
    /**
     * @var ExtensibleDataObjectConverter
     */
    private $extensionDataObjectConverter;
    /**
     * @var StoreManagerInterface
     */
    private $storeManager;
    /**
     * @var Priority
     */
    private $priority;
    /**
     * @var Status
     */
    private $status;
    /**
     * @var
     */
    private $url;

    private $appState;

    private $ticketFactory;
    /**
     * @var ScopeConfigInterface
     */
    private ScopeConfigInterface $scopeConfig;
    /**
     * @var \Magento\Sales\Model\ResourceModel\Order\CollectionFactory
     */
    private \Magento\Sales\Model\ResourceModel\Order\CollectionFactory $orderCollectionFactory;
    private MaskRulesComposite $maskRuleComposite;

    /**
     * @param UrlInterface $url
     * @param TicketRepositoryInterface $ticketRepository
     * @param RequestInterface $request
     * @param CollectionFactory $categoryCollectionFactory
     * @param \Magento\Sales\Model\ResourceModel\Order\CollectionFactory $orderCollectionFactory
     * @param StoreManagerInterface $storeManager
     * @param Priority $priority
     * @param Status $status
     * @param ExtensibleDataObjectConverter $extensibleDataObjectConverter
     * @param TicketFactory $ticketFactory
     * @param State $state
     * @param ScopeConfigInterface $scopeConfig
     * @param MaskRulesComposite $maskRulesComposite
     */
    public function __construct(
        UrlInterface                                               $url,
        TicketRepositoryInterface                                  $ticketRepository,
        RequestInterface                                           $request,
        CollectionFactory                                          $categoryCollectionFactory,
        \Magento\Sales\Model\ResourceModel\Order\CollectionFactory $orderCollectionFactory,
        StoreManagerInterface                                      $storeManager,
        Priority                                                   $priority,
        Status                                                     $status,
        ExtensibleDataObjectConverter                              $extensibleDataObjectConverter,
        TicketFactory                                              $ticketFactory,
        State                                                      $state,
        ScopeConfigInterface                                       $scopeConfig,
        MaskRulesComposite                                         $maskRulesComposite
    )
    {
        $this->ticketFactory = $ticketFactory;
        $this->appState = $state;
        $this->url = $url;
        $this->status = $status;
        $this->priority = $priority;
        $this->storeManager = $storeManager;
        $this->categoryCollectionFactory = $categoryCollectionFactory;
        $this->extensionDataObjectConverter = $extensibleDataObjectConverter;
        $this->request = $request;
        $this->repository = $ticketRepository;
        $this->scopeConfig = $scopeConfig;
        $this->orderCollectionFactory = $orderCollectionFactory;
        $this->maskRuleComposite = $maskRulesComposite;
    }

    /**
     * @return TicketInterface
     */
    public function getTicket()
    {
        if ($this->ticket === null) {
            try {
                $this->ticket = $this->repository->getById(
                    (int)$this->request->getParam('ticket_id')
                );
            } catch (NoSuchEntityException $exception) {
                $this->ticket = $this->ticketFactory->create();
            }
        }
        return $this->ticket;
    }

    /**
     * @return array
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function getConfig()
    {
        $isAdmin = $this->appState->getAreaCode() === Area::AREA_ADMINHTML;
        $categoryOptions = $this->getCategoryOptions();
        $statusOptions = $this->status->toOptions();
        $priorityOptions = $this->priority->toOptions();
        $ticket = $this->getTicket();
        $categoryName = isset($categoryOptions[$ticket->getCategoryId()]) ? $categoryOptions[$ticket->getCategoryId()] : '';
        $statusName = isset($statusOptions[$ticket->getFrontendStatus()]) ? $statusOptions[$ticket->getFrontendStatus()] : '';
        $priorityName = isset($priorityOptions[$ticket->getPriority()]) ? $priorityOptions[$ticket->getPriority()] : '';
        $converted = $this->extensionDataObjectConverter->toNestedArray(
            $ticket,
            [],
            TicketInterface::class
        );
        $converted = array_merge(
            $converted, [
                'customer_email_masked' => $this->maskRuleComposite->mask('email', $ticket->getCustomerEmail()),
                'phone_masked' => $this->maskRuleComposite->mask('phone', $ticket->getPhone()),
                'categoryName' => $categoryName,
                'statusName' => $statusName,
                'priorityName' => $priorityName,
                'orderLinks' => $this->getOrderLinks($ticket)
            ]
        );

        return [
            'ticket' => $converted,
            'showTicketInformation' => $this->appState->getAreaCode() === Area::AREA_FRONTEND,
            'enableChat' => $isAdmin ? $this->enableAdminChatMessage() : $this->enableCustomerChatMessage()
        ];
    }

    /**
     * @return array|null
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    private function getCategoryOptions()
    {
        if ($this->categoryOptions == null) {
            $this->categoryOptions = [];
            /**
             * @var $collection \Branch8\HelpDesk\Model\ResourceModel\Category\Collection
             */
            $collection = $this->categoryCollectionFactory->create()
                ->addFieldToFilter('is_active', 1)
                ->addStoreFilter(
                    $this->storeManager->getStore()->getId(),
                    true
                )->load();
            foreach ($collection as $item) {
                $this->categoryOptions[$item->getCategoryId()] = $item->getTitle();
            }
        }
        return $this->categoryOptions;
    }

    /**
     * @return array
     */
    public function getPostReplyConfiguration()
    {
        return [
            'postUrl' => $this->url->getUrl('helpdesk/ticket/PostMessage/'),
            'ticket_id' => (int)$this->getTicket()->getId()
        ];
    }

    /**
     * Get config for History Component
     * @return array
     */
    public function getHistoryConfiguration()
    {
        $mediaUrl = $this->storeManager
            ->getStore()
            ->getBaseUrl(\Magento\Framework\UrlInterface::URL_TYPE_MEDIA);

        return [
            'loadUrl' => $this->url->getUrl('helpdesk/ticket/LoadMessages/'),
            'ticket_id' => (int)$this->getTicket()->getId(),
            'messagePerPage' => 5,
            'mediaBasePath' => trim(
                sprintf("%s/%s", trim($mediaUrl, '\/'),
                    'helpdesk/attachment/'))
        ];
    }

    /**
     * @return int
     */
    public function getMaxImageUploadCount()
    {
        $max = (int)$this->scopeConfig->getValue(self::XML_PATH_MAX_IMAGE_UPLOAD_FILES);
        return $max ? $max : 5;
    }

    /**
     * @return int
     */
    public function getMaxFileSize()
    {
        $max = (int)$this->scopeConfig->getValue(self::XML_PATH_MAX_IMAGE_MAX_FILE_SIZE);
        return $max ? $max : 5242880;
    }

    /**
     * Get config for upload component
     * @return array
     */
    public function getUploaderConfig()
    {
        return [
            'url' => $this->url->getUrl('helpdesk/message/postAttachment')
        ];
    }

    /**
     * Config disable/enable chat for Admin
     * @return bool
     */
    public function enableAdminChatMessage()
    {
        return (bool)$this->scopeConfig->getValue(self::XML_PATH_ADMIN_ENABLE_CHAT);
    }

    /**
     * Config disable/enable chat for Customer
     * @return bool
     */
    public function enableCustomerChatMessage()
    {
        return (bool)$this->scopeConfig->getValue(self::XML_PATH_CUSTOMER_ENABLE_CHAT);
    }

    /**
     * Get array Order Links
     * @return array
     */
    public function getOrderLinks(Ticket $ticket)
    {
        $orderIds = explode(',', (string)$ticket->getOrder());
        $urls = [];
        if ($orderIds) {
            $collection = $this->orderCollectionFactory->create()
                ->addFieldToFilter('entity_id', ['in' => $orderIds]);
            foreach ($collection as $item) {
                $urls[] = [
                    'title' => '#' . $item->getIncrementId(),
                    'link' => $this->url->getUrl('sales/order/view',
                        ['order_id' => $item->getId()])
                ];

            }
        }
        return $urls;
    }
}
