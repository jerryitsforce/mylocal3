<?php

namespace Branch8\Customer\Helper;

use Magento\Framework\Exception\LocalizedException;

class Organization extends \Magento\Framework\App\Helper\AbstractHelper{
    /**
     * @var \Psr\Log\LoggerInterface
     */
    protected $logger;

    protected $_resourceConnection;

    /**
     * @param \Magento\Framework\App\Helper\Context $context
     * @param \Psr\Log\LoggerInterface $logger
     */
    public function __construct(
        \Magento\Framework\App\Helper\Context $context,
        \Magento\Framework\App\ResourceConnection $resourceConnection,
        \Psr\Log\LoggerInterface $logger
    ){
        $this->logger = $logger;
        parent::__construct($context);
        $this->_resourceConnection = $resourceConnection;
    }
    /**
     * @param array $data
     * @return void
     */
    public function createOrganization(array $data){
        $organizations = [];
        if(isset($data['membership'])){
            $organizations = $data['membership'];
        }
        try {
            foreach ($organizations as $_organization) {
                $_organization = trim($_organization);
                $orgCol = $this->organizationCollectionFactory->create()
                    ->addFieldToFilter('name', $_organization);
                if($orgCol->getSize()){
                    continue;
                }
                $newOrganization = $this->organizationFactory->create();
                $newOrganization->setName($_organization)
                    ->setCreatedAt($this->_timezoneInterface->convertConfigTimeToUtc($this->_timezoneInterface->date()))
                    ->setSortOrder(1000)
                    ->setHotaiAuthId($_organization)
                    ->save();
            }
        }catch (LocalizedException $e){
            if(\Branch8\HotaiCore\Helper\DebugLog::isEnable('Branch8_Customer', 'exceptionlog')){
                $this->logger->critical($e->getMessage());
            }
        }
    }

    public function updateGroupFullname($org_id, $new_name){
        $sqlGroup = 'select * from customer_group where organization='.$org_id;
        $conn = $this->_resourceConnection->getConnection();
        $result = $conn->query($sqlGroup);
        while($row = $result->fetch()){
            $newFullname = $new_name.' - '.$row['customer_group_code'];
            $sqlUpdate = 'update customer_group set fullname="'.$newFullname.'" where customer_group_id='.$row['customer_group_id'];
            $conn->query($sqlUpdate);
        }
    }
}