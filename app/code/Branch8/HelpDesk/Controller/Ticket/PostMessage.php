<?php
declare(strict_types=1);

namespace Branch8\HelpDesk\Controller\Ticket;

use Branch8\HelpDesk\Api\AttachmentRepositoryInterface;
use Branch8\HelpDesk\Api\Data\AttachmentInterface;
use Branch8\HelpDesk\Api\Data\MessageInterface;
use Branch8\HelpDesk\Api\MessageRepositoryInterface;
use Branch8\HelpDesk\Api\TicketRepositoryInterface;
use Branch8\HelpDesk\Controller\AbstractController;
use Branch8\HelpDesk\Model\Attachment\CreateFromPost;
use Branch8\HelpDesk\Model\EmailNotificationInterface;
use Branch8\HelpDesk\Model\Message;
use Branch8\HelpDesk\Model\Message\AssignCustomerData;
use Branch8\HelpDesk\Model\MessageRepository;
use Branch8\HelpDesk\Model\Ticket\Authorize;
use Branch8\HelpDesk\Model\Ticket\BelongTo;
use Branch8\HelpDesk\Model\Ticket\CreateMessageFromPostAction;
use Branch8\HelpDesk\Model\Ticket\LastReplyAction;
use Magento\Framework\App\Action\HttpGetActionInterface;
use Magento\Framework\App\Action\HttpPostActionInterface;
use Magento\Framework\Exception\InputException;
use Magento\Framework\Exception\LocalizedException;
use Branch8\HelpDesk\Model\TicketFactory;
use Branch8\HelpDesk\Model\MessageFactory;
use Magento\Framework\Exception\NoSuchEntityException;
use Psr\Log\LoggerInterface;

/**
 * Post Message Class
 */
class PostMessage extends AbstractController implements HttpPostActionInterface, HttpGetActionInterface
{
    /**
     * @var Authorize
     */
    private $authorize;
    /**
     * @var \Magento\Framework\Json\Helper\Data $helper
     */
    protected $helper;
    /**
     * @var \Magento\Framework\Controller\Result\JsonFactory
     */
    protected $resultJsonFactory;

    /**
     * @var \Magento\Framework\Controller\Result\RawFactory
     */
    protected $resultRawFactory;
    /**
     * @var \Magento\Customer\Model\Session
     */
    private $customerSession;
    /**
     * @var TicketFactory
     */
    private $ticketFactory;
    /**
     * @var TicketRepositoryInterface
     */
    private $ticketRepository;

    /**
     * @var LoggerInterface
     */
    private $logger;
    /**
     * @var LastReplyAction
     */
    private $lastReplyAction;
    /**
     * @var CreateMessageFromPostAction
     */
    private $createMessageFromPost;


    private $emailNotification;
    /**
     * @param \Magento\Framework\App\Action\Context $context
     * @param \Magento\Customer\Model\Session $customerSession
     * @param \Magento\Framework\Json\Helper\Data $helper
     * @param \Magento\Framework\Controller\Result\JsonFactory $resultJsonFactory
     * @param \Magento\Framework\Controller\Result\RawFactory $resultRawFactory
     * @param TicketFactory $ticketFactory
     * @param TicketRepositoryInterface $ticketRepository
     * @param Authorize $authorize
     * @param LastReplyAction $lastReplyAction
     * @param CreateMessageFromPostAction $createMessageFromPostAction
     * @param LoggerInterface $logger
     * @param EmailNotificationInterface $emailNotification
     */
    public function __construct(
        \Magento\Framework\App\Action\Context            $context,
        \Magento\Customer\Model\Session                  $customerSession,
        \Magento\Framework\Json\Helper\Data              $helper,
        \Magento\Framework\Controller\Result\JsonFactory $resultJsonFactory,
        \Magento\Framework\Controller\Result\RawFactory  $resultRawFactory,
        TicketFactory                                    $ticketFactory,
        TicketRepositoryInterface                        $ticketRepository,
        Authorize                                        $authorize,
        LastReplyAction                                  $lastReplyAction,
        CreateMessageFromPostAction                      $createMessageFromPostAction,
        LoggerInterface                                  $logger,
        EmailNotificationInterface                       $emailNotification
    )
    {
        parent::__construct($context);
        $this->lastReplyAction = $lastReplyAction;
        $this->logger = $logger;
        $this->customerSession = $customerSession;
        $this->helper = $helper;
        $this->resultJsonFactory = $resultJsonFactory;
        $this->resultRawFactory = $resultRawFactory;
        $this->ticketFactory = $ticketFactory;
        $this->ticketRepository = $ticketRepository;
        $this->authorize = $authorize;
        $this->createMessageFromPost = $createMessageFromPostAction;
        $this->emailNotification = $emailNotification;
    }

    /**
     * Execute
     * @return \Magento\Framework\App\ResponseInterface|\Magento\Framework\Controller\Result\Json|\Magento\Framework\Controller\Result\Raw|\Magento\Framework\Controller\ResultInterface
     */
    public function execute()
    {
        $httpBadRequestCode = 400;
        /** @var \Magento\Framework\Controller\Result\Raw $resultRaw */
        $resultRaw = $this->resultRawFactory->create();
        $ticket = $this->getTicket();
        try {
            $postDetail = $this->helper->jsonDecode($this->getRequest()->getContent());
        } catch (\Exception $e) {
            return $resultRaw->setHttpResponseCode($httpBadRequestCode);
        }
        if (!$this->customerSession->isLoggedIn()
            || !$postDetail
            || !$this->getRequest()->isXmlHttpRequest()
            || $this->getRequest()->getMethod() !== 'POST'
            || !$ticket
            || !$ticket->getId()
            || !$this->authorize->authorize($ticket)
        ) {
            return $resultRaw->setHttpResponseCode($httpBadRequestCode);
        }
        $response = [
            'errors' => false,
            'messages' => [__('Message was successfully sent')]
        ];
        try {
            $customer = $this->customerSession->getCustomer();
            /**
             * @var $message MessageInterface
             */
            $message = $this->createMessageFromPost->createForCustomer($ticket,
                $customer,
                $postDetail
            );
            $this->lastReplyAction->saveByCustomer($ticket,
                $customer
            );
            $this->emailNotification->notifyUserWhenHaveMessage(
                $ticket->getUser(),
                $ticket,
                $message->getExtensionAttributes()->getAttachments()
            );
        } catch (InputException $exception) {
            $response = [
                'errors' => true,
                'messages' => [],
            ];
            $response['messages'][$exception->getMessage()] = $exception->getMessage();
            foreach ($exception->getErrors() as $error) {
                $response['messages'][$error->getMessage()] = $error->getMessage();
            }
            $response['messages'] = array_values(array_unique($response['messages']));
        } catch (LocalizedException $e) {
            $response = [
                'errors' => true,
                'messages' => [$e->getMessage()],
            ];
        } catch (\Exception $e) {
            $response = [
                'errors' => true,
                'messages' => [__('Error when posting new message', $e->getMessage())],
            ];
        }
        /** @var \Magento\Framework\Controller\Result\Json $resultJson */
        $resultJson = $this->resultJsonFactory->create();
        return $resultJson->setData($response);
    }

    /**
     * @return \Branch8\HelpDesk\Api\Data\TicketInterface|null
     */
    private function getTicket()
    {
        try {
            return $this->ticketRepository->getById(
                (int)$this->_request->getParam('ticket_id')
            );
        } catch (NoSuchEntityException $exception) {
            return null;
        }
    }
}
