<?php
declare(strict_types=1);

namespace Ecpay\Invoice\ViewModel;

use Magento\Framework\View\Element\Block\ArgumentInterface;
use Magento\Framework\Registry;
use HotaiConnected\ManualInvoice\Api\ManualInvoiceRepositoryInterface;
use HotaiConnected\ManualInvoice\Api\Data\ManualInvoiceInterface;
use Psr\Log\LoggerInterface;

class ManualInvoice implements ArgumentInterface
{
    /**
     * @var Registry
     */
    private $registry;

    /**
     * @var ManualInvoiceRepositoryInterface
     */
    private $manualInvoiceRepository;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var ManualInvoiceInterface|null
     */
    private $manualInvoice;

    /**
     * @param Registry $registry
     * @param ManualInvoiceRepositoryInterface $manualInvoiceRepository
     * @param LoggerInterface $logger
     */
    public function __construct(
        Registry $registry,
        ManualInvoiceRepositoryInterface $manualInvoiceRepository,
        LoggerInterface $logger
    ) {
        $this->registry = $registry;
        $this->manualInvoiceRepository = $manualInvoiceRepository;
        $this->logger = $logger;
    }

    /**
     * 取得目前訂單
     *
     * @return \Magento\Sales\Model\Order|null
     */
    private function getCurrentOrder()
    {
        return $this->registry->registry('sales_order');
    }

    /**
     * 取得手開發票資料
     *
     * @return ManualInvoiceInterface|null
     */
    public function getManualInvoice(): ?ManualInvoiceInterface
    {
        if ($this->manualInvoice === null) {
            $order = $this->getCurrentOrder();
            if ($order && $order->getId()) {
                try {
                    $this->manualInvoice = $this->manualInvoiceRepository->getByOrderId((int)$order->getId());
                } catch (\Exception $e) {
                    $this->logger->error('Failed to load manual invoice: ' . $e->getMessage());
                    $this->manualInvoice = false;
                }
            }
        }
        
        return $this->manualInvoice ?: null;
    }

    /**
     * 檢查是否已有手開發票記錄
     *
     * @return bool
     */
    public function hasManualInvoice(): bool
    {
        return $this->getManualInvoice() !== null;
    }

    /**
     * 取得發票號碼
     *
     * @return string
     */
    public function getInvoiceNumber(): string
    {
        $manualInvoice = $this->getManualInvoice();
        return $manualInvoice ? (string)$manualInvoice->getInvoiceNumber() : '';
    }

    /**
     * 取得發票開立日期 (格式化為 HTML date input 格式)
     *
     * @return string
     */
    public function getInvoiceDate(): string
    {
        $manualInvoice = $this->getManualInvoice();
        if ($manualInvoice && $manualInvoice->getInvoiceCreatedAt()) {
            return date('Y-m-d', strtotime($manualInvoice->getInvoiceCreatedAt()));
        }
        return '';
    }

    /**
     * 取得隨機碼
     *
     * @return string
     */
    public function getRandomCode(): string
    {
        $manualInvoice = $this->getManualInvoice();
        return $manualInvoice && $manualInvoice->getRandomCode() ? (string)$manualInvoice->getRandomCode() : '';
    }

    /**
     * 取得發票類型
     *
     * @return string
     */
    public function getInvoiceType(): string
    {
        $manualInvoice = $this->getManualInvoice();
        if (!$manualInvoice) {
            return 'p'; // 預設為個人發票
        }
        return $manualInvoice->getInvoiceType();
    }

    /**
     * 取得發票狀態
     *
     * @return string
     */
    public function getInvoiceStatus(): string
    {
        $manualInvoice = $this->getManualInvoice();
        if (!$manualInvoice) {
            return 'issue';
        }

        switch ($manualInvoice->getInvoiceStatus()) {
            case 1:
                return 'issue';
            case 2:
                return 'void';
            case 3:
                return 'allowance';
            default:
                return 'issue';
        }
    }

    /**
     * 取得交易編號
     *
     * @return string
     */
    public function getTransactionNumber(): string
    {
        $manualInvoice = $this->getManualInvoice();
        return $manualInvoice ? (string)$manualInvoice->getTransNo() : '';
    }

    /**
     * 取得公司名稱
     *
     * @return string
     */
    public function getCompanyName(): string
    {
        $manualInvoice = $this->getManualInvoice();
        return $manualInvoice ? (string)$manualInvoice->getCompanyName() : '';
    }

    /**
     * 取得統一編號
     *
     * @return string
     */
    public function getTaxId(): string
    {
        $manualInvoice = $this->getManualInvoice();
        return $manualInvoice ? (string)$manualInvoice->getInvoiceCustomerIdentifier() : '';
    }

    /**
     * 檢查是否需要顯示公司欄位
     *
     * @return bool
     */
    public function shouldShowCompanyFields(): bool
    {
        return $this->getInvoiceType() === 'c';
    }
}