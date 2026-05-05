<?php

namespace Branch8\SellerContactInformation\Block\Adminhtml\Customer\Edit;

/**
 * Class BackButton
 */
class BackButton extends \Magento\Customer\Block\Adminhtml\Edit\BackButton
{

    /**
     * @var \Magento\Backend\Block\Widget\Context
     */
    protected $context;

    /**
     * Constructor
     *
     * @param \Magento\Backend\Block\Widget\Context $context
     * @param \Magento\Framework\Registry $registry
     */
    public function __construct(
        \Magento\Backend\Block\Widget\Context $context,
        \Magento\Framework\Registry $registry
    ) {
        $this->context = $context;
        parent::__construct($context, $registry);
    }

    /**
     * @return array
     */
    public function getButtonData()
    {
        return [
            'label' => __('Back'),
            'on_click' => sprintf("location.href = '%s';", $this->getBackUrl()),
            'class' => 'back',
            'sort_order' => 10
        ];
    }

    /**
     * Get URL for back (reset) button
     *
     * @return string
     */
    public function getBackUrl()
    {
        $sellerPanel = $this->context->getRequest()->getParam('seller_panel');
        if ($sellerPanel) {
            return $this->getUrl('marketplace/seller/index');
        }
        return $this->getUrl('*/*/');
    }
}
