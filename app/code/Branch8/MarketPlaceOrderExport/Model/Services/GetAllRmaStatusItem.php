<?php

namespace Branch8\MarketPlaceOrderExport\Model\Services;

use Branch8\Rma\Model\MarketplaceRmaStatusHistoryFactory;
use Magento\Sales\Model\Order;

class GetAllRmaStatusItem
{
    private $cache = [];
    /** @var \Magento\Sales\Model\ResourceModel\Order\Status\History\CollectionFactory $historyCollection */
    protected $historyCollection;
    /** @var \Branch8\Rma\Helper\Config\StatusLabel $rmaStatusLabel */
    protected $rmaStatusLabel;
    private \Magento\Sales\Model\ResourceModel\Order\Status\History\CollectionFactory $historyCollectionFactory;

    private $marketplaceRmaStatusHistoryFactory;

    /**
     * @param \Magento\Sales\Model\ResourceModel\Order\Status\History\CollectionFactory $historyCollectionFactory
     * @param \Branch8\Rma\Helper\Config\StatusLabel $rmaStatusLabel
     * @param MarketplaceRmaStatusHistoryFactory $marketplaceRmaStatusHistoryFactory
     */
    public function __construct(
        \Magento\Sales\Model\ResourceModel\Order\Status\History\CollectionFactory $historyCollectionFactory,
        \Branch8\Rma\Helper\Config\StatusLabel                                    $rmaStatusLabel,
        MarketplaceRmaStatusHistoryFactory                                        $marketplaceRmaStatusHistoryFactory
    )
    {
        $this->rmaStatusLabel = $rmaStatusLabel;
        $this->historyCollectionFactory = $historyCollectionFactory;
        $this->marketplaceRmaStatusHistoryFactory = $marketplaceRmaStatusHistoryFactory;
    }

    /**
     * @param $orderId
     * @param $itemId
     * @param $rmaOptions
     * @return array|mixed
     */
    public function get($orderId, $itemId, $rmaOptions)
    {
        $key = $orderId . '_' . $itemId;
        if (isset($this->cache[$key])) {
            return $this->cache[$key];
        }
        $statusCollection = $this->getItemStatusCollection($orderId, $itemId);
        $rmaCollection = $this->getRmaStatusCollection($this->getRmaId($rmaOptions));
        $statusTimeArray = $this->getOrderItemStatusHistoryTime($statusCollection, $rmaCollection);
        $this->cache[$key] = $statusTimeArray;
        return $this->cache[$key];
    }

    /**
     * @param $orderId
     * @param $itemId
     * @return \Magento\Sales\Model\ResourceModel\Order\Status\History\Collection
     */
    private function getItemStatusCollection($orderId, $itemId)
    {
        $statusCollection = $this->historyCollectionFactory->create();
        $statusCollection
            ->addFieldToFilter('parent_id', $orderId)
            ->addFieldToFilter('item_id', $itemId);
        return $statusCollection;
    }

    /**
     * @param $statusCollection
     * @param $rmaCollection
     * @return array
     */
    private function getOrderItemStatusHistoryTime($statusCollection, $rmaCollection)
    {
        $statusHistoryArray = [];

        // Formal Flow正流程
        foreach ($statusCollection->getItems() as $data) {
            $status = $data->getItemStatus();
            $rmaLabel = $this->rmaStatusLabel->getCustomerRmaStatusTitle($data->getItemStatus());
            if ($rmaLabel) {
                continue;
            }

            // Get the First Record
            if (isset($statusHistoryArray[$status])) {
                continue;
            }

            $statusHistoryArray[$status] = $data->getCreatedAt();
        }

        // Reverse Flow 逆流程
        foreach ($rmaCollection->getItems() as $data) {
            $rmaLabel = $this->rmaStatusLabel->getCustomerRmaStatusTitle($data->getStatus());
            // Get the First Record
            if (isset($statusHistoryArray[$rmaLabel])) {
                continue;
            }
            $statusHistoryArray[$rmaLabel] = $data->getCreatedAt();
        }

        return $statusHistoryArray;
    }

    /**
     * @param $rmaOptions
     * @return int|mixed
     */
    private function getRmaId($rmaOptions)
    {
        $options = $this->getRmaOptionsArray($rmaOptions);
        if (empty($options)) {
            return 0;
        }

        return $options['rma_id'];

    }

    /**
     * @param $rmaOptions
     * @return array|mixed
     */
    private function getRmaOptionsArray($rmaOptions)
    {
        if (is_null($rmaOptions)) {
            return [];
        }

        return json_decode($rmaOptions, true);
    }

    /**
     * @param $rmaId
     * @return \Magento\Framework\Data\Collection\AbstractDb|\Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection|null
     */
    private function getRmaStatusCollection($rmaId)
    {
        $statusCollection = $this->marketplaceRmaStatusHistoryFactory->create()->getCollection();
        $statusCollection
            ->addFieldToFilter('parent_id', $rmaId);
        return $statusCollection;
    }
}
