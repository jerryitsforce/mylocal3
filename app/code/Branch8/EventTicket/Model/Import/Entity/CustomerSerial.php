<?php

namespace Branch8\EventTicket\Model\Import\Entity;
use Exception;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\DB\Adapter\AdapterInterface;
use Magento\Framework\Json\Helper\Data as JsonHelper;
use Magento\ImportExport\Helper\Data as ImportHelper;
use Magento\ImportExport\Model\Import;
use Magento\ImportExport\Model\Import\Entity\AbstractEntity;
use Magento\ImportExport\Model\Import\ErrorProcessing\ProcessingErrorAggregatorInterface;
use Magento\ImportExport\Model\ResourceModel\Helper;
use Magento\ImportExport\Model\ResourceModel\Import\Data;


class CustomerSerial extends \Magento\ImportExport\Model\Import\Entity\AbstractEntity
{
    const ENTITY_CODE = 'mapping_one_id_serial';
    const TABLE = 'ticket_event_ticket_customer';
    const ENTITY_ID_COLUMN = 'id';

    const SERIAL_COLUMN = 'serial_number';

    /**
     * If we should check column names
     */
    protected $needColumnCheck = true;

    /**
     * Need to log in import history
     */
    protected $logInHistory = true;

    /**
     * Valid column names
     */
    protected $validColumnNames = [
        'one_id',
        'serial_number'
    ];

    protected $updateOnDuplicateSerial = [
        'one_id',
        'updated_at'
    ];

    /**
     * @var AdapterInterface
     */
    protected $connection;

    /**
     * @var ResourceConnection
     */
    private $resource;

    protected $timezone;

    /**
     * Courses constructor.
     *
     * @param JsonHelper $jsonHelper
     * @param ImportHelper $importExportData
     * @param Data $importData
     * @param ResourceConnection $resource
     * @param Helper $resourceHelper
     * @param ProcessingErrorAggregatorInterface $errorAggregator
     */
    public function __construct(
        JsonHelper $jsonHelper,
        ImportHelper $importExportData,
        Data $importData,
        ResourceConnection $resource,
        Helper $resourceHelper,
        ProcessingErrorAggregatorInterface $errorAggregator,
        \Magento\Framework\Stdlib\DateTime\TimezoneInterface $timezone
    ) {
        $this->jsonHelper = $jsonHelper;
        $this->_importExportData = $importExportData;
        $this->_resourceHelper = $resourceHelper;
        $this->_dataSourceModel = $importData;
        $this->resource = $resource;
        $this->connection = $resource->getConnection(ResourceConnection::DEFAULT_CONNECTION);
        $this->errorAggregator = $errorAggregator;
        $this->timezone = $timezone;
        $this->initMessageTemplates();
    }

    /**
     * @return void
     */
    private function initMessageTemplates(): void
    {
        $this->addMessageTemplate(
            'SerialNotExisted',
            __('The serial number does not exist')
        );
        $this->addMessageTemplate(
            'SerialUsed',
            __('Sub-Order Increment ID cannot be empty.')
        );
    }
    /**
     * Entity type code getter.
     *
     * @return string
     */
    public function getEntityTypeCode()
    {
        return static::ENTITY_CODE;
    }

    /**
     * Get available columns
     *
     * @return array
     */
    public function getValidColumnNames(): array
    {
        return $this->validColumnNames;
    }

    /**
     * Row validation
     *
     * @param array $rowData
     * @param int $rowNum
     *
     * @return bool
     */
    public function validateRow(array $rowData, $rowNum): bool
    {
        if (isset($this->_validatedRows[$rowNum])) {
            return !$this->getErrorAggregator()->isRowInvalid($rowNum);
        }
        $serialNumber = $rowData[self::SERIAL_COLUMN];

        $select = $this->connection->select()
            ->from(['sr' => 'ticket_event_ticket'], '*')
            ->where('serial_number="'.$serialNumber.'"');
        $data = $this->connection->fetchRow($select);
        if(!$data){
            $this->addRowError('SerialNotExisted', $rowNum);
        }
        if(is_array($data) && isset($data['status']) && $data['status'] == \Branch8\HotaiCore\Model\Ticket\Status::STATUS_USED){
            $this->addRowError('SerialUsed', $rowNum);
        }

        $this->_validatedRows[$rowNum] = true;

        return !$this->getErrorAggregator()->isRowInvalid($rowNum);
    }

    /**
     * Import data
     *
     * @return bool
     *
     * @throws Exception
     */
    protected function _importData(): bool
    {
        switch ($this->getBehavior()) {
            case Import::BEHAVIOR_APPEND:
                $this->saveAndReplaceEntity();
                break;
        }

        return true;
    }

    /**
     * Save and replace entities
     *
     * @return void
     */
    private function saveAndReplaceEntity()
    {
        $behavior = $this->getBehavior();
        $rows = [];
        while ($bunch = $this->_dataSourceModel->getNextBunch()) {
            $entityList = [];

            foreach ($bunch as $rowNum => $row) {
                if (!$this->validateRow($row, $rowNum)) {
                    continue;
                }

                if ($this->getErrorAggregator()->hasToBeTerminated()) {
                    $this->getErrorAggregator()->addRowToSkip($rowNum);

                    continue;
                }

                $rowId = $row[static::SERIAL_COLUMN];
                $rows[] = $rowId;
                $columnValues = [];

                foreach ($this->getAvailableColumns() as $columnKey) {
                    $columnValues[$columnKey] = $row[$columnKey];
                }

                $entityList[$rowId][] = $columnValues;
                $this->countItemsCreated += (int) !isset($row[static::ENTITY_ID_COLUMN]);
                $this->countItemsUpdated += (int) isset($row[static::ENTITY_ID_COLUMN]);
            }

            if (Import::BEHAVIOR_REPLACE === $behavior) {
                if ($rows && $this->deleteEntityFinish(array_unique($rows))) {
                    $this->saveEntityFinish($entityList);
                }
            } elseif (Import::BEHAVIOR_APPEND === $behavior) {
                $this->saveEntityFinish($entityList);
            }
        }
    }

    /**
     * Save entities
     *
     * @param array $entityData
     *
     * @return bool
     */
    private function saveEntityFinish(array $entityData): bool
    {
        if ($entityData) {
            $tableName = $this->connection->getTableName(static::TABLE);
            $rowsAddUpdate = [];
            $rowsDelete = [];
            $updatedAt = $this->timezone->convertConfigTimeToUtc($this->timezone->date());
            foreach ($entityData as $entityRows) {
                foreach ($entityRows as $row) {
                    if(trim((string)$row['one_id']) != ''){
                        $row['updated_at'] = $updatedAt;
                        $row['created_at'] = $updatedAt;
                        $rowsAddUpdate[] = $row;
                    }else{
                        $rowsDelete[] = $row['serial_number'];
                    }
                }
            }
            if(!empty($rowsAddUpdate) || !empty($rowsDelete)){
                if ($rowsAddUpdate) {
                    $this->connection->insertOnDuplicate($tableName, $rowsAddUpdate, $this->getUpdateColumnOnDuplicate());
                }
                if(!empty($rowsDelete)){
                    array_walk($rowsDelete, function(&$value, $key){
                        $value = '"'.$value.'"';
                    });
                    $strDelete = implode(',', $rowsDelete);
                    $this->connection->delete($tableName, 'serial_number in('.$strDelete.')');
                }
                return true;
            }


            return false;
        }
        return false;
    }

    public function getUpdateColumnOnDuplicate()
    {
        return $this->updateOnDuplicateSerial;
    }

    /**
     * Get available columns
     *
     * @return array
     */
    private function getAvailableColumns(): array
    {
        return $this->validColumnNames;
    }

}