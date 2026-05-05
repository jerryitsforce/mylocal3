<?php

namespace Branch8\MarketPlaceSeller\Setup\Patch\Data;

use Magento\Customer\Api\GroupRepositoryInterface;
use Magento\Customer\Api\Data\GroupInterfaceFactory;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\Setup\Patch\DataPatchInterface;
use Magento\Framework\Stdlib\DateTime\TimezoneInterface;

class SellerOrgGroup implements DataPatchInterface
{
    /**
     * @var \Branch8\Customer\Model\OrganizationFactory
     */
    protected $organizationFactory;
    /**
     * @var TimezoneInterface
     */
    protected $_timezoneInterface;
    /**
     * @var GroupFactory
     */
    protected $groupFactory;
    /**
     * @var \Magento\Customer\Api\Data\GroupExtensionInterfaceFactory
     */
    protected $groupExtensionInterfaceFactory;
    /**
     * @var GroupRepositoryInterface
     */
    protected $groupRepository;
    /**
     * @var \Magento\Framework\App\Config\Storage\WriterInterface
     */
    protected $configWriter;
    /**
     * @var ResourceConnection
     */
    protected $resourceConnection;

    /**
     * @param \Branch8\Customer\Model\OrganizationFactory $organizationFactory
     * @param TimezoneInterface $timezoneInterface
     * @param GroupInterfaceFactory $groupFactory
     * @param \Magento\Customer\Api\Data\GroupExtensionInterfaceFactory $groupExtensionInterfaceFactory
     * @param GroupRepositoryInterface $groupRepository
     * @param \Magento\Framework\App\Config\Storage\WriterInterface $configWriter
     * @param ResourceConnection $resourceConnection
     */
    public function __construct(
        \Branch8\Customer\Model\OrganizationFactory $organizationFactory,
        TimezoneInterface $timezoneInterface,
        \Magento\Customer\Api\Data\GroupInterfaceFactory $groupFactory,
        \Magento\Customer\Api\Data\GroupExtensionInterfaceFactory $groupExtensionInterfaceFactory,
        GroupRepositoryInterface $groupRepository,
        \Magento\Framework\App\Config\Storage\WriterInterface $configWriter,
        ResourceConnection $resourceConnection
    ){
        $this->organizationFactory = $organizationFactory;
        $this->_timezoneInterface = $timezoneInterface;
        $this->groupFactory = $groupFactory;
        $this->groupExtensionInterfaceFactory = $groupExtensionInterfaceFactory;
        $this->groupRepository = $groupRepository;
        $this->configWriter = $configWriter;
        $this->resourceConnection = $resourceConnection;

    }

    public function getAliases()
    {
        return [];
    }

    /**
     * {@inheritdoc}
     */
    public static function getDependencies()
    {
        return [

        ];
    }

    public static function getVersion()
    {
        return '1.0.0';
    }

    public function apply()
    {
        //create seller ORG
        $newOrganization = $this->organizationFactory->create();
        $newOrganization->setName('Seller Organization')
            ->setCreatedAt($this->_timezoneInterface->convertConfigTimeToUtc($this->_timezoneInterface->date()))
            ->setSortOrder(1)
            ->setHotaiAuthId(5);
        $newOrganization->setData('hotai1_name', 'seller_org')
            ->save();
        //create group
        $groupCode = 'Seller Default Group';
        $group = $this->groupFactory->create();
        $customerGroupExtensionAttributes = $this->groupExtensionInterfaceFactory->create();
        $customerGroupExtensionAttributes->setOrganization($newOrganization->getId());
        $customerGroupExtensionAttributes->setPeriod(0);
        $customerGroupExtensionAttributes->setLabel($groupCode);
        $customerGroupExtensionAttributes->setIcon(NULL);
        $customerGroupExtensionAttributes->setConditions(json_encode([]));
        $fullname = $newOrganization->getName().' - '. $groupCode;
        $customerGroupExtensionAttributes->setFullname($fullname);
        $group->setCode($groupCode)
            ->setTaxClassId(3);
        $group->setExtensionAttributes($customerGroupExtensionAttributes);
        $groupSaved = $this->groupRepository->save($group);
        //set default seller group
        $this->configWriter->save('seller_info/group_management/default_seller_group', $groupSaved->getId());
        //update seller for existed group
        $connection = $this->resourceConnection->getConnection();
        $sql = 'update customer_entity set group_id='.$groupSaved->getId().' where platform="seller";';
        $connection->query($sql);
    }
}