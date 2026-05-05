<?php
namespace Branch8\GiftToFriend\Controller\GiftBox;


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

class Ticket extends \Magento\Framework\App\Action\Action
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

    public function execute()
    {
        $guestSession = $this->customerSession->getGuestGiftBoxSession();
        if(!$guestSession){
            return $this->_redirect('gift-order/giftBox/giftForm')->sendResponse();
        }
        $phoneNumber = $guestSession['phone_number'];
        $ticketId = (int)$this->getRequest()->getParam('id');
        if($ticketId == null) {
            return $this->_redirect('gift-order/giftBox/giftForm')->sendResponse();
        }

        $ticket = $this->customerTicketRepository->getById($ticketId);
        if(!$ticket || !$ticket->getId()){
            $ticket = $this->customerTicketOverDueRepository->getById($ticketId);
        }
        /** The telephone has no member seq */
        if($guestSession['member_seq'] == ''){
            if(!$ticket || !$ticket->getId() || $ticket->getTelephone() != $phoneNumber || $ticket->getMemberSeq() != ''){
                return $this->_redirect('gift-order/giftBox/giftForm');
            }
        }else{
            if(!$ticket || !$ticket->getId() || $ticket->getMemberSeq() != $guestSession['member_seq']){
                return $this->_redirect('gift-order/giftBox/giftForm');
            }
        }

        if($ticket && $ticket->getData('ticket_table_record_id')){
            $edenredTicket = $this->edenredTicketRepository->getById((int)$ticket->getData('ticket_table_record_id'));
            $qwareTicket   = $this->qwareTicketRepository->getById((int)$ticket->getData('ticket_table_record_id'));
        }

        $product = $this->getProduct($ticket->getBelongToProductId());
        if(!$product) {
            return $this->_redirect('gift-order/giftBox/giftForm');
        }

        $ticket->setData('product', $product);
        $ticket->setData('password', 2972); //TODO
        if($edenredTicket && $edenredTicket->getId()){
            $ticket->setData('edenred_short_url', $edenredTicket->getData('edenred_short_url'));
            $ticket->setData('edenred_short_url_auth_code', $edenredTicket->getData('edenred_short_url_auth_code'));
            $ticket->setData('password', $edenredTicket->getData('edenred_short_url_auth_code'));
        } else if ($qwareTicket && $qwareTicket->getId()){
            $ticket->setData('qware_url', $qwareTicket->getData('qware_url'));
            $ticket->setData('password', $qwareTicket->getData('qware_pwd'));
        }


        if (!$this->registry->registry('current_ticket')) {
            $this->registry->register('current_ticket', $ticket);
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
}
