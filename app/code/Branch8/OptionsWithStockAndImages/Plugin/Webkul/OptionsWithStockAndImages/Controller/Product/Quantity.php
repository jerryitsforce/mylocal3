<?php
/**
 * Copyright ©  All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Branch8\OptionsWithStockAndImages\Plugin\Webkul\OptionsWithStockAndImages\Controller\Product;

use Webkul\OptionsWithStockAndImages\Model\ResourceModel\Variations\CollectionFactory;

class Quantity
{
    /**
     * @var \Magento\Framework\Json\Helper\Data
     */
    public $jsonHelper;

    /**
     * @var CollectionFactory
     */
    public $variation;

    /**
     * @var \Branch8\OptionsWithStockAndImages\Helper\Salable
     */
    protected $salable;

    /**
     * @var \Webkul\MarketplacePreorder\Helper\Data
     */
    protected $_preorderHelper;

    /**
     * Constructor
     *
     * @param \Magento\Framework\App\Action\Context $context
     * @param \Magento\Framework\Json\Helper\Data $jsonHelper
     * @param CollectionFactory $variation
     */
    public function __construct(
        \Magento\Framework\Json\Helper\Data $jsonHelper,
        \Branch8\OptionsWithStockAndImages\Helper\Salable $salable,
        \Webkul\MarketplacePreorder\Helper\Data $preorderHelper,
        CollectionFactory $variation
    ) {
        $this->jsonHelper = $jsonHelper;
        $this->variation = $variation;
        $this->_preorderHelper = $preorderHelper;
        $this->salable = $salable;
    }

    public function aroundExecute(
        \Webkul\OptionsWithStockAndImages\Controller\Product\Quantity $subject,
        \Closure $proceed
    ) {

        $data = $subject->getRequest()->getParams();

        if (!empty($data)) {
            $productRowId = $this->salable->getCurrentProductRowId($data['productId']);
            if (!$productRowId) {
                return $subject->getResponse()->representJson(
                    $this->jsonHelper->jsonEncode(0)
                );
            }
            if ($this->_preorderHelper->isPreorder($data['productId'])) {
                $stock = $this->_preorderHelper->getStockDetails($data['productId']);
                // Get the preorder quantity or default to 9999
                $quantity = $stock['preorder']['preorder_qty'] ?? 9999;
                return $subject->getResponse()->representJson(
                        $this->jsonHelper->jsonEncode($quantity)
                    );
            } else {
                $comb = html_entity_decode($data['combination']);
                $collection = $this->variation->create()
                                            ->addFieldToFilter('comb', $comb)
                                            ->addFieldToFilter('product_id', $productRowId);                   
                if ($collection->getSize()) {
                    $variation = $collection->getFirstItem();
                    $sku = $variation->getData('sku');
                    $is_sync = $variation->getData('is_sync');
                    $quantity = $variation->getData('stock');
                    if((int)$is_sync == 1){
                        $qty = $this->salable->getQtyBySku($sku);
                        if($qty != $quantity){
                            $quantity = min($qty, $quantity);
                            $variation->setData('stock', $quantity)->save();
                        }
                    }
                    return $subject->getResponse()->representJson(
                        $this->jsonHelper->jsonEncode($quantity)
                    );
                }
            }
        }
    }
}