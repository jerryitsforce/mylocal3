<?php

namespace Branch8\Customer\Block\Adminhtml\Group\Edit;

use Magento\Customer\Api\GroupExcludedWebsiteRepositoryInterface;
use Magento\Customer\Controller\RegistryConstants;
use Magento\Store\Model\System\Store as SystemStore;

class Form extends \Magento\Customer\Block\Adminhtml\Group\Edit\Form{
    /**
     * @var \Branch8\Customer\Model\Config\Source\Organization
     */
    protected $organizationConfig;
    /**
     * @var \Branch8\Customer\Helper\Group
     */
    protected $customGroupHelper;
    /**
     * @var \Magento\Customer\Model\ResourceModel\Group\CollectionFactory
     */
    protected $groupCollectionFactory;

    protected $_storeManager;

    /**
     * @param \Magento\Backend\Block\Template\Context $context
     * @param \Magento\Framework\Registry $registry
     * @param \Magento\Framework\Data\FormFactory $formFactory
     * @param \Magento\Tax\Model\TaxClass\Source\Customer $taxCustomer
     * @param \Magento\Tax\Helper\Data $taxHelper
     * @param \Magento\Customer\Api\GroupRepositoryInterface $groupRepository
     * @param \Magento\Customer\Api\Data\GroupInterfaceFactory $groupDataFactory
     * @param \Branch8\Customer\Model\Config\Source\Organization $organizationConfig
     * @param \Branch8\Customer\Helper\Group $customGroupHelper
     * @param \Magento\Customer\Model\ResourceModel\Group\CollectionFactory $groupCollectionFactory
     * @param array $data
     * @param SystemStore|null $systemStore
     * @param GroupExcludedWebsiteRepositoryInterface|null $groupExcludedWebsiteRepository
     */
    public function __construct(
        \Magento\Backend\Block\Template\Context $context,
        \Magento\Framework\Registry $registry,
        \Magento\Framework\Data\FormFactory $formFactory,
        \Magento\Tax\Model\TaxClass\Source\Customer $taxCustomer,
        \Magento\Tax\Helper\Data $taxHelper,
        \Magento\Customer\Api\GroupRepositoryInterface $groupRepository,
        \Magento\Customer\Api\Data\GroupInterfaceFactory $groupDataFactory,
        \Branch8\Customer\Model\Config\Source\Organization $organizationConfig,
        \Branch8\Customer\Helper\Group $customGroupHelper,
        \Magento\Customer\Model\ResourceModel\Group\CollectionFactory $groupCollectionFactory,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        array $data = [],
        SystemStore $systemStore = null,
        GroupExcludedWebsiteRepositoryInterface $groupExcludedWebsiteRepository = null
    ){
        parent::__construct($context, $registry, $formFactory, $taxCustomer, $taxHelper, $groupRepository,
            $groupDataFactory, $data, $systemStore, $groupExcludedWebsiteRepository);
        $this->organizationConfig = $organizationConfig;
        $this->customGroupHelper = $customGroupHelper;
        $this->groupCollectionFactory = $groupCollectionFactory;
        $this->_storeManager = $storeManager;
    }

    /**
     * @return void
     * @throws \Magento\Framework\Exception\LocalizedException
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    protected function _prepareLayout()
    {
        parent::_prepareLayout();
        $groupId = $this->_coreRegistry->registry(RegistryConstants::CURRENT_GROUP_ID);
        /** @var \Magento\Customer\Api\Data\GroupInterface $customerGroup */
        $customerGroupExcludedWebsites = [];
        if ($groupId === null) {
            $customerGroup = $this->groupDataFactory->create();
        } else {
            $customerGroup = $this->_groupRepository->getById($groupId);
        }

        $form = $this->getForm();
        $form->setEnctype('multipart/form-data');
        $fieldset = $form->getElement('base_fieldset');
        $fieldset->addField(
            'label',
            'text',
            [
                'name' => 'label',
                'label' => __('Label'),
                'title' => __('Label'),
                'class' => '',
                'required' => false,
            ],
            'customer_group_code'
        );
        $mediaUrl = $this->_storeManager->getStore()
            ->getBaseUrl(\Magento\Framework\UrlInterface::URL_TYPE_MEDIA);
        $levelIcon = $customerGroup->getExtensionAttributes()->getIcon();
        $fieldset->addField(
            'icon',
            'file',
            [
                'name' => 'icon',
                'data-form-part' => $this->getData('target_form'),
                'label' => __('Icon'),
                'title' => __('Icon'),
                'value' => '',
                'after_element_html' => '<label style="width:100%;">
                    Allowed File Type : [jpg, jpeg, gif, png]
                </label><br />
                <img style="margin:5px 0;width:100px;"
                src="'.$mediaUrl.$levelIcon.'"
                />',
            ],
            'label'
        );

        $fieldset->addField(
            'organization',
            'select',
            [
                'name' => 'organization',
                'label' => __('Organization'),
                'title' => __('Organization'),
                'class' => 'required-entry',
                'required' => true,
                'values' => $this->organizationConfig->toOptionArray()
            ],
            'customer_group_code'
        );
        $fieldset->addField(
            'prev_level',
            'select',
            [
                'name' => 'prev_level',
                'label' => __('Previous level'),
                'title' => __('Previous level'),
                'class' => '',
                'required' => false,
                'values' => []
            ],
            'organization'
        );
        $fieldset->addField(
            'nxt_level',
            'select',
            [
                'name' => 'nxt_level',
                'label' => __('Next level'),
                'title' => __('Next level'),
                'class' => '',
                'required' => false,
                'values' => []
            ],
            'prev_level'
        );
        $fieldset->addField(
            'period',
            'text',
            [
                'name' => 'period',
                'label' => __('Period'),
                'title' => __('Period'),
                'note' => __('Unit is month, otherwise leave blank or enter 0'),
                'class' => 'number',
                'required' => false,
            ],
            'nxt_level'
        );

        $fieldset->addField(
            'conditions',
            \Branch8\Customer\Block\Adminhtml\Form\Element\GroupCondition::class,
            [
                'name' => 'conditions',
                'label' => __('Conditions'),
                'title' => __('Conditions'),
                'class' => '',
                'required' => false,
            ]
        );

        $form->addValues([
            'organization' => $customerGroup->getExtensionAttributes()->getOrganization(),
            'fullname' => $customerGroup->getExtensionAttributes()->getFullname(),
            'nxt_level' => $customerGroup->getExtensionAttributes()->getNxtLevel(),
            'prev_level' => $customerGroup->getExtensionAttributes()->getPrevLevel(),
            'period' => $customerGroup->getExtensionAttributes()->getPeriod(),
            'conditions' => $customerGroup->getExtensionAttributes()->getConditions(),
            'label' => $customerGroup->getExtensionAttributes()->getLabel()
        ]);
    }

    /**
     * @return string
     */
    public function getFormHtml(){
        $html = parent::getFormHtml();
        $conditionAttr = $this->getForm()->getElement('conditions');
        $conditions = $this->customGroupHelper->parseGroupCondition((string)$conditionAttr->getValue());
        $prevLevel = (int)$this->getForm()->getElement('prev_level')->getValue();
        $nxtLevel = (int)$this->getForm()->getElement('nxt_level')->getValue();
        $numberOfOrder = '';
        if(isset($conditions['condition_num_orders'])){
            $numberOfOrder = $conditions['condition_num_orders'];
        }
        $combine = '';
        if(isset($conditions['condition_combine'])){
            $combine = $conditions['condition_combine'];
        }
        $totalValue = '';
        if(isset($conditions['condition_total_value'])){
            $totalValue = $conditions['condition_total_value'];
        }
        $groupsCollection = $this->groupCollectionFactory->create();
        $groups = [];
        foreach ($groupsCollection as $_group) {
            $groups[$_group->getCustomerGroupId()] = [
                'value' => $_group->getCustomerGroupCode(),
                'prev_level' => $_group->getPrevLevel(),
                'nxt_level' => $_group->getNxtLevel(),
                'organization' => $_group->getOrganization()
            ];
        }
        $groupId = $this->_coreRegistry->registry(RegistryConstants::CURRENT_GROUP_ID);
        $layout = $this->getLayout();
        $appendFormBlock = $layout->createBlock(\Magento\Backend\Block\Template::class)
            ->setTemplate('Branch8_Customer::edit_form_append.phtml');
        $appendBlockData = [
            'groups' => $groups,
            'prevLevel' => $prevLevel,
            'nxtLevel' => $nxtLevel,
            'numberOfOrder' => $numberOfOrder,
            'combine' => $combine,
            'totalValue' =>$totalValue,
            'groupId' => $groupId
        ];
        $appendFormBlock->setData('scriptData', $appendBlockData);
        $html .= $appendFormBlock->toHtml();
        return $html;
    }

}