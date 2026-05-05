<?php

namespace Branch8\MarketPlaceOrderExport\Model\Column;

use Branch8\MarketPlaceOrderExport\Model\ColumnInterface;

class ProductType implements ColumnInterface
{
    private \Branch8\HotaiCore\Helper\VirtualProduct $virtualProduct;

    /**
     * @param \Branch8\HotaiCore\Helper\VirtualProduct $virtualProduct
     */
    public function __construct(
        \Branch8\HotaiCore\Helper\VirtualProduct $virtualProduct
    )
    {
        $this->virtualProduct = $virtualProduct;
    }

    public function getHeader()
    {
        return __('Product Type');
    }

    /**
     * @param array $row
     * @return int|mixed|string
     */
    public function processColumnData(array $row = [])
    {
        if ($row['item_type'] !== 'item') {
            return '';
        }
        return $this->getLabel($row);
    }

    /**
     * @param $type
     * @return \Magento\Framework\Phrase|mixed|string
     */
    private function getLabel($row)
    {
        $type = $row['product_type'];
        /*$isticket = $this->virtualProduct->checkIsProductTicketTypeByOrderItemId($row['item_id']);
        if ($isticket) {
            return __('電子票券');
        }*/
        switch ($type) {
            case 'simple':
                return __('實體商品');
            case 'virtual':
                return __('電子票券');
            default:
                return $type;
        }
    }
}
