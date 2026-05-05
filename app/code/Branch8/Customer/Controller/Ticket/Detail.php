<?php
/**
 * Copyright ©  All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Branch8\Customer\Controller\Ticket;

use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Framework\App\Action\HttpGetActionInterface;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\App\ResponseInterface;
use Magento\Framework\Controller\ResultInterface;
use Magento\Framework\Exception\NotFoundException;
use Magento\Framework\Exception\SessionException;
use Magento\Framework\View\Result\PageFactory;
use Magento\Framework\App\Action\Context;
use Magento\Customer\Model\Session as CustomerSession;
use Magento\Framework\Registry;
use Branch8\CustomerTicketTable\Model\CustomerTicketRepository;
use Branch8\CustomerTicketTable\Model\CustomerTicketOverDueRepository;
use Branch8\Edenred\Model\EdenredTicketRecordRepository;
use HotaiConnected\Qware\Model\QwareTicketRecordRepository;

class Detail extends \Magento\Framework\App\Action\Action implements HttpGetActionInterface
{

    /**
     * @var PageFactory
     */
    protected $resultPageFactory;

    /**
     * @var CustomerSession
     */
    private $customerSession;

    /**
     * @var \Magento\Framework\Registry
     */
    protected $registry;

    /**
     * @var CustomerTicketRepository
     */
    protected $customerTicketRepository;

    /**
     * @var CustomerTicketOverDueRepository
     */
    protected $customerTicketOverDueRepository;

    /**
     * @var EdenredTicketRecordRepository
     */
    protected $edenredTicketRepository;

    /**
     * @var QwareTicketRecordRepository
     */
    protected $qwareTicketRepository;

    private ProductRepositoryInterface $productRepository;

    protected $ticketHelper;

    /**
     * Setting constructor.
     * @param Context $context
     * @param PageFactory $resultPageFactory
     * @param CustomerSession $customerSession
     */
    public function __construct(
        Context $context,
        PageFactory $resultPageFactory,
        CustomerSession $customerSession,
        Registry $registry,
        CustomerTicketRepository $customerTicketRepository,
        CustomerTicketOverDueRepository $customerTicketOverDueRepository,
        EdenredTicketRecordRepository $edenredTicketRepository,
        QwareTicketRecordRepository $qwareTicketRepository,
        ProductRepositoryInterface $productRepository,
        \Branch8\Customer\Helper\Ticket $ticketHelper
    ) {
        $this->resultPageFactory = $resultPageFactory;
        $this->customerSession = $customerSession;
        $this->registry = $registry;
        $this->customerTicketOverDueRepository = $customerTicketOverDueRepository;
        $this->customerTicketRepository = $customerTicketRepository;
        $this->edenredTicketRepository = $edenredTicketRepository;
        $this->qwareTicketRepository = $qwareTicketRepository;
        $this->productRepository = $productRepository;
        parent::__construct($context);
        $this->ticketHelper = $ticketHelper;
    }

    /**
     * Execute view action
     *
     * @return ResultInterface
     */
    public function execute()
    {
        $customerId = $this->customerSession->getCustomerId();

        $ticketId = (int)$this->getRequest()->getParam('id');
        if($ticketId == null) {
            return $this->_redirect('member/ticket');
        }

        $ticket = $this->customerTicketRepository->getById($ticketId);
        if(!$ticket || !$ticket->getId()){
            $ticket = $this->customerTicketOverDueRepository->getById($ticketId);
        }

        if(!$ticket || !$ticket->getId()){
            return $this->_redirect('member/ticket');
        }

        $edenredTicket = null;
        $qwareTicket = null;
        
        if($ticket && $ticket->getData('ticket_table_record_id')){
            // 根據票券表名稱決定要查詢哪個廠商的資料
            $ticketTableName = $ticket->getData('ticket_table_name');
            
            if($ticketTableName === 'edenred_ticket_record') {
                $edenredTicket = $this->edenredTicketRepository->getById((int)$ticket->getData('ticket_table_record_id'));
            } elseif($ticketTableName === 'qware_ticket_record') {
                $qwareTicket = $this->qwareTicketRepository->getById((int)$ticket->getData('ticket_table_record_id'));
            }
        }

        if($this->ticketHelper->isGiftEventTicket(['ticket_table_name' => $ticket->getData('ticket_table_name')])){
            $customerMemberSeq = $this->customerSession->getCustomer()->getDataModel()->getCustomAttribute('member_seq')->getValue();
            $ticketMemberSeq = $ticket->getMemberSeq();
            if(strtolower($customerMemberSeq) != strtolower($ticketMemberSeq)){
                return $this->_redirect('member/ticket');
            }
            $giftTicket = $this->ticketHelper->getGiftTicket($ticket['ticket_table_record_id']);
            $ticket->setData('giftTicketData', $giftTicket);
            $ticket->setData('isGiftTicket', true);
            if (!$this->registry->registry('current_ticket')) {
                $this->registry->register('current_ticket', $ticket);
            }
        }else{
            if($ticket->getCustomerId() != $customerId){
                return $this->_redirect('member/ticket');
            }
            $product = $this->getProduct($ticket->getBelongToProductId());

            if(!$product) {
                return $this->_redirect('member/ticket');
            }

            $ticket->setData('product', $product);
            $ticket->setData('password', 2972); //TODO
            
            // 宜睿票券資料處理
            if($edenredTicket && $edenredTicket->getId()){
                $ticket->setData('edenred_short_url', $edenredTicket->getData('edenred_short_url'));
                $ticket->setData('edenred_short_url_auth_code', $edenredTicket->getData('edenred_short_url_auth_code'));
                $ticket->setData('password', $edenredTicket->getData('edenred_short_url_auth_code'));
            }
            
            // 安源票券資料處理
            if($qwareTicket && $qwareTicket->getId()){
                $ticket->setData('qware_url', $qwareTicket->getData('qware_url'));
                $ticket->setData('qware_pwd', $qwareTicket->getData('qware_pwd'));
                $ticket->setData('password', $qwareTicket->getData('qware_pwd'));
            }
            if (!$this->registry->registry('current_ticket')) {
                $this->registry->register('current_ticket', $ticket);
            }
        }

        return $this->resultPageFactory->create();
    }

    protected function getProduct($product_id)
    {
        try {
            $product = $this->productRepository->getById($product_id);
        } catch (\Exception $e) {
            $product = null;
        }
        return $product;
    }

    /**
     * Retrieve customer session object
     *
     * @return \Magento\Customer\Model\Session
     */
    protected function _getSession()
    {
        return $this->customerSession;
    }

    /**
     * Check customer authentication
     *
     * @param RequestInterface $request
     * @return ResponseInterface
     * @throws NotFoundException
     * @throws SessionException
     */
    public function dispatch(RequestInterface $request)
    {
        if (!$this->_getSession()->authenticate()) {
            $this->_actionFlag->set('', 'no-dispatch', true);
        }
        return parent::dispatch($request);
    }

}

