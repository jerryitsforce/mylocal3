<?php

namespace Branch8\EventTicket\Controller\Adminhtml\Ticket;

use Magento\Backend\App\Action;
use Magento\Framework\Controller\Result\JsonFactory;

class Import extends Action{
    /**
     * @var JsonFactory
     */
    protected $resultJsonFactory;
    /**
     * @var \Branch8\EventTicket\Model\Import\SerialFromFile
     */
    protected $serialFromFile;
    /**
     * @var \Branch8\EventTicket\Helper\Data
     */
    protected $eventTicketHelper;
    /**
     * @var \Magento\Framework\Stdlib\DateTime\TimezoneInterface
     */
    protected $timezone;

    /**
     * @param Action\Context $context
     * @param JsonFactory $resultJsonFactory
     * @param \Branch8\EventTicket\Model\Import\SerialFromFile $serialFromFile
     * @param \Branch8\EventTicket\Helper\Data $eventTicketHelper
     * @param \Magento\Framework\Stdlib\DateTime\TimezoneInterface $timezone
     */
    public function __construct(
        \Magento\Backend\App\Action\Context $context,
        JsonFactory $resultJsonFactory,
        \Branch8\EventTicket\Model\Import\SerialFromFile $serialFromFile,
        \Branch8\EventTicket\Helper\Data $eventTicketHelper,
        \Magento\Framework\Stdlib\DateTime\TimezoneInterface $timezone
    ){
        parent::__construct($context);
        $this->resultJsonFactory = $resultJsonFactory;
        $this->serialFromFile = $serialFromFile;
        $this->eventTicketHelper = $eventTicketHelper;
        $this->timezone = $timezone;
    }

    /**
     * @return \Magento\Framework\App\ResponseInterface|\Magento\Framework\Controller\Result\Json|\Magento\Framework\Controller\ResultInterface
     */
    public function execute()
    {
        $resultJson = $this->resultJsonFactory->create();
        $request = $this->getRequest();
        if(!$request->isPost()){
            return $resultJson->setData(['error' => true, 'msg' => __('Something went wrong')]);
        }
        if(!$request->isXmlHttpRequest()){
            return $resultJson->setData(['error' => true, 'msg' => __('Something went wrong')]);
        }
        $data = $this->getRequest()->getPostValue();
        if(trim($data['start_date']) == ''){
            return $resultJson->setData(['error' => true, 'msg' => __('Start date is required.')]);
        }
        if(trim($data['end_date']) == ''){
            return $resultJson->setData(['error' => true, 'msg' => __('End date is required.')]);
        }
        if($data['start_date'] != '' && $data['end_date'] != ''
            && strtotime($data['start_date'] > strtotime($data['end_date']))
        ){
            return $resultJson->setData(['error' => true, 'msg' => __('End date should be greater than Start date.')]);
        }
        if($data['end_date'] != '' && $this->timezone->date($data['end_date']) < $this->timezone->date()){
            return $resultJson->setData(['error' => true, 'msg' => __('End date should be in the future.')]);
        }
        if(!isset($_FILES['import_serial_file'])){
            return $resultJson->setData(['error' => true, 'msg' => __('Import File does not exist')]);
        }
        try {
            $dataSerial = $this->serialFromFile->execute();
            if(count($dataSerial) < 2){
                return $resultJson->setData(['error' => true, 'msg' => __('No records found')]);
            }
            unset($dataSerial[0]);
        }catch (\Exception $e){
            return $resultJson->setData(['error' => true, 'msg' => __('Can\'t process import file')]);
        }
        /**
         * Validate serial data
         */
        $postData = $request->getParams();
        $validateData = $this->eventTicketHelper->validateImportSerial($postData['event_id'], $dataSerial);
        if($validateData['error']){
            return $resultJson->setData(['error' => true, 'msg' => $validateData['msg']]);
        }
        /**
         * Validate batch code
         */
        if(!$this->eventTicketHelper->validateBatchCode($postData['batch_code'], $postData['event_id'])){
            return $resultJson->setData(['error' => true, 'msg' => __('Batch code existed')]);
        }
        $batchStartDate = trim((string)$postData['start_date']) == '' ? NULL:$this->timezone->convertConfigTimeToUtc(trim((string)$postData['start_date']));
        $batchEndDate = trim((string)$postData['end_date']) == '' ? NULL:$this->timezone->convertConfigTimeToUtc(trim((string)$postData['end_date']));
        $importData = [
            'batch_code' => $postData['batch_code'],
            'event_id' => $postData['event_id'],
            'start_date' => $batchStartDate,
            'end_date' => $batchEndDate
        ];
        /**
         * Remove header
         */

        $resultImport = $this->eventTicketHelper->importSerial($importData, $dataSerial);
        if($resultImport){
            return $resultJson->setData(['error' => false, 'msg' => __('Import Successfully')]);
        }
        return $resultJson->setData(['error' => true, 'msg' => __('Import Fail')]);
    }

    public function _isAllowed()
    {
        return $this->_authorization->isAllowed('Branch8_EventTicket::event_ticket');
    }

}