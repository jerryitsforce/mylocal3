<?php
declare(strict_types=1);

namespace Branch8\MarketPlaceParentOrderAdminUi\ViewModel;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\UrlInterface;
use Magento\Framework\View\Element\Block\ArgumentInterface;
use Magento\Store\Model\ScopeInterface as StoreScopeInterface;

class CancelModel implements ArgumentInterface
{
    const XML_PATH_CANCEL_REASONS = 'sales/cancellation/reasons';
    private \Magento\Framework\Registry $registry;

    private ScopeConfigInterface $scopeConfig;

    private UrlInterface $url;

    /**
     * @param ScopeConfigInterface $scopeConfig
     * @param UrlInterface $url
     * @param \Magento\Framework\Registry $registry
     */
    public function __construct(
        ScopeConfigInterface        $scopeConfig,
        UrlInterface                $url,
        \Magento\Framework\Registry $registry
    )
    {
        $this->scopeConfig = $scopeConfig;
        $this->registry = $registry;
        $this->url = $url;
    }

    /**
     * @return mixed
     */
    public function getParentOrder()
    {
        return $this->registry->registry('parent_order');
    }

    /**
     * @return string
     */
    public function getCancelUrl()
    {
        return $this->url->getUrl('sales/parent_order/cancelAjax');
    }

    /**
     * @return array
     */
    public function getCancellationReasons()
    {
        $reasons = $this->scopeConfig->getValue(
            self::XML_PATH_CANCEL_REASONS,
            StoreScopeInterface::SCOPE_STORE
        );
        return array_map(function ($reason) {
            return $reason['description'];
        }, is_array($reasons) ? $reasons : json_decode($reasons, true));
    }
}
