<?php

namespace Branch8\MarketPlaceOrderExport\Model\Column;

use Branch8\MarketPlaceOrderExport\Model\ColumnInterface;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Sales\Model\OrderRepository;

class PickUpStoreName implements ColumnInterface
{
    private $cached = [];

    public function getHeader()
    {
        return __('Pick Up Store Name');
    }

    /**
     * @param array $row
     * @return int|mixed|string
     * @throws \Magento\Framework\Exception\InputException
     */
    public function processColumnData(array $row = [])
    {
        if (isset($this->cached[$row['order_id']])) {
            return $this->cached[$row['order_id']];
        }
        $this->cached[$row['order_id']] = '';
        try {
            if (!empty($row['shipping_method']) && $row['shipping_method'] === 'hotai_711_hotai_711') {
                $this->cached[$row['order_id']] = $row['city'];
            }
        } catch (NoSuchEntityException $exception) {

        }
        return $this->cached[$row['order_id']];
    }
}
