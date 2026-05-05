<?php
declare(strict_types=1);
/**
 * @project    HotaiConnected
 * @package    Branch8
 * @author     cuongho803@gmail.com
 * @date       26/03/2026
 */

namespace Branch8\OptionsWithStockAndImages\Model\Actions;

use Branch8\Preorder\Model\IsPreorderProduct;

class IsProductPreorder
{
    private IsPreorderProduct $isPreorderProduct;

    /**
     * @param IsPreorderProduct $isPreorderProduct
     */
    public function __construct(
        IsPreorderProduct $isPreorderProduct
    )
    {
        $this->isPreorderProduct = $isPreorderProduct;
    }

    /**
     * @param int $productId
     * @return bool
     */
    public function execute(int $productId)
    {
        return $this->isPreorderProduct->checkIsPreorder($productId);
    }
}
