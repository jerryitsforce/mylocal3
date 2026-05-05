<?php
declare(strict_types=1);

namespace Branch8\HelpDesk\Controller\Adminhtml\Ticket;

use Branch8\HelpDesk\Api\TicketRepositoryInterface;
use Branch8\HelpDesk\Controller\AjaxGetMessages;
use Branch8\HelpDesk\Model\Ticket\BelongTo;
use Branch8\HelpDesk\Model\Ticket\MessageActions;
use Magento\Backend\App\Action\Context;
use Magento\Framework\App\Action\HttpPostActionInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;

/**
 *Load Messages for Ticket in Admin
 */
class LoadMessages extends \Magento\Backend\App\Action implements HttpPostActionInterface
{
    /**
     * @var TicketRepositoryInterface
     */
    private $ticketRepository;
    /**
     * @var \Magento\Framework\Controller\Result\JsonFactory
     */
    private $resultJsonFactory;
    /**
     * @var \Magento\Framework\Controller\Result\RawFactory
     */
    private $resultRawFactory;
    /**
     * @var AjaxGetMessages
     */
    private $ajaxGetMessages;
    /**
     * @var \Magento\Framework\Json\Helper\Data
     */
    private $helper;
    /**
     * @var MessageActions
     */
    private MessageActions $messageActions;

    /**
     * @param Context $context
     * @param \Magento\Framework\Controller\Result\JsonFactory $resultJsonFactory
     * @param \Magento\Framework\Controller\Result\RawFactory $resultRawFactory
     * @param \Magento\Framework\Json\Helper\Data $helper
     * @param AjaxGetMessages $ajaxGetMessages
     */
    public function __construct(
        Context                                          $context,
        \Magento\Framework\Controller\Result\JsonFactory $resultJsonFactory,
        \Magento\Framework\Controller\Result\RawFactory  $resultRawFactory,
        \Magento\Framework\Json\Helper\Data              $helper,
        TicketRepositoryInterface                        $ticketRepository,
        AjaxGetMessages                                  $ajaxGetMessages,
        MessageActions                                   $messageActions
    )
    {
        $this->resultJsonFactory = $resultJsonFactory;
        $this->resultRawFactory = $resultRawFactory;
        $this->ajaxGetMessages = $ajaxGetMessages;
        $this->helper = $helper;
        $this->ticketRepository = $ticketRepository;
        $this->messageActions = $messageActions;
        parent::__construct($context);
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
        if (!$this->getRequest()->isXmlHttpRequest()
            || $this->getRequest()->getMethod() !== 'POST'
            || !$ticket
            || !$ticket->getId()
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
            $p = (int)$this->_request->getParam('page');
            $p = $p > 0 ? $p : 1;
            $searchResults = $this->ajaxGetMessages->getMessageByPage($ticket, $p);
            if (count($searchResults['items'])) {
                $this->messageActions->proccessMaskRead(
                    $searchResults['items'],
                    BelongTo::USER
                );
            }
            $response = array_merge($response, $searchResults);
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
