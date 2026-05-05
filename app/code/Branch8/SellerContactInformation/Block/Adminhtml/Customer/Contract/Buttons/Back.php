<?php

namespace Branch8\SellerContactInformation\Block\Adminhtml\Customer\Contract\Buttons;

use Magento\Framework\View\Element\UiComponent\Control\ButtonProviderInterface;
class Back extends Generic implements ButtonProviderInterface
{
    protected $httpRequest;

    public function __construct(
        \Magento\Backend\Block\Widget\Context $context,
        \Magento\Framework\App\Request\Http $httpRequest
    )
    {
        parent::__construct($context);
        $this->httpRequest = $httpRequest;
    }

    public function getButtonData()
    {
        return [
            'label' => __('Back'),
            'class' => 'back',
            'url' => $this->getUrl('customer/index/edit', ['id' => $this->httpRequest->getParam('seller_id'), 'seller_panel' => 1, '_query' => ['tab' => 'contract']]),
            'sort_order' => 90,
        ];
    }
}