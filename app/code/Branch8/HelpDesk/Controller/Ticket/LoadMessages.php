<?php
declare(strict_types=1);

namespace Branch8\HelpDesk\Controller\Ticket;

use Branch8\HelpDesk\Api\MessageRepositoryInterface;
use Branch8\HelpDesk\Api\TicketRepositoryInterface;
use Branch8\HelpDesk\Controller\AbstractController;
use Branch8\HelpDesk\Controller\AjaxGetMessages;
use Branch8\HelpDesk\Model\Ticket\Authorize;
use Branch8\HelpDesk\Model\Ticket\BelongTo;
use Branch8\HelpDesk\Model\Ticket\MessageActions;
use Magento\Framework\Api\FilterFactory;
use Magento\Framework\Api\Search\SearchCriteriaBuilder;
use Magento\Framework\App\Action\HttpPostActionInterface;
use Magento\Framework\Exception\InputException;
use Magento\Framework\Exception\LocalizedException;
use Branch8\HelpDesk\Model\TicketFactory;
use Magento\Framework\Exception\NoSuchEntityException;

/**
 *
 */
class LoadMessages extends AbstractController implements HttpPostActionInterface
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
     * @var
     */
    private $ajaxGetMessages;
    private MessageActions $messageActions;

    /**
     * @param \Magento\Framework\App\Action\Context $context
     * @param \Magento\Customer\Model\Session $customerSession
     * @param \Magento\Framework\Json\Helper\Data $helper
     * @param \Magento\Framework\Controller\Result\JsonFactory $resultJsonFactory
     * @param \Magento\Framework\Controller\Result\RawFactory $resultRawFactory
     * @param TicketFactory $ticketFactory
     * @param TicketRepositoryInterface $ticketRepository
     * @param Authorize $authorize
     * @param AjaxGetMessages $ajaxGetMessages
     * @param MessageActions $messageActions
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
        AjaxGetMessages                                  $ajaxGetMessages,
        MessageActions                                   $messageActions
    )
    {
        parent::__construct($context);
        $this->ajaxGetMessages = $ajaxGetMessages;
        $this->ticketRepository = $ticketRepository;
        $this->customerSession = $customerSession;
        $this->helper = $helper;
        $this->resultJsonFactory = $resultJsonFactory;
        $this->resultRawFactory = $resultRawFactory;
        $this->ticketFactory = $ticketFactory;
        $this->authorize = $authorize;
        $this->messageActions = $messageActions;
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
            'messages' => []
        ];
        try {
            /**
             * @var $ticket \Branch8\HelpDesk\Model\Ticket
             */
            $p = (int)$postDetail['page'];
            $p = $p > 0 ? $p : 1;
            $searchResults = $this->ajaxGetMessages->getMessageByPage(
                $ticket,
                $p,
                true
            );
            if (count($searchResults['items'])) {
                $this->messageActions->proccessMaskRead(
                    $searchResults['items'],
                    BelongTo::CUSTOMER
                );
            }
            $response = array_merge($response, $searchResults);
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
                'messages' => [__('Error when loading messages')],
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
