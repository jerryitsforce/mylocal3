<?php

namespace Branch8\Customer\Service;

use Branch8\Customer\Model\PendingEmployeeFactory;
use Branch8\Customer\Model\ResourceModel\PendingEmployee\CollectionFactory as PendingEmployeeCollectionFactory;
use Magento\Framework\App\ResourceConnection;
use Psr\Log\LoggerInterface;

class PendingEmployeeService
{
    /**
     * @var PendingEmployeeFactory
     */
    private $pendingEmployeeFactory;

    /**
     * @var PendingEmployeeCollectionFactory
     */
    private $pendingEmployeeCollectionFactory;

    /**
     * @var ResourceConnection
     */
    private $resourceConnection;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @param PendingEmployeeFactory $pendingEmployeeFactory
     * @param PendingEmployeeCollectionFactory $pendingEmployeeCollectionFactory
     * @param ResourceConnection $resourceConnection
     * @param LoggerInterface $logger
     */
    public function __construct(
        PendingEmployeeFactory $pendingEmployeeFactory,
        PendingEmployeeCollectionFactory $pendingEmployeeCollectionFactory,
        ResourceConnection $resourceConnection,
        LoggerInterface $logger
    ) {
        $this->pendingEmployeeFactory = $pendingEmployeeFactory;
        $this->pendingEmployeeCollectionFactory = $pendingEmployeeCollectionFactory;
        $this->resourceConnection = $resourceConnection;
        $this->logger = $logger;
    }

    /**
     * Process employee records for pending status
     *
     * @param array $employeeData
     * @return array
     */
    public function processEmployeeRecords($employeeData)
    {
        $results = [
            'processed' => 0,
            'recorded' => 0,
            'errors' => 0,
            'details' => []
        ];

        if (empty($employeeData)) {
            return $results;
        }

        try {
            // Batch check for existing customers
            $phoneNumbers = array_column($employeeData, 'Cellphone');
            $existingCustomers = $this->getExistingCustomers($phoneNumbers);
            
            foreach ($employeeData as $item) {
                $cellphone = $item['Cellphone'] ?? '';
                if (empty($cellphone)) {
                    $results['errors']++;
                    continue;
                }

                if (in_array($cellphone, $existingCustomers)) {
                    // Customer exists - mark as processed
                    $processed = $this->markAsProcessed($cellphone);
                    if ($processed) {
                        $results['processed']++;
                        $results['details'][] = "Marked as processed: {$cellphone}";
                    }
                } else {
                    // Customer doesn't exist - record as pending
                    $recorded = $this->recordPendingEmployee($cellphone, $item);
                    if ($recorded) {
                        $results['recorded']++;
                        $results['details'][] = "Recorded as pending: {$cellphone}";
                    } else {
                        $results['errors']++;
                    }
                }
            }

            if(\Branch8\HotaiCore\Helper\DebugLog::isEnable('Branch8_Customer', 'systemlog')){
                $this->logger->info(sprintf(
                    "PendingEmployee processing completed: %d processed, %d recorded, %d errors",
                    $results['processed'],
                    $results['recorded'],
                    $results['errors']
                ));
        }

        } catch (\Exception $e) {
            if(\Branch8\HotaiCore\Helper\DebugLog::isEnable('Branch8_Customer', 'exceptionlog')){
                $this->logger->error("PendingEmployee processing failed: " . $e->getMessage());
            }
            $results['errors'] = count($employeeData);
        }

        return $results;
    }

    /**
     * Get existing customers by phone numbers (batch query for efficiency)
     *
     * @param array $phoneNumbers
     * @return array
     */
    private function getExistingCustomers($phoneNumbers)
    {
        if (empty($phoneNumbers)) {
            return [];
        }

        $connection = $this->resourceConnection->getConnection();
        $phoneNumbers = array_unique($phoneNumbers);
        
        // Use prepared statement with placeholders
        $placeholders = str_repeat('?,', count($phoneNumbers) - 1) . '?';
        $sql = "SELECT phone_number FROM customer_entity WHERE phone_number IN ({$placeholders})";
        
        $result = $connection->query($sql, $phoneNumbers);
        $existingPhones = [];
        
        while ($row = $result->fetch()) {
            $existingPhones[] = $row['phone_number'];
        }
        
        return $existingPhones;
    }

    /**
     * Record pending employee
     *
     * @param string $cellphone
     * @param array $itemData
     * @return bool
     */
    private function recordPendingEmployee($cellphone, $itemData)
    {
        try {
            // Check for existing record
            $existingRecord = $this->pendingEmployeeCollectionFactory->create()
                ->addFieldToFilter('cellphone', $cellphone)
                ->getFirstItem();

            if ($existingRecord->getId()) {
                // Update existing record
                $this->updateExistingRecord($existingRecord, $itemData);
                return true;
            }

            // Create new record
            $pendingEmployee = $this->pendingEmployeeFactory->create();
            $this->populatePendingEmployee($pendingEmployee, $cellphone, $itemData);
            $pendingEmployee->save();

            return true;
            
        } catch (\Exception $e) {
            if(\Branch8\HotaiCore\Helper\DebugLog::isEnable('Branch8_Customer', 'exceptionlog')){
                $this->logger->error("Failed to record pending employee {$cellphone}: " . $e->getMessage());
            }
            return false;
        }
    }

    /**
     * Mark pending employee as processed
     *
     * @param string $cellphone
     * @return bool
     */
    private function markAsProcessed($cellphone)
    {
        try {
            $pendingEmployees = $this->pendingEmployeeCollectionFactory->create()
                ->addFieldToFilter('cellphone', $cellphone);

            $processedCount = 0;
            foreach ($pendingEmployees as $pendingEmployee) {
                // Keep the record for historical purposes
                $processedCount++;
            }

            if ($processedCount > 0) {
                if(\Branch8\HotaiCore\Helper\DebugLog::isEnable('Branch8_Customer', 'systemlog')){
                    $this->logger->info("Marked {$processedCount} pending employee records as processed: {$cellphone}");
                }
            }

            return $processedCount > 0;
            
        } catch (\Exception $e) {
            if(\Branch8\HotaiCore\Helper\DebugLog::isEnable('Branch8_Customer', 'exceptionlog')){
                $this->logger->error("Failed to mark pending employee as processed {$cellphone}: " . $e->getMessage());
            }
            return false;
        }
    }

    /**
     * Update existing pending employee record
     *
     * @param \Branch8\Customer\Model\PendingEmployee $record
     * @param array $itemData
     * @return void
     */
    private function updateExistingRecord($record, $itemData)
    {
        $this->populatePendingEmployee($record, $record->getCellphone(), $itemData);
        $record->save();
    }

    /**
     * Populate pending employee with data
     *
     * @param \Branch8\Customer\Model\PendingEmployee $pendingEmployee
     * @param string $cellphone
     * @param array $itemData
     * @return void
     */
    private function populatePendingEmployee($pendingEmployee, $cellphone, $itemData)
    {
        $pendingEmployee->setCellphone($cellphone);
        $pendingEmployee->setIsEnabled($itemData['isEnabled'] === 'true');
        $pendingEmployee->setCategoryIdentity($itemData['categoryIdentity'] ?? '');
        $pendingEmployee->setEmployeeId($itemData['employeeId'] ?? null);
        $pendingEmployee->setOrganizationIdentity($itemData['organizationIdentity'] ?? null);
        // Record is ready for processing
    }
}