<?php

namespace Branch8\Customer\Controller\Ticket;

use Branch8\Customer\Block\Ticket\Row\Ticket as RowTicket;
use Magento\Framework\DataObject;
use Branch8\CustomerTicketTable\Model\ResourceModel\CustomerTicket\CollectionFactory as CustomerTicket;
use Branch8\CustomerTicketTable\Model\ResourceModel\CustomerTicketOverDue\CollectionFactory as CustomerTicketOverDue;
use Branch8\Customer\Helper\Ticket as HelperTicket;
use Branch8\HotaiCore\Model\Ticket\Status;

class Ajax extends \Magento\Framework\App\Action\Action
{
    /**
     * @var \Magento\Framework\Controller\Result\JsonFactory
     */
    protected $resultJsonFactory;

    /**
     * @var \Magento\Customer\Model\Session
     */
    protected $customerSession;

    /**
     * @var \Branch8\CustomerTicketTable\Model\ResourceModel\CustomerTicket\CollectionFactory
     */
    protected $ticketCollectionFactory;

    /**
     * @var \Branch8\CustomerTicketTable\Model\ResourceModel\CustomerTicketOverDue\CollectionFactory
     */
    protected $overDueTicketCollectionFactory;

    /**
     * @var HelperTicket
     */
    protected $helperTicket;

    /**
     * @var \Magento\Framework\View\LayoutInterface
     */
    protected \Magento\Framework\View\LayoutInterface $layout;

    public function __construct(
        \Magento\Framework\App\Action\Context $context,
        \Magento\Framework\Controller\Result\JsonFactory $resultJsonFactory,
        \Magento\Customer\Model\Session $customerSession,
        CustomerTicketOverDue $overDueTicketCollectionFactory,
        CustomerTicket $ticketCollectionFactory,
        HelperTicket $helperTicket,
        \Magento\Framework\View\LayoutInterface $layout
    )
    {
        $this->resultJsonFactory = $resultJsonFactory;
        $this->customerSession = $customerSession;
        $this->overDueTicketCollectionFactory = $overDueTicketCollectionFactory;
        $this->ticketCollectionFactory = $ticketCollectionFactory;
        $this->helperTicket = $helperTicket;
        $this->layout = $layout;
        parent::__construct($context);
    }

    public function execute()
    {
        $result = $this->resultJsonFactory->create();
        $response = [
            'success' => false,
            'message' => 'Invalid request'
        ];

        if ($this->getRequest()->isAjax() && $this->getRequest()->isPost()) {
            $tab = $this->helperTicket->getActiveTab($this->getRequest());
            $response = $this->getTicketCollection($tab);
        }

        return $result->setData($response);
    }

    public function getTicketCollection($tab)
    {
        $curPage = $this->helperTicket->getCurPage($this->getRequest(), $tab);

        switch ($tab) {
            case HelperTicket::TABS[0]:
                $type = Status::STATUS_UNUSED;
                $collection = $this->helperTicket->getUnusedTicketCollection($this->getRequest());
                $totalCount = $this->helperTicket->getTicketsCollectionCount($type);
                break;
            case HelperTicket::TABS[1]:
                $type = Status::STATUS_USED;
                $collection = $this->helperTicket->getUsedTicketCollection($this->getRequest());
                $totalCount = $this->helperTicket->getTicketsCollectionCount($type);
                break;
            case HelperTicket::TABS[2]:
                $type = Status::STATUS_OVER_DUE;
                $collection = $this->helperTicket->getOverDueTicketCollection($this->getRequest());
                $totalCount = $this->helperTicket->getOverDueTicketCollectionCount();
                break;
        }

        
        $totalPage = ceil($totalCount / HelperTicket::PAGE_SIZE);

        if ($curPage > $totalPage) {
            return [
                'success' => true,
                'html' => '',
                'hasMore' => false,
                'totalPage' => $totalPage,
                'totalCount' => $totalCount,
                'curPage' => $curPage
            ];
        }

        $html = '';
        foreach ($collection as $item) {
            $html .= $this->getLayout()->createBlock(RowTicket::class)
                    ->setData('type', $type)
                    ->setData('item', $item)
                    ->toHtml();
        }
        return [
            'success' => true,
            'html' => $html,
            'hasMore' => $curPage < $totalPage,
            'totalPage' => $totalPage,
            'totalCount' => $totalCount,
            'curPage' => $curPage
        ];
    }

    public function getLayout()
    {
        return $this->layout;
    }
}
