<?php
declare(strict_types=1);
/**
 * @project    HotaiConnected
 * @package    Branch8
 * @author     cuongho803@gmail.com
 * @date       10/02/2026
 */

namespace Branch8\OptionsWithStockAndImages\Model\Actions;

class GetStockCombo
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
     * @param int $productId
     * @param string $combo
     * @return int|null
     */
    public function execute(int $productId, string $combo)
    {
        /**
         * @var $variation \Webkul\OptionsWithStockAndImages\Model\Variations
         */
        $variation = $this->helper->getCombData($productId, $combo);
        if ($variation->getId()) {
            return (int)$variation->getStock();
        }
        return 0;
    }
}
