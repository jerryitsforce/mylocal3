<?php

namespace Branch8\TicketApi\Helper;

use Magento\Framework\Webapi\Rest\Request;
use Branch8\TicketApi\Model\TicketApiMerchant;
use Branch8\TicketApi\Model\TicketApiMerchantRepository;
use Branch8\TicketApi\Model\TicketApiBrandRepository;
use Branch8\TicketApi\Model\TicketApiPermissionRepository;
use Webkul\Marketplace\Model\ResourceModel\Seller\CollectionFactory as SellerCollectionFactory;
use Magento\Framework\Event\ManagerInterface as EventManager;
use Branch8\TicketOrderStatusChangeObserver\Helper\EventName;

class Api
{
    const RETURN_CODE_SUCCESS           = "0000";
    const RETURN_CODE_USED_ALREADY      = "0318";
    const RETURN_CODE_UNABLE_TO_USE_YET = "0319";
    const RETURN_CODE_OVER_DUE          = "0320";
    const RETURN_CODE_NOT_EXIST         = "0321";
    const RETURN_CODE_DEFAULT_FAIL      = "9999";

    const RETURN_MESSAGE_SUCCESS                           = "";
    const RETURN_MESSAGE_USED_ALREADY                      = "該序號已被使用";
    const RETURN_MESSAGE_UNABLE_TO_USE_YET                 = "該序號還不可使用";
    const RETURN_MESSAGE_OVER_DUE                          = "該序號已過期(使用時已逾序號可用效期)";
    const RETURN_MESSAGE_NOT_EXIST                         = "該序號不存在";
    const RETURN_MESSAGE_TICKET_CHECK_UNEXPECTED_CONDITION = "票券查詢判斷異常";

    const RETURN_MESSAGE_UNEXPECTED = '出了點問題';
    const RETURN_DATA_USE_STATUS_TRUE  = "Y";
    const RETURN_DATA_USE_STATUS_FALSE = "N";

    const ENCRYPT_METHOD = "AES-256-CBC";

    /** @var Request */
    protected $request;

    /** @var TicketApiMerchantRepository */
    protected $ticketApiMerchantRepository;

    /** @var TicketApiBrandRepository */
    protected $ticketApiBrandRepository;

    /** @var TicketApiPermissionRepository */
    protected $ticketApiPermissionRepository;

    /** @var SellerCollectionFactory */
    protected $sellerCollectionFactory;

    /** @var EventManager */
    protected $eventManager;

    protected $merchant;

    public function __construct(
        Request $request,
        TicketApiMerchantRepository $ticketApiMerchantRepository,
        TicketApiBrandRepository $ticketApiBrandRepository,
        TicketApiPermissionRepository $ticketApiPermissionRepository,
        SellerCollectionFactory $sellerCollectionFactory,
        EventManager $eventManager
    ) {
        $this->request                       = $request;
        $this->ticketApiMerchantRepository   = $ticketApiMerchantRepository;
        $this->ticketApiBrandRepository      = $ticketApiBrandRepository;
        $this->ticketApiPermissionRepository = $ticketApiPermissionRepository;
        $this->sellerCollectionFactory       = $sellerCollectionFactory;
        $this->eventManager                  = $eventManager;
    }

    /**
     * 獲取請求的header MERCHANT-ID
     * @return null|string
     */
    public function getMerchantId(): null|string
    {
        $merchantId = $this->request->getHeader("MERCHANT-ID");

        return empty($merchantId) ? null : $merchantId;
    }

    /**
     * 獲取請求body中的加密字串欄位
     * @return string
     */
    public function getAesString(): string
    {
        $body = $this->request->getBodyParams();

        return $body["aesString"] ?? "";
    }

    /**
     * 獲取解密後的請求資料
     * @return array
     */
    public function getDecryptArray(): null|array
    {
        $merchant = $this->getMerchant();

        $encryptString = $this->getAesString();

        $decryptString = $this->aesDecrypt(
            $encryptString,
            $merchant->getAesKey(),
            $merchant->getAesIv()
        );

        $decryptArray = json_decode($decryptString, true);

        return $decryptArray;
    }

    /**
     * 從請求解密資料獲取票券品牌代號
     * @return string
     */
    public function getBrandCode(): string
    {
        return $this->getDecryptArray()["brand"];
    }

    /**
     * 從請求解密資料獲取商家代號
     * @return string
     */
    public function getUsedStoreNo(): string
    {
        return $this->getDecryptArray()["usedStoreNo"] ?? "";
    }

    /**
     * 從請求解密資料獲取使用票券交易序號
     * @return string
     */
    public function getUsedTransactionNo(): string
    {
        return $this->getDecryptArray()["usedTransactionNo"];
    }

    public function checkUsedTransactionNo(): bool
    {
        return isset($this->getDecryptArray()["usedTransactionNo"]) && !empty($this->getDecryptArray()["usedTransactionNo"]);
    }

    /**
     * 從請求解密資料獲取票券唯一識別ID
     * @return string
     */
    public function getUniqueId(): string
    {
        return $this->getDecryptArray()["uniqueId"];
    }

    /**
     * 從請求解密資料獲取票券唯一識別ID
     * @return string
     */
    public function getSerialNo(): string
    {
        return $this->getDecryptArray()["serialNo"];
    }

    /**
     * 依據請求資料獲取merchant物件
     * @return null|\Branch8\TicketApi\Model\TicketApiMerchant
     */
    public function getMerchant(): null|TicketApiMerchant
    {
        if ($this->merchant) {
            return $this->merchant;
        }

        $merchantId = $this->getMerchantId();

        if (empty($merchantId)) {
            return null;
        }

        $merchant = $this->ticketApiMerchantRepository->getSettingByMerchantId($merchantId, TicketApiMerchant::IS_ACTIVE_TRUE);

        $this->merchant = $merchant ?? null;

        return $this->merchant;
    }

    /**
     * 獲取請求IP
     * @return string
     */
    public function getIp(): string
    {
        return $this->request->getClientIp();
    }

    /**
     * 判斷請求是否通過merchant白名單設定
     * @return bool
     */
    public function checkWhitelist(): bool
    {
        $merchantId = $this->getMerchantId();

        if (empty($merchantId)) {
            return false;
        }

        $merchant = $this->ticketApiMerchantRepository->getSettingByMerchantId($merchantId, TicketApiMerchant::IS_ACTIVE_TRUE);

        if (!$merchant) {
            return false;
        }

        return in_array($this->getIp(), $merchant->getWhitelistArray());
    }

    public function checkDecryptArray(): bool
    {
        return !is_null($this->getDecryptArray());
    }

    /**
     * 判斷商家代號是否正確以及是否有使用商家代號的權限
     * @return bool
     */
    public function checkUsedStoreNo(): bool
    {
        $merchant = $this->getMerchant();

        if (!$merchant) {
            return false;
        }

        $usedStoreNo = $this->getUsedStoreNo();

        if (empty($usedStoreNo)) {
            return false;
        }

        // 檢查usedStoreNo是不是能找到唯一的seller_code
        $collection = $this->sellerCollectionFactory->create();
        $collection->addFieldToFilter("seller_code", $usedStoreNo);
        $collection->load();
        $sellerArray = $collection->getItems();

        if (count($sellerArray) == 0 || count($sellerArray) > 1) {
            return false;
        }

        // 如果是開放所有seller權限的設定, 直接通過
        if ($merchant->getAllowAllSeller() == 1) {
            return true;
        }

        // 若沒有開放所有商家則必須有指定的seller設定
        if (empty($merchant->getSellerId())) {
            return false;
        }

        $seller = array_pop($sellerArray);

        // 而且merchant的seller_id要和傳來的usedStoreNo連結到的seller_id能對上
        return $merchant->getSellerId() == $seller->getData("seller_id");
    }

    /**
     * 判斷票券品牌代號是否正確
     * @return bool
     */
    public function checkBrand(): bool
    {
        $merchant = $this->getMerchant();

        if (!$merchant) {
            return false;
        }

        $brandCode = $this->getBrandCode();
        $brand     = $this->ticketApiBrandRepository->getSettingByBrandCode($brandCode);

        return !empty($brand);
    }

    /**
     * 判斷merchant是否有請求目標票券品牌的權限
     * @return bool
     */
    public function checkBrandPermission(): bool
    {
        $merchant = $this->getMerchant();

        if (!$merchant) {
            return false;
        }

        $brandCode = $this->getBrandCode();
        $brand     = $this->ticketApiBrandRepository->getSettingByBrandCode($brandCode);

        if (!$brand) {
            return false;
        }

        $permission = $this->ticketApiPermissionRepository->getSettingByBrandIdAndMerchantId(
            $brand->getBrandId(),
            $merchant->getId(),
        );

        return !empty($permission);
    }

    /**
     * 加密
     *
     * @param string $requestDataArray
     * @return string
     */
    public function aesEncrypt(string $rawString, string $aesKey, string $aesIv): string
    {
        $aesString = openssl_encrypt($rawString, self::ENCRYPT_METHOD, $aesKey, OPENSSL_RAW_DATA, $aesIv);

        return base64_encode($aesString);
    }

    /**
     * 解密
     *
     * @param string $responseString
     * @return string
     */
    public function aesDecrypt(string $encryptString, string $aesKey, string $aesIv): string
    {
        return openssl_decrypt(\base64_decode($encryptString), self::ENCRYPT_METHOD, $aesKey, OPENSSL_RAW_DATA, $aesIv);
    }

    public function fireEventAfterUseHandle(int $orderId)
    {
        $this->eventManager->dispatch(EventName::CHECK_TICKET_ORDER_FOR_USE_API_HANDLE, [
            "orderId" => $orderId
        ]);
    }

    public function fireEventAfterCancelHandle(int $orderId)
    {
        $this->eventManager->dispatch(EventName::CHECK_TICKET_ORDER_FOR_CANCEL_API_HANDLE, [
            "orderId" => $orderId
        ]);
    }
}
