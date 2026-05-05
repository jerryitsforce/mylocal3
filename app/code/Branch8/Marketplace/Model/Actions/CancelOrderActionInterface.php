<?php
declare(strict_types=1);

namespace Branch8\Marketplace\Model\Actions;

use Magento\Framework\DataObject;

interface CancelOrderActionInterface
{
    /***
     * @param \Magento\Sales\Model\Order $order
     * @param int $sellerId
     * @return mixed
     */
    public function execute(\Magento\Sales\Model\Order $order, int $sellerId = 0);
}
