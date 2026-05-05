<?php
namespace Branch8\RewardSystem\Controller\Adminhtml\Reward;

use Magento\Framework\Controller\Result\JsonFactory;

class LoadBatchCode extends \Magento\Backend\App\Action
{
    const ADMIN_RESOURCE = 'Branch8_RewardSystem::loadbatchcode';

    private $resultJsonFactory;

    /**
     * @var \Magento\Framework\View\Result\PageFactory
     */
    protected $_pageFactory;

    protected $ticketEventFactory;

    protected $ticketEventTicketCollectionFactory;

    protected $eventFactory;

    protected $timezone;
    
    public function __construct(
       \Magento\Backend\App\Action\Context $context,
       JsonFactory $resultJsonFactory,
       \Branch8\EventTicket\Model\TicketEventFactory $ticketEventFactory,
       \Branch8\EventTicket\Model\ResourceModel\TicketEventTicket\CollectionFactory $ticketEventTicketCollectionFactory,
       \Branch8\RewardSystem\Model\EventFactory $eventFactory,
       \Magento\Framework\Stdlib\DateTime\TimezoneInterface $timezone
    )
    {
        $this->resultJsonFactory = $resultJsonFactory;
        parent::__construct($context);
        $this->ticketEventFactory = $ticketEventFactory;
        $this->ticketEventTicketCollectionFactory = $ticketEventTicketCollectionFactory;
        $this->eventFactory = $eventFactory;
        $this->timezone = $timezone;
    }

    /**
     * Index action
     *
     * @return \Magento\Framework\View\Result\Page
     */
    public function execute()
    {
        $poolId = $this->getRequest()->getParam('id');
        $event = $this->ticketEventFactory->create()->load($poolId);

        $resultJson = $this->resultJsonFactory->create();

        if(!$event->getId() || !$event->getIsEnable()){
            return $resultJson->setData(['data' => []]);
        }
        $conn = $this->ticketEventTicketCollectionFactory->create()->getConnection();
        $sqlBatchCode = 'select distinct batch_code, ADDTIME(end_date, "08:00:00") as ed_date from ticket_event_ticket where (`event_id` = "'.$poolId.'")';
        $queryBatchCode = $conn->query($sqlBatchCode);
        /** Load event */
        $eid = $this->getRequest()->getParam('eid');
        $event = $this->eventFactory->create()->load((int)$eid);
        $batchCode = '';
        if($event->getId()){
            $batchCode = $event->getData('reward_pool_batch_code');
        }
        $batchCodes = [];
        $currentTime = $this->timezone->date()->format('Y-m-d H:i:s');
        while($_row = $queryBatchCode->fetch()){
            $rowBatchCode = $_row['batch_code'];
            $isExpired = 0;
            if(strtotime($currentTime) > strtotime($_row['ed_date'])){
                $rowBatchCode .= ' - '.__('Batch code was expired at %1', $_row['ed_date']);
                $isExpired = 1;
            }
            if($batchCode == $_row['batch_code']){
                $batchCodes[] = ['selected' => 1, 'disabled' => 0, 'code' => $_row['batch_code'], 'label' => $rowBatchCode];
            }else{
                $batchCodes[] = ['selected' => 0, 'disabled' => $isExpired, 'code' => $_row['batch_code'], 'label' => $rowBatchCode];
            }
        }
        
        return $resultJson->setData(['data' => $batchCodes]);
    }

    /**
     * Is the user allowed to view the page.
    *
    * @return bool
    */
    protected function _isAllowed()
    {
        return $this->_authorization->isAllowed(static::ADMIN_RESOURCE);
    }
}
