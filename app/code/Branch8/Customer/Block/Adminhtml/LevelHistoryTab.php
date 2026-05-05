<?php

namespace Branch8\Customer\Block\Adminhtml;

use Magento\Backend\Block\Template\Context;
use Magento\Customer\Controller\RegistryConstants;
use Magento\Framework\Registry;
use Magento\Ui\Component\Layout\Tabs\TabWrapper;

class LevelHistoryTab extends TabWrapper{
    /**
     * @var Registry
     */
    protected $coreRegistry = null;
    /**
     * @var bool
     */
    protected $isAjaxLoaded = true;
    /**
     * @var \Webkul\Marketplace\Block\Adminhtml\Customer\Edit
     */
    protected $customerEdit;

    protected $customerModelFactory;
    /**
     * Constructor
     *
     * @param Context $context
     * @param Registry $registry
     * @param \Webkul\Marketplace\Block\Adminhtml\Customer\Edit $customerEdit
     * @param array $data
     */
    public function __construct(
        Context $context,
        Registry $registry,
        \Magento\Customer\Model\CustomerFactory $customerFactory,
        array $data = []
    ) {
        $this->coreRegistry = $registry;
        $this->customerModelFactory = $customerFactory;
        parent::__construct($context, $data);
    }

    /**
     * Function getCustomerId
     *
     * @return string|null
     */
    public function getCustomerId()
    {
        return $this->coreRegistry->registry(RegistryConstants::CURRENT_CUSTOMER_ID);
    }

    /**
     * @inheritdoc
     */
    public function canShowTab()
    {
        if($this->getCustomerId()) {
            $customer = $this->customerModelFactory->create()->load($this->getCustomerId());
            if($customer->getData('platform') == 'seller'){
                return false;
            }
            return true;
        }
        return false;
    }
    /**
     * Return Tab label
     *
     * @return \Magento\Framework\Phrase
     */
    public function getTabLabel()
    {
        return __('Customer group history');
    }
    /**
     * Return URL link to Tab content
     *
     * @return string
     */
    public function getTabUrl()
    {
        return $this->getUrl('b8customer/groupHistory/index', ['_current' => true]);
    }
}