<?php
declare(strict_types=1);

namespace Branch8\MarketPlaceParentOrder\Model\Services;

use Branch8\MarketPlaceParentOrder\Api\Data\ParentOrderAddressInterface;
use Branch8\MarketPlaceParentOrder\Model\ParentOrderAddressFactory;
use Magento\Framework\DataObject\Copy;

class  CopyAddressesFromSalesOrderToParentOrder
{
    public Copy $objectCopyService;
    public ParentOrderAddressFactory $addressFactory;
    private \Magento\Framework\Api\DataObjectHelper $dataObjectHelper;

    /**
     * @param Copy $copy
     * @param \Magento\Framework\Api\DataObjectHelper $dataObjectHelper
     * @param ParentOrderAddressFactory $addressFactory
     */
    public function __construct(
        \Magento\Framework\DataObject\Copy      $copy,
        \Magento\Framework\Api\DataObjectHelper $dataObjectHelper,
        ParentOrderAddressFactory               $addressFactory
    )
    {
        $this->addressFactory = $addressFactory;
        $this->objectCopyService = $copy;
        $this->dataObjectHelper = $dataObjectHelper;
    }

    /**
     * @param \Magento\Sales\Api\Data\OrderInterface $order
     * @return array
     */
    public function copy(
        \Magento\Sales\Api\Data\OrderInterface $order
    )
    {
        $addresses = [];
        foreach ($order->getAddresses() as $saleAddress) {
            $address = $this->addressFactory->create();
            $orderAddressData = $this->objectCopyService->getDataFromFieldset(
                'sales_address_convert_parent_order_address',
                'to_parent_order_address',
                $saleAddress
            );
            $this->dataObjectHelper->populateWithArray(
                $address,
                $orderAddressData,
                ParentOrderAddressInterface::class
            );
            $addresses[] = $address;
        }
        return $addresses;
    }
}
