<?php

namespace Branch8\Customer\Plugin;

use Magento\Customer\Api\Data\GroupInterface;
use Magento\Customer\Api\GroupRepositoryInterface;
use Magento\Customer\Model\ResourceModel\GroupExcludedWebsiteRepository;
use Magento\Framework\App\ResourceConnection;
class SaveGroupOtherFields{
    /**
     * @var ResourceConnection
     */
    private $resourceConnection;

    /**
     * @param ResourceConnection $resourceConnection
     */
    public function __construct(
        ResourceConnection $resourceConnection
    ) {
        $this->resourceConnection = $resourceConnection;
    }

    /**
     * @param GroupRepositoryInterface $subject
     * @param GroupInterface $result
     * @param GroupInterface $group
     * @return GroupInterface
     */
    public function afterSave(
        GroupRepositoryInterface $subject,
        GroupInterface $result,
        GroupInterface $group
    ){
        $extAttr = $result->getExtensionAttributes();
        $sql = 'update customer_group set organization="'.$extAttr->getOrganization().'", fullname="'.$extAttr->getFullname().'", 
                prev_level='.((string)$extAttr->getPrevLevel() != '' ? $extAttr->getPrevLevel() : 'NULL').', 
                nxt_level='.((string)$extAttr->getNxtLevel() != '' ? $extAttr->getNxtLevel() : 'NULL').', period='.$extAttr->getPeriod().', 
                conditions="'.addslashes($extAttr->getConditions()).'", label="'.$extAttr->getLabel().'"';

        if((string)$extAttr->getIcon() != ''){
            $sql  .= ', icon="'.\Branch8\Customer\Helper\Group::ICON_FOLDER.$extAttr->getIcon().'" ';
        }

        $sql .= ' where customer_group_id = '.$result->getId();
        $connection = $this->resourceConnection->getConnection();
        $connection->query($sql);

        return $result;
    }
}