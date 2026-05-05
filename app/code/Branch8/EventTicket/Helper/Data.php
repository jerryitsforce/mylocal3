<?php

namespace Branch8\EventTicket\Helper;

use Branch8\HotaiCore\Model\Ticket\Status as TicketStatus;
use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\App\Filesystem\DirectoryList;

class Data extends  AbstractHelper
{
    const IMPORT_END_DATE_DATE = 1;

    const IMPORT_END_DATE_DAY = 2;

    const IMPORT_MAPPING_SUB_REQUEST = 600;
    /**
     * @var \Magento\Framework\App\ResourceConnection
     */
    protected $resourceConnection;
    /**
     * @var \Magento\Framework\DB\Adapter\AdapterInterface
     */
    protected $conn;
    /**
     * @var \Magento\Framework\Stdlib\DateTime\TimezoneInterface
     */
    protected $timezone;
    /**
     * @var \Branch8\EventTicket\Model\TicketEventLogFactory
     */
    protected $ticketEventLogFactory;
    /**
     * @var \Magento\Framework\Filesystem
     */
    protected $filesystem;

    protected $mappingLog;

    /**
     * @param \Magento\Framework\App\Helper\Context $context
     * @param \Magento\Framework\App\ResourceConnection $resourceConnection
     * @param \Magento\Framework\Stdlib\DateTime\TimezoneInterface $timezone
     * @param \Branch8\EventTicket\Model\TicketEventLogFactory $ticketEventLogFactory
     * @param \Magento\Framework\Filesystem $filesystem
     */
    public function __construct(
        \Magento\Framework\App\Helper\Context $context,
        \Magento\Framework\App\ResourceConnection $resourceConnection,
        \Magento\Framework\Stdlib\DateTime\TimezoneInterface $timezone,
        \Branch8\EventTicket\Model\TicketEventLogFactory $ticketEventLogFactory,
        \Magento\Framework\Filesystem $filesystem,
        \Branch8\EventTicket\Helper\MappingLog $mappingLog
    ){
        parent::__construct($context);
        $this->resourceConnection = $resourceConnection;
        $this->conn = $this->resourceConnection->getConnection();
        $this->timezone = $timezone;
        $this->ticketEventLogFactory = $ticketEventLogFactory;
        $this->filesystem = $filesystem->getDirectoryWrite(DirectoryList::VAR_DIR);
        $this->mappingLog = $mappingLog;
    }

    /**
     * @param $eventId
     * @param $data
     * @return array
     */
    public function validateImportSerial($eventId, $data){
        $result = ['error' => true, 'msg' => ''];
        if($data == null){
            return ['error' => true, 'msg' => __('Data is empty')];
        }
        $cnt = 1;
        $errorData = [];
        $errorRow = [];
        $serials = [];
        foreach($data as $_row){
            $cnt ++;

            if(trim((string)$_row[0]) == ''){
                $errorData[] = 'Row #'.$cnt.' is  empty';
                $errorRow[$cnt] = $cnt;
            }
            if(strlen((string)$_row[0]) > 255){
                $errorData[] = 'Row #'.$cnt.' is  too long';
                $errorRow[$cnt] = $cnt;
            }
            $serials[$_row[0]][] = $cnt;
        }
        if(count($errorRow)){
            return ['error' => true, 'msg' => __('Error on rows: %1', implode(',', $errorRow))];
        }
        /**
         * If duplicate row
         */
        $dupRow = [];
        if(count($serials) < count($data)){
            foreach($serials as $_serial => $_rows){
                if(count($_rows) > 1){
                    $dupRow[] = implode('-', $_rows);
                }
            }
            $result['msg'] = __('Duplicate rows: %1', implode(',', $dupRow));
            return $result;
        }
        /**
         * Validate duplicate in DB
         */
        $conn = $this->conn;
        $selectTicketData = $conn->select()
            ->from(['ev' => 'ticket_event'], ['ticket_type', 'seller_id'])
            ->where('entity_id='.$eventId);
        $ticketData = $conn->fetchRow($selectTicketData);
        if(empty($ticketData)){
            return ['error' => true, 'msg' => __('Event not found')];
        }
        $ticketType = $ticketData['ticket_type'];
        $serialsArr = array_keys($serials);
        array_walk($serialsArr, function(&$val, $key){
            $val = '"'.$val.'"';
        });

        if(in_array($ticketType, [
            \Branch8\TicketApi\Model\TicketApiBrand\Source\Brand::BRAND_CODE_GENERAL_NOTIFY,
            \Branch8\TicketApi\Model\TicketApiBrand\Source\Brand::BRAND_CODE_GENERAL_NON_NOTIFY
        ])){
            $sellerId = $ticketData['seller_id'];
            return $this->validateDuplicateGeneralTicket($ticketType, $sellerId, $serialsArr);
        }else{
            return $this->validateNotGeneralTicket($ticketType, $serialsArr);
        }  
        
        /**
         * Val
         */
        $result = ['error' => false, 'msg' => ''];
        return $result;
    }

    public function validateDuplicateGeneralTicket($ticketType, $sellerId, $serialsArr){
        $select = $this->conn->select();
        $select->from(['tc' => 'ticket_event_ticket'], 'serial_number')
            ->joinLeft(['event' => 'ticket_event'], 'tc.event_id = event.entity_id', [])
            ->where('event.ticket_type="'.$ticketType.'" and event.seller_id='.$sellerId.' and serial_number in('.implode(',', $serialsArr).')');
        $checkDupDb = $this->conn->fetchCol($select);
        if(count($checkDupDb)){
            return ['error' => true, 'msg' => __('Serials %1 is existed', implode(',', $checkDupDb))];
        }else{
            return ['error' => false];
        }
    }

    public function validateNotGeneralTicket($ticketType, $serialsArr){
        $select = $this->conn->select();
        $select->from(['tc' => 'ticket_event_ticket'], 'serial_number')
            ->joinLeft(['event' => 'ticket_event'], 'tc.event_id = event.entity_id', [])
            ->where('event.ticket_type="'.$ticketType.'" and serial_number in('.implode(',', $serialsArr).')');
        $checkDupDb = $this->conn->fetchCol($select);
        if(count($checkDupDb)){
            return ['error' => true, 'msg' => __('Serials %1 is existed', implode(',', $checkDupDb))];
        }else{
            return ['error' => false];
        }
    }


    /**
     * @param $batchCode
     * @param $eventId
     * @return bool
     */
    public function validateBatchCode($batchCode, $eventId){
        $conn = $this->conn;
        $select = $conn->select()
            ->from(['ticket' => 'ticket_event_ticket'], ['entity_id'])
            ->where('batch_code="'.$batchCode.'" and event_id='.$eventId);
        return $conn->fetchOne($select) ? false : true;
    }

    /**
     * @param $data
     * @param $serials
     * @return bool
     */
    public function importSerial($data, $serials)
    {
        $sql = 'insert into ticket_event_ticket values';
        $sqlCustomerTicket = 'insert into customer_ticket(record_id, ticket_table_name, ticket_table_record_id, batch_code, seller_id, ticket_unique_content,status,use_start_time, use_end_time, member_seq) values';
        $serialImported = [];

        /**
         * Product ticket is developed before pool ticket
         * The use_start_time/ use_end_time in database is TW time, not UTC time
         * So the pool ticket have to do that at customer ticket(deleveop after product ticket))
         */
        $addTimeValue = 8;
        $addTimeIntervalValue = new \DateInterval('PT'.$addTimeValue.'H');
        $eventTicketUseStart = $this->timezone->date($data['start_date'])->sub($addTimeIntervalValue)->format('Y-m-d H:i:s');
        $eventTicketUseEnd = $this->timezone->date($data['end_date'])->sub($addTimeIntervalValue)->format('Y-m-d H:i:s');

        foreach($serials as $_serial){
            $serialImported[] = '"'.$_serial[0].'"';
            $sql .= '(NULL, '.$data['event_id'].', "'.$data['batch_code'].'", "'.$_serial[0].'", NULL, '.\Branch8\HotaiCore\Model\Ticket\Status::STATUS_IMPORTED.', NULL, NULL,"'.$eventTicketUseStart.'","'.$eventTicketUseEnd.'"),';
        }
        $sql = rtrim($sql, ',');
        try{
            if(empty($serialImported)){
                return true;
            }
            $this->conn->beginTransaction();
            $this->conn->query($sql);

            //get list ID of ticket_event_ticket, index to customer ticket
            $selectImported = $this->conn->select()
                ->from(['ticket' => 'ticket_event_ticket'], ['entity_id', 'serial_number', 'batch_code', 'start_date', 'end_date'])
                ->joinLeft(['event' => 'ticket_event'], 'event.entity_id = ticket.event_id', ['seller_id'])
                ->where('serial_number in('.implode(',', $serialImported).') and event.entity_id='.$data['event_id']);
            $dataEvent = $this->conn->fetchAll($selectImported);
            $customerTicketUseStart = $this->timezone->date($data['start_date'])->format('Y-m-d H:i:s');
            $customerTicketUseEnd = $this->timezone->date($data['end_date'])->format('Y-m-d H:i:s');
            foreach($dataEvent as $_ev){
                $sqlCustomerTicket .= '(NULL,"ticket_event_ticket",'.$_ev['entity_id'].',"'.$_ev['batch_code'].'",'.$_ev['seller_id'].',"'.$_ev['serial_number'].'",'.TicketStatus::STATUS_IMPORTED.',"'.$customerTicketUseStart.'","'.$customerTicketUseEnd.'",NULL),';
            }
            $sqlCustomerTicket = trim($sqlCustomerTicket, ',');
            $this->conn->query($sqlCustomerTicket);

            $this->conn->commit();
            return true;
        }catch (\Exception $e){echo $e->getMessage();
            $this->conn->rollBack();
            return false;
        }
        return true;
    }

    /**
     * @param $data
     * @param $isCheckEnable
     * @return false|mixed
     */
    public function isEventTicket($data, $isCheckEnable = true)
    {
        $isEnableWhere = '';
        if($isCheckEnable){
            $isEnableWhere = ' and event.is_enable = 1';
        }
        $brand = $data['brand'];
        $serialNumber = $data['serialNo'];
        $select = $this->conn->select()
            ->from(['tc' => 'ticket_event_ticket'], ['entity_id'])
            ->joinLeft(['event' => 'ticket_event'], 'event.entity_id = tc.event_id', ['serial_id' => 'event.entity_id', 'ticket_type'])
            ->where('event.ticket_type="'.$brand.'" and tc.serial_number="'.$serialNumber.'"'.$isEnableWhere);
        $result = $this->conn->fetchRow($select);
        return $result ? $result : false;
    }

    /**
     * @param $eventId
     * @param $data
     * @return array|false[]
     */
    public function validateMappingData($eventId, $data){
        $result = ['error' => true, 'msg' => ''];
        if($data == null){
            return ['error' => true, 'msg' => __('Data is empty')];
        }
        $cnt = 1;
        $mappingCheckDup = [];
        $mapping = [];
        $errorRow = [];
        $cntEmptySerial = 0;
        $notEmptySerial = [];
        foreach($data as $_row) {
            $cnt++;
            /**
             * Allow mapping with empty serial
             * Mean can select one of unmapped serial
             */
            if($_row[0] == '' && $_row[1] != ''){
                $cntEmptySerial ++;
                continue;
            }
            if((string)$_row[1] == ''){
                $errorRow[] = $cnt;
                continue;
            }

            if (strlen((string)$_row[0]) > 255) {
                $errorRow[$cnt] = $cnt;
            }
            $mappingCheckDup[$_row[0]][] = $cnt;
            $mapping[$_row[0]] = $_row[1];
            $notEmptySerial[] = '"'.$_row[0].'"';
        }
        if(count($errorRow)){
            return ['error' => true, 'msg' => __('Error on rows: %1', implode(',', $errorRow))];
        }
        /**
         * If duplicate row in file
         */
        $dupRow = [];
        if(count($mappingCheckDup) != count($data) - $cntEmptySerial){
            foreach($mappingCheckDup as $_serial => $_rows){
                if(count($_rows) > 1){
                    $dupRow[] = implode('-', $_rows);
                }
            }
            $result['msg'] = __('Duplicate serial number at rows %1', implode(',', $dupRow));
            return $result;
        }

        /**
         * Get valid serials for rows that have not serial
         * Because they only need to assign to member_seq any serials
         */
        if($cntEmptySerial){
            $exclNotEmptySerial = '';
            if(!empty($notEmptySerial)) {
                $exclNotEmptySerial = 'and ticket.serial_number not in(' . implode(',', $notEmptySerial) . ')';
            }
            $sqlFindAvailableSerial = $this->conn
                ->select()
                ->from(['ticket' => 'ticket_event_ticket'], ['serial_number'])
                ->where('event_id = '.$eventId.' and status='.TicketStatus::STATUS_IMPORTED.' '.$exclNotEmptySerial)
                ->limit($cntEmptySerial, 0);
            $validSerials = $this->conn->fetchCol($sqlFindAvailableSerial);
            if(count($validSerials) != $cntEmptySerial){
                $result['msg'] = __('Not enough serials for Member seq');
                return $result;
            }
        }

        /**
         * Validate ticket type
         */
        $eventSelect = $this->conn->select()
            ->from(['ev' => 'ticket_event'], ['ticket_type'])
            ->where('entity_id='.$eventId);
        $eventTicketType = $this->conn->fetchOne($eventSelect);
        if(!$eventTicketType){
            return ['error' => true, 'msg' => __('Invalid event data, please verify ticket type.')];
        }

        $serials = array_keys($mapping);
        if(count($serials) == 0){
            return ['error' => false];
        }
        /**
         * Validate existed serial
         */

        array_walk($serials, function (&$value, $key){
            $value = '"'.$value.'"';
        });
        $selectSerial = $this->conn->select()
            ->from(['ticket' => 'ticket_event_ticket'], ['serial_number'])
            ->where('event_id='.$eventId.' and serial_number in('.implode(',', $serials).')');
        $cntSerialInDb = $this->conn->fetchCol($selectSerial);
        if(count($cntSerialInDb) != count($serials)){
            $serialNotExist = array_diff($serials, $cntSerialInDb);
            return ['error' => true, 'msg' => __('Some Serials do not exist, please see: %1', implode(', ', $serialNotExist))];
        }
        /**
         * Validate mapped
         */

        $sqlImported = $this->conn->select()
            ->from(['ticket' => 'ticket_event_ticket'], ['serial_number'])
            ->joinLeft(['ev' => 'ticket_event'], 'ticket.event_id = ev.entity_id', [])
            ->where('ticket.event_id='.$eventId.' and ticket.serial_number in('.implode(',', $serials).') and ticket.status <> '.TicketStatus::STATUS_IMPORTED);
        $importedSerials = $this->conn->fetchCol($sqlImported);
        if(count($importedSerials)){
            return ['error' => true, 'msg' => __('These serials are mapped: '.implode(',', $importedSerials))];
        }

        /**
         * Validate count valid serial status
         */
        $selectSerialNotUsed = $this->conn->select()
            ->from(['ticket' => 'ticket_event_ticket'], ['serial_number'])
            ->joinLeft(['ev' => 'ticket_event'], 'ev.entity_id = ticket.event_id', [])
            ->where('ev.entity_id='.$eventId.' and status='.TicketStatus::STATUS_IMPORTED.' and serial_number in('.implode(',', $serials).')');
        $cntSerialNotUsed = $this->conn->fetchCol($selectSerialNotUsed);
        if(count($cntSerialNotUsed) != count($serials)){
            $serialUsed = array_diff($serials, $cntSerialInDb);
            return ['error' => true, 'msg' => __('Some Serials were used, please see: %1', implode(', ', $serialUsed))];
        }
        return ['error' => false];
    }

    /**
     * @param $eventId
     * @param $data
     * @return void
     * @throws \Exception
     */
    public function importMappingQueue($eventId, $data){
        $serialArr = [];
        $serials = [];
        /**
         * 0: serial
         * 1: one_id
         */
        $needToGetSerial = [];
        foreach($data as $_row){
            if($_row[0] == ''){
                $needToGetSerial[] = $_row[1];
                continue;
            }
            $serialArr[$_row[0]] = $_row[1];
            $serials[] = '"'.$_row[0].'"';
        }

        if(count($needToGetSerial) > 0){
            $exclNotEmptySerial = '';
            if(!empty($serials)) {
                $exclNotEmptySerial = ' and ticket.serial_number not in(' . implode(',', $serials) . ')';
            }
//            $sqlFindAvailableSerial = $this->conn->select()
//                ->from(['ticket' => 'ticket_event_ticket'], ['serial_number'])
//                ->joinLeft(['ct' => 'customer_ticket'], 'ct.ticket_table_name="ticket_event_ticket" and ct.ticket_unique_content=ticket.serial_number and ct.ticket_table_record_id = ticket.entity_id', ['status'])
//                ->where('event_id = '.$eventId.' and ticket.redeemed_at is null and member_seq is null '.$exclNotEmptySerial)
//                ->limit(count($needToGetSerial), 0);

            $sqlFindAvailableSerial = $this->conn->select()
                ->from(['ticket' => 'ticket_event_ticket'], ['serial_number'])
                ->where('event_id = '.$eventId.' and status='.TicketStatus::STATUS_IMPORTED.' '.$exclNotEmptySerial)
                ->limit(count($needToGetSerial), 0);
            $validSerials = $this->conn->fetchCol($sqlFindAvailableSerial);
            foreach($validSerials as $key => $_validSerial){
                $serialArr[$_validSerial] = $needToGetSerial[$key];
                $serials[] = '"'.$_validSerial.'"';
            }
        }


        $select = $this->conn->select()
            ->from(['ticket' => 'ticket_event_ticket'], ['entity_id', 'serial_number'])
            ->where('serial_number in('.implode(',', $serials).') and ticket.event_id='.$eventId);
        $dataEvent = $this->conn->fetchAll($select);
        if(count($dataEvent) != count($serials)){
            throw new \Exception(__('Not enough available serial.')->render());
        }

        try {
            $this->conn->beginTransaction();
            $sqlQueue = 'insert into ticket_import_mapping_queue values';
            $queueId = microtime(true);
            $cnt = 0;
            $createdAt = $this->timezone->convertConfigTimeToUtc($this->timezone->date());
            foreach($dataEvent as $_ev){
                $cnt ++;
                $sqlQueue .= '(NULL,"'.$queueId.'","'.$serialArr[$_ev['serial_number']].'","'.$_ev['serial_number'].'",'.$_ev['entity_id'].',"'.$createdAt.'", '.$eventId.'),';

                if($cnt == 5000){
                    $sqlQueue = substr($sqlQueue, 0, -1).';';
                    $this->conn->query($sqlQueue);
                    $sqlQueue = 'insert into ticket_import_mapping_queue values';
                    $cnt = 0;
                }

//                $this->conn->update('ticket_event_ticket', [
//                    'status' => TicketStatus::STATUS_UNUSED
//                ], 'entity_id='.$_ev['entity_id']);
//
//                $this->conn->update('customer_ticket', [
//                    'member_seq' => $serialArr[$_ev['serial_number']],
//                    'updated_at' => $this->timezone->convertConfigTimeToUtc($this->timezone->date()),
//                    'status' => TicketStatus::STATUS_UNUSED
//                ], 'ticket_table_name="ticket_event_ticket" and ticket_table_record_id='.$_ev['entity_id'].' and ticket_unique_content="'.$_ev['serial_number'].'"');
            }
            if($cnt > 0){
                $sqlQueue = substr($sqlQueue, 0, -1).';';
                $this->conn->query($sqlQueue);
            }
            $this->conn->commit();
            // $logPath = 'log/import_pool_mapping';
            // if(!$this->filesystem->isDirectory($logPath)){
            //     $this->filesystem->create($logPath);
            // }
            // $writer = new \Zend_Log_Writer_Stream(BP .'/var/log/import_pool_mapping/mapping_pool_'.$eventId.'.log');
            // $logger = new \Zend_Log();
            // $logger->addWriter($writer);
            // $logger->info('Import to queue ID '.$eventId.' at '.$this->timezone->date()->format('Y-m-d H:i:s'));
            // $logger->info('Number of mapping: '.count($dataEvent));
            // $logger->info('Event ID: '.$eventId.' --- Queue ID: '.$queueId);

            $this->mappingLog->writeLog(['pool_id' => $eventId, 'content' => 'Import to queue ID '.$eventId]);
            $this->mappingLog->writeLog(['pool_id' => $eventId, 'content' => 'Number of mapping: '.count($dataEvent)]);
            $this->mappingLog->writeLog(['pool_id' => $eventId, 'content' => 'Event ID: '.$eventId.' --- Queue ID: '.$queueId]);
            return ['queueId' => $queueId, 'cntItem' => count($dataEvent)];
        }catch (\Exception $e){echo $e->getMessage();
            $this->conn->rollBack();
            throw new \Exception(__('Import fail')->render());
        }
    }

    /**
     * @param $data
     * @return \Branch8\EventTicket\Model\TicketEventLog
     * @throws \Exception
     */
    public function addLogApiCheckSerial($data){
        $model = $this->ticketEventLogFactory->create()
            ->setData($data)
            ->save();
        return $model;
    }

    /**
     * @param $model
     * @param $data
     * @return void
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function updateLogApiCheckSerial($model, $data)
    {
        $dataResponse = json_encode($data);
        $updatedAt = $this->timezone->convertConfigTimeToUtc($this->timezone->date());
        $model->setData('response', $dataResponse)
            ->setData('updated_at', $updatedAt)
            ->save();
    }

    public function hasSerials($eventId){
        if((int)$eventId == 0){
            return 0;
        }
        $select = $this->conn->select()
            ->from(['ticket' => 'ticket_event_ticket'], [])
            ->columns(['cnt' => 'count(*)'])
            ->where('event_id = '.$eventId);
        return $this->conn->fetchOne($select);
    }
    
}