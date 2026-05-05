<?php

namespace Branch8\GeneralNonNotifyTicket\Helper;

use Branch8\HotaiCore\Model\Product\VirtualProductType;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Filesystem\DirectoryList;
use Magento\Framework\Filesystem\Io\File;
use Branch8\HotaiCore\Helper\DebugLog as HotaiCoreDebugLog;
use Branch8\HotaiCore\Helper\Common as HotaiCoreCommonHelper;

class Common
{
    /** @var ScopeConfigInterface */
    protected $scopeConfig;

    /** @var ProductRepositoryInterface */
    protected $productRepository;

    /** @var File */
    protected $file;

    /** @var DirectoryList */
    protected $directoryList;

    /** @var HotaiCoreCommonHelper */
    protected $hotaiCoreCommonHelper;

    public function __construct(
        ScopeConfigInterface $scopeConfig,
        ProductRepositoryInterface $productRepository,
        File $file,
        DirectoryList $directoryList,
        HotaiCoreCommonHelper $hotaiCoreCommonHelper
    ) {
        $this->scopeConfig       = $scopeConfig;
        $this->productRepository = $productRepository;
        $this->file              = $file;
        $this->directoryList     = $directoryList;
        $this->hotaiCoreCommonHelper = $hotaiCoreCommonHelper;
    }

    /**
     * 取得後臺設定
     *
     * @param string $configPath
     * @return string|null
     */
    public function getConfig(string $configPath): ?string
    {
        return $this->scopeConfig->getValue($configPath);
    }

    /**
     * @param integer $productId
     * @return boolean
     */
    public function IsGeneralNonNotifyTicketProduct(int $productId): bool
    {
        try {
            $product = $this->productRepository->getById($productId);
        } catch (\Magento\Framework\Exception\NoSuchEntityException $e) {
            return false;
        }

        $virtualProductType = $product->getCustomAttribute(VirtualProductType::ATTRIBUTE_CODE);

        if (empty($virtualProductType)) {
            return false;
        }

        return $virtualProductType->getValue() == VirtualProductType::TYPE_GENERAL_NON_NOTIFY_TICKET;
    }

    public function writeLogIfEnabled(
        string|array $message,
        string $folderName,
        string $logOptionValue,
        string $fileName = ""
    ): void {
        if (!HotaiCoreDebugLog::isEnable('Branch8_GeneralNonNotifyTicket', $logOptionValue)) {
            return;
        }

        $this->hotaiCoreCommonHelper->writeLog($message, $folderName, $fileName);
    }
}
