<?php
namespace Branch8\MarketPlaceParentOrderFrontendUi\Helper;

class VirtualTicket extends \Magento\Framework\App\Helper\AbstractHelper
{
    public $_conn;

    const STATUS_NOT_VIRTUAL = '-1';

    const STATUS_PROCESSING = '0';

    const STATUS_USED = '1';

    const STATUS_UNLLIMIT = '2';

    const STATUS_EXPIRED = '3';

    const STATUS_VALID = '4';

    // const STATUS_VALID_IN_FUTURE = '5';

    protected $timezone;

    protected $itemFactory;

    public function __construct(
        \Magento\Framework\App\ResourceConnection $resourceConnection,
        \Magento\Framework\Stdlib\DateTime\TimezoneInterface $timezone,
        \Magento\Sales\Model\Order\ItemFactory $itemFactory
    ){
        $this->_conn = $resourceConnection->getConnection();
        $this->timezone = $timezone;
        $this->itemFactory = $itemFactory;
    }
    
    public function getTicketStatus($orderItemId){
        $result = [
            'success' => false
        ];
        $item = $this->itemFactory->create()->load($orderItemId);
        if(!$item->getId()){
            return $result;
        }
        if(!$item->getData('is_virtual')){
            $result['success'] = true;
            $result['status'] = self::STATUS_NOT_VIRTUAL;
            return $result;
        }

        $selectTicket = $this->_conn->select()
            ->from(['ticket' => 'customer_ticket'], ['record_id', 'use_start_time', 'use_end_time', 'status'])
            ->where('sales_order_item_id = ?', $orderItemId);
        $record = $this->_conn->fetchRow($selectTicket);
        if(!$record->getId()){
            $result['success'] = true;
            $result['status'] = self::STATUS_PROCESSING;
            return $result;
        }else if($record['status'] == \Branch8\HotaiCore\Model\Ticket\Status::STATUS_USED){
            $result['success'] = true;
            $result['status'] = self::STATUS_USED;
        }else if($record['status'] == \Branch8\HotaiCore\Model\Ticket\Status::STATUS_OVER_DUE){
            $result['success'] = true;
            $result['status'] = self::STATUS_EXPIRED;
        }else if(in_array($record['status'], [
            \Branch8\HotaiCore\Model\Ticket\Status::STATUS_ERROR,
            \Branch8\HotaiCore\Model\Ticket\Status::STATUS_RETURNED
        ])){
            $result = [
                'success' => false
            ];
            return $result;
        }else if($record['status'] == \Branch8\HotaiCore\Model\Ticket\Status::STATUS_UNUSED){

            $currentTime = $this->timezone->date()->format('Y-m-d H:i:s');
            if((string)$record['use_end_time'] == ''){
                if((string)$record['use_start_time'] == '' || strtotime($currentTime) >= strtotime((string)$record['use_start_time'])){
                    $result['success'] = true;
                    $result['status'] = self::STATUS_UNLLIMIT;
                }else{
                    /**NEED TO UPDATE NUM OF DAY WILL BE VALID */
                    $result['success'] = true;
                    $result['status'] = self::STATUS_VALID;
                    $result['days'] = $this->getRemainDay((string)$record['use_end_time']);
                }
            }else if((string)$record['use_start_time'] == ''){
                if((string)$record['use_end_time'] == ''){
                    $result['success'] = true;
                    $result['status'] = self::STATUS_UNLLIMIT;
                }else if(strtotime($currentTime) >= strtotime((string)$record['use_end_time'])){
                    $result['success'] = true;
                    $result['status'] = self::STATUS_EXPIRED;
                }else{
                    /** NEED TO UPDATE VALID DAYS */
                    $result['success'] = true;
                    $result['status'] = self::STATUS_VALID;
                    $result['days'] = $this->getRemainDay((string)$record['use_end_time']);
                }
            }else if(strtotime($currentTime) > strtotime($record['use_end_time'])){
                $result['success'] = true;
                $result['status'] = self::STATUS_EXPIRED;
            }else if(strtotime($currentTime) < strtotime($record['use_start_time'])){
                /**NEED TO UPDATE NUM OF DAY WILL BE VALID */
                $result['success'] = true;
                $result['status'] = self::STATUS_VALID;
                $result['days'] = $this->getRemainDay((string)$record['use_end_time']);
            }else{
                /** NEED TO UPDATE VALID DAYS */
                $result['success'] = true;
                $result['status'] = self::STATUS_VALID;
                $result['days'] = $this->getRemainDay((string)$record['use_end_time']);
            }   
        }else{
            $result = [
                'success' => false
            ];
        }
        return $result;
    }

    public function getRemainDay($dateString)
    {
        if(empty($dateString)) {
            return 0;
        }
        $date = $this->timezone->date($dateString)->sub(new \DateInterval('PT8H'));
        $now = $this->timezone->date();
        $days = $now->diff($date)->format('%r%a');
        return $days;
    }
}
