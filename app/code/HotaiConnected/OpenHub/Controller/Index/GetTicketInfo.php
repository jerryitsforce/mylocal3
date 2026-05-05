<?php

namespace HotaiConnected\OpenHub\Controller\Index;

use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Framework\Controller\Result\JsonFactory;
use HotaiConnected\OpenHub\Model\ResourceModel\OpenHubTicketRecord\CollectionFactory as OpenHubCollectionFactory;
use HotaiConnected\OpenHub\Model\OpenHubTicketRecord;
use Branch8\HotaiCore\Model\Ticket\Status as TicketStatus;

class GetTicketInfo extends Action
{
    /** @var JsonFactory */
    protected $jsonFactory;

    /** @var OpenHubCollectionFactory */
    protected $openHubCollectionFactory;

    public function __construct(
        Context $context,
        JsonFactory $jsonFactory,
        OpenHubCollectionFactory $openHubCollectionFactory
    ) {
        $this->jsonFactory = $jsonFactory;
        $this->openHubCollectionFactory = $openHubCollectionFactory;
        parent::__construct($context);
    }

    public function execute()
    {
        $result = $this->jsonFactory->create();
        
        try {
            $serialNumber = $this->getRequest()->getParam('serial_number');
            
            if (empty($serialNumber)) {
                return $result->setData([
                    'success' => false,
                    'message' => 'Serial number is required'
                ]);
            }

            $collection = $this->openHubCollectionFactory->create();
            $collection->addFieldToFilter(OpenHubTicketRecord::SERIAL_NUMBER, $serialNumber);
            $collection->addFieldToFilter(OpenHubTicketRecord::STATUS, TicketStatus::STATUS_UNUSED);

            if ($collection->getSize() === 0) {
                return $result->setData([
                    'success' => false,
                    'message' => 'Ticket not found or already used'
                ]);
            }

            /** @var OpenHubTicketRecord $ticket */
            $ticket = $collection->getFirstItem();

            return $result->setData([
                'success' => true,
                'data' => [
                    'serial_number' => $ticket->getSerialNumber(),
                    'product_id' => $ticket->getProductId(),
                    'status' => $ticket->getStatus(),
                    'external_order_no' => $ticket->getExternalOrderNo(),
                    'external_transaction_no' => $ticket->getExternalTransactionNo()
                ]
            ]);

        } catch (\Exception $e) {
            return $result->setData([
                'success' => false,
                'message' => 'An error occurred: ' . $e->getMessage()
            ]);
        }
    }
}