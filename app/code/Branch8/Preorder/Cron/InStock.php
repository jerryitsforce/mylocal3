<?php

namespace Branch8\Preorder\Cron;

class InStock extends \Webkul\MarketplacePreorder\Cron\InStock {
    /**
     * Update some information for  preorder
     * Send mail for user on pre-order product is available
     * Update stock to In stock after available date(start/ end mode)
     * @return void
     */
    public function execute()
    {
        $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
        $timezone = $objectManager->get(\Magento\Framework\Stdlib\DateTime\TimezoneInterface::class);
        $preorderHelper = $objectManager->get(\Branch8\Preorder\Helper\Data::class);
        $emailArray = [];
        $notifyArray = [];
        try {
            $helper = $this->_preorderHelper;
            $productIds = $this->getStockDetails();
            $productCollection = $this->_productCollectionFactory->create()
                ->addAttributeToSelect('wk_marketplace_availability', 'left')
                ->addAttributeToSelect('preorder_mode', 'left')
                ->addAttributeToFilter(
                    'entity_id',
                    ['in' => $productIds]
                )
                ->addAttributeToFilter('preorder_mode', \Branch8\Preorder\Model\Source\PreorderMode::START_END_DATE);
            $select = $productCollection->getSelect();

            $currentDatetime = $timezone->date()->format('Y-m-d H:i:s');
            $select->where('`at_wk_marketplace_availability`.`value` <= "'.$currentDatetime.'"');

            foreach ($productCollection as $_product) {
                $productId = $_product->getId();
                $sellerId = $helper->getSellerIdByProductId($productId);
                if(\Branch8\HotaiCore\Helper\DebugLog::isEnable('Branch8_Preorder', 'debuglog')){
                    $this->logger->debug($_product->getSku());
                    $this->logger->debug('cron is running');
                }
                $sku = $_product->getSku();
                $stockItem = $this->_stockRegistry->getStockItem($productId);
                /**
                 * Update stock for start/ end mode
                 */
                if($_product->getPreorderMode() == \Branch8\Preorder\Model\Source\PreorderMode::START_END_DATE && !$stockItem->getIsInStock()) {
                    $stockItem->setData('is_in_stock', 1);
                    $this->_stockRegistry->updateStockItemBySku($sku, $stockItem);
                    if(\Branch8\HotaiCore\Helper\DebugLog::isEnable('Branch8_Preorder', 'debuglog')){
                        $this->logger->debug($_product->getSku().': instock');
                    }
                }
                $collection = $this->_preorderItemCollection->create()
                    ->addFieldToFilter('status', ['eq' => 0])
                    ->addFieldToFilter('notify', ['eq' => 0])
                    ->addFieldToFilter('product_id', ['eq' => $productId]);

                foreach ($collection as $item) {
                    $emailArray[] = $item->getCustomerEmail();
                    $notifyArray[] = $item->getId();
/**
 * No need to enable/ disable because we don't use Buyer Specification attribute
 */
//                    if ($helper->getSellerPreorderSpecification($item->getSellerId())) {
//                        $helper->setProductDisabled($productId, $_product->getStoreId());
//                    } else {
//                        $helper->setProductEnabled($productId, $_product->getStoreId());
//                    }
                }

                $emailArray = array_unique($emailArray);
                $notifyArray = array_unique($notifyArray);
                if ($this->_emailHelper->isAutoEmail($sellerId)) {
                    $this->_emailHelper->sendNotifyEmail($emailArray, $_product->getName());
                    foreach ($notifyArray as $temId) {
                        $updateData = [
                            'notify' => 1
                        ];
                        $this->updatePreorderItem($temId, $updateData);
                    }
                }
                /**
                 * Update available date to empty on available date
                 */
                if($_product->getPreorderMode() == \Branch8\Preorder\Model\Source\PreorderMode::START_END_DATE) {
                    $preorderHelper->updateProductAttribute($productId, 'wk_marketplace_availability', NULL, $_product->getStoreId());
                }
                $this->cleanByTags($productId);

            }
        } catch (\Exception $e) {
            if(\Branch8\HotaiCore\Helper\DebugLog::isEnable('Branch8_Preorder', 'debuglog')){
                $this->logger->debug($e->getMessage());
            }
        }
    }


}