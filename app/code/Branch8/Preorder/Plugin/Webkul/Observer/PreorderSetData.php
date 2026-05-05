<?php

namespace Branch8\Preorder\Plugin\Webkul\Observer;
use Magento\Framework\Api\DataObjectHelper;
use Webkul\MarketplacePreorder\Api\Data\PreorderItemsInterfaceFactory;
use Webkul\MarketplacePreorder\Model\PreorderItemsRepository as ItemsRepository;

class PreorderSetData
{
    /**
     * @var \Webkul\MarketplacePreorder\Helper\Data
     */
    protected $_preorderHelper;
    /**
     * @var \Magento\Catalog\Model\ProductFactory
     */
    protected $_productFactory;
    /**
     * @var \Magento\Framework\Stdlib\DateTime\DateTime
     */
    protected $_date;
    /**
     * @var PreorderItemsInterfaceFactory
     */
    protected $_preorderItemsFactory;
    /**
     * @var DataObjectHelper
     */
    protected $dataObjectHelper;
    /**
     * @var ItemsRepository
     */
    protected $_itemsRepository;
    /**
     * @var \Magento\Framework\Stdlib\DateTime\TimezoneInterface
     */
    protected $timeZone;

    /**
     * @param \Webkul\MarketplacePreorder\Helper\Data $preorderHelper
     * @param \Magento\Catalog\Model\ProductFactory $productFactory
     * @param \Magento\Framework\Stdlib\DateTime\DateTime $date
     * @param PreorderItemsInterfaceFactory $preorderItemsFactory
     * @param DataObjectHelper $dataObjectHelper
     * @param ItemsRepository $itemsRepository
     * @param \Magento\Framework\Stdlib\DateTime\TimezoneInterface $timeZone
     */
    public function __construct(
        \Webkul\MarketplacePreorder\Helper\Data $preorderHelper,
        \Magento\Catalog\Model\ProductFactory $productFactory,
        \Magento\Framework\Stdlib\DateTime\DateTime $date,
        PreorderItemsInterfaceFactory $preorderItemsFactory,
        DataObjectHelper $dataObjectHelper,
        ItemsRepository $itemsRepository,
        \Magento\Framework\Stdlib\DateTime\TimezoneInterface $timeZone
    ){
        $this->_preorderHelper = $preorderHelper;
        $this->_productFactory = $productFactory;
        $this->_date = $date;
        $this->_preorderItemsFactory = $preorderItemsFactory;
        $this->dataObjectHelper = $dataObjectHelper;
        $this->_itemsRepository = $itemsRepository;
        $this->timeZone = $timeZone;
    }

    public function aroundSetPreorderData($subject, $process, $item, $order)
    {
        $helper = $this->_preorderHelper;

        $time = time();
        $customerId = (int) $order->getCustomerId();
        $customerEmail = $order->getCustomerEmail();
        $remainingAmount = 0;
        $preorderPercent = '';
        $parent = ($item->getParentItem() ? $item->getParentItem() : $item);
        $parentId = $parent->getProductId();
        $productId = $item->getProductId();
        $sellerId = $helper->getSellerIdByProductId($productId);
        $preorderType = $helper->getSellerPreorderType($sellerId);
        $quoteItemId = $item->getQuoteItemId();
        if ($parentId == $productId) {
            $parentId = 0;
        }
        if ($helper->isPreorder($productId)) {
            $orderItemId = $item->getId();
            $parentItemId = $item->getParentItemId();
            $qty = $item->getQtyOrdered();

            $product = $this->_productFactory->create()->load($productId);
            $price = $parent->getPrice();
            $preorderItemStatus = null;
            if ($helper->isPartialPreorder($productId)) {
                $preorderPercent = $helper->getPreorderPercent($sellerId);
                $totalPrice = ($price * 100) / $preorderPercent;
                $remainingAmount = $totalPrice - $price;
                $preorderItemStatus = 0;
            }else{
                $preorderItemStatus = 1;
            }
            if ($orderItemId !== null) {
                $preorderItemData = [
                    'seller_id' => $sellerId,
                    'order_id' => $order->getId(),
                    'item_id' => $orderItemId,
                    'product_id' => $productId,
                    'parent_id' => $parentId,
                    'customer_id' => $customerId,
                    'customer_email' => $customerEmail,
                    'preorder_percent' => $preorderPercent,
                    'paid_amount' => $parent->getPrice(),
                    'remaining_amount' => $remainingAmount,
                    'qty' => $qty,
                    'type' => $preorderType,
                    'status' => $preorderItemStatus,
                    'time' => $this->_date->gmtDate(),
                    'tax_class_id' => $product->getTaxClassId(),
                    'preorder_message' => $this->getPreorderMessage($product)
                ];

                $itemsDataObject = $this->_preorderItemsFactory->create()->load($orderItemId, 'item_id');

                $this->dataObjectHelper->populateWithArray(
                    $itemsDataObject,
                    $preorderItemData,
                    \Webkul\MarketplacePreorder\Api\Data\PreorderItemsInterface::class
                );
                $itemsDataObject->setData('preorder_message', $preorderItemData['preorder_message']);
                try {
                    $this->_itemsRepository->save($itemsDataObject);
                } catch (\Exception $e) {
                    throw new \Magento\Framework\Exception\LocalizedException(__($e->getMessage()));
                }
            }

        }
    }

    public function getPreorderMessage($product){
        $msg = null;
        $preorderMode  = $product->getData('preorder_mode');
        if($preorderMode == \Branch8\Preorder\Model\Source\PreorderMode::START_END_DATE){
            $availableDate = $product->getData('wk_marketplace_availability');
            $avaiDate = $this->timeZone->date($availableDate)->format('m/d');
            $msg = sprintf('此商品為預購商品，預計出貨日%s.', $avaiDate);

        }else if($preorderMode == \Branch8\Preorder\Model\Source\PreorderMode::X_DAYS){
            $xDays = $product->getData('preorder_x_days');
            $msg = sprintf('此商品為預購商品，預計下單後%s出貨.', $xDays);

        }else if($preorderMode == \Branch8\Preorder\Model\Source\PreorderMode::SPECIFY_SHIPPING_DATE) {
            $availableDate = $product->getData('preorder_ship_date');
            $avaiDate = $this->timeZone->date($availableDate)->format('m/d');
            $msg = sprintf('此商品為預購商品，預計出貨日%s.', $avaiDate);
        }

        return $msg;
    }

}