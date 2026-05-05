<?php

namespace Branch8\Customer\Block\Adminhtml\Form\Element;

use Magento\Framework\Data\Form\Element\AbstractElement;
use Magento\Framework\Data\Form\Element\CollectionFactory;
use Magento\Framework\Data\Form\Element\Factory;
use Magento\Framework\Escaper;

class GroupCondition extends AbstractElement{
    /**
     * @var \Magento\Backend\Block\Template
     */
    protected $blockTemplate;

    /**
     * @param Factory $factoryElement
     * @param CollectionFactory $factoryCollection
     * @param Escaper $escaper
     * @param \Magento\Backend\Block\Template $blockTemplate
     * @param $data
     */
    public function __construct(
        Factory $factoryElement,
        CollectionFactory $factoryCollection,
        Escaper $escaper,
        \Magento\Framework\View\Element\Template $blockTemplate,
        $data = []
    ) {
        parent::__construct($factoryElement, $factoryCollection, $escaper, $data);
        $this->setType('group_condition');
        $this->blockTemplate = $blockTemplate;
    }

    /**
     * @return string
     */
    public function getElementHtml()
    {
        $blockContent = $this->blockTemplate->setTemplate('Branch8_Customer::form/element/group_condition.phtml');

        $html = $this->getBeforeElementHtml()
            . $blockContent->toHtml()
            . $this->getAfterElementHtml();
        return $html;
    }
}