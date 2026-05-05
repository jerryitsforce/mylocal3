<?php

declare(strict_types=1);

namespace Branch8\TicketApi\Controller\Adminhtml\Merchant;

use Branch8\TicketApi\Helper\Common as CommonHelper;
use Branch8\TicketApi\Model\TicketApiMerchantRepository;
use Branch8\TicketApi\Model\TicketApiPermission;
use Branch8\TicketApi\Model\TicketApiPermissionFactory;
use Branch8\TicketApi\Model\TicketApiPermissionRepository;
use Magento\Framework\DB\Transaction;
use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\Controller\Result\Redirect;
use Magento\Framework\Message\ManagerInterface as MessageManagerInterface;

class EditSubmit extends \Magento\Backend\App\Action implements \Magento\Framework\App\Action\HttpPostActionInterface
{
    /** @var CommonHelper */
    protected $commonHelper;

    /** @var TicketApiMerchantRepository */
    protected $ticketApiMerchantRepository;

    /** @var TicketApiPermissionRepository */
    protected $ticketApiPermissionRepository;

    /** @var TicketApiPermissionFactory */
    protected $ticketApiPermissionFactory;

    /** @var Transaction */
    protected $transaction;

    /** @var MessageManagerInterface */
    protected $messageManager;

    protected $merchantId;
    protected $merchant;
    protected $isActive;
    protected $merchantName;
    protected $allowAllSeller;
    protected $sellerId;
    protected $sellerIds;
    protected $whitelist;
    protected $brandPermission;

    public function __construct(
        CommonHelper $commonHelper,
        TicketApiMerchantRepository $ticketApiMerchantRepository,
        TicketApiPermissionRepository $ticketApiPermissionRepository,
        TicketApiPermissionFactory $ticketApiPermissionFactory,
        Transaction $transaction,
        MessageManagerInterface $messageManager,
        \Magento\Backend\App\Action\Context $context,
    ) {
        $this->commonHelper                  = $commonHelper;
        $this->ticketApiMerchantRepository   = $ticketApiMerchantRepository;
        $this->ticketApiPermissionRepository = $ticketApiPermissionRepository;
        $this->ticketApiPermissionFactory    = $ticketApiPermissionFactory;
        $this->transaction                   = $transaction;
        $this->messageManager                = $messageManager;

        parent::__construct($context);
    }

    public function execute()
    {
        try {
            $this->initParameters();

            $this->updateMerchant();

            $this->updatePermission();

            $this->messageManager->addSuccess(__("Edit success."));
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
        $this->merchant   = $this->ticketApiMerchantRepository->getById($this->merchantId);

        $this->isActive        = (int) $this->_request->getParam("is_active");
        $this->merchantName    = $this->_request->getParam("merchant_name");
        $this->whitelist       = $this->_request->getParam("whitelist");
        $this->brandPermission = $this->_request->getParam("brand_permission");
        $this->allowAllSeller  = (int) $this->_request->getParam("allow_all_seller") ?? 0;
        $this->sellerId        = (int) $this->_request->getParam("seller_id");

        $this->sellerIds = $this->_request->getParam("seller_ids");
        $this->sellerIds = empty($this->sellerIds) ? "" : implode(",", $this->sellerIds);
    }

    /**
     * 更新merchant
     * @return void
     */
    protected function updateMerchant(): void
    {
        $this->merchant->setIsActive($this->isActive);
        $this->merchant->setMerchantName($this->merchantName);
        $this->merchant->setAllowAllSeller($this->allowAllSeller);
        // $this->merchant->setSellerId($this->sellerId);
        $this->merchant->setSellerIds($this->sellerIds);
        $this->merchant->setWhitelist($this->whitelist);

        $this->ticketApiMerchantRepository->save($this->merchant);
    }

    /**
     * 更新品牌權限
     * @return void
     */
    protected function updatePermission(): void
    {
        $permissionCollection = $this->ticketApiPermissionRepository->getPermissionByMerchantId($this->merchantId);

        /** @var TicketApiPermission $permission */
        foreach ($permissionCollection->getItems() as $permission) {
            $this->transaction->addObject($permission);
        }
        $this->transaction->delete();

        foreach ($this->brandPermission as $brandId) {
            /** @var TicketApiPermission $permission */
            $permission = $this->ticketApiPermissionFactory->create();
            $permission->setBrandId((int) $brandId);
            $permission->setMerchantId($this->merchantId);

            $this->transaction->addObject($permission);
        }
        $this->transaction->save();
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
