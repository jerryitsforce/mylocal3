<?php

namespace Branch8\EventTicket\Controller\Adminhtml\Ticket;

use Magento\Backend\App\Action;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Setup\Exception;

class ImportMapping extends Action{
    /**
     * @var JsonFactory
     */
    protected $resultJsonFactory;
    /**
     * @var \Branch8\EventTicket\Helper\Data
     */
    protected $eventTicketHelper;
    /**
     * @var \Magento\Framework\Stdlib\DateTime\TimezoneInterface
     */
    protected $timezone;
    /**
     * @var \Branch8\EventTicket\Model\Import\MappingFromFile
     */
    protected $mappinglFromFile;

    /**
     * @param Action\Context $context
     * @param JsonFactory $resultJsonFactory
     * @param \Branch8\EventTicket\Helper\Data $eventTicketHelper
     * @param \Magento\Framework\Stdlib\DateTime\TimezoneInterface $timezone
     * @param \Branch8\EventTicket\Model\Import\MappingFromFile $mappinglFromFile
     */
    public function __construct(
        \Magento\Backend\App\Action\Context $context,
        JsonFactory $resultJsonFactory,
        \Branch8\EventTicket\Helper\Data $eventTicketHelper,
        \Magento\Framework\Stdlib\DateTime\TimezoneInterface $timezone,
        \Branch8\EventTicket\Model\Import\MappingFromFile $mappinglFromFile
    ){
        parent::__construct($context);
        $this->resultJsonFactory = $resultJsonFactory;
        $this->eventTicketHelper = $eventTicketHelper;
        $this->timezone = $timezone;
        $this->mappinglFromFile = $mappinglFromFile;
    }

    /**
     * @return \Magento\Framework\App\ResponseInterface|\Magento\Framework\Controller\Result\Json|\Magento\Framework\Controller\ResultInterface
     */
    public function execute(){
        $resultJson = $this->resultJsonFactory->create();

        $request = $this->getRequest();
        if(!$request->isPost()){
            return $resultJson->setData(['error' => true, 'msg' => __('Something went wrong')]);
        }
        if(!$request->isXmlHttpRequest()){
            return $resultJson->setData(['error' => true, 'msg' => __('Something went wrong')]);
        }
        if(!isset($_FILES['import_mapping_file'])){
            return $resultJson->setData(['error' => true, 'msg' => __('Import File does not exist')]);
        }
        try {
            $dataMapping = $this->mappinglFromFile->execute();
            if(count($dataMapping) < 2){
                return $resultJson->setData(['error' => true, 'msg' => __('No records found')]);
            }
            /**
             * Remove header
             */
            unset($dataMapping[0]);
        }catch (\Exception $e){
            return $resultJson->setData(['error' => true, 'msg' => __('Can\'t process import file')]);
        }
        /**
         * Validate mapping data
         */
        $postData = $this->getRequest()->getPost();
        $validateData = $this->eventTicketHelper->validateMappingData($postData['mapping_event_id'], $dataMapping);
        if($validateData['error']){
            return $resultJson->setData(['error' => true, 'msg' => $validateData['msg']]);
        }

        /**
         * Do import
         */
        try {
            $queueResult = $this->eventTicketHelper->importMappingQueue($postData['mapping_event_id'], $dataMapping);
        }catch (\Exception $e){
            return $resultJson->setData(['error' => true, 'msg' => __('Import Fail')]);
        }
        return $resultJson->setData([
            'error' => false,
            'data' => [
                'mappingSubRequestUrl' => $this->_backendUrl->getUrl('eventticket/ticket/importMappingSubRequest', [
                    'eventId' => $postData['mapping_event_id'],
                    'queueId' => $queueResult['queueId']
                ]),
                'totalItems' => $queueResult['cntItem']
            ]
        ]);
    }
    public function _isAllowed()
    {
        return $this->_authorization->isAllowed('Branch8_EventTicket::event_ticket');
    }
}