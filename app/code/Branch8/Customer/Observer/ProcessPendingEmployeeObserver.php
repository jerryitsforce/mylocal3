<?php

namespace Branch8\Customer\Observer;

use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Customer\Api\CustomerRepositoryInterface;
use Branch8\Customer\Service\PendingEmployeeMatchingService;
use Psr\Log\LoggerInterface;

class ProcessPendingEmployeeObserver implements ObserverInterface
{
    /**
     * @var PendingEmployeeMatchingService
     */
    private $pendingEmployeeMatchingService;

    /**
     * @var CustomerRepositoryInterface
     */
    private $customerRepository;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @param PendingEmployeeMatchingService $pendingEmployeeMatchingService
     * @param CustomerRepositoryInterface $customerRepository
     * @param LoggerInterface $logger
     */
    public function __construct(
        PendingEmployeeMatchingService $pendingEmployeeMatchingService,
        CustomerRepositoryInterface $customerRepository,
        LoggerInterface $logger
    ) {
        $this->pendingEmployeeMatchingService = $pendingEmployeeMatchingService;
        $this->customerRepository = $customerRepository;
        $this->logger = $logger;
    }

    /**
     * Process pending employee matching after customer registration
     *
     * @param Observer $observer
     * @return void
     */
    public function execute(Observer $observer)
    {
        try {
            $customer = $observer->getEvent()->getCustomer();

            if (!$customer || !$customer->getId()) {
                return;
            }

            // 重新載入完整 customer 資料（含 EAV 屬性）
            $customer = $this->customerRepository->getById($customer->getId());

            // 只處理非賣家的客戶
            $platform = $customer->getCustomAttribute('platform');
            if ($platform && $platform->getValue() === 'seller') {
                return;
            }

            // 處理 pending employee 匹配
            $this->pendingEmployeeMatchingService->processCustomerRegistration($customer);

        } catch (\Exception $e) {
            // 記錄錯誤但不中斷註冊流程
            if(\Branch8\HotaiCore\Helper\DebugLog::isEnable('Branch8_Customer', 'exceptionlog')){
                $this->logger->error('Failed to process pending employee matching', [
                    'customer_id' => $customer ? $customer->getId() : 'unknown',
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString()
                ]);
            }
        }
    }
}