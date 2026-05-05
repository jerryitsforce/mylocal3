<?php

namespace Branch8\SalesRule\Block;

use Branch8\SalesRule\Model\Actions\GetSaleRuleAttributeOptionsInterface;
use Magento\Backend\Block\Widget\Context;
use Magento\Framework\Registry;
use Magento\Framework\View\Helper\SecureHtmlRenderer;

class AttributeDropDown extends \Magento\Config\Block\System\Config\Form\Field
{

    protected $_template = 'Branch8_SalesRule::options/dropdown.phtml';

    private $getSellerOptions;

    /**
     * @param Context $context
     * @param Registry $registry
     * @param GetSaleRuleAttributeOptionsInterface $getoptions
     * @param array $data
     * @param SecureHtmlRenderer|null $secureRenderer
     */
    public function __construct(
        Context             $context,
        Registry            $registry,
        GetSaleRuleAttributeOptionsInterface    $getoptions,
        array               $data = [],
        ?SecureHtmlRenderer $secureRenderer = null
    )
    {
        $this->coreRegistry = $registry;
        $this->getSellerOptions = $getoptions;
        parent::__construct($context, $data, $secureRenderer);
    }


    public function getJsFormObject()
    {
        return $this->getData('js_form_object');
    }

    /**
     * @return mixed
     */
    public function getSelected()
    {
        $selected = $this->getRequest()->getPost('selected', []);
        return $selected;
    }

    /**
     * @return mixed
     */
    public function getOptions()
    {
        return $this->getSellerOptions->get()['options'];
    }
}
