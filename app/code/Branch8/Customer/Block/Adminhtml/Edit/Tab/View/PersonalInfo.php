<?php

namespace Branch8\Customer\Block\Adminhtml\Edit\Tab\View;

use Magento\Customer\Api\AccountManagementInterface;
use Magento\Customer\Model\Address\Mapper;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Stdlib\DateTime\TimezoneInterface;

class PersonalInfo extends \Magento\Customer\Block\Adminhtml\Edit\Tab\View\PersonalInfo{
    /**
     * @var \Branch8\Customer\Model\OrganizationFactory
     */
    protected $organizationFactory;
    /**
     * @var \Magento\Customer\Model\GroupFactory
     */
    protected $groupFactory;
    /**
     * @var TimezoneInterface
     */
    protected $_timezoneInterface;
    /**
     * @var \Branch8\Customer\Model\ResourceModel\CustomerOtherOrganization\CollectionFactory
     */
    protected $otherOrganizationCollectionFactory;
    /**
     * @var \Branch8\Customer\Model\ResourceModel\Organization\CollectionFactory
     */
    protected $organizationCollectionFactory;

    /**
     * @param \Magento\Backend\Block\Template\Context $context
     * @param AccountManagementInterface $accountManagement
     * @param \Magento\Customer\Api\GroupRepositoryInterface $groupRepository
     * @param \Magento\Customer\Api\Data\CustomerInterfaceFactory $customerDataFactory
     * @param \Magento\Customer\Helper\Address $addressHelper
     * @param \Magento\Framework\Stdlib\DateTime $dateTime
     * @param \Magento\Framework\Registry $registry
     * @param Mapper $addressMapper
     * @param \Magento\Framework\Api\DataObjectHelper $dataObjectHelper
     * @param \Magento\Customer\Model\Logger $customerLogger
     * @param \Branch8\Customer\Model\OrganizationFactory $organizationFactory
     * @param \Magento\Customer\Model\GroupFactory $groupFactory
     * @param TimezoneInterface $timezoneInterface
     * @param \Branch8\Customer\Model\ResourceModel\CustomerOtherOrganization\CollectionFactory $otherOrganizationCollectionFactory
     * @param \Branch8\Customer\Model\ResourceModel\Organization\CollectionFactory $organizationCollectionFactory
     * @param array $data
     */
    public function __construct(
        \Magento\Backend\Block\Template\Context $context,
        AccountManagementInterface $accountManagement,
        \Magento\Customer\Api\GroupRepositoryInterface $groupRepository,
        \Magento\Customer\Api\Data\CustomerInterfaceFactory $customerDataFactory,
        \Magento\Customer\Helper\Address $addressHelper,
        \Magento\Framework\Stdlib\DateTime $dateTime,
        \Magento\Framework\Registry $registry,
        Mapper $addressMapper,
        \Magento\Framework\Api\DataObjectHelper $dataObjectHelper,
        \Magento\Customer\Model\Logger $customerLogger,
        \Branch8\Customer\Model\OrganizationFactory $organizationFactory,
        \Magento\Customer\Model\GroupFactory $groupFactory,
        TimezoneInterface $timezoneInterface,
        \Branch8\Customer\Model\ResourceModel\CustomerOtherOrganization\CollectionFactory $otherOrganizationCollectionFactory,
        \Branch8\Customer\Model\ResourceModel\Organization\CollectionFactory $organizationCollectionFactory,
        array $data = []
    ) {
        parent::__construct($context, $accountManagement, $groupRepository, $customerDataFactory, $addressHelper,
            $dateTime, $registry, $addressMapper, $dataObjectHelper, $customerLogger, $data);
        $this->organizationFactory = $organizationFactory;
        $this->groupFactory = $groupFactory;
        $this->_timezoneInterface = $timezoneInterface;
        $this->otherOrganizationCollectionFactory = $otherOrganizationCollectionFactory;
        $this->organizationCollectionFactory  = $organizationCollectionFactory;
    }

    /**
     * @param $groupId
     * @return \Magento\Customer\Model\Group|null
     */
    public function getGroupCustom($groupId)
    {
        try {
            $group = $this->groupFactory->create()->load($groupId);
        } catch (NoSuchEntityException $e) {
            $group = null;
        }
        return $group;
    }

    /**
     * @param $organizationId
     * @return \Branch8\Customer\Model\Organization|null
     */
    protected function getOrganization($organizationId){
        try {
            $group = $this->organizationFactory->create()->load($organizationId);
        } catch (NoSuchEntityException $e) {
            $group = null;
        }
        return $group;
    }

    /**
     * @return mixed
     */
    public function getCustomerGroupFullname(){
        $organizationId = $this->getGroupCustom($this->getCustomer()->getGroupId())->getOrganization();
        $organization = $this->getOrganization($organizationId);
        return $organization->getName();
    }

    /**
     * @return string
     */
    public function getGroupDate(){
        $groupDate = $this->getCustomer()->getCustomAttribute('group_date');
        if($groupDate){
            $groupDateValue = $groupDate->getValue();
            $groupDateStr = $this->_timezoneInterface->formatDate($groupDateValue, \IntlDateFormatter::MEDIUM);
        }else{
            $groupDateStr = 'N/A';
        }

        return $groupDateStr;
    }

    /**
     * @return string
     */
    public function getOtherGrouds(){
        $otherOrgs = $this->otherOrganizationCollectionFactory->create()
            ->addFieldToFilter('customer_id', $this->getCustomer()->getId())
            ->addFieldToSelect('organization_id')
            ->getColumnValues('organization_id');
        $orgs = $this->organizationCollectionFactory->create()
            ->addFieldToFilter('entity_id', ['in' => $otherOrgs]);
        $otherOrg = [];
        foreach($orgs as $_item){
            $otherOrg[] = $_item->getName();
        }
        return implode(',', $otherOrg);
    }
}