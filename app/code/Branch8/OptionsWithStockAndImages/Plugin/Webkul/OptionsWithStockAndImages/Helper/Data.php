<?php
/**
 * Copyright ©  All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Branch8\OptionsWithStockAndImages\Plugin\Webkul\OptionsWithStockAndImages\Helper;

class Data
{
    /**
     * @var \Webkul\OptionsWithStockAndImages\Model\VariationsFactory
     */
    public $variationFactory;

    /**
     * @var \Branch8\OptionsWithStockAndImages\Helper\Salable
     */
    protected $salable;

    private $cache = [];

    /**
     * Constructor
     *
     * @param \Magento\Framework\App\Action\Context $context
     * @param \Magento\Framework\Json\Helper\Data $jsonHelper
     * @param CollectionFactory $variation
     */
    public function __construct(
        \Branch8\OptionsWithStockAndImages\Helper\Salable         $salable,
        \Webkul\OptionsWithStockAndImages\Model\VariationsFactory $variationFactory,
    )
    {
        $this->variationFactory = $variationFactory;
        $this->salable = $salable;
    }

    public function aroundPregMatch(
        \Webkul\OptionsWithStockAndImages\Helper\Data $subject,
        \Closure                                      $proceed
    )
    {
        return false;
    }

    public function aroundGetCombData(
        \Webkul\OptionsWithStockAndImages\Helper\Data $subject,
        \Closure                                      $proceed,
                                                      $productId,
                                                      $comb
    )
    {
        $cacheKey = trim(join('|', [$productId, $comb]));
        if (isset($this->cache[$cacheKey])) {
            return $this->cache[$cacheKey];
        }
        $variation = $this->variationFactory->create()
            ->getCollection()
            ->addFieldToFilter("product_id", $productId)
            ->addFieldToFilter("comb", $comb)
            ->setOrder('stock', 'ASC')
            ->getLastItem();
        $sku = $variation->getData('sku');
        $is_sync = $variation->getData('is_sync');
        $quantity = $variation->getData('stock');
        if ((int)$is_sync == 1) {
            $qty = $this->salable->getQtyBySku($sku);
            if ($qty != $quantity) {
                //$quantity = min($qty, $quantity);
                $variation->setData('stock', $qty)->save();
            }
        }
        $this->cache[$cacheKey] = $variation;
        return $variation;
    }
}
