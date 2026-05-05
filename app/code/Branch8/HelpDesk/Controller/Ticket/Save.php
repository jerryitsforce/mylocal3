<?php
declare(strict_types=1);

namespace Branch8\HelpDesk\Controller\Ticket;

use Branch8\HelpDesk\Api\TicketRepositoryInterface;
use Branch8\HelpDesk\Controller\AbstractController;
use Branch8\HelpDesk\Model\EmailNotificationInterface;
use Branch8\HelpDesk\Model\Ticket\CreateMessageFromPostAction;
use Branch8\HelpDesk\Model\Ticket\LastReplyAction;
use Branch8\HelpDesk\Model\Ticket\Validate;
use Magento\Framework\App\Action\HttpPostActionInterface;
use Magento\Framework\Exception\InputException;
use Magento\Framework\Exception\LocalizedException;
use Branch8\HelpDesk\Model\TicketFactory;

/**
 *
 */
class Save extends AbstractController implements HttpPostActionInterface
{
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
     * @var CreateMessageFromPostAction
     */
    private $createMessageFromPostAction;
    /**
     * @var Validate
     */
    private Validate $validatePost;
    /**
     * @var LastReplyAction
     */
    private LastReplyAction $lastReplyAction;
    /**
     * @var EmailNotificationInterface
     */
    private EmailNotificationInterface $emailNotification;

    /**
     * @param \Magento\Framework\App\Action\Context $context
     * @param \Magento\Customer\Model\Session $customerSession
     * @param \Magento\Framework\Json\Helper\Data $helper
     * @param \Magento\Framework\Controller\Result\JsonFactory $resultJsonFactory
     * @param \Magento\Framework\Controller\Result\RawFactory $resultRawFactory
     * @param TicketFactory $ticketFactory
     * @param Validate $validate
     * @param TicketRepositoryInterface $ticketRepository
     * @param CreateMessageFromPostAction $createMessageFromPostAction
     * @param LastReplyAction $lastReplyAction
     * @param EmailNotificationInterface $emailNotification
     */
    public function __construct(
        \Magento\Framework\App\Action\Context            $context,
        \Magento\Customer\Model\Session                  $customerSession,
        \Magento\Framework\Json\Helper\Data              $helper,
        \Magento\Framework\Controller\Result\JsonFactory $resultJsonFactory,
        \Magento\Framework\Controller\Result\RawFactory  $resultRawFactory,
        TicketFactory                                    $ticketFactory,
        Validate                                         $validate,
        TicketRepositoryInterface                        $ticketRepository,
        CreateMessageFromPostAction                      $createMessageFromPostAction,
        LastReplyAction                                  $lastReplyAction,
        EmailNotificationInterface                       $emailNotification
    )
    {
        parent::__construct($context);
        $this->customerSession = $customerSession;
        $this->helper = $helper;
        $this->resultJsonFactory = $resultJsonFactory;
        $this->resultRawFactory = $resultRawFactory;
        $this->ticketFactory = $ticketFactory;
        $this->validatePost = $validate;
        $this->createMessageFromPostAction = $createMessageFromPostAction;
        $this->ticketRepository = $ticketRepository;
        $this->lastReplyAction = $lastReplyAction;
        $this->emailNotification = $emailNotification;
    }

    /**
     * Execute
     * @return \Magento\Framework\App\ResponseInterface|\Magento\Framework\Controller\Result\Json|\Magento\Framework\Controller\Result\Raw|\Magento\Framework\Controller\ResultInterface
     */
    public function execute()
    {
        $postDetail = null;
        $httpBadRequestCode = 400;

        /** @var \Magento\Framework\Controller\Result\Raw $resultRaw */
        $resultRaw = $this->resultRawFactory->create();
        try {
            $postDetail = $this->helper->jsonDecode($this->getRequest()->getContent());
        } catch (\Exception $e) {
            return $resultRaw->setHttpResponseCode($httpBadRequestCode);
        }
        if (!$postDetail || $this->getRequest()->getMethod() !== 'POST'
            || !$this->getRequest()->isXmlHttpRequest()
            || !$this->customerSession->isLoggedIn()
        ) {
            return $resultRaw->setHttpResponseCode($httpBadRequestCode);
        }

        $response = [
            'errors' => false,
            'messages' => [__('Ticket was successfully sent')]
        ];
        try {
            /**
             * @var $ticket \Branch8\HelpDesk\Model\Ticket
             */
            $ticket = $this->ticketFactory->create();
            $customer = $this->customerSession->getCustomer();
            if (isset($postDetail['orders']) && is_array($postDetail['orders'])) {
                $postDetail['order'] = implode(',', $postDetail['orders']);
            }
            $ticket->setData($postDetail)->populateWithCustomerData(
                $customer
            );
            if (isset($postDetail['email'])) {
                $ticket->setData('customer_email', $postDetail['email']);
            }
            $errors = $this->validatePost->validate($ticket);
            if (count($errors)) {
                $inputException = new InputException();
                foreach ($errors as $error) {
                    $inputException->addError($error);
                }
                throw $inputException;
            }
            $ticket = $this->ticketRepository->save($ticket);
            $message = $this->createMessageFromPostAction->createForCustomer(
                $ticket,
                $customer,
                $postDetail
            );
            $this->lastReplyAction->saveByCustomer($ticket,
                $customer
            );
            /**
             * save first message attachments for ticket
             */
            if ($attachments = $message->getExtensionAttributes()->getAttachments()) {
                $attachmentIds = [];
                foreach ($attachments as $item) {
                    $attachmentIds[] = $item->getId();
                }
                if ($attachmentIds) {
                    $ticket->setData('attachment_ids', join(',', $attachmentIds))->save();
                }
            }
            $ticket = $this->ticketRepository->getById(
                (int)$ticket->getTicketId()
            );
            $this->emailNotification->notifyToCsrHeadTeamWhenHaveNewTicket($ticket, $attachments);
            $this->emailNotification->notifyCustomerWhenHaveNewTicket(
                $ticket,
                $ticket->getCustomerEmail()
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
                'messages' => [__('Error when creating support ticket')],
            ];
        }
        /** @var \Magento\Framework\Controller\Result\Json $resultJson */
        $resultJson = $this->resultJsonFactory->create();
        $this->messageManager->addSuccessMessage(__('Ticket was successfully sent'));
        return $resultJson->setData($response);
    }
}
