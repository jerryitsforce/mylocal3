<?php

namespace Branch8\SalesReports\Block\Adminhtml;

use Branch8\SalesReports\Model\Actions\GetOptionsInterface;
use Branch8\SalesRule\Model\Actions\GetSaleRuleAttributeOptionsInterface;
use Magento\Backend\Block\Widget\Context;
use Magento\Framework\Registry;
use Magento\Framework\View\Helper\SecureHtmlRenderer;

class BasicDropdown extends \Magento\Config\Block\System\Config\Form\Field
{

    protected $_template = 'Branch8_SalesReports::options/dropdown.phtml';

    private $getCouponOptions;

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
        GetOptionsInterface $getOptions,
        array               $data = [],
        ?SecureHtmlRenderer $secureRenderer = null
    )
    {
        $this->coreRegistry = $registry;
        $this->getCouponOptions = $getOptions;
        parent::__construct($context, $data, $secureRenderer);
    }


    public function getJsFormObject()
    {
        return $this->getData('input_hidden_field');
    }

    /**
     * @return mixed
     */
    public function getSelected()
    {
        $selected = $this->getData('selected');
        return $selected ?: [];
    }

    /**
     * @return mixed
     */
    public function getOptions()
    {
        return $this->getCouponOptions->get()['options'];
    }
}
