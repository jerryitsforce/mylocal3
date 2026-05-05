<?php
namespace Branch8\Preorder\Plugin\Webkul\Observer;

use Webkul\MarketplacePreorder\Model\ResourceModel\PreorderItems\CollectionFactory as PreorderItemsCollection;

class AfterSaveProduct
{
    /**
     * @var \Webkul\MarketplacePreorder\Helper\Email
     */
    protected $_emailHelper;

    /**
     * @var PreorderItemsCollection
     */
    protected $_preorderItemCollection;

    /**
     *
     * @param \Webkul\MarketplacePreorder\Helper\Email              $emailHelper
     * @param PreorderItemsCollection                               $preorderItemCollection
     */
    public function __construct(
        \Webkul\MarketplacePreorder\Helper\Email $emailHelper,
        PreorderItemsCollection $preorderItemCollection
    ) {
        $this->_emailHelper                 = $emailHelper;
        $this->_preorderItemCollection      = $preorderItemCollection;
    }

    /**
     * Save Product Data
     *
     * @param array $data
     * @param collection $product
     * @param bool $checkPreorderSpecific
     * @param int $sellerId
     * @param int $storeId
     */
    public function aroundSaveProductData(
        \Webkul\MarketplacePreorder\Observer\AfterSaveProduct $subject,
        callable $proceed, $data, $product, $checkPreorderSpecific, $sellerId, $storeId)
    {
        $emailArray = [];
        $notifyArray= [];
        $isInStock = 0;
        $stockData = $data['product']['stock_data'];
        if (array_key_exists('is_in_stock', $stockData)) {
            $isInStock = $stockData['is_in_stock'];
        }
        if (!$isInStock) {
            $stockData = $data['product']['quantity_and_stock_status'];
            if (is_numeric($stockData)) {
                $isInStock = $stockData;
            } else if (array_key_exists('is_in_stock', $stockData)) {
                $isInStock = $stockData['is_in_stock'];
            }
        }
        if ($isInStock == 1) {
            $collection = $this->_preorderItemCollection->create()
                ->addFieldToFilter('status', ['eq' => 0])
                ->addFieldToFilter('notify', ['eq' => 0])
                ->addFieldToFilter('product_id', ['eq' => $product->getId()]);

            foreach ($collection as $item) {
                $invoice = $subject->checkInvoice($item->getOrderId(), $item->getItemId());
                if ($item->getProductId() == $product->getId()) {
                    $emailArray[] = $item->getCustomerEmail();
                    $notifyArray[] = $item->getId();

                    $subject->checkPreorderSpecific($item, $checkPreorderSpecific, $product, $storeId);
                }
            }
            $emailArray = array_unique($emailArray);
            $notifyArray = array_unique($notifyArray);
            if ($this->_emailHelper->isAutoEmail($sellerId)) {
                $this->_emailHelper->sendNotifyEmail($emailArray, $product->getName());
                foreach ($notifyArray as $temId) {
                    $updateData = [
                        'notify' => 1
                    ];
                    $subject->updatePreorderItem($temId, $updateData);
                }
            }
        } else {
            $collection = $this->_preorderItemCollection->create()
                ->addFieldToFilter('status', ['eq' => 0])
                ->addFieldToFilter('notify', ['eq' => 1]);

            foreach ($collection as $item) {
                if ($item->getProductId() == $product->getId()) {
                    $updateData = [
                        'notify' => 0
                    ];
                    $subject->updatePreorderItem($item->getId(), $updateData);
                }
            }
        }
    }
}
