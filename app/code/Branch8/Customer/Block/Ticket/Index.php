<?php
/**
 * Copyright ©  All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Branch8\Customer\Block\Ticket;

use Branch8\HotaiCore\Model\Ticket\Status;
use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Framework\App\Http\Context as HttpContext;
use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Framework\App\ObjectManager;
use Magento\Framework\DataObject;
use Branch8\Customer\Helper\Ticket as HelperTicket;

class Index extends \Magento\Framework\View\Element\Template
{
    /**
     * @var CustomerRepositoryInterface
     */
    protected $customerRepository;

    /**
     * @var HttpContext
     */
    protected $httpContext;

    /**
     * @var HelperTicket
     */
    protected $helperTicket;

    /**
     * Constructor
     *
     * @param \Magento\Framework\View\Element\Template\Context  $context
     * @param array $data
     */
    public function __construct(
        \Magento\Framework\View\Element\Template\Context $context,
        HttpContext $httpContext,
        CustomerRepositoryInterface $customerRepository,
        HelperTicket $helperTicket,
        array $data = []
    ) {
        $this->httpContext = $httpContext;
        $this->customerRepository = $customerRepository;
        $this->helperTicket = $helperTicket;
        parent::__construct($context, $data);
    }

    public function getCustomer(){
        if ($this->httpContext->getValue('customer_id')) {
            return $this->customerRepository->getById($this->httpContext->getValue('customer_id'));
        }
        return null;
    }

    public function getCustomerId()
    {
        if ($this->httpContext->getValue('customer_id')) {
            return (int)$this->httpContext->getValue('customer_id');
        }
        return null;
    }

    public function countExpiringUnusedTickets(int $withinXDays = 30)
    {
        return $this->helperTicket->countExpiringUnusedTickets($withinXDays);
    }

    public function getUnusedTicketCollection()
    {
        return $this->helperTicket->getUnusedTicketCollection($this->getRequest());
    }

    public function getUsedTicketCollection()
    {
        return $this->helperTicket->getUsedTicketCollection($this->getRequest());
    }

    /**
     * TODO: real data
     * MOCK DATA
     */
    public function getOverDueTicketCollection()
    {
        return $this->helperTicket->getOverDueTicketCollection($this->getRequest());
    }

    /**
     * TODO
     * @return int
     */
    public function getTotalUnsedTicket()
    {
        return $this->helperTicket->getTicketsCollectionCount(Status::STATUS_UNUSED);
    }

    public function getActiveTab()
    {
        return $this->helperTicket->getActiveTab($this->getRequest());
    }

    public function getCurPage($tabId)
    {
        return $this->helperTicket->getCurPage($this->getRequest(), $tabId);
    }

    public function getTabData(){
        return HelperTicket::TABS;
    }

    public function getCollectionPagerHtml($collection, $name, $tab)
    {
        return $this->getLayout()->createBlock(
            \Branch8\HotaiPoint\Block\Html\CustomPager::class,
            $name
        )
            ->setCollection($collection)
            ->setShowPerPage(false)
            ->setData('show_amounts', false)
            ->setData('tab', $tab)
            ->toHtml();
    }

}
