<?php

declare(strict_types=1);

namespace Branch8\TicketApi\Controller\Adminhtml\Merchant;

use Branch8\TicketApi\Model\TicketApiMerchantRepository;
use Branch8\TicketApi\Model\TicketApiPermission;
use Branch8\TicketApi\Model\TicketApiPermissionRepository;
use Magento\Framework\DB\Transaction;
use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\Controller\Result\Redirect;
use Magento\Framework\Message\ManagerInterface as MessageManagerInterface;

class Delete extends \Magento\Backend\App\Action implements \Magento\Framework\App\Action\HttpGetActionInterface
{
    /** @var TicketApiMerchantRepository */
    protected $ticketApiMerchantRepository;

    /** @var TicketApiPermissionRepository */
    protected $ticketApiPermissionRepository;

    /** @var Transaction */
    protected $transaction;

    /** @var MessageManagerInterface */
    protected $messageManager;

    protected $merchantId;

    public function __construct(
        TicketApiMerchantRepository $ticketApiMerchantRepository,
        TicketApiPermissionRepository $ticketApiPermissionRepository,
        Transaction $transaction,
        MessageManagerInterface $messageManager,
        \Magento\Backend\App\Action\Context $context,
    ) {
        $this->ticketApiMerchantRepository   = $ticketApiMerchantRepository;
        $this->ticketApiPermissionRepository = $ticketApiPermissionRepository;
        $this->transaction                   = $transaction;
        $this->messageManager                = $messageManager;

        parent::__construct($context);
    }

    public function execute()
    {
        try {
            $this->initParameters();

            $this->addMerchantToTransaction();

            $this->addPermissionToTransaction();

            $this->transaction->delete();

            $this->messageManager->addSuccess(__("Delete success."));
        } catch (\Exception $e) {
            $this->messageManager->addError($e->getMessage());
        }

        return $this->returnToListingPage();
    }

    /**
     * 參數初始化
     * @return void
     */
    protected function initParameters()
    {
        $this->merchantId = (int) $this->_request->getParam("entity_id");
    }

    /**
     * 將merchant加入transaction(準備後續刪除)
     * @return void
     */
    protected function addMerchantToTransaction(): void
    {
        $merchant = $this->ticketApiMerchantRepository->getById($this->merchantId);

        $this->transaction->addObject($merchant);
    }

    /**
     * 將權限加入transaction(準備後續刪除)
     * @return void
     */
    protected function addPermissionToTransaction(): void
    {
        $permissionCollection = $this->ticketApiPermissionRepository->getPermissionByMerchantId($this->merchantId);

        /** @var TicketApiPermission $permission */
        foreach ($permissionCollection->getItems() as $permission) {
            $this->transaction->addObject($permission);
        }

        $this->transaction->delete();
    }

    /**
     * 引導回前頁
     *
     * @return \Magento\Framework\Controller\Result\Redirect
     */
    protected function returnToListingPage(): Redirect
    {
        /** @var Redirect $resultRedirect */
        $resultRedirect = $this->resultFactory->create(ResultFactory::TYPE_REDIRECT);
        $resultRedirect->setUrl($this->_url->getUrl("ticket_api/merchant/listing"));

        return $resultRedirect;
    }
}
