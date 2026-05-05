<?php
namespace Branch8\Customer\Block\Adminhtml\System\Config\Form\Field;

use Magento\Framework\View\Element\Html\Select;
use Magento\Customer\Model\ResourceModel\Group\CollectionFactory;

class CustomerGroupRenderer extends Select
{
    protected $customerGroupCollectionFactory;

    public function __construct(
        \Magento\Framework\View\Element\Context $context,
        CollectionFactory $customerGroupCollectionFactory,
        array $data = []
    ) {
        $this->customerGroupCollectionFactory = $customerGroupCollectionFactory;
        parent::__construct($context, $data);
    }

    protected function _toHtml()
    {
        if (!$this->getOptions()) {
            foreach ($this->customerGroupCollectionFactory->create()->toOptionArray() as $group) {
                $this->addOption($group['value'], $group['label']);
            }
        }
        return parent::_toHtml();
    }

    public function setInputName($name)
    {
        return $this->setName($name);
    }
}
