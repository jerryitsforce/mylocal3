<?php

namespace Branch8\CustomNotification\Ui\Component\Notification;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Sales\Model\ResourceModel\Order\Status\CollectionFactory;
use Magento\Store\Model\ScopeInterface;

class OrderStatus implements \Magento\Framework\Option\ArrayInterface
{

    /** @var CollectionFactory */
    protected $statusCollectionFactory;

    /** @var ScopeConfigInterface */
    protected $scopeConfig;

    /**
     * @param CollectionFactory $statusCollectionFactory
     * @param ScopeConfigInterface $scopeConfig
     */
    public function __construct(
        CollectionFactory $statusCollectionFactory,
        ScopeConfigInterface $scopeConfig
    ) {
        $this->statusCollectionFactory = $statusCollectionFactory;
        $this->scopeConfig = $scopeConfig;
    }

    /**
     * Get selected order statuses from config
     *
     * @return array
     */
    protected function getSelectedStatuses()
    {
        $selectedStatuses = $this->scopeConfig->getValue(
            'magenest_notification_box/notification_box/order_status_notification',
            ScopeInterface::SCOPE_STORE
        );

        return $selectedStatuses ? explode(',', $selectedStatuses) : [];
    }

    /**
     * @return array
     */
    public function toOptionArray()
    {
        $selectedStatuses = $this->getSelectedStatuses();

        if (empty($selectedStatuses)) {
            return []; // Return empty array if no statuses selected
        }

        $collection = $this->statusCollectionFactory->create();
        $collection->addFieldToFilter('status', ['in' => $selectedStatuses]);

        return $collection->toOptionArray();
    }

    /**
     * Get options in "key-value" format
     *
     * @return array
     */
    public function toArray()
    {
        $selectedStatuses = $this->getSelectedStatuses();

        if (empty($selectedStatuses)) {
            return [];
        }

        $collection = $this->statusCollectionFactory->create();
        $collection->addFieldToFilter('status', ['in' => $selectedStatuses]);

        $options = [];
        foreach ($collection as $status) {
            $options[$status->getStatus()] = $status->getLabel();
        }

        return $options;
    }
}
