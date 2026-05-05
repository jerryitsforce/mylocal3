<?php

declare(strict_types=1);

namespace Branch8\Sales\Ui\Component\Listing\Column\Status;

use Branch8\Sales\Helper\Config as ConfigHelper;
use Magento\Backend\Model\Auth\Session as AuthSession;
use Magento\Framework\Data\OptionSourceInterface;
use Magento\Sales\Model\ResourceModel\Order\Status\CollectionFactory as StatusCollectionFactory;
use Magento\Store\Model\Store;
use Magento\Store\Model\StoreManagerInterface;

class Options implements OptionSourceInterface
{
    /**
     * @var ConfigHelper
     */
    private ConfigHelper $configHelper;

    /**
     * @var AuthSession
     */
    private AuthSession $authSession;

    /**
     * @var StoreManagerInterface
     */
    private StoreManagerInterface $storeManager;

    /**
     * @var StatusCollectionFactory
     */
    private StatusCollectionFactory $statusCollectionFactory;

    /**
     * Constructor
     *
     * @param ConfigHelper $configHelper
     * @param AuthSession $authSession
     * @param StoreManagerInterface $storeManager
     * @param StatusCollectionFactory $statusCollectionFactory
     */
    public function __construct(
        ConfigHelper            $configHelper,
        AuthSession             $authSession,
        StoreManagerInterface   $storeManager,
        StatusCollectionFactory $statusCollectionFactory
    ) {
        $this->configHelper = $configHelper;
        $this->storeManager = $storeManager;
        $this->authSession = $authSession;
        $this->statusCollectionFactory = $statusCollectionFactory;
    }

    /**
     * @inheritdoc
     */
    public function toOptionArray(): array
    {
        $options = [];
        if (empty($this->options)) {
            $statusCollection = $this->statusCollectionFactory->create();
            $specificStatuses = false;
            if ($this->configHelper->isFilterBySpecificStatusesEnabled()) {
                $specificStatuses = $this->configHelper->getSpecificStatusesForFilter();
            }
            $storeId = $this->getStoreId();
            $statusCollection->getSelect()
                ->joinLeft(
                    ['label_table' => $statusCollection->getTable('sales_order_status_label')],
                    'main_table.status = label_table.status AND label_table.store_id = ' . $storeId,
                    ['label_store' => 'label']
                );
            foreach ($statusCollection as $status) {
                $orderStatus = $status->getStatus();
                $option = [
                    'label' => $status->getLabelStore() ?: $status->getLabel(),
                    'value' => $orderStatus
                ];
                if ($specificStatuses && !in_array($orderStatus, $specificStatuses)) {
                    $option['disable'] = true;
                }
                $options[] = $option;
            }
        }

        return $options;
    }

    /**
     * Get store id
     *
     * @return int
     */
    private function getStoreId(): int
    {
        if ($this->authSession->getUser()?->getInterfaceLocale() === 'en_US') {
            return Store::DEFAULT_STORE_ID;
        }
        try {
            return (int)$this->storeManager->getStore()->getStoreId();
        } catch (\Exception $e) {
            return Store::DEFAULT_STORE_ID;
        }
    }
}
