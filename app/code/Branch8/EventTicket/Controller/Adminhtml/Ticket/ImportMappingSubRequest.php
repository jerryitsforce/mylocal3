<?php

namespace Branch8\EventTicket\Controller\Adminhtml\Ticket;

use Branch8\HotaiCore\Model\Ticket\Status as TicketStatus;
use Magento\Backend\App\Action;
use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\Controller\Result\JsonFactory;

class ImportMappingSubRequest extends Action{
    /**
     * @var \Magento\Framework\DB\Adapter\AdapterInterface
     */
    protected $conn;
    /**
     * @var \Magento\Framework\App\ResourceConnection
     */
    protected $resourceConnection;
    /**
     * @var
     */
    protected $timezone;
    /**
     * @var JsonFactory
     */
    protected $resultJsonFactory;
    /**
     * @var \Magento\Framework\Filesystem\Directory\WriteInterface
     */
    protected $filesystem;

    protected $mappingLog;

    /**
     * @param Action\Context $context
     * @param \Magento\Framework\App\ResourceConnection $resourceConnection
     * @param \Magento\Framework\Stdlib\DateTime\TimezoneInterface $timezone
     * @param JsonFactory $resultJsonFactory
     * @param \Magento\Framework\Filesystem $filesystem
     * @throws \Magento\Framework\Exception\FileSystemException
     */
    public function __construct(
        \Magento\Backend\App\Action\Context $context,
        \Magento\Framework\App\ResourceConnection $resourceConnection,
        \Magento\Framework\Stdlib\DateTime\TimezoneInterface $timezone,
        JsonFactory $resultJsonFactory,
        \Magento\Framework\Filesystem $filesystem,
        \Branch8\EventTicket\Helper\MappingLog $mappingLog
    ){
        parent::__construct($context);
        $this->resourceConnection = $resourceConnection;
        $this->conn = $this->resourceConnection->getConnection();
        $this->timezone = $timezone;
        $this->resultJsonFactory = $resultJsonFactory;
        $this->filesystem = $filesystem->getDirectoryWrite(DirectoryList::VAR_DIR);
        $this->mappingLog = $mappingLog;
    }

    public function execute(){
        $result = $this->resultJsonFactory->create();

        $queueId = $this->getRequest()->getParam('queueId');
        $eventId = $this->getRequest()->getParam('eventId');

        $logPath = 'log/import_pool_mapping';
        if(!$this->filesystem->isDirectory($logPath)){
            $this->filesystem->create($logPath);
        }

        // $writer = new \Zend_Log_Writer_Stream(BP .'/var/log/import_pool_mapping/mapping_pool_'.$eventId.'.log');
        // $logger = new \Zend_Log();
        // $logger->addWriter($writer);
        // $logger->info('----------START IMPORT QUEUE '.$queueId.' --- POOL '.$eventId.'--------------'.$this->timezone->date()->format('Y-m-d H:i:s'));
        $this->mappingLog->writeLog([
            'pool_id' => $eventId,
            'content' => '----------START IMPORT QUEUE '.$queueId.' --- POOL '.$eventId
        ]);
        /**
         * Get data from queue and import
         */
        $sqlGetData = $this->conn->select()
            ->from(['queue' => 'ticket_import_mapping_queue'], '*')
            ->where('queue_id='.$queueId)
            ->limit(\Branch8\EventTicket\Helper\Data::IMPORT_MAPPING_SUB_REQUEST);
        $resultData = $this->conn->query($sqlGetData);
        try {
            $this->conn->beginTransaction();
            $eventTicketRecordIds = [];
            $queueIds = [];
            while ($row = $resultData->fetch()) {

                $eventTicketRecordIds[] = $row['record_id'];
                $queueIds[] = $row['entity_id'];

                $this->conn->update('customer_ticket', [
                    'member_seq' => $row['member_seq'],
                    'updated_at' => $row['created_at'],
                    'status' => TicketStatus::STATUS_UNUSED
                ], 'ticket_table_name="ticket_event_ticket" and ticket_table_record_id='.$row['record_id'].' and ticket_unique_content="'.$row['serial_number'].'"');


                // $logger->info('Mapping: '.$queueId.' ----- '.$row['member_seq'].' ----- '.$row['serial_number']);
                $this->mappingLog->writeLog([
                    'pool_id' => $eventId,
                    'content' => 'Mapping: Queue:'.$queueId.' ----- '.$row['member_seq'].' ----- '.$row['serial_number']
                ]);
            }
            $this->conn->update('ticket_event_ticket', [
                'status' => TicketStatus::STATUS_UNUSED
            ], 'entity_id in('.implode(',', $eventTicketRecordIds).')');

            $this->conn->delete('ticket_import_mapping_queue', 'entity_id in('.implode(',',$queueIds).')');

            
            // $logger->info('Done a sub request at: '.$this->timezone->date()->format('Y-m-d H:i:s'));
            $this->mappingLog->writeLog([
                'pool_id' => $eventId,
                'content' => 'Done a sub request'
            ]);

            $this->conn->commit();

            $sqlRemainData = $this->conn->select()
                ->from(['queue' => 'ticket_import_mapping_queue'], [])
                ->columns(['cnt' => 'count(*)'])
                ->where('queue_id='.$queueId);
            $remainRows = (int)$this->conn->fetchOne($sqlRemainData);
            $result->setData(['error' => false, 'remain' => $remainRows]);
            return $result;
        }catch (\Exception $e){
            if(isset($row)){
                $this->mappingLog->writeLog([
                    'pool_id' => $eventId,
                    'content' => 'Broken at ID: '.$row['entity_id'].'. Rollback'
                ]);
            }
            $this->conn->rollBack();
            // $logger->info($e->getMessage());
            $this->mappingLog->writeLog([
                'pool_id' => $eventId,
                'content' => $e->getMessage()
            ]);
            // $logger->info($_SERVER['REQUEST_URI']);
            $this->mappingLog->writeLog([
                'pool_id' => $eventId,
                'content' => $_SERVER['REQUEST_URI']
            ]);
            return $result->setData(['error' => true]);
        }

    }

}