<?php
declare(strict_types=1);

namespace Branch8\Sales\ViewModel;

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
    public function getOrder()
    {
        return $this->registry->registry('sales_order');
    }

    /**
     * @return string
     */
    public function getCancelUrl()
    {
        return $this->url->getUrl('sales/order/cancel');
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

    /**
     * @param \Magento\Sales\Model\Order $order
     * @return string
     */
    public function getOrderCancellation(\Magento\Sales\Model\Order $order)
    {
        if ($order->getStatus() !== 'canceled') {
            return '';
        }
        $connection = $order->getResource()->getConnection();
        $columns = ['comment'];
        $select = $connection->select()
            ->from(
                'sales_order_status_history',
                $columns
            )->where('parent_id = ?', $order->getId())
            ->where('status = ? ', 'canceled')
            ->where('entity_name = ?', 'order')->order('created_at ASC')
            ->limit('1');
        $reason = (string)$connection->fetchOne($select);
        if ($reason) {
            return $reason;
        }
        return '';
    }
}
