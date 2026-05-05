<?php

namespace Branch8\EventTicket\Helper;

use Branch8\HotaiCore\Model\Ticket\Status as TicketStatus;
use Branch8\TicketApi\Helper\Api;
use Branch8\TicketApi\Helper\CheckHandler\Const\SerialNoStatus;
use Branch8\TicketApi\Model\Api\Data\CheckDataFactory as DataFactory;
use Branch8\TicketApi\Model\TicketApiBrand\Source\Brand;
use Branch8\Yoxi\Model\YoxiTicketRecord as Model;
use Magento\Framework\App\Helper\AbstractHelper;

class SerialHandle extends  \Branch8\TicketApi\Helper\CheckHandler\BaseHandler
{
    /**
     * @var
     */
    protected $resourceConnection;
    /**
     * @var \Magento\Framework\DB\Adapter\AdapterInterface
     */
    protected $connection;
    /**
     * @var \Magento\Framework\Stdlib\DateTime\TimezoneInterface
     */
    protected $timezone;

    /**
     * @param DataFactory $dataFactory
     * @param \Magento\Framework\App\ResourceConnection $resourceConnection
     * @param \Magento\Framework\Stdlib\DateTime\TimezoneInterface $timezone
     */
    public function __construct(
        DataFactory $dataFactory,
        \Magento\Framework\App\ResourceConnection $resourceConnection,
        \Magento\Framework\Stdlib\DateTime\TimezoneInterface $timezone
    ){
        parent::__construct($dataFactory);
        $this->connection = $resourceConnection->getConnection();
        $this->timezone = $timezone;
    }

    /**
     * @param $data
     * @param $merchant
     * @return void
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function checkSerial($data, $merchant)
    {
        $ticketType = $data['brand'];
        if(!in_array($ticketType, [Brand::BRAND_CODE_YOXI, Brand::BRAND_CODE_EDENRED, Brand::BRAND_CODE_FAMILY_BONUS_PIN,
            Brand::BRAND_CODE_GENERAL_NOTIFY, Brand::BRAND_CODE_GENERAL_NON_NOTIFY])){
            throw new \Exception("Brand code not valid.");
            return;
        }

        $serialNumber = $data['serialNo'];

        if($merchant == null){
            $this->setNotExist();
            return;
        }
        $sellerIdFromMerchant = $merchant->getSellerIds();
        $ticketSelect = $this->connection->select()
            ->from(['ticket' => 'ticket_event_ticket'], '*')
            ->joinLeft(['ev' => 'ticket_event'], 'ev.entity_id = ticket.event_id', ['ticket_type', 'amount'])
            ->where('serial_number="'.$serialNumber.'" and ticket_type="'.$ticketType.'" and ev.seller_id in('.$sellerIdFromMerchant.') and ev.is_enable=1');
        $ticketRecord = $this->connection->fetchRow($ticketSelect);
        /*
         * If ticket not exist.
         */
        if (!$ticketRecord){
            $this->setNotExist();
            return;
        }
        // If ticket not mapped yet.
        if (!$this->checkTicketMapped($ticketRecord)) {
            $this->setNotMapping();
            return;
        }
        /**
         * Used?
         */
        if ($this->checkTicketAlreadyUsed($ticketRecord)) {
            $this->setAlreadyUsed($ticketRecord);
            return;
        }
        /**
         * Before start time
         */
        if ($this->checkIfTicketUnableToUseYet($ticketRecord)) {
            $this->setUnableToUseYet($ticketRecord);
            return;
        }
        // If ticket is over due.
        if ($this->checkIfTicketOverDue($ticketRecord)) {
            $this->setOverDue($ticketRecord);
            return;
        }

        /**
         * Available to use
         * Return data to 3rd
         */
        $this->setAvailable($ticketRecord);
        return;
    }

    /**
     * @param $data
     * @param $merchant
     * @return void
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function useSerial($data, $merchant){
        $ticketType = $data['brand'];
        if(!in_array($ticketType, [Brand::BRAND_CODE_YOXI, Brand::BRAND_CODE_FAMILY_BONUS_PIN, Brand::BRAND_CODE_GENERAL_NOTIFY])){
            throw new \Exception("Brand code not valid.");
            return;
        }

        $serialNumber = $data['serialNo'];


        if($merchant == null){
            $this->setNotExist();
            return;
        }
        $sellerIdFromMerchant = $merchant->getSellerIds();

        $ticketSelect = $this->connection->select()
            ->from(['ticket' => 'ticket_event_ticket'], '*')
            ->joinLeft(['ev' => 'ticket_event'], 'ev.entity_id = ticket.event_id', ['ticket_type', 'amount'])
            ->where('serial_number="'.$serialNumber.'" and ticket_type="'.$ticketType.'" and seller_id in('.$sellerIdFromMerchant.') and ev.is_enable=1');
        $ticketRecord = $this->connection->fetchRow($ticketSelect);
        /*
         * If ticket not exist.
         */
        if (!$ticketRecord){
            $this->setNotExist();
            return;
        }
        // If ticket not map yet.
        if (!$this->checkTicketMapped($ticketRecord)) {
            $this->setNotMapping();
            return;
        }
        /**
         * Used?
         */
        if ($this->checkTicketAlreadyUsed($ticketRecord)) {
            $this->setAlreadyUsed($ticketRecord);
            return;
        }
        /**
         * Before start time
         */
        if ($this->checkIfTicketUnableToUseYet($ticketRecord)) {
            $this->setUnableToUseYet($ticketRecord);
            return;
        }
        // If ticket is over due.
        if ($this->checkIfTicketOverDue($ticketRecord)) {
            $this->setOverDue($ticketRecord);
            return;
        }
        /**
         * Use serial
         */
        $this->setUsedSerial($data, $ticketRecord);
        return;
    }

    /**
     * @param $data
     * @param $merchant
     * @return void
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function cancelSerial($data, $merchant){
        $ticketType = $data['brand'];
        if(!in_array($ticketType, [Brand::BRAND_CODE_YOXI, Brand::BRAND_CODE_GENERAL_NOTIFY, Brand::BRAND_CODE_FAMILY_BONUS_PIN])){
            throw new \Exception("Brand code not valid.");
            return;
        }

        $serialNumber = $data['serialNo'];

        if($merchant == null){
            $this->setNotExist();
            return;
        }
        $sellerIdFromMerchant = $merchant->getSellerIds();

        $ticketSelect = $this->connection->select()
            ->from(['ticket' => 'ticket_event_ticket'], '*')
            ->joinLeft(['ev' => 'ticket_event'], 'ev.entity_id = ticket.event_id', ['ticket_type', 'amount'])
            ->where('serial_number="'.$serialNumber.'" and seller_id in('.$sellerIdFromMerchant.') and ticket_type="'.$ticketType.'"');
        $ticketRecord = $this->connection->fetchRow($ticketSelect);
        /*
         * If ticket not exist.
         */
        if (!$ticketRecord){
            $this->setNotExist();
            return;
        }
        // If ticket not mapped yet or used.
        if (!$this->checkTicketMapped($ticketRecord) && !$this->checkTicketAlreadyUsed($ticketRecord)) {
            $this->setNotMapping();
            return;
        }

        if(!$this->checkTicketCanBeReturnedToUnused($ticketRecord['status'])){
            $this->setCanNotCancelByUnused($ticketRecord, $data);
            return;
        }

        if(!$this->checkUsedTransactionNoStoredInRecord($ticketRecord, $data['usedTransactionNo'])){
            $this->setNotHaveTransactionNo($ticketRecord, $data['usedTransactionNo']);
            return;
        }

        /**
         * Set data for cancel
         */
        $this->setCancelSerial($ticketRecord, $data);
        return;
    }

    /**
     * @param $record
     * @return bool
     */
    private function checkTicketMapped($record){
        if($record['status'] == TicketStatus::STATUS_UNUSED){
            return true;
        }
        return false;
    }

    /**
     * @param $record
     * @return bool
     */
    private function checkTicketAlreadyUsed($record)
    {
        return $record['status'] == TicketStatus::STATUS_USED ? true : false;
    }

    /**
     * @param $record
     * @return bool
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    private function checkIfTicketOverDue($record){
        $endDate = $record['end_date'];
        if((string)$endDate == ''){
            return false;
        }
        $currentTime = $this->timezone->convertConfigTimeToUtc($this->timezone->date());
        if(in_array($record['status'], [TicketStatus::STATUS_UNUSED]) && strtotime($currentTime) > strtotime($endDate)){
            return true;
        }
        return false;
    }

    /**
     * @param $record
     * @return bool
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    private function checkIfTicketUnableToUseYet($record)
    {
        $startDate = $record['start_date'];
        if((string)$startDate == ''){
            return false;
        }
        $currentTime = $this->timezone->convertConfigTimeToUtc($this->timezone->date());
        $startDateTW = $this->timezone->convertConfigTimeToUtc($this->timezone->date($startDate));
        if(TicketStatus::STATUS_UNUSED == $record['status'] && strtotime($currentTime) < strtotime($startDateTW)){
            return true;
        }
        return false;
    }

    /**
     * @return void
     */
    private function setNotExist()
    {
        $this->returnCode = Api::RETURN_CODE_NOT_EXIST;
        $this->returnMsg  = Api::RETURN_MESSAGE_NOT_EXIST;

        $this->returnData->setIsUsable(false);
        $this->returnData->setSerialNoStatus(SerialNoStatus::NOT_EXIST);
        $this->returnData->setProductNo("");
        $this->returnData->setProductNameM("");
        $this->returnData->setProductNameS("");
        $this->returnData->setExpiry("");
    }

    /**
     * @return void
     */
    private function setNotMapping(){
        $this->returnCode = Api::RETURN_CODE_UNABLE_TO_USE_YET;
        $this->returnMsg  = Api::RETURN_MESSAGE_UNABLE_TO_USE_YET;

        $this->returnData->setIsUsable(false);
        $this->returnData->setSerialNoStatus(SerialNoStatus::UNSOLD);
        $this->returnData->setProductNo("");
        $this->returnData->setProductNameM("");
        $this->returnData->setProductNameS("");
        $this->returnData->setExpiry("");
    }

    /**
     * @param $record
     * @return void
     */
    private function setAlreadyUsed($record){
        $this->returnCode = Api::RETURN_CODE_USED_ALREADY;
        $this->returnMsg  = Api::RETURN_MESSAGE_USED_ALREADY;

        $this->returnData->setIsUsable(false);
        $this->returnData->setSerialNoStatus(SerialNoStatus::USED);
        $this->returnData->setProductNo($record['batch_code']);
        $this->returnData->setProductNameM();
        $this->returnData->setProductNameS();
        $this->returnData->setExpiry($this->timezone->date($record['end_date'])->format('Ymd'));
    }

    /**
     * @param $record
     * @return void
     */
    private function setUnableToUseYet($record){
        $this->returnCode = Api::RETURN_CODE_UNABLE_TO_USE_YET;
        $this->returnMsg  = Api::RETURN_MESSAGE_UNABLE_TO_USE_YET;

        $this->returnData->setIsUsable(false);
        $this->returnData->setSerialNoStatus(SerialNoStatus::DATE_INVALID);
        $this->returnData->setProductNo($record['batch_code']);
        $this->returnData->setProductNameM('');
        $this->returnData->setProductNameS('');
        $this->returnData->setExpiry($this->timezone->date($record['end_date'])->format('Ymd'));
    }

    /**
     * @param $record
     * @return void
     */
    private function setOverDue($record){
        $this->returnCode = Api::RETURN_CODE_OVER_DUE;
        $this->returnMsg  = Api::RETURN_MESSAGE_OVER_DUE;

        $this->returnData->setIsUsable(false);
        $this->returnData->setSerialNoStatus(SerialNoStatus::DATE_INVALID);
        $this->returnData->setProductNo($record['batch_code']);
        $this->returnData->setProductNameM('');
        $this->returnData->setProductNameS('');
        $this->returnData->setExpiry($this->timezone->date($record['end_date'])->format('Ymd'));
    }

    /**
     * @param $record
     * @param $transactionNo
     * @return bool
     */
    private function checkUsedTransactionNoStoredInRecord($record, $transactionNo)
    {
        return $transactionNo == $record['used_transaction_no'];
    }

    /**
     * @param $status
     * @return bool
     */
    private function checkTicketCanBeReturnedToUnused($status)
    {
        return $status == TicketStatus::STATUS_USED;
    }

    /**
     * @param $record
     * @param $requestTransNo
     * @return void
     */
    private function setNotHaveTransactionNo($record, $requestTransNo)
    {
        $this->returnCode = Api::RETURN_CODE_UNABLE_TO_USE_YET;
        $this->returnMsg  = Api::RETURN_MESSAGE_UNABLE_TO_USE_YET;
        $this->returnData->setUseStatus($this->getUseStatusByTicketRecord($record['status']));
        $this->returnData->setUsedTransactionNo($requestTransNo);
        $this->returnData->setSerialNo($record['serial_number']);
        $this->returnData->setProductNo("");
        $this->returnData->setProductNameM("");
        $this->returnData->setProductNameS("");
    }

    /**
     * @param $status
     * @return string
     */
    protected function getUseStatusByTicketRecord($status): string
    {

        switch ($status) {
            case TicketStatus::STATUS_UNUSED:
                return Api::RETURN_DATA_USE_STATUS_TRUE;

            default:
                return Api::RETURN_DATA_USE_STATUS_FALSE;
        }
    }

    /**
     * @param $record
     * @return void
     */
    private function setAvailable($record)
    {
        $this->returnCode = Api::RETURN_CODE_SUCCESS;
        $this->returnMsg  = Api::RETURN_MESSAGE_SUCCESS;

        $this->returnData->setIsUsable(true);
        $this->returnData->setSerialNoStatus(SerialNoStatus::USEABLE);
        $this->returnData->setProductNo($record['batch_code']);
        $this->returnData->setProductNameM('');
        $this->returnData->setProductNameS('');
        $this->returnData->setExpiry($this->timezone->date($record['end_date'])->format('Ymd'));
        $this->returnData->setSellPrice($record['amount']);
        $this->returnData->setSellPoint(0);
        $this->returnData->setTotalSellAmount($record['amount']);
        $this->returnData->setProductSellPrice($record['amount']);
        $this->returnData->setOrderAmount($record['amount']);
    }

    /**
     * @param $requestData
     * @param $record
     * @return void
     * @throws \Exception
     */
    private function setUsedSerial($requestData, $record){
        try{
            /**
             * Update serial status to used
             * Update transaction no
             * Update store no
             * Update redeemed at
             */
            $this->connection->beginTransaction();
            $usedDate = $this->timezone->date();
            $customerTicketUsedDate = $usedDate->format('Y-m-d H:i:s');
            $redeemed_at = $this->timezone->convertConfigTimeToUtc($usedDate);
            $updateQuery = [
                'redeemed_at' => $redeemed_at,
                'status' => TicketStatus::STATUS_USED,
                'used_transaction_no' => $requestData['usedTransactionNo'],
                'used_store_no' => $requestData['usedStoreNo']
            ];
            $this->connection->update('ticket_event_ticket', $updateQuery, 'entity_id='.$record['entity_id']);

            /**
             * Update to customer ticket as index field
             * redeemed_at in customer_ticket have to TW time, not UTC time(this is requirement, to match product ticket)
             */

            $this->connection->update(
                'customer_ticket',
                [
                    'redeemed_at' => $customerTicketUsedDate,
                    'status' => TicketStatus::STATUS_USED,
                    'updated_at' => $this->timezone->convertConfigTimeToUtc($this->timezone->date())
                ],
                'ticket_table_name = "ticket_event_ticket" and ticket_table_record_id='.$record['entity_id']
            );

            $this->connection->commit();
        }catch (\Exception $e){
            $this->connection->rollBack();
            throw new \Exception(__("Can't use this serial. Please try again or call support.")->render());
        }

        $this->returnCode = Api::RETURN_CODE_SUCCESS;
        $this->returnMsg  = Api::RETURN_MESSAGE_SUCCESS;

        $this->returnCode     = Api::RETURN_CODE_SUCCESS;
        $this->returnMsg      = Api::RETURN_MESSAGE_SUCCESS;
        $this->returnData->setUseStatus(Api::RETURN_DATA_USE_STATUS_TRUE);
        $this->returnData->setUsedTransactionNo($requestData['usedTransactionNo']);
        $this->returnData->setSerialNo($record['serial_number']);
        $this->returnData->setProductNo($record['batch_code']);
        $this->returnData->setProductNameM('');
        $this->returnData->setProductNameS("");
        $this->returnData->setSellPrice((int) $record['amount']);
        $this->returnData->setSellPoint((int) $record['amount']);
        $this->returnData->setTotalSellAmount($record['amount']);
        $this->returnData->setProductSellPrice($record['amount']);
        $this->returnData->setOrderAmount(1);
    }

    /**
     * @param $record
     * @param $requestData
     * @return void
     */
    private function setCanNotCancelByUnused($record, $requestData)
    {
        $this->returnCode = Api::RETURN_CODE_USED_ALREADY;
        $this->returnMsg  = Api::RETURN_MESSAGE_USED_ALREADY;
        $this->returnData->setUseStatus($this->getUseStatusByTicketRecord($record['status']));
        $this->returnData->setUsedTransactionNo($requestData['usedTransactionNo']);
        $this->returnData->setSerialNo($record['serial_number']);
        $this->returnData->setProductNo($record['batch_code']);
        $this->returnData->setProductNameM('');
        $this->returnData->setProductNameS("");

        $this->returnData->setSellPrice($record['amount']);
        $this->returnData->setSellPoint(0);
        $this->returnData->setTotalSellAmount($record['amount']);
        $this->returnData->setProductSellPrice($record['amount']);
        $this->returnData->setOrderAmount(1);
    }

    /**
     * @param $record
     * @param $requestData
     * @return void
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    private function setCancelSerial($record, $requestData){
        try {
            $this->connection->beginTransaction();
            $updateQuery = [
                'redeemed_at' => NULL,
                'status' => TicketStatus::STATUS_UNUSED,
                'used_transaction_no' => NULL,
                'used_store_no' => NULL
            ];
            $this->connection->update('ticket_event_ticket', $updateQuery, 'entity_id=' . $record['entity_id']);

            $this->connection->update(
                'customer_ticket',
                [
                    'redeemed_at' => NULL,
                    'status' => TicketStatus::STATUS_UNUSED,
                    'updated_at' => $this->timezone->convertConfigTimeToUtc($this->timezone->date())
                ],
                'ticket_table_name = "ticket_event_ticket" and ticket_table_record_id='.$record['entity_id']
            );
            $this->connection->commit();
            $this->returnCode = Api::RETURN_CODE_SUCCESS;
            $this->returnMsg  = Api::RETURN_MESSAGE_SUCCESS;
        }catch (\Exception $e){
            $this->connection->rollBack();
            $this->returnCode = Api::RETURN_CODE_DEFAULT_FAIL;
            $this->returnMsg  = Api::RETURN_MESSAGE_UNEXPECTED;
        }


        /**
         * Check status of serial after cancel
         * That this serial can use or not
         */
        $currentTime = $this->timezone->convertConfigTimeToUtc($this->timezone->date());
        $selectUseStatus = $this->connection->select()
            ->from(['ticket' => 'ticket_event_ticket'], ['entity_id'])
            ->joinLeft(['ev' => 'ticket_event'], 'ev.entity_id = ticket.event_id', [])
            ->where('serial_number="'.$record['serial_number'].'" and ticket_type="'.$record['ticket_type'].'" and ev.is_enable=1 and ticket.start_date <="'.$currentTime.'" and ticket.end_date >="'.$currentTime.'"');
        if($this->connection->fetchOne($selectUseStatus)){
            $useStatus = Api::RETURN_DATA_USE_STATUS_TRUE;
        }else{
            $useStatus = Api::RETURN_DATA_USE_STATUS_FALSE;
        }
        $this->returnData->setUseStatus($useStatus);
        $this->returnData->setUsedTransactionNo($requestData['usedTransactionNo']);
        $this->returnData->setSerialNo($record['serial_number']);
        $this->returnData->setProductNo('');
        $this->returnData->setProductNameM('');
        $this->returnData->setProductNameS("");

        $this->returnData->setSellPrice($record['amount']);
        $this->returnData->setSellPoint(0);
        $this->returnData->setTotalSellAmount($record['amount']);
        $this->returnData->setProductSellPrice($record['amount']);
        $this->returnData->setOrderAmount(1);
    }
}