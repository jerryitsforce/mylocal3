<?php

namespace Branch8\MarketPlaceOrderExport\Model\Column;

use Branch8\MarketPlaceOrderExport\Model\ColumnInterface;

class DistributionThermosphere implements ColumnInterface
{
    private $cached = [];
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
        return __('Distribution Thermosphere');
    }

    /**
     * @param array $row
     * @return int|mixed|string
     */
    public function processColumnData(array $row = [])
    {
        $type = trim((string)$row['distribution_thermosphere']);
        if ($type === 'ticket') {
            return __('電子票券');
        }
        if ($type === '普通的') {
            return __('常溫');
        }
        return $type;
    }

}
