<?php

namespace Branch8\Customer\Helper;
use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\Stdlib\DateTime\TimezoneInterface;

class GroupHistory extends \Magento\Framework\App\Helper\AbstractHelper{
    /**
     * @var \Branch8\Customer\Model\LevelHistoryFactory
     */
    protected $groupHistoryFactory;
    /**
     * @var TimezoneInterface
     */
    protected $_timezone;

    protected $resourceConnection;

    /**
     * @param \Magento\Framework\App\Helper\Context $context
     * @param \Branch8\Customer\Model\LevelHistoryFactory $groupHistoryFactory
     * @param TimezoneInterface $_timezone
     */
    public function __construct(
        \Magento\Framework\App\Helper\Context $context,
        \Branch8\Customer\Model\LevelHistoryFactory $groupHistoryFactory,
        TimezoneInterface $_timezone,
        ResourceConnection $resourceConnection
    ){
        parent::__construct($context);
        $this->groupHistoryFactory = $groupHistoryFactory;
        $this->_timezone = $_timezone;
        $this->resourceConnection = $resourceConnection;
    }

    /**
     * @paramstomer$cuId
     * @param $currentGroup
     * @param $newGroup
     * @param $details
     * @return void
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function addGroupHistory($customerId, $currentGroup, $newGroup, $details, $connection = null){
        if($connection == null){
            $connection = $this->resourceConnection->getConnection();
        }

        $createdAt = $this->_timezone->convertConfigTimeToUtc($this->_timezone->date(), 'Y-m-d H:i:s');
        $sql = 'insert into branch8_level_historys values(NULL, '.$customerId.', '.$currentGroup.', '.$newGroup.', "'.addslashes($createdAt).'", "'.addslashes($details).'");';
        $connection = $this->resourceConnection->getConnection();
        $connection->query($sql);
    }
}
