<?php

namespace Branch8\EventTicket\Controller\Adminhtml\Event;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\Stdlib\DateTime\TimezoneInterface;

class Save extends Action
{
    /**
     * @var \Branch8\EventTicket\Model\TicketEventFactory
     */
    protected $ticketEventFactory;
    /**
     * @var TimezoneInterface
     */
    protected $_timezone;
    /**
     * @var \Branch8\EventTicket\Model\ResourceModel\TicketEvent
     */
    protected $ticketEvent;
    /**
     * @var \Magento\Framework\Serialize\Serializer\Json
     */
    protected $serialize;
    /**
     * @var \Branch8\EventTicket\Model\ImageUploader
     */
    protected $imageUploader;
    /**
     * @var \Branch8\EventTicket\Helper\Data
     */
    protected $ticketHelper;

    /**
     * @param Context $context
     * @param \Branch8\EventTicket\Model\TicketEventFactory $ticketEventFactory
     * @param \Branch8\EventTicket\Model\ResourceModel\TicketEvent $ticketEvent
     * @param TimezoneInterface $_timezone
     * @param \Magento\Framework\Serialize\Serializer\Json $serialize
     * @param \Branch8\EventTicket\Model\ImageUploader $imageUploader
     * @param \Branch8\EventTicket\Helper\Data $ticketHelper
     */
    public function __construct(
        Context                                     $context,
        \Branch8\EventTicket\Model\TicketEventFactory $ticketEventFactory,
        \Branch8\EventTicket\Model\ResourceModel\TicketEvent $ticketEvent,
        TimezoneInterface                           $_timezone,
        \Magento\Framework\Serialize\Serializer\Json $serialize,
        \Branch8\EventTicket\Model\ImageUploader $imageUploader,
        \Branch8\EventTicket\Helper\Data $ticketHelper
    )
    {
        $this->ticketEventFactory = $ticketEventFactory;
        $this->_timezone = $_timezone;
        $this->ticketEvent = $ticketEvent;
        $this->serialize = $serialize;
        $this->imageUploader = $imageUploader;
        parent::__construct($context);
        $this->ticketHelper = $ticketHelper;
    }

    /**
     * Provides content
     *
     * @return Magento\Framework\Controller\Result\Redirect
     */
    public function execute()
    {

        $resultRedirect = $this->resultRedirectFactory->create();
        $data = $this->getRequest()->getPostValue();

        try {
            $conn = $this->ticketEvent->getConnection();
            $id = $this->getRequest()->getParam('entity_id');
            $model = $this->ticketEventFactory->create();
            $this->ticketEvent->load($model, $id);
            if($id){
                $originData = $model->getData();
            }
            /**
             * save image
             */
            if (isset($data['image_url'][0]['name']) && isset($data['image_url'][0]['tmp_name'])) {
                $this->imageUploader->moveFileFromTmp($data['image_url'][0]['name']);
            }

            /**
             * prepair data for saving Event
             */
            if (isset($data['image_url'])) {
                $data['image_url'] = $this->serialize->serialize($data['image_url']);
            }
            unset($data['form_key']);
            unset($data['entity_id']);
            if(!$id){
                $data['created_at'] = $this->_timezone->convertConfigTimeToUtc($this->_timezone->date());
            }else{
                $data['updated_at'] = $this->_timezone->convertConfigTimeToUtc($this->_timezone->date());
            }
            /**
             * Prepair data for indexing to customer_ticket
             */
            $conn->beginTransaction();
            $recordIds = '';
            if($id){
                if($this->ticketHelper->hasSerials($id)){
                    unset($data['ticket_type']);
                }
                /**
                 * get mapped serial
                 */
                $selectMapped = $conn->select()
                    ->from(['ticket' => 'ticket_event_ticket'], [])
                    ->joinLeft(['ct' => 'customer_ticket'], 'ct.ticket_unique_content = ticket.serial_number', ['record_id'])
                    ->where('ct.ticket_table_name="ticket_event_ticket" and ticket.event_id='.$id);
                $recordIds = $conn->fetchCol($selectMapped);
                $conn->update(
                    'ticket_event',
                    $data,
                    'entity_id = '.$id
                );
            }else{
                /**
                 * Add new
                 */
                $conn->insert('ticket_event', $data);
            }


            if(!empty($recordIds)) {
                $indexFields = [
                    'seller_id' => 'seller_id',
                ];
                $updateIndexData = [];
                foreach ($indexFields as $indexField => $_field){
                    if($data[$_field] != $originData[$_field]){
                        $updateIndexData[$indexField] = $data[$_field];
                    }
                }
                if(!empty($updateIndexData)) {
                    $conn->update(
                        'customer_ticket',
                        $updateIndexData,
                        'record_id in(' . implode(',', $recordIds) . ')'
                    );
                }
            }
            $conn->commit();
        } catch (\Exception $e) {
            $conn->rollBack();
            $this->messageManager->addErrorMessage(__('Something went wrong while trying to save the Event. Please try again.'));
        }
        return $resultRedirect->setPath('*/*/listEvent');
    }

    /**
     * Check Autherization
     *
     * @return boolean
     */
    public function _isAllowed()
    {
        return $this->_authorization->isAllowed('Branch8_EventTicket::event_ticket');
    }
}