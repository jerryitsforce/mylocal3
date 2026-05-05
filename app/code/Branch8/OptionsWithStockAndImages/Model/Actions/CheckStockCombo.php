<?php
declare(strict_types=1);
/**
 * @project    HotaiConnected
 * @package    Branch8
 * @author     cuongho803@gmail.com
 * @date       10/02/2026
 */

namespace Branch8\OptionsWithStockAndImages\Model\Actions;

class CheckStockCombo
{
    /**
     * @var \Webkul\OptionsWithStockAndImages\Helper\Data
     */
    protected $helper;

    /**
     * @param \Webkul\OptionsWithStockAndImages\Helper\Data $helper
     */
    public function __construct(
        \Webkul\OptionsWithStockAndImages\Helper\Data $helper
    )
    {
        $this->helper = $helper;
    }

    /**
     * @param float $requestQty
     * @param int $productId
     * @param string $combo
     * @return bool
     */
    public function execute(float $requestQty, int $productId, string $combo)
    {
        /**
         * @var $variation \Webkul\OptionsWithStockAndImages\Model\Variations
         */
        $variation = $this->helper->getCombData($productId, $combo);
        if ($variation->getId()) {
            return $variation->getStock() - $requestQty > 0;
        }
        return false;
    }
}
