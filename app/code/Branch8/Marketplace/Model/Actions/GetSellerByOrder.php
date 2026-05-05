<?php
declare(strict_types=1);

namespace Branch8\Marketplace\Model\Actions;

use Magento\Framework\Exception\LocalizedException;
use Magento\Sales\Model\Order;

class GetSellerByOrder
{

    private \Webkul\Marketplace\Model\ResourceModel\Orders\CollectionFactory $collectionFactory;

    private $cached = [];

    private \Magento\Customer\Model\CustomerFactory $customerFactory;

    /**
     * @param \Webkul\Marketplace\Model\ResourceModel\Orders\CollectionFactory $collectionFactory
     * @param \Magento\Customer\Model\CustomerFactory $customerFactory
     */
    public function __construct(
        \Webkul\Marketplace\Model\ResourceModel\Orders\CollectionFactory $collectionFactory,
        \Magento\Customer\Model\CustomerFactory                         $customerFactory
    )
    {
        $this->customerFactory = $customerFactory;
        $this->collectionFactory = $collectionFactory;
    }

    /**
     * @param Order $order
     * @return mixed
     * @throws LocalizedException
     */
    public function execute(Order $order)
    {
        if (isset($this->cached[$order->getId()])) {
            return $this->cached[$order->getId()];
        }
        /**
         * @var $collection \Webkul\Marketplace\Model\ResourceModel\Orders\Collection
         */
        $collection = $this->collectionFactory->create();
        $collection->addFieldToFilter('order_id', $order->getId());
        if ($collection->getSize() === 0) {
            $this->cached[$order->getId()] = null;
        } elseif ($collection->getSize() > 1) {
            //probably this is an issue since we split order into seller , 1 order belong only 1 seller
            throw new LocalizedException(__('Multiple Sellers for Order :%1', $order->getId()));
        }
        $customer = $this->customerFactory->create()->load($collection->getFirstItem()->getData('seller_id'));
        $this->cached[$order->getId()] = $customer;
        return $this->cached[$order->getId()];
    }
}
