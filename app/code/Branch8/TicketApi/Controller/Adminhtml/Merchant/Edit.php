<?php

declare(strict_types=1);

namespace Branch8\TicketApi\Controller\Adminhtml\Merchant;

use Magento\Framework\App\Action\HttpGetActionInterface;
use Branch8\TicketApi\Model\TicketApiMerchantRepository;

class Edit extends \Magento\Backend\App\Action implements HttpGetActionInterface
{
    /** @var \Magento\Framework\View\Result\PageFactory */
    protected $resultPageFactory;

    /** @var TicketApiMerchantRepository */
    protected $ticketApiMerchantRepository;

    public function __construct(
        TicketApiMerchantRepository $ticketApiMerchantRepository,
        \Magento\Backend\App\Action\Context $context,
        \Magento\Framework\View\Result\PageFactory $resultPageFactory
    ) {
        $this->ticketApiMerchantRepository = $ticketApiMerchantRepository;
        $this->resultPageFactory           = $resultPageFactory;

        parent::__construct($context);
    }

    /**
     * Index action
     *
     * @return \Magento\Framework\Controller\ResultInterface
     */
    public function execute()
    {
        /** @var \Magento\Backend\Model\View\Result\Page $resultPage */
        $resultPage = $this->resultPageFactory->create();

        $resultPage->getConfig()->getTitle()->prepend(__('Edit Merchant'));

        return $resultPage;
    }
}
