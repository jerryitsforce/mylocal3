<?php
namespace Branch8\Customer\Block\Adminhtml\System\Config\Form\Field;

use Branch8\Customer\Model\ResourceModel\Organization\CollectionFactory;
use Magento\Framework\View\Element\Html\Select;

class OrganizationRenderer extends Select
{
    protected $organizationCollectionFactory;

    public function __construct(
        \Magento\Framework\View\Element\Context $context,
        CollectionFactory $organizationCollectionFactory,
        array $data = []
    ) {
        $this->organizationCollectionFactory = $organizationCollectionFactory;
        parent::__construct($context, $data);
    }

    protected function _toHtml()
    {
        if (!$this->getOptions()) {
            foreach ($this->organizationCollectionFactory->create()->toOptionArray() as $group) {
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
