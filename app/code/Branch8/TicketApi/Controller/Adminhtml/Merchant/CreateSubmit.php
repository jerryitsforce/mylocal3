<?php

declare(strict_types=1);

namespace Branch8\TicketApi\Controller\Adminhtml\Merchant;

use Branch8\TicketApi\Helper\Common as CommonHelper;
use Branch8\TicketApi\Model\TicketApiMerchant;
use Branch8\TicketApi\Model\TicketApiMerchantFactory;
use Branch8\TicketApi\Model\TicketApiMerchantRepository;
use Branch8\TicketApi\Model\TicketApiPermission;
use Branch8\TicketApi\Model\TicketApiPermissionFactory;
use Magento\Framework\DB\Transaction;
use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\Controller\Result\Redirect;

class CreateSubmit extends \Magento\Backend\App\Action implements \Magento\Framework\App\Action\HttpPostActionInterface
{
    /** @var CommonHelper */
    protected $commonHelper;

    /** @var TicketApiMerchantFactory */
    protected $ticketApiMerchantFactory;

    /** @var TicketApiMerchantRepository */
    protected $ticketApiMerchantRepository;

    /** @var TicketApiPermissionFactory */
    protected $ticketApiPermissionFactory;

    /** @var Transaction */
    protected $transaction;

    public function __construct(
        CommonHelper $commonHelper,
        TicketApiMerchantFactory $ticketApiMerchantFactory,
        TicketApiMerchantRepository $ticketApiMerchantRepository,
        TicketApiPermissionFactory $ticketApiPermissionFactory,
        Transaction $transaction,
        \Magento\Backend\App\Action\Context $context,
    ) {
        $this->commonHelper                = $commonHelper;
        $this->ticketApiMerchantFactory    = $ticketApiMerchantFactory;
        $this->ticketApiMerchantRepository = $ticketApiMerchantRepository;
        $this->ticketApiPermissionFactory  = $ticketApiPermissionFactory;
        $this->transaction                 = $transaction;

        parent::__construct($context);
    }

    public function execute()
    {
        $merchantName    = $this->_request->getParam("merchant_name");
        $brandPermission = $this->_request->getParam("brand_permission");
        $whitelist       = $this->_request->getParam("whitelist");
        $allowAllSeller  = $this->_request->getParam("allow_all_seller") ?? "0";
        // $sellerId        = $this->_request->getParam("seller_id");
        $sellerIds = $this->_request->getParam("seller_ids");

        $merchantId = $this->createMerchant($merchantName, $whitelist, $allowAllSeller, $sellerIds);

        $this->createPermission($merchantId, $brandPermission);

        return $this->returnToListingPage();
    }

    /**
     * 創建merchant
     * @param string $merchantName
     * @param string $whitelist
     * @return int
     */
    protected function createMerchant(string $merchantName, string $whitelist, string $allowAllSeller, string|array $sellerIds): int
    {
        $sellerIds = empty($sellerIds) ? "" : implode(",", $sellerIds);

        /** @var TicketApiMerchant $merchant */
        $merchant = $this->ticketApiMerchantFactory->create();

        $merchant->setMerchantName($merchantName);
        $merchant->setMerchantId($this->commonHelper->generateMerchantId());
        $merchant->setAllowAllSeller((int) $allowAllSeller);
        // $merchant->setSellerId((int) $sellerId);
        $merchant->setSellerIds($sellerIds);
        $merchant->setWhitelist($whitelist);
        $merchant->setAesKey($this->commonHelper->generateAesKey());
        $merchant->setAesIv($this->commonHelper->generateAesIv());

        $this->ticketApiMerchantRepository->save($merchant);

        return $merchant->getId();
    }

    /**
     * 創建品牌權限
     * @param int $merchantId
     * @param array $brandPermission
     * @return void
     */
    protected function createPermission(int $merchantId, array $brandPermission): void
    {
        foreach ($brandPermission as $brandId) {
            /** @var TicketApiPermission $permission */
            $permission = $this->ticketApiPermissionFactory->create();
            $permission->setBrandId((int) $brandId);
            $permission->setMerchantId($merchantId);

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
